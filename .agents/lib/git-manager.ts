import { exec } from 'child_process';
import { promisify } from 'util';

const execAsync = promisify(exec);

export interface GitCommit {
  hash: string;
  message: string;
  timestamp: Date;
  author: string;
}

export interface GitBranch {
  name: string;
  current: boolean;
  upstream?: string;
}

export class GitManager {
  constructor(private workingDirectory: string) {}

  /**
   * Create a new branch for an agent task
   */
  async createTaskBranch(agentId: string, taskId: string): Promise<string> {
    const branchName = this.generateBranchName(agentId, taskId);

    try {
      // Ensure we're on main/master
      await this.ensureMainBranch();

      // Pull latest changes
      await this.pull();

      // Create and checkout new branch
      await this.executeGit(`checkout -b ${branchName}`);

      console.log(`✓ Created branch: ${branchName}`);
      return branchName;
    } catch (error) {
      throw new Error(`Failed to create branch ${branchName}: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  /**
   * Commit changes with agent information
   */
  async commit(
    agentId: string,
    agentName: string,
    taskId: string,
    message: string,
    files?: string[]
  ): Promise<GitCommit> {
    try {
      // Stage files
      if (files && files.length > 0) {
        for (const file of files) {
          await this.executeGit(`add "${file}"`);
        }
      } else {
        // Stage all changes
        await this.executeGit('add .');
      }

      // Check if there are changes to commit
      const { stdout: status } = await this.executeGit('status --porcelain');
      if (!status.trim()) {
        console.log('⚠️  No changes to commit');
        return {
          hash: '',
          message: 'No changes',
          timestamp: new Date(),
          author: agentName,
        };
      }

      // Create commit message
      const fullMessage = `[${taskId}] ${message}

Agent: ${agentName} (${agentId})
Task: ${taskId}

🤖 Generated with Claude Agent Orchestrator`;

      // Commit with agent as author
      await this.executeGit(`commit -m "${fullMessage.replace(/"/g, '\\"')}"`);

      // Get commit hash
      const { stdout: hash } = await this.executeGit('rev-parse HEAD');

      console.log(`✓ Committed: ${message}`);

      return {
        hash: hash.trim(),
        message,
        timestamp: new Date(),
        author: agentName,
      };
    } catch (error) {
      throw new Error(`Failed to commit: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  /**
   * Create a checkpoint (tag) for versioning
   */
  async createCheckpoint(taskId: string, description: string): Promise<string> {
    try {
      const checkpointName = `checkpoint/${taskId}/${Date.now()}`;

      await this.executeGit(`tag -a ${checkpointName} -m "${description}"`);

      console.log(`✓ Created checkpoint: ${checkpointName}`);
      return checkpointName;
    } catch (error) {
      throw new Error(`Failed to create checkpoint: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  /**
   * Rollback to a previous commit or checkpoint
   */
  async rollback(target: string): Promise<void> {
    try {
      // Stash any uncommitted changes
      await this.stash('Auto-stash before rollback');

      // Reset to target
      await this.executeGit(`reset --hard ${target}`);

      console.log(`✓ Rolled back to: ${target}`);
    } catch (error) {
      throw new Error(`Failed to rollback: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  /**
   * Run tests and rollback if they fail
   */
  async commitWithTests(
    agentId: string,
    agentName: string,
    taskId: string,
    message: string,
    testCommand: string,
    files?: string[]
  ): Promise<{ success: boolean; commit?: GitCommit; error?: string }> {
    try {
      // Get current commit (for potential rollback)
      const { stdout: beforeCommit } = await this.executeGit('rev-parse HEAD');
      const rollbackPoint = beforeCommit.trim();

      // Commit changes
      const commit = await this.commit(agentId, agentName, taskId, message, files);

      if (!commit.hash) {
        return { success: true, commit }; // No changes, nothing to test
      }

      // Run tests
      console.log('🧪 Running tests...');
      try {
        await execAsync(testCommand, { cwd: this.workingDirectory });
        console.log('✓ Tests passed');
        return { success: true, commit };
      } catch (testError) {
        console.log('✗ Tests failed, rolling back...');

        // Rollback
        await this.rollback(rollbackPoint);

        return {
          success: false,
          error: testError instanceof Error ? testError.message : 'Tests failed',
        };
      }
    } catch (error) {
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Unknown error',
      };
    }
  }

  /**
   * Merge task branch back to gitbutler/workspace
   */
  async mergeToMain(branchName: string, taskId: string): Promise<void> {
    try {
      // Switch to gitbutler/workspace
      await this.executeGit('checkout gitbutler/workspace');

      // Pull latest
      await this.pull();

      // Merge branch
      await this.executeGit(`merge --no-ff ${branchName} -m "Merge ${taskId}: ${branchName}"`);

      console.log(`✓ Merged ${branchName} to gitbutler/workspace`);

      // Push to origin
      await this.executeGit('push origin gitbutler/workspace');
      console.log(`✓ Pushed gitbutler/workspace to origin`);

      // Delete branch (force delete to handle all cases)
      await this.executeGit(`branch -D ${branchName}`);

      console.log(`✓ Deleted branch: ${branchName}`);
    } catch (error) {
      throw new Error(`Failed to merge branch: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  /**
   * Push branch to remote
   */
  async push(branchName?: string, force: boolean = false): Promise<void> {
    try {
      const branch = branchName || (await this.getCurrentBranch());
      const forceFlag = force ? '--force' : '';

      await this.executeGit(`push ${forceFlag} -u origin ${branch}`);

      console.log(`✓ Pushed ${branch} to remote`);
    } catch (error) {
      // Remote might not exist, that's okay
      console.log(`⚠️  Could not push to remote: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  /**
   * Stash changes
   */
  async stash(message: string): Promise<void> {
    try {
      await this.executeGit(`stash push -m "${message}"`);
      console.log(`✓ Stashed changes: ${message}`);
    } catch (error) {
      // No changes to stash, that's okay
    }
  }

  /**
   * Pop stashed changes
   */
  async popStash(): Promise<void> {
    try {
      await this.executeGit('stash pop');
      console.log('✓ Popped stashed changes');
    } catch (error) {
      // No stash to pop, that's okay
    }
  }

  /**
   * Get current branch name
   */
  async getCurrentBranch(): Promise<string> {
    const { stdout } = await this.executeGit('rev-parse --abbrev-ref HEAD');
    return stdout.trim();
  }

  /**
   * Get commit history
   */
  async getHistory(limit: number = 10): Promise<GitCommit[]> {
    try {
      const { stdout } = await this.executeGit(
        `log -${limit} --pretty=format:"%H|%s|%aI|%an"`
      );

      const commits = stdout
        .split('\n')
        .filter((line) => line.trim())
        .map((line) => {
          const [hash, message, timestamp, author] = line.split('|');
          return {
            hash,
            message,
            timestamp: new Date(timestamp),
            author,
          };
        });

      return commits;
    } catch (error) {
      return [];
    }
  }

  /**
   * List all branches
   */
  async listBranches(): Promise<GitBranch[]> {
    try {
      const { stdout } = await this.executeGit('branch -a');

      const branches = stdout
        .split('\n')
        .filter((line) => line.trim())
        .map((line) => {
          const current = line.startsWith('*');
          const name = line.replace('*', '').trim();

          return {
            name,
            current,
          };
        });

      return branches;
    } catch (error) {
      return [];
    }
  }

  /**
   * Check if working directory is clean
   */
  async isClean(): Promise<boolean> {
    try {
      const { stdout } = await this.executeGit('status --porcelain');
      return !stdout.trim();
    } catch (error) {
      return false;
    }
  }

  /**
   * Get diff of current changes
   */
  async getDiff(staged: boolean = false): Promise<string> {
    try {
      const stagedFlag = staged ? '--cached' : '';
      const { stdout } = await this.executeGit(`diff ${stagedFlag}`);
      return stdout;
    } catch (error) {
      return '';
    }
  }

  // Private helper methods

  private generateBranchName(agentId: string, taskId: string): string {
    const timestamp = Date.now();
    return `agent/${agentId}/${taskId}-${timestamp}`;
  }

  private async ensureMainBranch(): Promise<void> {
    try {
      // Try to checkout main
      await this.executeGit('checkout main');
    } catch {
      // If main doesn't exist, try master
      try {
        await this.executeGit('checkout master');
      } catch {
        // Neither exists, create main
        await this.executeGit('checkout -b main');
      }
    }
  }

  private async pull(): Promise<void> {
    try {
      await this.executeGit('pull');
    } catch (error) {
      // No remote or no upstream, that's okay
      console.log('⚠️  Could not pull from remote (no remote configured)');
    }
  }

  private async executeGit(command: string): Promise<{ stdout: string; stderr: string }> {
    return execAsync(`git ${command}`, { cwd: this.workingDirectory });
  }
}
