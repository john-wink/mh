import { exec } from 'child_process';
import { promisify } from 'util';
import { existsSync } from 'fs';
import { mkdir, rm } from 'fs/promises';
import { join } from 'path';
import chalk from 'chalk';

const execAsync = promisify(exec);

export class GitWorktreeManager {
  private projectRoot: string;
  private worktreeBaseDir: string;
  private worktrees: Map<string, string> = new Map();

  constructor(projectRoot: string) {
    this.projectRoot = projectRoot;
    this.worktreeBaseDir = join(projectRoot, '.worktrees');
  }

  async ensureWorktreeDirectory(): Promise<void> {
    if (!existsSync(this.worktreeBaseDir)) {
      await mkdir(this.worktreeBaseDir, { recursive: true });
      console.log(chalk.green(`✓ Created worktree directory: ${this.worktreeBaseDir}`));
    }
  }

  async createWorktreeForAgent(agentId: string, branchName?: string): Promise<string> {
    await this.ensureWorktreeDirectory();

    const worktreePath = join(this.worktreeBaseDir, agentId);

    // If worktree already exists, return it
    if (this.worktrees.has(agentId)) {
      console.log(chalk.yellow(`⚠️  Worktree for ${agentId} already exists`));
      return this.worktrees.get(agentId)!;
    }

    try {
      // Create a new branch for this agent if not specified
      const branch = branchName || `worktree/${agentId}`;

      // Check if worktree directory exists
      if (existsSync(worktreePath)) {
        console.log(chalk.yellow(`⚠️  Removing existing worktree directory: ${worktreePath}`));
        await rm(worktreePath, { recursive: true, force: true });
      }

      // Create git worktree
      await execAsync(`git worktree add -b ${branch} ${worktreePath}`, {
        cwd: this.projectRoot,
      });

      this.worktrees.set(agentId, worktreePath);
      console.log(chalk.green(`✓ Created worktree for ${agentId}: ${worktreePath}`));

      return worktreePath;
    } catch (error) {
      // If branch already exists, try to checkout the existing branch
      if (error instanceof Error && error.message.includes('already exists')) {
        try {
          const branch = branchName || `worktree/${agentId}`;
          await execAsync(`git worktree add ${worktreePath} ${branch}`, {
            cwd: this.projectRoot,
          });

          this.worktrees.set(agentId, worktreePath);
          console.log(chalk.green(`✓ Created worktree for ${agentId} (using existing branch): ${worktreePath}`));

          return worktreePath;
        } catch (retryError) {
          console.error(chalk.red(`❌ Failed to create worktree for ${agentId}:`, retryError));
          throw retryError;
        }
      }

      console.error(chalk.red(`❌ Failed to create worktree for ${agentId}:`, error));
      throw error;
    }
  }

  async removeWorktree(agentId: string): Promise<void> {
    const worktreePath = this.worktrees.get(agentId);

    if (!worktreePath) {
      console.log(chalk.yellow(`⚠️  No worktree found for ${agentId}`));
      return;
    }

    try {
      // Remove git worktree
      await execAsync(`git worktree remove ${worktreePath} --force`, {
        cwd: this.projectRoot,
      });

      this.worktrees.delete(agentId);
      console.log(chalk.green(`✓ Removed worktree for ${agentId}`));
    } catch (error) {
      console.error(chalk.red(`❌ Failed to remove worktree for ${agentId}:`, error));

      // Try to clean up directory manually
      if (existsSync(worktreePath)) {
        await rm(worktreePath, { recursive: true, force: true });
        console.log(chalk.yellow(`⚠️  Manually removed worktree directory for ${agentId}`));
      }

      this.worktrees.delete(agentId);
    }
  }

  async cleanupAllWorktrees(): Promise<void> {
    console.log(chalk.blue('🧹 Cleaning up all worktrees...'));

    const agentIds = Array.from(this.worktrees.keys());

    for (const agentId of agentIds) {
      await this.removeWorktree(agentId);
    }

    console.log(chalk.green('✓ All worktrees cleaned up'));
  }

  getWorktreePath(agentId: string): string | undefined {
    return this.worktrees.get(agentId);
  }

  async listWorktrees(): Promise<void> {
    try {
      const { stdout } = await execAsync('git worktree list', {
        cwd: this.projectRoot,
      });

      console.log(chalk.blue('\n📋 Git Worktrees:'));
      console.log(stdout);
    } catch (error) {
      console.error(chalk.red('❌ Failed to list worktrees:', error));
    }
  }

  async mergeBranch(agentId: string, targetBranch: string = 'main'): Promise<void> {
    const worktreePath = this.worktrees.get(agentId);

    if (!worktreePath) {
      throw new Error(`No worktree found for ${agentId}`);
    }

    try {
      // Get current branch name
      const { stdout: currentBranch } = await execAsync('git branch --show-current', {
        cwd: worktreePath,
      });

      const branch = currentBranch.trim();

      // Switch to target branch in main repo
      await execAsync(`git checkout ${targetBranch}`, {
        cwd: this.projectRoot,
      });

      // Merge the agent's branch
      await execAsync(`git merge ${branch} --no-ff -m "Merge ${agentId} work"`, {
        cwd: this.projectRoot,
      });

      console.log(chalk.green(`✓ Merged ${agentId}'s branch into ${targetBranch}`));
    } catch (error) {
      console.error(chalk.red(`❌ Failed to merge branch for ${agentId}:`, error));
      throw error;
    }
  }
}
