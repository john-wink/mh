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

      // 2. Create MCP config for this session
      await this.createMCPConfig(worktreePath, agent.id, task.id);

      // 3. Build initial prompt for Claude Code
      const initialPrompt = this.buildInitialPrompt(task, agent);

      // 4. Start Claude Code session (foreground, user can see & interact)
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
   * Create MCP config for this session in the worktree directory
   */
  private async createMCPConfig(worktreePath: string, agentId: string, taskId: string): Promise<void> {
    const { mkdir, copyFile } = await import('fs/promises');
    const claudeDir = join(worktreePath, '.claude');

    // Create .claude directory if it doesn't exist
    await mkdir(claudeDir, { recursive: true });

    // Create MCP config with agent-specific environment variables
    const mcpConfig = {
      mcpServers: {
        'agent-orchestrator': {
          command: 'npm',
          args: ['run', 'mcp-server'],
          cwd: join(this.projectRoot, '.agents'),
          env: {
            BOARD_DIRECTORY: join(this.projectRoot, '.board/tasks'),
            AGENT_ID: agentId,
            TASK_ID: taskId,
          },
          alwaysAllow: ['tools', 'resources'],
        },
        'laravel-boost': {
          command: 'php',
          args: ['artisan', 'boost:mcp'],
          cwd: this.projectRoot,
          alwaysAllow: ['tools', 'resources'],
        },
      },
      globalSettings: {
        autoApprove: true,
      },
    };

    // Write config to worktree .claude directory
    const configPath = join(claudeDir, 'mcp-config.json');
    await writeFile(configPath, JSON.stringify(mcpConfig, null, 2));

    // Copy settings.local.json from project root to worktree for auto-approval
    const settingsSource = join(this.projectRoot, '.claude', 'settings.local.json');
    const settingsTarget = join(claudeDir, 'settings.local.json');

    try {
      await copyFile(settingsSource, settingsTarget);
      console.log(chalk.blue(`✓ Copied settings to worktree`));
    } catch (error) {
      console.log(chalk.yellow(`⚠️  Could not copy settings: ${error}`));

      // Create default settings with auto-approval
      const defaultSettings = {
        permissions: {
          allow: ['mcp__*', 'Bash', 'WebFetch(domain:manhunt.at)'],
        },
        enableAllProjectMcpServers: true,
        enabledMcpjsonServers: ['laravel-boost'],
      };
      await writeFile(settingsTarget, JSON.stringify(defaultSettings, null, 2));
      console.log(chalk.blue(`✓ Created default settings in worktree`));
    }

    // Copy .mcp.json from project root to worktree
    const mcpJsonSource = join(this.projectRoot, '.mcp.json');
    const mcpJsonTarget = join(worktreePath, '.mcp.json');

    try {
      await copyFile(mcpJsonSource, mcpJsonTarget);
      console.log(chalk.blue(`✓ Copied .mcp.json to worktree`));
    } catch (error) {
      console.log(chalk.yellow(`⚠️  Could not copy .mcp.json: ${error}`));
    }

    console.log(chalk.blue(`✓ Created MCP config for ${agentId} in worktree`));
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
    console.log(chalk.blue(`\n📋 Starting Claude Code in new Terminal window...`));
    console.log(chalk.gray(`   Working Directory: ${config.workingDirectory}`));
    console.log(chalk.gray(`   MCP Config: ${this.mcpConfigPath}`));

    return new Promise(async (resolve) => {
      console.log(chalk.cyan(`\n┌─────────────────────────────────────────────────────┐`));
      console.log(chalk.cyan(`│  Claude Code Session for ${config.agentName.padEnd(24)} │`));
      console.log(chalk.cyan(`│  Task: ${config.taskId.padEnd(40)} │`));
      console.log(chalk.cyan(`└─────────────────────────────────────────────────────┘\n`));

      try {
        // Create a temporary shell script to run Claude Code
        const { mkdtemp, writeFile: fsWriteFile, chmod } = await import('fs/promises');
        const { tmpdir } = await import('os');
        const tempDir = await mkdtemp(join(tmpdir(), 'claude-code-'));
        const scriptPath = join(tempDir, 'start-claude.sh');

        // Write the prompt to a separate file to avoid escaping issues
        const promptPath = join(tempDir, 'prompt.txt');
        await fsWriteFile(promptPath, config.initialPrompt);

        // Create shell script that will run in new Terminal
        const shellScript = `#!/bin/bash
cd "${config.workingDirectory}"
claude "$(cat '${promptPath}')"
rm -rf "${tempDir}"
`;

        await fsWriteFile(scriptPath, shellScript);
        await chmod(scriptPath, 0o755);

        // AppleScript to open new Terminal window and run the script
        const appleScript = `tell application "Terminal"
    do script "${scriptPath}"
    activate
end tell`;

        // Execute AppleScript to open new Terminal window
        const osascriptProcess = spawn('osascript', ['-e', appleScript], {
          stdio: ['pipe', 'pipe', 'pipe'],
        });

        let errorOutput = '';
        osascriptProcess.stderr?.on('data', (data) => {
          errorOutput += data.toString();
        });

        osascriptProcess.on('close', (code) => {
          if (code === 0) {
            console.log(chalk.green(`\n✓ Claude Code session started in new Terminal window`));
            console.log(chalk.yellow(`   Note: Close the Terminal window when you're done with the task`));
            resolve({
              success: true,
              exitCode: 0,
              output: 'Claude Code started in new Terminal window',
            });
          } else {
            console.log(chalk.red(`\n✗ Failed to open new Terminal window (exit code ${code})`));
            if (errorOutput) {
              console.log(chalk.red(`   Error: ${errorOutput}`));
            }
            resolve({
              success: false,
              exitCode: code || 1,
              output: '',
              error: `Failed to open Terminal window: ${errorOutput}`,
            });
          }
        });

        osascriptProcess.on('error', (error) => {
          console.error(chalk.red(`\n❌ Failed to start Terminal:`), error.message);
          resolve({
            success: false,
            exitCode: 1,
            output: '',
            error: error.message,
          });
        });
      } catch (error) {
        console.error(chalk.red(`\n❌ Failed to create launch script:`), error);
        resolve({
          success: false,
          exitCode: 1,
          output: '',
          error: error instanceof Error ? error.message : 'Unknown error',
        });
      }
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
6. **CRITICAL:** When finished, call complete_task(taskId="${task.id}", summary="<your summary>")
7. After calling complete_task(), you can exit (the system will auto-merge your branch to main)

**Important:**
- All changes are in your dedicated worktree (isolated from other agents)
- Commit your changes as you make progress
- Write tests for all code
- Follow Laravel best practices
- **You MUST call complete_task() to mark the task as done - this is how the system knows you're finished**
- After complete_task() is called, simply exit this session (Ctrl+C or end conversation)

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
