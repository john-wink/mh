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

    // Create settings.local.json in worktree with auto-approval
    const settingsTarget = join(claudeDir, 'settings.local.json');
    const settings = {
      permissions: {
        allow: ['mcp__*', 'Bash', 'WebFetch(domain:manhunt.at)'],
      },
      enabledMcpjsonServers: ['laravel-boost', 'agent-orchestrator'],
    };
    await writeFile(settingsTarget, JSON.stringify(settings, null, 2));
    console.log(chalk.blue(`✓ Created settings in worktree`));

    // Create .mcp.json in worktree with all needed MCP servers
    const worktreeMcpConfig = {
      mcpServers: {
        'laravel-boost': {
          command: 'php',
          args: ['artisan', 'boost:mcp'],
          cwd: this.projectRoot,
        },
        'agent-orchestrator': {
          command: 'npm',
          args: ['run', 'mcp-server'],
          cwd: join(this.projectRoot, '.agents'),
          env: {
            BOARD_DIRECTORY: join(this.projectRoot, '.board/tasks'),
            AGENT_ID: agentId,
            TASK_ID: taskId,
          },
        },
      },
    };

    const mcpJsonTarget = join(worktreePath, '.mcp.json');
    await writeFile(mcpJsonTarget, JSON.stringify(worktreeMcpConfig, null, 2));
    console.log(chalk.blue(`✓ Created .mcp.json in worktree`));

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
claude --dangerously-skip-permissions "$(cat '${promptPath}')"
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

**IMPORTANT: MCP Tools Access**
You have direct access to MCP tools through the Claude Code interface. These tools are already configured and ready to use - do NOT try to run \`php artisan mcp:list\` or similar commands. Just use the tools directly.

**Available MCP Servers & Tools:**

**agent-orchestrator** - Task management (use these tools to interact with the task system):
  • mcp__agent_orchestrator__get_assigned_task() - Get your complete task details
  • mcp__agent_orchestrator__complete_task(taskId, summary) - Mark task as done when finished
  • mcp__agent_orchestrator__report_progress(taskId, status, message) - Update your progress
  • mcp__agent_orchestrator__get_task_context(taskId) - Get epic & dependency information

**laravel-boost** - Laravel development tools (database, tinker, logs, tests, etc.):
  • mcp__laravel_boost__database_schema() - View database structure
  • mcp__laravel_boost__database_query(query) - Run SELECT queries
  • mcp__laravel_boost__tinker(code) - Execute PHP in Laravel context
  • mcp__laravel_boost__last_error() - Get last backend error
  • mcp__laravel_boost__browser_logs(entries) - Get frontend errors
  • mcp__laravel_boost__read_log_entries(entries) - Read application logs
  • mcp__laravel_boost__list_routes() - List all routes
  • mcp__laravel_boost__search_docs(queries, packages) - Search Laravel docs
  • And many more - check the tool list in Claude Code

**herd** - Local development services (MySQL, Redis, etc.)
**jetbrains** - PhpStorm/IDE integration

**Your workflow:**
1. Start immediately - use mcp__agent_orchestrator__get_assigned_task() if you need more context
2. Analyze the task and plan your approach
3. Use mcp__agent_orchestrator__report_progress() to update status as you work
4. Implement the solution following Laravel best practices
5. Write comprehensive tests for your changes
6. **CRITICAL:** Before committing, run \`vendor/bin/rector\` then \`vendor/bin/pint\`
7. **CRITICAL:** Before committing, run the ENTIRE test suite with \`php artisan test\`
   - If ANY test fails (even existing ones not related to your changes):
     * Investigate the failure
     * Either fix the bug causing the test to fail
     * OR update the test if it's outdated
   - Do NOT proceed until ALL tests pass
8. After all tests pass, commit your changes
9. **CRITICAL:** When finished, call mcp__agent_orchestrator__complete_task(taskId="${task.id}", summary="<your summary>")
10. After calling complete_task(), you can exit (the system will auto-merge your branch to gitbutler/workspace)

**Important:**
- All changes are in your dedicated worktree (isolated from other agents)
- Follow the CLAUDE.md guidelines (especially for Laravel, Filament, Pest, etc.)
- **You MUST run rector + pint before every commit**
- **You MUST run the full test suite before every commit - no exceptions**
- **You MUST call mcp__agent_orchestrator__complete_task() to mark the task as done**
- After complete_task() is called, simply exit this session (Ctrl+C or end conversation)
- Do NOT try to use artisan commands to access MCP tools - they're already available directly

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
