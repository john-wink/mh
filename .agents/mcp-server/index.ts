#!/usr/bin/env node

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ListResourcesRequestSchema,
  ListToolsRequestSchema,
  ReadResourceRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';
import { TaskManager } from '../runtime/task-manager.js';
import { z } from 'zod';
import { join } from 'path';

// Schema definitions for tool parameters
const GetAssignedTaskSchema = z.object({
  agentId: z.string().optional(),
  sessionId: z.string().optional(),
});

const CompleteTaskSchema = z.object({
  taskId: z.string(),
  summary: z.string(),
  filesChanged: z.array(z.string()).optional(),
});

const ReportProgressSchema = z.object({
  taskId: z.string(),
  status: z.string(),
  message: z.string().optional(),
});

const GetTaskContextSchema = z.object({
  taskId: z.string(),
});

/**
 * Agent Orchestrator MCP Server
 * Provides task management tools and resources for Claude Code agents
 */
class AgentOrchestratorServer {
  private server: Server;
  private taskManager: TaskManager;
  private currentSession: Map<string, string> = new Map(); // sessionId -> taskId

  constructor() {
    // Initialize task manager
    const boardDirectory = process.env.BOARD_DIRECTORY || join(process.cwd(), '.board/tasks');
    this.taskManager = new TaskManager(boardDirectory);

    // Create MCP server
    this.server = new Server(
      {
        name: 'agent-orchestrator',
        version: '1.0.0',
      },
      {
        capabilities: {
          tools: {},
          resources: {},
        },
      }
    );

    this.setupToolHandlers();
    this.setupResourceHandlers();
    this.setupErrorHandling();
  }

  private setupToolHandlers(): void {
    // List available tools
    this.server.setRequestHandler(ListToolsRequestSchema, async () => ({
      tools: [
        {
          name: 'get_assigned_task',
          description: 'Get the task assigned to the current Claude Code session. Returns task details including title, description, requirements, and context.',
          inputSchema: {
            type: 'object',
            properties: {
              agentId: {
                type: 'string',
                description: 'Agent ID (optional, can be inferred from session)',
              },
              sessionId: {
                type: 'string',
                description: 'Session ID to track task assignment',
              },
            },
          },
        },
        {
          name: 'complete_task',
          description: 'Mark a task as completed and provide a summary of what was accomplished. This will update the task status and notify the orchestrator.',
          inputSchema: {
            type: 'object',
            properties: {
              taskId: {
                type: 'string',
                description: 'ID of the task to complete',
              },
              summary: {
                type: 'string',
                description: 'Summary of what was accomplished',
              },
              filesChanged: {
                type: 'array',
                items: { type: 'string' },
                description: 'List of files that were created or modified',
              },
            },
            required: ['taskId', 'summary'],
          },
        },
        {
          name: 'report_progress',
          description: 'Report progress on a task. Use this to update the dashboard in real-time with status updates.',
          inputSchema: {
            type: 'object',
            properties: {
              taskId: {
                type: 'string',
                description: 'ID of the task',
              },
              status: {
                type: 'string',
                description: 'Current status (e.g., "analyzing", "implementing", "testing")',
              },
              message: {
                type: 'string',
                description: 'Progress message',
              },
            },
            required: ['taskId', 'status'],
          },
        },
        {
          name: 'get_task_context',
          description: 'Get full context for a task including epic information, dependencies, and related tasks.',
          inputSchema: {
            type: 'object',
            properties: {
              taskId: {
                type: 'string',
                description: 'ID of the task',
              },
            },
            required: ['taskId'],
          },
        },
      ],
    }));

    // Handle tool calls
    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      try {
        switch (name) {
          case 'get_assigned_task':
            return await this.handleGetAssignedTask(args);

          case 'complete_task':
            return await this.handleCompleteTask(args);

          case 'report_progress':
            return await this.handleReportProgress(args);

          case 'get_task_context':
            return await this.handleGetTaskContext(args);

          default:
            throw new Error(`Unknown tool: ${name}`);
        }
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : 'Unknown error';
        return {
          content: [
            {
              type: 'text',
              text: `Error: ${errorMessage}`,
            },
          ],
          isError: true,
        };
      }
    });
  }

  private setupResourceHandlers(): void {
    // List available resources
    this.server.setRequestHandler(ListResourcesRequestSchema, async () => {
      const tasks = this.taskManager.getTasks({ status: 'pending' });

      return {
        resources: [
          {
            uri: 'task://current',
            name: 'Current Task',
            description: 'The task currently being worked on',
            mimeType: 'application/json',
          },
          ...tasks.map((task) => ({
            uri: `task://${task.id}`,
            name: task.title,
            description: task.description,
            mimeType: 'application/json',
          })),
        ],
      };
    });

    // Handle resource reads
    this.server.setRequestHandler(ReadResourceRequestSchema, async (request) => {
      const { uri } = request.params;

      if (uri === 'task://current') {
        // Return current session's task
        // For now, just return the first pending task
        const tasks = this.taskManager.getTasks({ status: 'pending' });
        const task = tasks[0];

        if (!task) {
          return {
            contents: [
              {
                uri,
                mimeType: 'application/json',
                text: JSON.stringify({ error: 'No pending tasks' }, null, 2),
              },
            ],
          };
        }

        return {
          contents: [
            {
              uri,
              mimeType: 'application/json',
              text: JSON.stringify(task, null, 2),
            },
          ],
        };
      }

      // Handle task://{taskId}
      const taskIdMatch = uri.match(/^task:\/\/(.+)$/);
      if (taskIdMatch) {
        const taskId = taskIdMatch[1];
        const task = this.taskManager.getTask(taskId);

        if (!task) {
          return {
            contents: [
              {
                uri,
                mimeType: 'application/json',
                text: JSON.stringify({ error: `Task ${taskId} not found` }, null, 2),
              },
            ],
          };
        }

        return {
          contents: [
            {
              uri,
              mimeType: 'application/json',
              text: JSON.stringify(task, null, 2),
            },
          ],
        };
      }

      throw new Error(`Unknown resource: ${uri}`);
    });
  }

  private setupErrorHandling(): void {
    this.server.onerror = (error) => {
      console.error('[MCP Server Error]', error);
    };

    process.on('SIGINT', async () => {
      await this.server.close();
      process.exit(0);
    });
  }

  // Tool handler implementations
  private async handleGetAssignedTask(args: unknown) {
    const params = GetAssignedTaskSchema.parse(args);

    // For now, return the first pending task
    // In the future, we can use sessionId to track which task is assigned to which session
    const tasks = this.taskManager.getTasks({ status: 'pending' });
    const task = tasks[0];

    if (!task) {
      return {
        content: [
          {
            type: 'text',
            text: 'No pending tasks available.',
          },
        ],
      };
    }

    // Track session -> task mapping
    if (params.sessionId) {
      this.currentSession.set(params.sessionId, task.id);
    }

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify(task, null, 2),
        },
      ],
    };
  }

  private async handleCompleteTask(args: unknown) {
    const params = CompleteTaskSchema.parse(args);

    try {
      await this.taskManager.completeTask(params.taskId);

      return {
        content: [
          {
            type: 'text',
            text: `Task ${params.taskId} marked as completed.\n\nSummary: ${params.summary}\n\nFiles changed: ${params.filesChanged?.join(', ') || 'none specified'}`,
          },
        ],
      };
    } catch (error) {
      throw new Error(`Failed to complete task: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  private async handleReportProgress(args: unknown) {
    const params = ReportProgressSchema.parse(args);

    console.log(`[Progress] Task ${params.taskId}: ${params.status} - ${params.message || ''}`);

    return {
      content: [
        {
          type: 'text',
          text: `Progress reported for task ${params.taskId}`,
        },
      ],
    };
  }

  private async handleGetTaskContext(args: unknown) {
    const params = GetTaskContextSchema.parse(args);

    const task = this.taskManager.getTask(params.taskId);

    if (!task) {
      throw new Error(`Task ${params.taskId} not found`);
    }

    // Get epic context if task belongs to an epic
    let epicContext = null;
    if (task.epicId) {
      // Epic loading would go here
      // For now, just note the epic ID
      epicContext = { epicId: task.epicId };
    }

    const context = {
      task,
      epic: epicContext,
      dependencies: task.dependencies || [],
      relatedTasks: [], // Could load related tasks here
    };

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify(context, null, 2),
        },
      ],
    };
  }

  async run(): Promise<void> {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);

    console.error('Agent Orchestrator MCP Server running on stdio');
  }
}

// Start the server
const server = new AgentOrchestratorServer();
server.run().catch(console.error);
