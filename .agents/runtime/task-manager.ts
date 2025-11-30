import { readFile, writeFile, mkdir } from 'fs/promises';
import { join } from 'path';
import { existsSync } from 'fs';
import type { Task, Epic } from '../lib/agent.js';

export class TaskManager {
  private tasks: Map<string, Task> = new Map();
  private epics: Map<string, Epic> = new Map();
  private tasksDirectory: string;
  private nextTaskId: number = 1;
  private nextEpicId: number = 1;

  constructor(tasksDirectory: string) {
    this.tasksDirectory = tasksDirectory;
    this.ensureTasksDirectory();
    this.loadTasks();
    this.loadEpics();
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

  // Epic Management Methods
  private async loadEpics(): Promise<void> {
    try {
      const epicsFile = join(this.tasksDirectory, 'epics.json');
      if (existsSync(epicsFile)) {
        const data = await readFile(epicsFile, 'utf-8');
        const epicsArray = JSON.parse(data);
        for (const epic of epicsArray) {
          if (epic.createdAt) epic.createdAt = new Date(epic.createdAt);
          if (epic.startedAt) epic.startedAt = new Date(epic.startedAt);
          if (epic.completedAt) epic.completedAt = new Date(epic.completedAt);
          this.epics.set(epic.id, epic);
        }
        const epicNumbers = Array.from(this.epics.keys())
          .map(id => parseInt(id.replace('EPIC-', '')))
          .filter(n => !isNaN(n));
        if (epicNumbers.length > 0) {
          this.nextEpicId = Math.max(...epicNumbers) + 1;
        }
        console.log(`✓ Loaded ${this.epics.size} epics`);
      }
    } catch (error) {
      console.log('⚠️  Could not load epics, starting fresh');
      this.epics = new Map();
    }
  }

  private async saveEpics(): Promise<void> {
    const epicsFile = join(this.tasksDirectory, 'epics.json');
    const epicsArray = Array.from(this.epics.values());
    await writeFile(epicsFile, JSON.stringify(epicsArray, null, 2), 'utf-8');
  }

  async createEpic(epicData: Omit<Epic, 'id' | 'status' | 'createdAt' | 'taskIds'>): Promise<Epic> {
    const epic: Epic = {
      id: `EPIC-${String(this.nextEpicId).padStart(3, '0')}`,
      status: 'pending',
      createdAt: new Date(),
      taskIds: [],
      ...epicData,
    };
    this.nextEpicId++;
    this.epics.set(epic.id, epic);
    await this.saveEpics();
    console.log(`✓ Created epic ${epic.id}: ${epic.title}`);
    return epic;
  }

  getEpic(epicId: string): Epic | undefined {
    return this.epics.get(epicId);
  }

  getEpics(filter?: any): Epic[] {
    let epics = Array.from(this.epics.values());
    if (filter) {
      if (filter.status) epics = epics.filter(e => e.status === filter.status);
      if (filter.team !== undefined) epics = epics.filter(e => e.team === filter.team);
      if (filter.sprint !== undefined) epics = epics.filter(e => e.sprint === filter.sprint);
    }
    return epics;
  }

  async updateEpic(epicId: string, updates: Partial<Epic>): Promise<Epic | null> {
    const epic = this.epics.get(epicId);
    if (!epic) return null;
    Object.assign(epic, updates);
    await this.saveEpics();
    return epic;
  }

  async deleteEpic(epicId: string): Promise<boolean> {
    const deleted = this.epics.delete(epicId);
    if (deleted) await this.saveEpics();
    return deleted;
  }

  async startEpic(epicId: string): Promise<Epic | null> {
    const epic = this.epics.get(epicId);
    if (!epic) return null;
    epic.status = 'in_progress';
    epic.startedAt = new Date();
    await this.saveEpics();
    return epic;
  }

  async completeEpic(epicId: string): Promise<Epic | null> {
    const epic = this.epics.get(epicId);
    if (!epic) return null;
    epic.status = 'completed';
    epic.completedAt = new Date();
    await this.saveEpics();
    return epic;
  }

  async addTaskToEpic(epicId: string, taskId: string): Promise<void> {
    const epic = this.epics.get(epicId);
    if (!epic) throw new Error(`Epic ${epicId} not found`);
    if (!epic.taskIds.includes(taskId)) {
      epic.taskIds.push(taskId);
      await this.saveEpics();
    }
  }

  getEpicProgress(epicId: string): { total: number; completed: number; inProgress: number; pending: number; blocked: number } {
    const epic = this.epics.get(epicId);
    if (!epic) return { total: 0, completed: 0, inProgress: 0, pending: 0, blocked: 0 };

    const tasks = epic.taskIds.map(id => this.tasks.get(id)).filter(t => t !== undefined) as Task[];

    return {
      total: tasks.length,
      completed: tasks.filter(t => t.status === 'completed').length,
      inProgress: tasks.filter(t => t.status === 'in_progress').length,
      pending: tasks.filter(t => t.status === 'pending').length,
      blocked: tasks.filter(t => t.status === 'blocked').length,
    };
  }
}
