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

  /**
   * Initialize the worktree manager and clean up stale registrations
   * Must be called explicitly after construction
   */
  async initialize(): Promise<void> {
    try {
      // Prune stale worktree registrations
      await execAsync('git worktree prune', {
        cwd: this.projectRoot,
      });
      console.log(chalk.green('✓ Pruned stale worktree registrations'));

      // Reconstruct worktrees map from existing worktrees
      await this.reconstructWorktreesMap();
    } catch (error) {
      console.log(chalk.yellow('⚠️  Could not prune worktrees:', error));
    }
  }

  /**
   * Reconstruct the worktrees map by parsing git worktree list
   */
  private async reconstructWorktreesMap(): Promise<void> {
    try {
      const { stdout } = await execAsync('git worktree list --porcelain', {
        cwd: this.projectRoot,
      });

      // Parse porcelain output - each worktree is separated by blank line
      const worktrees = stdout.split('\n\n').filter((block) => block.trim());

      for (const block of worktrees) {
        const lines = block.split('\n');
        const worktreeLine = lines.find((l) => l.startsWith('worktree '));

        if (worktreeLine) {
          const worktreePath = worktreeLine.replace('worktree ', '');

          // Only register worktrees in our .worktrees directory
          if (worktreePath.includes(this.worktreeBaseDir)) {
            const agentId = worktreePath.split('/').pop();
            if (agentId) {
              this.worktrees.set(agentId, worktreePath);
              console.log(chalk.blue(`  ↳ Registered existing worktree: ${agentId}`));
            }
          }
        }
      }

      if (this.worktrees.size > 0) {
        console.log(chalk.green(`✓ Reconstructed ${this.worktrees.size} worktree(s) from git`));
      }
    } catch (error) {
      console.log(chalk.yellow('⚠️  Could not reconstruct worktrees map:', error));
    }
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

    // If worktree already exists in our map, return it
    if (this.worktrees.has(agentId)) {
      console.log(chalk.yellow(`⚠️  Worktree for ${agentId} already exists`));
      return this.worktrees.get(agentId)!;
    }

    const branch = branchName || `worktree/${agentId}`;

    // FAILSAFE: Clean up any stale worktree registration or directory
    try {
      // Try to remove any existing registration (even if directory is missing)
      await execAsync(`git worktree remove ${worktreePath} --force 2>/dev/null || true`, {
        cwd: this.projectRoot,
      });
    } catch {
      // Ignore errors - worktree might not exist
    }

    // Remove directory if it exists
    if (existsSync(worktreePath)) {
      console.log(chalk.yellow(`⚠️  Removing existing worktree directory: ${worktreePath}`));
      await rm(worktreePath, { recursive: true, force: true });
    }

    // Now try to create the worktree
    try {
      // Try creating with new branch first
      await execAsync(`git worktree add -b ${branch} ${worktreePath}`, {
        cwd: this.projectRoot,
      });

      this.worktrees.set(agentId, worktreePath);
      console.log(chalk.green(`✓ Created worktree for ${agentId}: ${worktreePath}`));

      // Run composer setup in the new worktree
      await this.runComposerSetup(worktreePath, agentId);

      return worktreePath;
    } catch (error) {
      // If branch already exists, checkout existing branch
      if (error instanceof Error && error.message.includes('already exists')) {
        try {
          await execAsync(`git worktree add ${worktreePath} ${branch}`, {
            cwd: this.projectRoot,
          });

          this.worktrees.set(agentId, worktreePath);
          console.log(chalk.green(`✓ Created worktree for ${agentId} (using existing branch): ${worktreePath}`));

          // Run composer setup in the new worktree
          await this.runComposerSetup(worktreePath, agentId);

          return worktreePath;
        } catch (retryError) {
          // Last resort: force add worktree
          console.log(chalk.yellow(`⚠️  Forcing worktree creation for ${agentId}...`));

          try {
            await execAsync(`git worktree add --force ${worktreePath} ${branch}`, {
              cwd: this.projectRoot,
            });

            this.worktrees.set(agentId, worktreePath);
            console.log(chalk.green(`✓ Force-created worktree for ${agentId}: ${worktreePath}`));

            // Run composer setup in the new worktree
            await this.runComposerSetup(worktreePath, agentId);

            return worktreePath;
          } catch (forceError) {
            console.error(chalk.red(`❌ Failed to create worktree for ${agentId}:`, forceError));
            throw forceError;
          }
        }
      }

      // If it's a different error, try force add as last resort
      console.log(chalk.yellow(`⚠️  Attempting force worktree creation for ${agentId}...`));

      try {
        await execAsync(`git worktree add --force ${worktreePath} ${branch}`, {
          cwd: this.projectRoot,
        });

        this.worktrees.set(agentId, worktreePath);
        console.log(chalk.green(`✓ Force-created worktree for ${agentId}: ${worktreePath}`));

        // Run composer setup in the new worktree
        await this.runComposerSetup(worktreePath, agentId);

        return worktreePath;
      } catch (forceError) {
        console.error(chalk.red(`❌ Failed to create worktree for ${agentId}:`, forceError));
        throw forceError;
      }
    }
  }

  /**
   * Run composer setup in the worktree
   */
  private async runComposerSetup(worktreePath: string, agentId: string): Promise<void> {
    try {
      console.log(chalk.blue(`📦 Running composer setup for ${agentId}...`));

      await execAsync('composer run setup', {
        cwd: worktreePath,
      });

      console.log(chalk.green(`✓ Composer setup completed for ${agentId}`));
    } catch (error) {
      console.log(chalk.yellow(`⚠️  Composer setup failed for ${agentId}, continuing anyway...`));
      // Don't throw - we want the worktree to be created even if composer setup fails
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

      // Delete the agent's branch after successful merge
      try {
        await execAsync(`git branch -D ${branch}`, {
          cwd: this.projectRoot,
        });
        console.log(chalk.blue(`✓ Deleted merged branch: ${branch}`));
      } catch (branchDeleteError) {
        console.log(
          chalk.yellow(
            `⚠️  Could not delete branch ${branch}: ${branchDeleteError instanceof Error ? branchDeleteError.message : 'Unknown error'}`
          )
        );
      }
    } catch (error) {
      console.error(chalk.red(`❌ Failed to merge branch for ${agentId}:`, error));
      throw error;
    }
  }

  /**
   * Get detailed status information for a worktree
   */
  async getWorktreeStatus(agentId: string): Promise<{
    exists: boolean;
    path?: string;
    branch?: string;
    hasChanges: boolean;
    ahead: number;
    behind: number;
    files: {
      added: string[];
      modified: string[];
      deleted: string[];
    };
  }> {
    const worktreePath = this.worktrees.get(agentId);

    if (!worktreePath || !existsSync(worktreePath)) {
      return {
        exists: false,
        hasChanges: false,
        ahead: 0,
        behind: 0,
        files: { added: [], modified: [], deleted: [] },
      };
    }

    try {
      // Get current branch
      const { stdout: branch } = await execAsync('git branch --show-current', {
        cwd: worktreePath,
      });

      // Get status
      const { stdout: status } = await execAsync('git status --porcelain', {
        cwd: worktreePath,
      });

      // Parse changed files
      const files = { added: [] as string[], modified: [] as string[], deleted: [] as string[] };
      const lines = status.split('\n').filter((line) => line.trim());

      for (const line of lines) {
        const statusCode = line.substring(0, 2);
        const file = line.substring(3);

        if (statusCode.includes('A')) files.added.push(file);
        else if (statusCode.includes('M')) files.modified.push(file);
        else if (statusCode.includes('D')) files.deleted.push(file);
        else if (statusCode === '??') files.added.push(file);
      }

      // Get commits ahead/behind main
      let ahead = 0;
      let behind = 0;

      try {
        const { stdout: revList } = await execAsync('git rev-list --left-right --count main...HEAD', {
          cwd: worktreePath,
        });

        const [behindStr, aheadStr] = revList.trim().split('\t');
        behind = parseInt(behindStr) || 0;
        ahead = parseInt(aheadStr) || 0;
      } catch {
        // Ignore errors (e.g., no main branch)
      }

      return {
        exists: true,
        path: worktreePath,
        branch: branch.trim(),
        hasChanges: lines.length > 0 || ahead > 0,
        ahead,
        behind,
        files,
      };
    } catch (error) {
      console.error(chalk.red(`❌ Failed to get status for ${agentId}:`, error));
      return {
        exists: true,
        path: worktreePath,
        hasChanges: false,
        ahead: 0,
        behind: 0,
        files: { added: [], modified: [], deleted: [] },
      };
    }
  }

  /**
   * Sync a worktree with main branch (pull latest changes)
   */
  async syncWorktreeWithMain(agentId: string, targetBranch: string = 'main'): Promise<void> {
    const worktreePath = this.worktrees.get(agentId);

    if (!worktreePath) {
      throw new Error(`No worktree found for ${agentId}`);
    }

    try {
      // Fetch latest from main repo
      await execAsync(`git fetch origin ${targetBranch}`, {
        cwd: this.projectRoot,
      });

      // Merge main into worktree branch
      await execAsync(`git merge origin/${targetBranch} --no-edit`, {
        cwd: worktreePath,
      });

      console.log(chalk.green(`✓ Synced ${agentId}'s worktree with ${targetBranch}`));
    } catch (error) {
      // Try alternative sync method if no remote
      try {
        // Update main in project root
        await execAsync(`git checkout ${targetBranch}`, {
          cwd: this.projectRoot,
        });

        await execAsync('git pull || true', {
          cwd: this.projectRoot,
        });

        // Get latest commit hash from main
        const { stdout: mainCommit } = await execAsync('git rev-parse HEAD', {
          cwd: this.projectRoot,
        });

        // Merge that commit into worktree
        await execAsync(`git merge ${mainCommit.trim()} --no-edit`, {
          cwd: worktreePath,
        });

        console.log(chalk.green(`✓ Synced ${agentId}'s worktree with ${targetBranch}`));
      } catch (syncError) {
        console.error(chalk.red(`❌ Failed to sync worktree for ${agentId}:`, syncError));
        throw syncError;
      }
    }
  }

  /**
   * Get all worktrees with their status
   */
  async getAllWorktreeStatuses(): Promise<
    Map<
      string,
      {
        exists: boolean;
        path?: string;
        branch?: string;
        hasChanges: boolean;
        ahead: number;
        behind: number;
        files: { added: string[]; modified: string[]; deleted: string[] };
      }
    >
  > {
    const statuses = new Map();

    for (const agentId of this.worktrees.keys()) {
      const status = await this.getWorktreeStatus(agentId);
      statuses.set(agentId, status);
    }

    return statuses;
  }

  /**
   * Sync all worktrees with main
   */
  async syncAllWorktrees(targetBranch: string = 'main'): Promise<void> {
    console.log(chalk.blue(`🔄 Syncing all worktrees with ${targetBranch}...`));

    const agentIds = Array.from(this.worktrees.keys());

    for (const agentId of agentIds) {
      try {
        await this.syncWorktreeWithMain(agentId, targetBranch);
      } catch (error) {
        console.error(chalk.red(`❌ Failed to sync ${agentId}:`, error));
      }
    }

    console.log(chalk.green('✓ All worktrees synced'));
  }

  /**
   * Get registered worktrees map
   */
  getWorktrees(): Map<string, string> {
    return this.worktrees;
  }
}
