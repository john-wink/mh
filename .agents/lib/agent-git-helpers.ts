// Git helper methods for Agent class (to be included in agent.ts)

/**
 * Commit current progress on the task
 */
async commitProgress(task: Task, message: string): Promise<void> {
  try {
    await this.gitManager.commit(
      this.id,
      this.name,
      task.id,
      message
    );
  } catch (error) {
    console.log(`⚠️  Could not commit: ${error instanceof Error ? error.message : 'Unknown error'}`);
  }
}

/**
 * Commit with automatic test validation
 */
async commitWithTests(task: Task, message: string, testCommand: string = 'php artisan test'): Promise<boolean> {
  try {
    const result = await this.gitManager.commitWithTests(
      this.id,
      this.name,
      task.id,
      message,
      testCommand
    );

    if (!result.success) {
      console.log(`✗ Tests failed, changes rolled back: ${result.error}`);
      return false;
    }

    console.log(`✓ Changes committed and tests passed`);
    return true;
  } catch (error) {
    console.log(`⚠️  Could not commit with tests: ${error instanceof Error ? error.message : 'Unknown error'}`);
    return false;
  }
}

/**
 * Create a checkpoint (tag) for this task
 */
async createCheckpoint(task: Task, description: string): Promise<void> {
  try {
    await this.gitManager.createCheckpoint(task.id, description);
  } catch (error) {
    console.log(`⚠️  Could not create checkpoint: ${error instanceof Error ? error.message : 'Unknown error'}`);
  }
}

/**
 * Rollback task to last checkpoint
 */
async rollbackTask(task: Task): Promise<void> {
  try {
    // Find last checkpoint for this task
    const checkpointName = `checkpoint/${task.id}`;
    await this.gitManager.rollback(checkpointName);
    console.log(`✓ Rolled back task ${task.id} to last checkpoint`);
  } catch (error) {
    console.log(`⚠️  Could not rollback: ${error instanceof Error ? error.message : 'Unknown error'}`);
  }
}

/**
 * Get Git history for current branch
 */
async getTaskHistory(limit: number = 10): Promise<any[]> {
  try {
    return await this.gitManager.getHistory(limit);
  } catch (error) {
    return [];
  }
}

/**
 * Check if working directory is clean
 */
async isWorkingDirectoryClean(): Promise<boolean> {
  try {
    return await this.gitManager.isClean();
  } catch (error) {
    return false;
  }
}
