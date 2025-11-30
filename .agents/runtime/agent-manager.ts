import { Agent } from '../lib/agent.js';
import type { Config } from '../lib/config.js';
import type { Task } from '../lib/agent.js';
import { CostTracker } from '../lib/cost-tracker.js';
import { TaskManager } from './task-manager.js';
import { GitWorktreeManager } from '../lib/git-worktree.js';
import { config as dotenvConfig } from 'dotenv';
import chalk from 'chalk';

dotenvConfig();

export class AgentManager {
  private agents: Map<string, Agent> = new Map();
  private config: Config;
  private costTracker?: CostTracker;
  private taskManager: TaskManager;
  private worktreeManager?: GitWorktreeManager;

  private initialized: boolean = false;

  constructor(config: Config) {
    this.config = config;
    this.taskManager = new TaskManager(config.orchestrator.boardDirectory + '/tasks');

    // Initialize worktree manager if git is enabled and branchPerAgent is true
    if (config.git?.enabled && config.git.branchPerAgent) {
      this.worktreeManager = new GitWorktreeManager(config.orchestrator.projectRoot);
      console.log(chalk.blue('✓ Git worktree manager initialized'));
    }

    this.initializeCostTracker();
  }

  async initialize(): Promise<void> {
    if (this.initialized) return;
    await this.initializeAgents();
    this.initialized = true;
  }

  private async initializeAgents(): Promise<void> {
    const apiKey = process.env.ANTHROPIC_API_KEY;

    if (!apiKey) {
      throw new Error('ANTHROPIC_API_KEY environment variable is required');
    }

    for (const agentConfig of this.config.agents) {
      // If worktrees are enabled, create a worktree for each agent
      if (this.worktreeManager) {
        try {
          const worktreePath = await this.worktreeManager.createWorktreeForAgent(agentConfig.id);

          // Update agent config to use worktree directory
          const agentConfigWithWorktree = {
            ...agentConfig,
            workingDirectory: worktreePath,
          };

          const agent = new Agent(agentConfigWithWorktree, apiKey);
          this.agents.set(agent.id, agent);

          console.log(chalk.green(`✓ Agent ${agentConfig.name} initialized with worktree: ${worktreePath}`));
        } catch (error) {
          console.error(chalk.red(`❌ Failed to create worktree for ${agentConfig.name}:`, error));
          // Fall back to regular directory
          const agent = new Agent(agentConfig, apiKey);
          this.agents.set(agent.id, agent);
        }
      } else {
        const agent = new Agent(agentConfig, apiKey);
        this.agents.set(agent.id, agent);
      }
    }
  }

  getAgent(id: string): Agent | undefined {
    return this.agents.get(id);
  }

  getAgentsByTeam(team: number): Agent[] {
    return Array.from(this.agents.values()).filter(
      (agent) => agent.team === team
    );
  }

  getAllAgents(): Agent[] {
    return Array.from(this.agents.values());
  }

  findBestAgentForTask(task: Task): Agent | null {
    const teamAgents = this.getAgentsByTeam(task.team);

    // Filter agents that can take the task
    const availableAgents = teamAgents.filter((agent) => agent.canTakeTask(task));

    if (availableAgents.length === 0) {
      return null;
    }

    // Sort by current load (ascending) and expertise match
    availableAgents.sort((a, b) => {
      const loadDiff = a.getCurrentLoad() - b.getCurrentLoad();
      if (loadDiff !== 0) {
        return loadDiff;
      }

      // Prefer agents with relevant expertise
      const aExpertiseMatch = this.countExpertiseMatch(a, task);
      const bExpertiseMatch = this.countExpertiseMatch(b, task);

      return bExpertiseMatch - aExpertiseMatch;
    });

    return availableAgents[0];
  }

  private countExpertiseMatch(agent: Agent, task: Task): number {
    const taskLower = (task.title + ' ' + task.description).toLowerCase();

    return agent.expertise.filter((expertise) =>
      taskLower.includes(expertise.toLowerCase())
    ).length;
  }

  async assignTask(task: Task): Promise<boolean> {
    const agent = this.findBestAgentForTask(task);

    if (!agent) {
      return false;
    }

    await agent.assignTask(task);
    return true;
  }

  async executeTask(taskId: string): Promise<{ success: boolean; output: string; cost?: any }> {
    // Find the agent that has this task
    for (const agent of this.agents.values()) {
      const task = agent.getStatus().currentTasks.find((t) => t.id === taskId);

      if (task) {
        return await agent.executeTask(task, this.costTracker);
      }
    }

    return {
      success: false,
      output: `Task ${taskId} not found in any agent's current tasks`,
    };
  }

  getCostTracker(): CostTracker | undefined {
    return this.costTracker;
  }

  getTaskManager(): TaskManager {
    return this.taskManager;
  }

  async assignTaskFromManager(taskId: string): Promise<{ success: boolean; message: string; agent?: Agent }> {
    const task = this.taskManager.getTask(taskId);
    if (!task) return { success: false, message: `Task ${taskId} not found` };
    if (task.status !== 'pending') return { success: false, message: `Task ${taskId} is not pending` };

    const agent = this.findBestAgentForTask(task);
    if (!agent) return { success: false, message: `No available agent found for task ${taskId}` };

    await this.taskManager.assignTask(taskId, agent.id);
    await agent.assignTask(task);

    return { success: true, message: `Task ${taskId} assigned to ${agent.name}`, agent };
  }

  async executeTaskFromManager(taskId: string): Promise<{ success: boolean; output: string; cost?: any }> {
    const task = this.taskManager.getTask(taskId);
    if (!task) return { success: false, output: `Task ${taskId} not found` };

    if (!task.assignedTo) {
      const assignResult = await this.assignTaskFromManager(taskId);
      if (!assignResult.success) return { success: false, output: assignResult.message };
    }

    const agent = this.getAgent(task.assignedTo!);
    if (!agent) return { success: false, output: `Agent ${task.assignedTo} not found` };

    const result = await agent.executeTask(task, this.costTracker);

    if (result.success) {
      await this.taskManager.completeTask(taskId);
      await agent.completeTask(task);
    } else {
      await this.taskManager.blockTask(taskId, result.output);
    }

    return result;
  }

  async autoAssignTasks(): Promise<{ assigned: number; failed: number }> {
    const pendingTasks = this.taskManager.getTasks({ status: 'pending' });
    let assigned = 0;
    let failed = 0;

    for (const task of pendingTasks) {
      const result = await this.assignTaskFromManager(task.id);
      if (result.success) assigned++;
      else failed++;
    }

    console.log(`✓ Auto-assigned ${assigned} tasks (${failed} failed)`);
    return { assigned, failed };
  }

  private initializeCostTracker(): void {
    if (this.config.costs?.tracking?.enabled) {
      const storageDir = this.config.costs.tracking.storageDirectory;
      const limits = this.config.costs.limits;

      this.costTracker = new CostTracker(storageDir, limits);

      // Setup alerts
      this.costTracker.onAlert((message, current, limit) => {
        console.log(chalk.red(`\n⚠️  COST ALERT: ${message}\n`));
        console.log(chalk.yellow(`Current: $${current.toFixed(2)}`));
        console.log(chalk.yellow(`Limit: $${limit.toFixed(2)}\n`));
      });

      console.log('✓ Cost tracking enabled');
    }
  }

  getOverallStatus(): {
    totalAgents: number;
    activeAgents: number;
    totalCurrentLoad: number;
    totalCompletedStoryPoints: number;
    agentStatuses: ReturnType<Agent['getStatus']>[];
  } {
    const agentStatuses = this.getAllAgents().map((agent) => agent.getStatus());

    return {
      totalAgents: this.agents.size,
      activeAgents: agentStatuses.filter((s) => s.currentTasks.length > 0).length,
      totalCurrentLoad: agentStatuses.reduce((sum, s) => sum + s.currentLoad, 0),
      totalCompletedStoryPoints: agentStatuses.reduce(
        (sum, s) => sum + s.completedStoryPoints,
        0
      ),
      agentStatuses,
    };
  }

  getTeamStatus(team: number): {
    team: number;
    agents: number;
    currentLoad: number;
    completedStoryPoints: number;
    agentStatuses: ReturnType<Agent['getStatus']>[];
  } {
    const teamAgents = this.getAgentsByTeam(team);
    const agentStatuses = teamAgents.map((agent) => agent.getStatus());

    return {
      team,
      agents: teamAgents.length,
      currentLoad: agentStatuses.reduce((sum, s) => sum + s.currentLoad, 0),
      completedStoryPoints: agentStatuses.reduce(
        (sum, s) => sum + s.completedStoryPoints,
        0
      ),
      agentStatuses,
    };
  }

  getWorktreeManager(): GitWorktreeManager | undefined {
    return this.worktreeManager;
  }

  async cleanupWorktrees(): Promise<void> {
    if (this.worktreeManager) {
      await this.worktreeManager.cleanupAllWorktrees();
    }
  }

  async listWorktrees(): Promise<void> {
    if (this.worktreeManager) {
      await this.worktreeManager.listWorktrees();
    }
  }

  async mergeAgentBranch(agentId: string, targetBranch: string = 'main'): Promise<void> {
    if (this.worktreeManager) {
      await this.worktreeManager.mergeBranch(agentId, targetBranch);
    }
  }
}
