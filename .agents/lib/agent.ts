import Anthropic from '@anthropic-ai/sdk';
import type { Agent as AgentConfig } from './config.js';
import { GitManager } from './git-manager.js';

export interface Epic {
  id: string;
  title: string;
  description: string;
  estimatedStoryPoints: number;
  team: number;
  sprint: number;
  status: 'pending' | 'in_progress' | 'completed';
  createdAt: Date;
  startedAt?: Date;
  completedAt?: Date;
  taskIds: string[]; // IDs of tasks created from this epic
}

export interface Task {
  id: string;
  title: string;
  description: string;
  storyPoints: number;
  dependencies: string[];
  team: number;
  sprint: number;
  status: 'pending' | 'in_progress' | 'completed' | 'blocked';
  assignedTo?: string;
  startedAt?: Date;
  completedAt?: Date;
  blockedReason?: string;
  epicId?: string; // Reference to parent epic
  parentTaskId?: string; // Reference to parent task (for subtasks)
}

export class Agent {
  private client: Anthropic;
  private config: AgentConfig;
  private currentTasks: Task[] = [];
  private completedTasks: Task[] = [];
  private gitManager: GitManager;
  private currentBranch?: string;

  constructor(config: AgentConfig, apiKey: string) {
    this.config = config;
    this.client = new Anthropic({ apiKey });
    this.gitManager = new GitManager(config.workingDirectory);
  }

  get id(): string {
    return this.config.id;
  }

  get name(): string {
    return this.config.name;
  }

  get team(): number | null {
    return this.config.team;
  }

  get role(): string {
    return this.config.role;
  }

  get expertise(): string[] {
    return this.config.expertise;
  }

  get capabilities() {
    return this.config.capabilities;
  }

  get maxConcurrentTasks(): number {
    return this.config.maxConcurrentTasks;
  }

  get sprintCapacity(): number | null {
    return this.config.sprintCapacity;
  }

  get workingDirectory(): string {
    return this.config.workingDirectory;
  }

  get boardDirectory(): string {
    return this.config.boardDirectory;
  }

  get modelPreference(): string {
    return this.config.modelPreference;
  }

  get temperature(): number {
    return this.config.temperature;
  }

  canTakeTask(task: Task): boolean {
    // Check if agent has capacity
    if (this.currentTasks.length >= this.maxConcurrentTasks) {
      return false;
    }

    // Check if task is for this agent's team
    if (task.team !== this.team) {
      return false;
    }

    // Check if agent has required capabilities
    const requiresArchitecture = task.title.toLowerCase().includes('architecture') ||
                                  task.title.toLowerCase().includes('design');
    if (requiresArchitecture && !this.capabilities.architectureDesign) {
      return false;
    }

    return true;
  }

  async assignTask(task: Task): Promise<void> {
    if (!this.canTakeTask(task)) {
      throw new Error(`Agent ${this.name} cannot take task ${task.id}`);
    }

    task.assignedTo = this.id;
    task.status = 'in_progress';
    task.startedAt = new Date();
    this.currentTasks.push(task);

    // Create Git branch for this task
    try {
      this.currentBranch = await this.gitManager.createTaskBranch(this.id, task.id);
      console.log(`✓ ${this.name} created branch: ${this.currentBranch}`);
    } catch (error) {
      console.log(`⚠️  Could not create Git branch: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  async executeTask(task: Task, costTracker?: any): Promise<{ success: boolean; output: string; cost?: any }> {
    const systemPrompt = this.buildSystemPrompt();
    const userPrompt = this.buildUserPrompt(task);

    try {
      // Create checkpoint before starting work
      await this.createCheckpoint(task, 'before-task-start');

      // Use prompt caching for system prompt (90% cheaper!)
      const response = await this.client.messages.create({
        model: this.mapModelPreference(),
        max_tokens: 8192,
        temperature: this.temperature,
        system: [
          {
            type: 'text',
            text: systemPrompt,
            cache_control: { type: 'ephemeral' }, // ← Enable caching!
          },
        ],
        messages: [
          {
            role: 'user',
            content: userPrompt,
          },
        ],
      });

      const output = response.content
        .filter((block) => block.type === 'text')
        .map((block) => ('text' in block ? block.text : ''))
        .join('\n');

      // Track costs
      let cost;
      if (costTracker && response.usage) {
        cost = await costTracker.trackUsage(
          this.mapModelPreference(),
          response.usage.input_tokens || 0,
          response.usage.output_tokens || 0,
          response.usage.cache_read_input_tokens || 0,
          this.id,
          task.id
        );

        console.log(`💰 Cost: $${cost.totalCost.toFixed(4)} (Saved: $${cost.costSaved.toFixed(4)} via cache)`);
      }

      // Commit changes after task execution
      await this.commitProgress(task, 'Task implementation completed');

      // Create checkpoint after completion
      await this.createCheckpoint(task, 'after-task-completion');

      return {
        success: true,
        output,
        cost,
      };
    } catch (error) {
      // Check if it's a spending limit error
      if (error instanceof Error && error.message.includes('spending limit')) {
        console.log(`🛑 ${error.message}`);
        return {
          success: false,
          output: `Task stopped: ${error.message}`,
        };
      }

      // Rollback on error
      await this.rollbackTask(task);

      return {
        success: false,
        output: error instanceof Error ? error.message : 'Unknown error',
      };
    }
  }

  private mapModelPreference(): string {
    const modelMap: Record<string, string> = {
      'claude-opus-4': 'claude-opus-4-20250514',
      'claude-sonnet-4-5': 'claude-sonnet-4-5-20250929',
      'claude-haiku-4': 'claude-haiku-4-20250514',
    };

    return modelMap[this.modelPreference] || 'claude-sonnet-4-5-20250929';
  }

  private buildSystemPrompt(): string {
    return `You are ${this.name}, a ${this.role} working on the Manhunt SaaS Platform.

Your expertise includes: ${this.expertise.join(', ')}.

Your capabilities:
- Code Generation: ${this.capabilities.codeGeneration ? 'Yes' : 'No'}
- Code Review: ${this.capabilities.codeReview ? 'Yes' : 'No'}
- Architecture Design: ${this.capabilities.architectureDesign ? 'Yes' : 'No'}
- Testing: ${this.capabilities.testing ? 'Yes' : 'No'}
- Documentation: ${this.capabilities.documentation ? 'Yes' : 'No'}

Working Directory: ${this.workingDirectory}
Board Directory: ${this.boardDirectory}

You are part of Team ${this.team} and work collaboratively with other agents.

When completing tasks:
1. Follow Laravel best practices and conventions
2. Write comprehensive tests for all code
3. Document your work clearly
4. Communicate with other agents when you need help or information
5. Ask for human approval when required (architecture changes, breaking changes, security changes)
6. Ensure code quality and maintainability

You have access to the following tools:
- Read files
- Write files
- Edit files
- Execute bash commands
- Search the codebase
- Run tests

Always provide clear, concise updates on your progress.`;
  }

  private buildUserPrompt(task: Task): string {
    return `Task: ${task.title}

Description:
${task.description}

Story Points: ${task.storyPoints}
Sprint: ${task.sprint}

Please complete this task following best practices. Provide a summary of:
1. What you implemented
2. What files you created/modified
3. What tests you wrote
4. Any blockers or questions you have

If you need information from other teams or agents, clearly state what you need.`;
  }

  async completeTask(task: Task): Promise<void> {
    task.status = 'completed';
    task.completedAt = new Date();

    const index = this.currentTasks.findIndex((t) => t.id === task.id);
    if (index !== -1) {
      this.currentTasks.splice(index, 1);
      this.completedTasks.push(task);
    }

    // Final commit before merging
    await this.commitProgress(task, 'Task completed - ready for review');

    // Merge branch back to main (if code review passed)
    if (this.currentBranch) {
      try {
        await this.gitManager.mergeToMain(this.currentBranch, task.id);
        console.log(`✓ ${this.name} merged task ${task.id} to main`);
        this.currentBranch = undefined;
      } catch (error) {
        console.log(`⚠️  Could not merge to main: ${error instanceof Error ? error.message : 'Unknown error'}`);
      }
    }
  }

  blockTask(task: Task, reason: string): void {
    task.status = 'blocked';
    task.blockedReason = reason;
  }

  getCurrentLoad(): number {
    return this.currentTasks.reduce((sum, task) => sum + task.storyPoints, 0);
  }

  getCompletedStoryPoints(): number {
    return this.completedTasks.reduce((sum, task) => sum + task.storyPoints, 0);
  }

  getStatus(): {
    id: string;
    name: string;
    currentTasks: Task[];
    completedTasks: Task[];
    currentLoad: number;
    completedStoryPoints: number;
  } {
    return {
      id: this.id,
      name: this.name,
      currentTasks: this.currentTasks,
      completedTasks: this.completedTasks,
      currentLoad: this.getCurrentLoad(),
      completedStoryPoints: this.getCompletedStoryPoints(),
    };
  }

  // Git helper methods

  /**
   * Commit current progress on the task
   */
  async commitProgress(task: Task, message: string): Promise<void> {
    try {
      await this.gitManager.commit(
        this.id,
        this.name,
        task.id,
        message
      );
    } catch (error) {
      console.log(`⚠️  Could not commit: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  /**
   * Commit with automatic test validation
   */
  async commitWithTests(task: Task, message: string, testCommand: string = 'php artisan test'): Promise<boolean> {
    try {
      const result = await this.gitManager.commitWithTests(
        this.id,
        this.name,
        task.id,
        message,
        testCommand
      );

      if (!result.success) {
        console.log(`✗ Tests failed, changes rolled back: ${result.error}`);
        return false;
      }

      console.log(`✓ Changes committed and tests passed`);
      return true;
    } catch (error) {
      console.log(`⚠️  Could not commit with tests: ${error instanceof Error ? error.message : 'Unknown error'}`);
      return false;
    }
  }

  /**
   * Create a checkpoint (tag) for this task
   */
  async createCheckpoint(task: Task, description: string): Promise<void> {
    try {
      await this.gitManager.createCheckpoint(task.id, description);
    } catch (error) {
      console.log(`⚠️  Could not create checkpoint: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  /**
   * Rollback task to last checkpoint
   */
  async rollbackTask(task: Task): Promise<void> {
    try {
      // Find last checkpoint for this task
      const checkpointName = `checkpoint/${task.id}`;
      await this.gitManager.rollback(checkpointName);
      console.log(`✓ Rolled back task ${task.id} to last checkpoint`);
    } catch (error) {
      console.log(`⚠️  Could not rollback: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  /**
   * Get Git history for current branch
   */
  async getTaskHistory(limit: number = 10): Promise<any[]> {
    try {
      return await this.gitManager.getHistory(limit);
    } catch (error) {
      return [];
    }
  }

  /**
   * Check if working directory is clean
   */
  async isWorkingDirectoryClean(): Promise<boolean> {
    try {
      return await this.gitManager.isClean();
    } catch (error) {
      return false;
    }
  }
}
