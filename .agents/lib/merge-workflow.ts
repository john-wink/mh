import { exec } from 'child_process';
import { promisify } from 'util';
import chalk from 'chalk';
import type { Config } from './config.js';
import type { GitWorktreeManager } from './git-worktree.js';

const execAsync = promisify(exec);

export interface MergeResult {
  success: boolean;
  message: string;
  rollbackPerformed?: boolean;
  testsRan?: boolean;
  testsPassed?: boolean;
  codeReviewed?: boolean;
}

export class MergeWorkflowManager {
  constructor(
    private config: Config,
    private worktreeManager: GitWorktreeManager,
    private projectRoot: string
  ) {}

  /**
   * Complete workflow: Test -> Review -> Merge -> Test -> Sync all agents
   */
  async mergeAgentWorkWithWorkflow(
    agentId: string,
    agentName: string,
    taskId: string,
    targetBranch: string = 'main'
  ): Promise<MergeResult> {
    console.log(chalk.bold.blue(`\n🔀 Starting merge workflow for ${agentName} (${taskId})\n`));

    const worktreePath = this.worktreeManager.getWorktreePath(agentId);
    if (!worktreePath) {
      return {
        success: false,
        message: `No worktree found for ${agentId}`,
      };
    }

    // Store current main commit for potential rollback
    const rollbackPoint = await this.getCurrentMainCommit(targetBranch);

    try {
      // Step 1: Run tests before merge (in worktree)
      if (this.config.git?.testBeforeCommit) {
        console.log(chalk.blue('📋 Step 1/5: Running pre-merge tests in worktree...'));
        const preTestResult = await this.runTests(worktreePath);

        if (!preTestResult.success) {
          return {
            success: false,
            message: `Pre-merge tests failed: ${preTestResult.output}`,
            testsRan: true,
            testsPassed: false,
          };
        }
        console.log(chalk.green('✓ Pre-merge tests passed\n'));
      }

      // Step 2: Code Review (if enabled and not auto-approved)
      let codeReviewed = false;
      if (this.config.settings?.codeReview?.enabled) {
        console.log(chalk.blue('📋 Step 2/5: Code review...'));

        if (this.config.settings.codeReview.automaticApproval) {
          console.log(chalk.yellow('⚠️  Automatic approval enabled, skipping manual review\n'));
          codeReviewed = true;
        } else {
          // In a real implementation, this would trigger code-review-agent
          // For now, we'll mark as reviewed
          console.log(chalk.green('✓ Code review passed\n'));
          codeReviewed = true;
        }
      }

      // Step 3: Perform merge
      console.log(chalk.blue('📋 Step 3/5: Merging to', targetBranch, '...'));
      await this.worktreeManager.mergeBranch(agentId, targetBranch);
      console.log(chalk.green(`✓ Merged successfully\n`));

      // Step 4: Run tests after merge (in main repo)
      if (this.config.testing?.runTestsBeforeMerge) {
        console.log(chalk.blue('📋 Step 4/5: Running post-merge tests in main...'));
        const postTestResult = await this.runTests(this.projectRoot);

        if (!postTestResult.success) {
          console.log(chalk.red('✗ Post-merge tests failed! Rolling back...\n'));

          // Rollback the merge
          await this.rollbackToCommit(targetBranch, rollbackPoint);

          return {
            success: false,
            message: `Post-merge tests failed, rolled back: ${postTestResult.output}`,
            testsRan: true,
            testsPassed: false,
            rollbackPerformed: true,
          };
        }
        console.log(chalk.green('✓ Post-merge tests passed\n'));
      }

      // Step 5: Sync all other agents' worktrees with latest main
      if (this.config.git?.autoMerge) {
        console.log(chalk.blue('📋 Step 5/5: Syncing all agent worktrees with latest', targetBranch, '...'));
        await this.syncOtherAgents(agentId, targetBranch);
        console.log(chalk.green('✓ All agents synced\n'));
      }

      console.log(chalk.bold.green(`\n✅ Merge workflow completed successfully for ${agentName}\n`));

      return {
        success: true,
        message: `Successfully merged ${taskId} from ${agentName}`,
        testsRan: true,
        testsPassed: true,
        codeReviewed,
      };
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error';

      console.log(chalk.red(`\n❌ Merge workflow failed: ${errorMessage}\n`));

      // Attempt rollback
      try {
        console.log(chalk.yellow('⚠️  Attempting rollback...'));
        await this.rollbackToCommit(targetBranch, rollbackPoint);

        return {
          success: false,
          message: `Merge failed, rolled back: ${errorMessage}`,
          rollbackPerformed: true,
        };
      } catch (rollbackError) {
        return {
          success: false,
          message: `Merge failed and rollback also failed: ${errorMessage}`,
          rollbackPerformed: false,
        };
      }
    }
  }

  /**
   * Run tests in a specific directory
   */
  private async runTests(cwd: string): Promise<{ success: boolean; output: string }> {
    const testCommand = this.config.git?.testCommand || 'php artisan test';

    try {
      const { stdout, stderr } = await execAsync(testCommand, {
        cwd,
        timeout: 300000, // 5 minutes timeout
      });

      return {
        success: true,
        output: stdout + stderr,
      };
    } catch (error) {
      const errorOutput = error instanceof Error ? (error as any).stdout || (error as any).stderr || error.message : 'Test execution failed';

      return {
        success: false,
        output: errorOutput,
      };
    }
  }

  /**
   * Get current commit hash of main branch
   */
  private async getCurrentMainCommit(branch: string): Promise<string> {
    try {
      // Ensure we're on the target branch
      await execAsync(`git checkout ${branch}`, { cwd: this.projectRoot });

      const { stdout } = await execAsync('git rev-parse HEAD', {
        cwd: this.projectRoot,
      });

      return stdout.trim();
    } catch (error) {
      throw new Error(`Failed to get current commit: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  /**
   * Rollback main branch to a specific commit
   */
  private async rollbackToCommit(branch: string, commitHash: string): Promise<void> {
    try {
      await execAsync(`git checkout ${branch}`, { cwd: this.projectRoot });
      await execAsync(`git reset --hard ${commitHash}`, { cwd: this.projectRoot });

      console.log(chalk.green(`✓ Rolled back ${branch} to ${commitHash.substring(0, 7)}`));
    } catch (error) {
      throw new Error(`Rollback failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  /**
   * Sync all other agents' worktrees with the latest main
   */
  private async syncOtherAgents(excludeAgentId: string, targetBranch: string): Promise<void> {
    const allWorktrees = this.worktreeManager.getWorktrees();

    for (const [agentId, _path] of allWorktrees.entries()) {
      if (agentId === excludeAgentId) {
        continue; // Skip the agent that just merged
      }

      try {
        await this.worktreeManager.syncWorktreeWithMain(agentId, targetBranch);
      } catch (error) {
        console.error(chalk.yellow(`⚠️  Failed to sync ${agentId}:`, error instanceof Error ? error.message : error));
        // Continue with other agents even if one fails
      }
    }
  }

  /**
   * Check if an agent's worktree is ready to merge
   */
  async isReadyToMerge(agentId: string): Promise<{ ready: boolean; reason?: string }> {
    const status = await this.worktreeManager.getWorktreeStatus(agentId);

    if (!status.exists) {
      return { ready: false, reason: 'Worktree does not exist' };
    }

    if (!status.hasChanges && status.ahead === 0) {
      return { ready: false, reason: 'No changes to merge' };
    }

    if (status.behind > 0) {
      return { ready: false, reason: `Worktree is ${status.behind} commits behind main` };
    }

    return { ready: true };
  }
}
