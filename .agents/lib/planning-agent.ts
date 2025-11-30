import Anthropic from '@anthropic-ai/sdk';
import { readFile } from 'fs/promises';
import { join } from 'path';
import { existsSync } from 'fs';
import type { Epic, Task } from './agent.js';
import type { Config } from './config.js';
import { TaskManager } from '../runtime/task-manager.js';
import chalk from 'chalk';

export interface TaskSuggestion {
  type: 'epic' | 'task';
  title: string;
  description: string;
  reasoning: string;
  estimatedStoryPoints?: number;
  team: number;
  sprint: number;
  priority: 'high' | 'medium' | 'low';
  dependencies?: string[];
  epicId?: string;
}

export interface PlanningAnalysis {
  projectOverview: string;
  completedWork: string;
  pendingWork: string;
  teamCapabilities: string;
  suggestions: TaskSuggestion[];
  nextSteps: string;
}

export class PlanningAgent {
  private client: Anthropic;
  private config: Config;
  private taskManager: TaskManager;
  private projectRoot: string;

  constructor(apiKey: string, config: Config, taskManager: TaskManager) {
    this.client = new Anthropic({ apiKey });
    this.config = config;
    this.taskManager = taskManager;
    this.projectRoot = config.orchestrator.projectRoot;
  }

  async analyzeProjectAndSuggest(sprint: number): Promise<PlanningAnalysis> {
    console.log(chalk.blue(`\n🧠 Planning Agent analyzing project for Sprint ${sprint}...`));

    // Gather project context
    const projectContext = await this.gatherProjectContext();

    // Build analysis prompt
    const prompt = this.buildAnalysisPrompt(projectContext, sprint);

    try {
      const response = await this.client.messages.create({
        model: 'claude-sonnet-4-20250514',
        max_tokens: 8000,
        temperature: 0.4,
        messages: [{
          role: 'user',
          content: prompt
        }]
      });

      const content = response.content[0];
      if (content.type !== 'text') {
        throw new Error('Unexpected response type');
      }

      const analysis = this.parseAnalysisResponse(content.text, sprint);

      console.log(chalk.green(`✓ Planning Agent generated ${analysis.suggestions.length} suggestions`));

      return analysis;
    } catch (error) {
      console.error(chalk.red('❌ Planning Agent failed:'), error);
      throw error;
    }
  }

  private async gatherProjectContext(): Promise<any> {
    const context: any = {
      readme: '',
      teams: [],
      tasks: {
        pending: [],
        inProgress: [],
        completed: [],
        blocked: []
      },
      epics: {
        pending: [],
        inProgress: [],
        completed: []
      },
      statistics: null
    };

    // Read project README
    const readmePath = join(this.projectRoot, 'README.md');
    if (existsSync(readmePath)) {
      try {
        context.readme = await readFile(readmePath, 'utf-8');
      } catch (error) {
        console.warn('Could not read README.md');
      }
    }

    // Get team information
    context.teams = this.config.agents.map(agent => ({
      id: agent.id,
      name: agent.name,
      team: agent.team,
      role: agent.role,
      expertise: agent.expertise,
      capabilities: agent.capabilities,
      sprintCapacity: agent.sprintCapacity
    }));

    // Get tasks
    const allTasks = this.taskManager.getTasks();
    context.tasks.pending = allTasks.filter(t => t.status === 'pending');
    context.tasks.inProgress = allTasks.filter(t => t.status === 'in_progress');
    context.tasks.completed = allTasks.filter(t => t.status === 'completed');
    context.tasks.blocked = allTasks.filter(t => t.status === 'blocked');

    // Get epics
    const allEpics = this.taskManager.getEpics();
    context.epics.pending = allEpics.filter(e => e.status === 'pending');
    context.epics.inProgress = allEpics.filter(e => e.status === 'in_progress');
    context.epics.completed = allEpics.filter(e => e.status === 'completed');

    // Get statistics
    context.statistics = this.taskManager.getStatistics();

    return context;
  }

  private buildAnalysisPrompt(context: any, sprint: number): string {
    return `You are an expert Product Owner and Technical Lead for a software development project. Your role is to analyze the project state and suggest the most valuable next tasks and epics.

PROJECT README:
${context.readme || 'No README available'}

TEAM STRUCTURE (${this.config.agents.length} agents across ${Math.max(...this.config.agents.map(a => a.team || 0)) + 1} teams):
${context.teams.map((t: any) => `- Team ${t.team}: ${t.name} (${t.role}) - Expertise: ${t.expertise.join(', ')}`).join('\n')}

CURRENT SPRINT: ${sprint}

WORK COMPLETED:
- Total Completed Tasks: ${context.tasks.completed.length}
- Completed Story Points: ${context.statistics.completedStoryPoints}
- Completed Epics: ${context.epics.completed.length}
${context.tasks.completed.slice(0, 10).map((t: Task) => `  • ${t.id}: ${t.title} (${t.storyPoints} SP)`).join('\n')}

WORK IN PROGRESS:
- Active Tasks: ${context.tasks.inProgress.length}
- Active Epics: ${context.epics.inProgress.length}
${context.tasks.inProgress.map((t: Task) => `  • ${t.id}: ${t.title} - Assigned to ${t.assignedTo || 'Unassigned'}`).join('\n')}

PENDING WORK:
- Pending Tasks: ${context.tasks.pending.length}
- Pending Epics: ${context.epics.pending.length}
${context.tasks.pending.slice(0, 5).map((t: Task) => `  • ${t.id}: ${t.title} (${t.storyPoints} SP)`).join('\n')}

BLOCKED WORK:
${context.tasks.blocked.length > 0 ? context.tasks.blocked.map((t: Task) => `  • ${t.id}: ${t.title} - Reason: ${t.blockedReason}`).join('\n') : '  None'}

YOUR TASK:
Analyze the project state and suggest 3-7 high-value next steps (Epics or Tasks) for Sprint ${sprint}.

CONSIDERATIONS:
1. What would provide the most value to users?
2. What are logical next steps based on completed work?
3. What dependencies need to be addressed?
4. What matches each team's expertise?
5. What is a realistic sprint capacity?
6. Are there any blocked tasks that need attention?

OUTPUT FORMAT (JSON only):
{
  "projectOverview": "Brief summary of project state",
  "completedWork": "Summary of what's been accomplished",
  "pendingWork": "Summary of pending work",
  "teamCapabilities": "Assessment of team skills and capacity",
  "suggestions": [
    {
      "type": "epic",
      "title": "Feature Name",
      "description": "Detailed description of what needs to be built and why",
      "reasoning": "Why this is valuable and should be prioritized now",
      "estimatedStoryPoints": 13,
      "team": 0,
      "sprint": ${sprint},
      "priority": "high"
    },
    {
      "type": "task",
      "title": "Specific Task",
      "description": "Concrete action item",
      "reasoning": "Why this task is needed",
      "estimatedStoryPoints": 5,
      "team": 1,
      "sprint": ${sprint},
      "priority": "medium",
      "dependencies": ["TASK-001"]
    }
  ],
  "nextSteps": "Recommended action plan"
}

IMPORTANT:
- Output ONLY valid JSON, no markdown code blocks
- Suggest 3-7 items (mix of epics and tasks)
- Be specific and actionable
- Consider team expertise and capacity
- Prioritize based on value and dependencies`;
  }

  private parseAnalysisResponse(response: string, sprint: number): PlanningAnalysis {
    // Remove markdown code blocks if present
    let jsonStr = response.trim();
    if (jsonStr.startsWith('```')) {
      jsonStr = jsonStr.replace(/```json\n?/g, '').replace(/```\n?/g, '').trim();
    }

    try {
      const parsed = JSON.parse(jsonStr);

      return {
        projectOverview: parsed.projectOverview || 'No overview provided',
        completedWork: parsed.completedWork || 'No summary available',
        pendingWork: parsed.pendingWork || 'No pending work summary',
        teamCapabilities: parsed.teamCapabilities || 'No team assessment',
        suggestions: parsed.suggestions || [],
        nextSteps: parsed.nextSteps || 'No next steps provided'
      };
    } catch (error) {
      console.error(chalk.red('Failed to parse Planning Agent response:'), error);
      console.log('Response:', response);

      // Return empty analysis
      return {
        projectOverview: 'Analysis failed',
        completedWork: '',
        pendingWork: '',
        teamCapabilities: '',
        suggestions: [],
        nextSteps: 'Please try again'
      };
    }
  }

  async createSuggestedItem(suggestion: TaskSuggestion): Promise<Epic | Task> {
    if (suggestion.type === 'epic') {
      const epic = await this.taskManager.createEpic({
        title: suggestion.title,
        description: suggestion.description,
        estimatedStoryPoints: suggestion.estimatedStoryPoints || 8,
        team: suggestion.team,
        sprint: suggestion.sprint
      });
      return epic;
    } else {
      const task = await this.taskManager.createTask({
        title: suggestion.title,
        description: suggestion.description,
        storyPoints: suggestion.estimatedStoryPoints || 3,
        dependencies: suggestion.dependencies || [],
        team: suggestion.team,
        sprint: suggestion.sprint,
        epicId: suggestion.epicId
      });
      return task;
    }
  }
}
