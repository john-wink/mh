import chalk from 'chalk';
import type { Config } from '../lib/config.js';
import type { AgentManager } from './agent-manager.js';
import type { Task } from '../lib/agent.js';

export class Scheduler {
  constructor(
    private config: Config,
    private agentManager: AgentManager
  ) {}

  simulate(tasks: Task[]): void {
    console.log(chalk.bold('\n📋 Sprint Task Assignment Simulation\n'));

    // Group tasks by team
    const tasksByTeam = this.groupTasksByTeam(tasks);

    for (const [team, teamTasks] of tasksByTeam.entries()) {
      console.log(chalk.blue.bold(`\nTeam ${team}:`));
      console.log(chalk.gray(`  ${teamTasks.length} tasks, ${this.calculateTotalStoryPoints(teamTasks)} total SP`));

      const teamAgents = this.agentManager.getAgentsByTeam(team);
      console.log(chalk.gray(`  ${teamAgents.length} agents available`));

      // Simulate assignment
      for (const task of teamTasks) {
        const agent = this.agentManager.findBestAgentForTask(task);

        if (agent) {
          console.log(chalk.green(`  ✓ ${task.id}: ${task.title}`));
          console.log(chalk.gray(`    → Assigned to: ${agent.name} (${task.storyPoints} SP)`));
        } else {
          console.log(chalk.red(`  ✗ ${task.id}: ${task.title}`));
          console.log(chalk.gray(`    → No available agent (${task.storyPoints} SP)`));
        }
      }
    }

    console.log('\n');
  }

  async execute(sprint: number, tasks: Task[]): Promise<void> {
    console.log(chalk.bold(`\n🚀 Executing Sprint ${sprint}\n`));

    // Check for blockers
    const blocked = this.checkDependencies(tasks);
    if (blocked.length > 0) {
      console.log(chalk.yellow(`\n⚠️  ${blocked.length} tasks are blocked by dependencies:\n`));
      for (const task of blocked) {
        console.log(chalk.gray(`  - ${task.id}: waiting for ${task.dependencies.join(', ')}`));
      }
    }

    // Get ready tasks (no pending dependencies)
    const readyTasks = this.getReadyTasks(tasks);
    console.log(chalk.green(`\n✓ ${readyTasks.length} tasks ready for execution\n`));

    // Assign tasks to agents
    const assignments = await this.assignTasks(readyTasks);

    console.log(chalk.bold('\n📊 Assignment Summary:\n'));
    console.log(chalk.gray(`  Total tasks: ${tasks.length}`));
    console.log(chalk.gray(`  Ready tasks: ${readyTasks.length}`));
    console.log(chalk.gray(`  Assigned tasks: ${assignments.assigned}`));
    console.log(chalk.gray(`  Unassigned tasks: ${assignments.unassigned}`));

    if (assignments.unassigned > 0) {
      console.log(chalk.yellow(`\n⚠️  ${assignments.unassigned} tasks could not be assigned (insufficient capacity)\n`));
    }

    // Execute tasks
    if (this.config.settings.autoAssignTasks) {
      console.log(chalk.blue('\n🏃 Starting task execution...\n'));
      await this.executeTasks(readyTasks);
    } else {
      console.log(chalk.yellow('\n⚠️  Auto-assignment disabled. Tasks assigned but not executed.\n'));
    }
  }

  private groupTasksByTeam(tasks: Task[]): Map<number, Task[]> {
    const grouped = new Map<number, Task[]>();

    for (const task of tasks) {
      if (!grouped.has(task.team)) {
        grouped.set(task.team, []);
      }
      grouped.get(task.team)!.push(task);
    }

    return grouped;
  }

  private calculateTotalStoryPoints(tasks: Task[]): number {
    return tasks.reduce((sum, task) => sum + task.storyPoints, 0);
  }

  private checkDependencies(tasks: Task[]): Task[] {
    return tasks.filter((task) => {
      if (task.dependencies.length === 0) {
        return false;
      }

      // Check if all dependencies are completed
      return task.dependencies.some((depId) => {
        const depTask = tasks.find((t) => t.id === depId);
        return !depTask || depTask.status !== 'completed';
      });
    });
  }

  private getReadyTasks(tasks: Task[]): Task[] {
    return tasks.filter((task) => {
      if (task.status !== 'pending') {
        return false;
      }

      if (task.dependencies.length === 0) {
        return true;
      }

      // Check if all dependencies are completed
      return task.dependencies.every((depId) => {
        const depTask = tasks.find((t) => t.id === depId);
        return depTask && depTask.status === 'completed';
      });
    });
  }

  private async assignTasks(tasks: Task[]): Promise<{ assigned: number; unassigned: number }> {
    let assigned = 0;
    let unassigned = 0;

    // Sort tasks by priority (story points descending)
    const sortedTasks = [...tasks].sort((a, b) => b.storyPoints - a.storyPoints);

    for (const task of sortedTasks) {
      const success = await this.agentManager.assignTask(task);

      if (success) {
        assigned++;
      } else {
        unassigned++;
      }
    }

    return { assigned, unassigned };
  }

  private async executeTasks(tasks: Task[]): Promise<void> {
    const assignedTasks = tasks.filter((t) => t.assignedTo);

    for (const task of assignedTasks) {
      console.log(chalk.blue(`\n🔨 Executing ${task.id}: ${task.title}`));
      console.log(chalk.gray(`   Agent: ${task.assignedTo}`));
      console.log(chalk.gray(`   Story Points: ${task.storyPoints}`));

      const result = await this.agentManager.executeTask(task.id);

      if (result.success) {
        console.log(chalk.green(`\n✓ Task ${task.id} completed`));
        console.log(chalk.gray('\nOutput:'));
        console.log(result.output);
      } else {
        console.log(chalk.red(`\n✗ Task ${task.id} failed`));
        console.log(chalk.gray('\nError:'));
        console.log(result.output);
      }
    }
  }
}
