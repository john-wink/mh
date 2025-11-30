import chalk from 'chalk';
import type { Config } from '../lib/config.js';
import { AgentManager } from './agent-manager.js';

export class Monitor {
  constructor(private config: Config) {}

  async showStatus(): Promise<void> {
    console.log(chalk.blue.bold('\n📊 Sprint Status\n'));

    const agentManager = new AgentManager(this.config);
    const status = agentManager.getOverallStatus();

    console.log(chalk.bold('Overall:'));
    console.log(chalk.gray(`  Total Agents: ${status.totalAgents}`));
    console.log(chalk.gray(`  Active Agents: ${status.activeAgents}`));
    console.log(chalk.gray(`  Current Load: ${status.totalCurrentLoad} SP`));
    console.log(chalk.gray(`  Completed: ${status.totalCompletedStoryPoints} SP`));

    // Group by team
    const teams = new Set(this.config.agents.map((a) => a.team).filter((t) => t !== null));

    for (const team of teams) {
      const teamStatus = agentManager.getTeamStatus(team as number);

      console.log(chalk.bold(`\nTeam ${team}:`));
      console.log(chalk.gray(`  Agents: ${teamStatus.agents}`));
      console.log(chalk.gray(`  Current Load: ${teamStatus.currentLoad} SP`));
      console.log(chalk.gray(`  Completed: ${teamStatus.completedStoryPoints} SP`));

      // Show individual agents
      for (const agentStatus of teamStatus.agentStatuses) {
        const statusIcon = agentStatus.currentTasks.length > 0 ? '🔨' : '💤';
        console.log(chalk.gray(`\n  ${statusIcon} ${agentStatus.name}:`));
        console.log(chalk.gray(`     Current: ${agentStatus.currentLoad} SP (${agentStatus.currentTasks.length} tasks)`));
        console.log(chalk.gray(`     Completed: ${agentStatus.completedStoryPoints} SP (${agentStatus.completedTasks.length} tasks)`));

        if (agentStatus.currentTasks.length > 0) {
          console.log(chalk.gray(`     Working on:`));
          for (const task of agentStatus.currentTasks) {
            console.log(chalk.gray(`       - ${task.id}: ${task.title} (${task.storyPoints} SP)`));
          }
        }
      }
    }

    console.log('\n');
  }

  async track(): Promise<void> {
    // This would be implemented to continuously monitor agent progress
    // For now, just show initial status
    await this.showStatus();
  }

  async generateReport(): Promise<string> {
    const agentManager = new AgentManager(this.config);
    const status = agentManager.getOverallStatus();

    let report = `# Sprint ${this.config.orchestrator.currentSprint} Report\n\n`;
    report += `## Overall Progress\n\n`;
    report += `- Total Agents: ${status.totalAgents}\n`;
    report += `- Active Agents: ${status.activeAgents}\n`;
    report += `- Current Load: ${status.totalCurrentLoad} SP\n`;
    report += `- Completed: ${status.totalCompletedStoryPoints} SP\n\n`;

    const teams = new Set(this.config.agents.map((a) => a.team).filter((t) => t !== null));

    for (const team of teams) {
      const teamStatus = agentManager.getTeamStatus(team as number);

      report += `## Team ${team}\n\n`;
      report += `- Agents: ${teamStatus.agents}\n`;
      report += `- Current Load: ${teamStatus.currentLoad} SP\n`;
      report += `- Completed: ${teamStatus.completedStoryPoints} SP\n\n`;

      report += `### Agents\n\n`;
      for (const agentStatus of teamStatus.agentStatuses) {
        report += `#### ${agentStatus.name}\n\n`;
        report += `- Current Load: ${agentStatus.currentLoad} SP (${agentStatus.currentTasks.length} tasks)\n`;
        report += `- Completed: ${agentStatus.completedStoryPoints} SP (${agentStatus.completedTasks.length} tasks)\n\n`;

        if (agentStatus.currentTasks.length > 0) {
          report += `Current Tasks:\n`;
          for (const task of agentStatus.currentTasks) {
            report += `- ${task.id}: ${task.title} (${task.storyPoints} SP)\n`;
          }
          report += `\n`;
        }
      }
    }

    return report;
  }
}
