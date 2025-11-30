import { spawn, ChildProcess } from 'child_process';
import { Agent, Task } from '../lib/agent.js';
import { GitWorktreeManager } from '../lib/git-worktree.js';
import { writeFile } from 'fs/promises';
import { join } from 'path';
import chalk from 'chalk';

export interface ExecutionResult {
  success: boolean;
  exitCode: number;
  output: string;
  error?: string;
}

export class ClaudeCodeExecutor {
  private worktreeManager?: GitWorktreeManager;
  private projectRoot: string;
  private mcpConfigPath: string;

  constructor(
    projectRoot: string,
    worktreeManager?: GitWorktreeManager,
    mcpConfigPath?: string
  ) {
    this.projectRoot = projectRoot;
    this.worktreeManager = worktreeManager;
    this.mcpConfigPath = mcpConfigPath || join(projectRoot, '.claude/mcp-config.json');
  }

  /**
   * Execute a task using Claude Code in a foreground session
   */
  async executeTask(task: Task, agent: Agent): Promise<ExecutionResult> {
    console.log(chalk.blue(`\n🚀 Starting Claude Code session for ${agent.name}`));
    console.log(chalk.gray(`   Task: ${task.title}`));

    try {
      // 1. Ensure worktree exists for this agent
      const worktreePath = await this.ensureWorktree(agent.id, agent.name);

      // 2. Build initial prompt for Claude Code
      const initialPrompt = this.buildInitialPrompt(task, agent);

      // 3. Start Claude Code session (foreground, user can see & interact)
      const result = await this.startClaudeCodeSession({
        workingDirectory: worktreePath,
        agentId: agent.id,
        agentName: agent.name,
        taskId: task.id,
        initialPrompt,
      });

      console.log(chalk.green(`\n✓ Claude Code session completed for ${agent.name}`));

      return result;
    } catch (error) {
      console.error(chalk.red(`\n❌ Claude Code session failed for ${agent.name}:`), error);

      return {
        success: false,
        exitCode: 1,
        output: '',
        error: error instanceof Error ? error.message : 'Unknown error',
      };
    }
  }

  /**
   * Ensure worktree exists for agent (create on-demand if needed)
   */
  private async ensureWorktree(agentId: string, agentName: string): Promise<string> {
    if (!this.worktreeManager) {
      // No worktree manager, use project root
      console.log(chalk.yellow(`⚠️  No worktree manager, using project root`));
      return this.projectRoot;
    }

    // Check if worktree already exists
    const existingWorktreePath = this.worktreeManager.getWorktreePath(agentId);
    if (existingWorktreePath) {
      console.log(chalk.gray(`   Using existing worktree: ${existingWorktreePath}`));
      return existingWorktreePath;
    }

    // Create worktree on-demand
    console.log(chalk.blue(`📦 Creating worktree for ${agentName}...`));
    const worktreePath = await this.worktreeManager.createWorktreeForAgent(agentId);
    console.log(chalk.green(`✓ Worktree created: ${worktreePath}`));

    return worktreePath;
  }

  /**
   * Start Claude Code session in foreground (user can see & interact)
   */
  private async startClaudeCodeSession(config: {
    workingDirectory: string;
    agentId: string;
    agentName: string;
    taskId: string;
    initialPrompt: string;
  }): Promise<ExecutionResult> {
    console.log(chalk.blue(`\n📋 Starting Claude Code...`));
    console.log(chalk.gray(`   Working Directory: ${config.workingDirectory}`));
    console.log(chalk.gray(`   MCP Config: ${this.mcpConfigPath}`));

    return new Promise((resolve) => {
      // Build Claude Code command
      // Claude Code is invoked with just the prompt as argument
      const args = [config.initialPrompt];

      console.log(chalk.cyan(`\n┌─────────────────────────────────────────────────────┐`));
      console.log(chalk.cyan(`│  Claude Code Session for ${config.agentName.padEnd(24)} │`));
      console.log(chalk.cyan(`│  Task: ${config.taskId.padEnd(40)} │`));
      console.log(chalk.cyan(`└─────────────────────────────────────────────────────┘\n`));

      // Spawn Claude Code process
      // Using 'inherit' stdio so user can see and interact
      const claudeProcess: ChildProcess = spawn('claude', args, {
        cwd: config.workingDirectory,
        stdio: 'inherit', // Pass through stdin/stdout/stderr
        shell: false, // Don't use shell to avoid interpreting prompt as commands
      });

      let output = '';

      // Handle process completion
      claudeProcess.on('close', (code) => {
        const exitCode = code || 0;

        if (exitCode === 0) {
          console.log(chalk.green(`\n✓ Claude Code session completed successfully`));
          resolve({
            success: true,
            exitCode,
            output,
          });
        } else {
          console.log(chalk.red(`\n✗ Claude Code session exited with code ${exitCode}`));
          resolve({
            success: false,
            exitCode,
            output,
            error: `Process exited with code ${exitCode}`,
          });
        }
      });

      claudeProcess.on('error', (error) => {
        console.error(chalk.red(`\n❌ Failed to start Claude Code:`), error.message);
        resolve({
          success: false,
          exitCode: 1,
          output: '',
          error: error.message,
        });
      });
    });
  }

  /**
   * Build initial prompt for Claude Code session
   */
  private buildInitialPrompt(task: Task, agent: Agent): string {
    const epicContext = task.epicId ? `\n\nThis task is part of Epic: ${task.epicId}` : '';
    const dependencies = task.dependencies?.length
      ? `\n\nDependencies: ${task.dependencies.join(', ')}`
      : '';

    return `You are ${agent.name}, a ${agent.role} on Team ${agent.team}.

Your expertise: ${agent.expertise.join(', ')}

You are working on:
**${task.title}**

Description:
${task.description}

Story Points: ${task.storyPoints}
Sprint: ${task.sprint}${epicContext}${dependencies}

You have access to the following MCP servers:
- **agent-orchestrator**: Task management tools
  • get_assigned_task() - Get full task context
  • complete_task(taskId, summary) - Mark task as done
  • report_progress(taskId, status, message) - Update progress
  • get_task_context(taskId) - Get epic & dependencies

- **laravel-boost**: Laravel development tools
- **herd**: Local development services
- **jetbrains**: IDE integration

**Your workflow:**
1. Use get_assigned_task() to get complete context
2. Analyze the task and plan your approach
3. Use report_progress() to update status as you work
4. Implement the solution following Laravel best practices
5. Write comprehensive tests
6. Use complete_task() when finished with a summary

**Important:**
- All changes are in your dedicated worktree (isolated from other agents)
- Commit your changes as you make progress
- Write tests for all code
- Follow Laravel best practices
- Ask questions if requirements are unclear

Ready to start working on this task?`;
  }

  /**
   * Create MCP configuration for Claude Code
   */
  async createMCPConfig(agentId?: string): Promise<void> {
    const config = {
      mcpServers: {
        'agent-orchestrator': {
          command: 'npm',
          args: ['run', 'mcp-server'],
          cwd: join(this.projectRoot, '.agents'),
          env: {
            BOARD_DIRECTORY: join(this.projectRoot, '.board/tasks'),
            AGENT_ID: agentId || '',
          },
        },
      },
    };

    await writeFile(this.mcpConfigPath, JSON.stringify(config, null, 2));
    console.log(chalk.green(`✓ MCP config created: ${this.mcpConfigPath}`));
  }
}
