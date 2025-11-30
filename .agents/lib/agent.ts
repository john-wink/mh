import Anthropic from '@anthropic-ai/sdk';
import type { Agent as AgentConfig } from './config.js';

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
}

export class Agent {
  private client: Anthropic;
  private config: AgentConfig;
  private currentTasks: Task[] = [];
  private completedTasks: Task[] = [];

  constructor(config: AgentConfig, apiKey: string) {
    this.config = config;
    this.client = new Anthropic({ apiKey });
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
  }

  async executeTask(task: Task): Promise<{ success: boolean; output: string }> {
    const systemPrompt = this.buildSystemPrompt();
    const userPrompt = this.buildUserPrompt(task);

    try {
      const response = await this.client.messages.create({
        model: this.mapModelPreference(),
        max_tokens: 8192,
        temperature: this.temperature,
        system: systemPrompt,
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

      return {
        success: true,
        output,
      };
    } catch (error) {
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

  completeTask(task: Task): void {
    task.status = 'completed';
    task.completedAt = new Date();

    const index = this.currentTasks.findIndex((t) => t.id === task.id);
    if (index !== -1) {
      this.currentTasks.splice(index, 1);
      this.completedTasks.push(task);
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
}
