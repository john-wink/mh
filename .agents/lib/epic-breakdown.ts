import Anthropic from '@anthropic-ai/sdk';
import type { Epic, Task } from './agent.js';
import { TaskManager } from '../runtime/task-manager.js';
import chalk from 'chalk';

export class EpicBreakdownService {
  private client: Anthropic;
  private taskManager: TaskManager;

  constructor(apiKey: string, taskManager: TaskManager) {
    this.client = new Anthropic({ apiKey });
    this.taskManager = taskManager;
  }

  async breakdownEpic(epic: Epic): Promise<Task[]> {
    console.log(chalk.blue(`\n🔨 Breaking down epic ${epic.id}: ${epic.title}...`));

    const prompt = this.buildBreakdownPrompt(epic);

    try {
      const response = await this.client.messages.create({
        model: 'claude-sonnet-4-20250514',
        max_tokens: 4096,
        temperature: 0.3,
        messages: [{
          role: 'user',
          content: prompt
        }]
      });

      const content = response.content[0];
      if (content.type !== 'text') {
        throw new Error('Unexpected response type');
      }

      const tasks = this.parseTasksFromResponse(content.text, epic);

      console.log(chalk.green(`✓ Created ${tasks.length} tasks from epic ${epic.id}`));

      // Create tasks in TaskManager
      const createdTasks: Task[] = [];
      for (const taskData of tasks) {
        const task = await this.taskManager.createTask(taskData);
        await this.taskManager.addTaskToEpic(epic.id, task.id);
        createdTasks.push(task);
      }

      // Start the epic
      await this.taskManager.startEpic(epic.id);

      return createdTasks;
    } catch (error) {
      console.error(chalk.red(`❌ Failed to breakdown epic ${epic.id}:`), error);
      throw error;
    }
  }

  private buildBreakdownPrompt(epic: Epic): string {
    return `You are an expert software architect tasked with breaking down a large feature (Epic) into smaller, manageable tasks.

EPIC INFORMATION:
- Title: ${epic.title}
- Description: ${epic.description}
- Estimated Story Points: ${epic.estimatedStoryPoints}
- Team: ${epic.team}
- Sprint: ${epic.sprint}

INSTRUCTIONS:
1. Break down this epic into 3-8 concrete, actionable tasks
2. Each task should be independently completable
3. Assign realistic story points (1-8) to each task
4. The total story points should approximately match the epic's estimate (${epic.estimatedStoryPoints})
5. Identify dependencies between tasks
6. Tasks should follow software development best practices

TASK CATEGORIES TO CONSIDER:
- Database/Model setup
- API/Backend implementation
- Frontend/UI implementation
- Testing (unit, integration, E2E)
- Documentation
- DevOps/Deployment

OUTPUT FORMAT (JSON only, no markdown):
[
  {
    "title": "Task title",
    "description": "Detailed description of what needs to be done",
    "storyPoints": 3,
    "dependencies": []
  },
  {
    "title": "Another task",
    "description": "Description",
    "storyPoints": 5,
    "dependencies": ["TASK-001"]
  }
]

IMPORTANT:
- Output ONLY valid JSON, no markdown code blocks
- Be specific and actionable
- Consider the full development lifecycle
- Dependencies should reference task indices (0, 1, 2, etc.)`;
  }

  private parseTasksFromResponse(response: string, epic: Epic): Omit<Task, 'id' | 'status'>[] {
    // Remove markdown code blocks if present
    let jsonStr = response.trim();
    if (jsonStr.startsWith('```')) {
      jsonStr = jsonStr.replace(/```json\n?/g, '').replace(/```\n?/g, '').trim();
    }

    try {
      const parsed = JSON.parse(jsonStr);

      if (!Array.isArray(parsed)) {
        throw new Error('Expected an array of tasks');
      }

      // Convert to Task objects
      const tasks: Omit<Task, 'id' | 'status'>[] = [];
      const taskIdMapping: Map<number, string> = new Map();

      parsed.forEach((item, index) => {
        // Temporarily assign placeholder IDs to resolve dependencies later
        const placeholderId = `TASK-TEMP-${index}`;
        taskIdMapping.set(index, placeholderId);

        const task: Omit<Task, 'id' | 'status'> = {
          title: item.title,
          description: item.description,
          storyPoints: item.storyPoints || 3,
          dependencies: [], // Will be resolved after all tasks are created
          team: epic.team,
          sprint: epic.sprint,
          epicId: epic.id,
        };

        tasks.push(task);
      });

      // Resolve dependencies after all tasks are known
      // Note: Dependencies will be resolved in AgentManager after task creation

      return tasks;
    } catch (error) {
      console.error(chalk.red('Failed to parse AI response:'), error);
      console.log('Response:', response);

      // Fallback: Create a single task from the epic
      return [{
        title: epic.title,
        description: epic.description,
        storyPoints: epic.estimatedStoryPoints,
        dependencies: [],
        team: epic.team,
        sprint: epic.sprint,
        epicId: epic.id,
      }];
    }
  }
}
