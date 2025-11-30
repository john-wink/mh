import { readFile, writeFile, mkdir } from 'fs/promises';
import { join } from 'path';
import { existsSync } from 'fs';
import type { Task } from '../lib/agent.js';

export class TaskManager {
  private tasks: Map<string, Task> = new Map();
  private tasksDirectory: string;
  private nextTaskId: number = 1;

  constructor(tasksDirectory: string) {
    this.tasksDirectory = tasksDirectory;
    this.ensureTasksDirectory();
    this.loadTasks();
  }

  private async ensureTasksDirectory(): Promise<void> {
    if (!existsSync(this.tasksDirectory)) {
      await mkdir(this.tasksDirectory, { recursive: true });
    }
  }

  private async loadTasks(): Promise<void> {
    try {
      const tasksFile = join(this.tasksDirectory, 'tasks.json');
      if (existsSync(tasksFile)) {
        const data = await readFile(tasksFile, 'utf-8');
        const tasksArray = JSON.parse(data);
        for (const task of tasksArray) {
          if (task.startedAt) task.startedAt = new Date(task.startedAt);
          if (task.completedAt) task.completedAt = new Date(task.completedAt);
          this.tasks.set(task.id, task);
        }
        const taskNumbers = Array.from(this.tasks.keys())
          .map(id => parseInt(id.replace('TASK-', '')))
          .filter(n => !isNaN(n));
        if (taskNumbers.length > 0) {
          this.nextTaskId = Math.max(...taskNumbers) + 1;
        }
        console.log(`✓ Loaded ${this.tasks.size} tasks`);
      }
    } catch (error) {
      console.log('⚠️  Could not load tasks, starting fresh');
      this.tasks = new Map();
    }
  }

  private async saveTasks(): Promise<void> {
    const tasksFile = join(this.tasksDirectory, 'tasks.json');
    const tasksArray = Array.from(this.tasks.values());
    await writeFile(tasksFile, JSON.stringify(tasksArray, null, 2), 'utf-8');
  }

  async createTask(taskData: Omit<Task, 'id' | 'status'>): Promise<Task> {
    const task: Task = {
      id: `TASK-${String(this.nextTaskId).padStart(3, '0')}`,
      status: 'pending',
      ...taskData,
    };
    this.nextTaskId++;
    this.tasks.set(task.id, task);
    await this.saveTasks();
    console.log(`✓ Created task ${task.id}: ${task.title}`);
    return task;
  }

  getTask(taskId: string): Task | undefined {
    return this.tasks.get(taskId);
  }

  getTasks(filter?: any): Task[] {
    let tasks = Array.from(this.tasks.values());
    if (filter) {
      if (filter.status) tasks = tasks.filter(t => t.status === filter.status);
      if (filter.team !== undefined) tasks = tasks.filter(t => t.team === filter.team);
      if (filter.assignedTo) tasks = tasks.filter(t => t.assignedTo === filter.assignedTo);
      if (filter.sprint !== undefined) tasks = tasks.filter(t => t.sprint === filter.sprint);
    }
    return tasks;
  }

  async updateTask(taskId: string, updates: Partial<Task>): Promise<Task | null> {
    const task = this.tasks.get(taskId);
    if (!task) return null;
    Object.assign(task, updates);
    await this.saveTasks();
    return task;
  }

  async deleteTask(taskId: string): Promise<boolean> {
    const deleted = this.tasks.delete(taskId);
    if (deleted) await this.saveTasks();
    return deleted;
  }

  async assignTask(taskId: string, agentId: string): Promise<Task | null> {
    const task = this.tasks.get(taskId);
    if (!task) return null;
    if (task.status !== 'pending') throw new Error(`Task ${taskId} is not pending`);
    task.assignedTo = agentId;
    task.status = 'in_progress';
    task.startedAt = new Date();
    await this.saveTasks();
    return task;
  }

  async completeTask(taskId: string): Promise<Task | null> {
    const task = this.tasks.get(taskId);
    if (!task) return null;
    task.status = 'completed';
    task.completedAt = new Date();
    await this.saveTasks();
    return task;
  }

  async blockTask(taskId: string, reason: string): Promise<Task | null> {
    const task = this.tasks.get(taskId);
    if (!task) return null;
    task.status = 'blocked';
    task.blockedReason = reason;
    await this.saveTasks();
    return task;
  }

  async unblockTask(taskId: string): Promise<Task | null> {
    const task = this.tasks.get(taskId);
    if (!task) return null;
    if (task.status !== 'blocked') throw new Error(`Task ${taskId} is not blocked`);
    task.status = 'pending';
    task.blockedReason = undefined;
    task.assignedTo = undefined;
    task.startedAt = undefined;
    await this.saveTasks();
    return task;
  }

  getStatistics() {
    const tasks = Array.from(this.tasks.values());
    return {
      total: tasks.length,
      pending: tasks.filter(t => t.status === 'pending').length,
      inProgress: tasks.filter(t => t.status === 'in_progress').length,
      completed: tasks.filter(t => t.status === 'completed').length,
      blocked: tasks.filter(t => t.status === 'blocked').length,
      totalStoryPoints: tasks.reduce((sum, t) => sum + t.storyPoints, 0),
      completedStoryPoints: tasks.filter(t => t.status === 'completed').reduce((sum, t) => sum + t.storyPoints, 0),
      byTeam: this.getStatsByTeam(),
      bySprint: this.getStatsBySprint(),
    };
  }

  private getStatsByTeam() {
    const tasks = Array.from(this.tasks.values());
    const teams = new Map<number, any>();
    for (const task of tasks) {
      if (!teams.has(task.team)) {
        teams.set(task.team, { team: task.team, total: 0, pending: 0, inProgress: 0, completed: 0, blocked: 0, storyPoints: 0, completedStoryPoints: 0 });
      }
      const stats = teams.get(task.team)!;
      stats.total++;
      stats.storyPoints += task.storyPoints;
      if (task.status === 'pending') stats.pending++;
      if (task.status === 'in_progress') stats.inProgress++;
      if (task.status === 'completed') { stats.completed++; stats.completedStoryPoints += task.storyPoints; }
      if (task.status === 'blocked') stats.blocked++;
    }
    return Array.from(teams.values());
  }

  private getStatsBySprint() {
    const tasks = Array.from(this.tasks.values());
    const sprints = new Map<number, any>();
    for (const task of tasks) {
      if (!sprints.has(task.sprint)) {
        sprints.set(task.sprint, { sprint: task.sprint, total: 0, pending: 0, inProgress: 0, completed: 0, blocked: 0, storyPoints: 0, completedStoryPoints: 0 });
      }
      const stats = sprints.get(task.sprint)!;
      stats.total++;
      stats.storyPoints += task.storyPoints;
      if (task.status === 'pending') stats.pending++;
      if (task.status === 'in_progress') stats.inProgress++;
      if (task.status === 'completed') { stats.completed++; stats.completedStoryPoints += task.storyPoints; }
      if (task.status === 'blocked') stats.blocked++;
    }
    return Array.from(sprints.values());
  }

  async importTasks(tasks: Omit<Task, 'id' | 'status'>[]): Promise<Task[]> {
    const created: Task[] = [];
    for (const taskData of tasks) {
      const task = await this.createTask(taskData);
      created.push(task);
    }
    return created;
  }
}
