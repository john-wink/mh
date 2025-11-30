import { readFile, readdir } from 'fs/promises';
import { join } from 'path';
import matter from 'gray-matter';
import type { Task } from './agent.js';

interface ParsedTask {
  id: string;
  title: string;
  description: string;
  storyPoints: number;
  dependencies: string[];
  team: number;
  sprint: number;
  status: 'pending' | 'in_progress' | 'completed' | 'blocked';
}

export class TaskParser {
  constructor(private boardDirectory: string) {}

  async parseSprintTasks(sprint: number): Promise<Task[]> {
    const tasks: Task[] = [];

    // Read all team directories
    const teamDirs = await this.getTeamDirectories();

    for (const teamDir of teamDirs) {
      const teamTasks = await this.parseTeamSprintTasks(teamDir, sprint);
      tasks.push(...teamTasks);
    }

    return tasks;
  }

  private async getTeamDirectories(): Promise<string[]> {
    const entries = await readdir(this.boardDirectory, { withFileTypes: true });

    return entries
      .filter((entry) => entry.isDirectory() && entry.name.startsWith('team-'))
      .map((entry) => entry.name);
  }

  private async parseTeamSprintTasks(teamDir: string, sprint: number): Promise<Task[]> {
    const teamPath = join(this.boardDirectory, teamDir);
    const sprintPlanPath = join(teamPath, 'sprint-plan.md');

    try {
      const content = await readFile(sprintPlanPath, 'utf-8');
      return this.extractTasksFromMarkdown(content, sprint, teamDir);
    } catch (error) {
      console.warn(`Could not read sprint plan for ${teamDir}:`, error);
      return [];
    }
  }

  private extractTasksFromMarkdown(content: string, sprint: number, teamDir: string): Task[] {
    const tasks: Task[] = [];
    const teamNumber = this.extractTeamNumber(teamDir);

    // Parse frontmatter if exists
    const { content: markdownContent } = matter(content);

    // Find sprint section
    const sprintRegex = new RegExp(`### Sprint ${sprint}:.*?(?=### Sprint \\d+:|$)`, 'gs');
    const sprintMatch = markdownContent.match(sprintRegex);

    if (!sprintMatch) {
      return tasks;
    }

    const sprintSection = sprintMatch[0];

    // Extract tasks from sprint section
    // Format: **T1.1.1: Task Title** (3 SP)
    const taskRegex = /\*\*([A-Z0-9.]+):\s+([^*]+)\*\*\s+\((\d+)\s+SP\)/g;
    let match;

    while ((match = taskRegex.exec(sprintSection)) !== null) {
      const taskId = match[1];
      const title = match[2].trim();
      const storyPoints = parseInt(match[3]);

      // Extract task description (lines following the task header)
      const taskStartIndex = match.index + match[0].length;
      const nextTaskIndex = sprintSection.indexOf('**T', taskStartIndex);
      const taskEndIndex = nextTaskIndex !== -1 ? nextTaskIndex : sprintSection.length;

      const descriptionSection = sprintSection.substring(taskStartIndex, taskEndIndex);
      const description = this.extractDescription(descriptionSection);

      tasks.push({
        id: taskId,
        title,
        description,
        storyPoints,
        dependencies: this.extractDependencies(descriptionSection),
        team: teamNumber,
        sprint,
        status: 'pending',
      });
    }

    return tasks;
  }

  private extractTeamNumber(teamDir: string): number {
    const match = teamDir.match(/team-(\d+)/);
    return match ? parseInt(match[1]) : 0;
  }

  private extractDescription(section: string): string {
    const lines = section.split('\n').filter((line) => line.trim());

    // Remove checkbox items and deliverables
    const descriptionLines = lines.filter(
      (line) =>
        !line.trim().startsWith('- [ ]') &&
        !line.trim().startsWith('- [x]') &&
        !line.trim().startsWith('**Deliverable**')
    );

    return descriptionLines.join('\n').trim();
  }

  private extractDependencies(section: string): string[] {
    const dependencies: string[] = [];
    const lines = section.split('\n');

    for (const line of lines) {
      // Look for dependency mentions like "depends on T1.1.1" or "requires T2.3.4"
      const dependsMatch = line.match(/depends on ([A-Z0-9.]+)/i);
      const requiresMatch = line.match(/requires ([A-Z0-9.]+)/i);

      if (dependsMatch) {
        dependencies.push(dependsMatch[1]);
      }
      if (requiresMatch) {
        dependencies.push(requiresMatch[1]);
      }
    }

    return dependencies;
  }

  async getTaskById(taskId: string): Promise<Task | null> {
    // Extract sprint number from task ID (e.g., T1.2.3 -> sprint 2)
    const parts = taskId.split('.');
    if (parts.length < 2) {
      return null;
    }

    const sprint = parseInt(parts[1]);
    const allTasks = await this.parseSprintTasks(sprint);

    return allTasks.find((task) => task.id === taskId) || null;
  }
}
