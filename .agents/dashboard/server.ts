import express from 'express';
import { createServer } from 'http';
import { WebSocketServer } from 'ws';
import { loadConfig } from '../lib/config.js';
import { AgentManager } from '../runtime/agent-manager.js';
import { Monitor } from '../runtime/monitoring.js';
import { EpicBreakdownService } from '../lib/epic-breakdown.js';
import { PlanningAgent } from '../lib/planning-agent.js';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import { config as dotenvConfig } from 'dotenv';

dotenvConfig();

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const app = express();
const server = createServer(app);
const wss = new WebSocketServer({ server });

let config: Awaited<ReturnType<typeof loadConfig>>;
let agentManager: AgentManager;
let monitor: Monitor;
let epicBreakdownService: EpicBreakdownService;
let planningAgent: PlanningAgent;

// Initialize
async function initialize() {
  config = await loadConfig();
  agentManager = new AgentManager(config);
  await agentManager.initialize(); // Initialize worktrees
  monitor = new Monitor(config);

  const apiKey = process.env.ANTHROPIC_API_KEY;
  if (!apiKey) {
    throw new Error('ANTHROPIC_API_KEY environment variable is required');
  }
  epicBreakdownService = new EpicBreakdownService(apiKey, agentManager.getTaskManager());
  planningAgent = new PlanningAgent(apiKey, config, agentManager.getTaskManager());
}

// Middleware
app.use(express.json());
app.use(express.static(join(__dirname, 'public')));

// API endpoints
app.get('/api/status', async (req, res) => {
  try {
    const status = agentManager.getOverallStatus();

    // Group by teams
    const teams = new Map<number, any>();
    for (const agent of config.agents) {
      if (agent.team !== null) {
        if (!teams.has(agent.team)) {
          teams.set(agent.team, {
            team: agent.team,
            agents: [],
            currentLoad: 0,
            completedStoryPoints: 0,
          });
        }
      }
    }

    // Get status for each team
    const teamStatuses = [];
    for (const [teamNum, _] of teams) {
      const teamStatus = agentManager.getTeamStatus(teamNum);
      teamStatuses.push(teamStatus);
    }

    res.json({
      overall: {
        totalAgents: status.totalAgents,
        activeAgents: status.activeAgents,
        totalCurrentLoad: status.totalCurrentLoad,
        totalCompletedStoryPoints: status.totalCompletedStoryPoints,
      },
      teams: teamStatuses,
      agents: status.agentStatuses,
    });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.get('/api/config', async (req, res) => {
  try {
    res.json({
      currentSprint: config.orchestrator.currentSprint,
      maxConcurrentAgents: config.settings.maxConcurrentAgents,
      autoAssignTasks: config.settings.autoAssignTasks,
      requireHumanApproval: config.settings.requireHumanApproval,
      codeReview: config.settings.codeReview,
      testing: config.settings.testing,
    });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.get('/api/agents', async (req, res) => {
  try {
    const agents = config.agents.map((agent) => ({
      id: agent.id,
      name: agent.name,
      team: agent.team,
      role: agent.role,
      expertise: agent.expertise,
      capabilities: agent.capabilities,
      maxConcurrentTasks: agent.maxConcurrentTasks,
      sprintCapacity: agent.sprintCapacity,
      modelPreference: agent.modelPreference,
    }));

    res.json({ agents });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.get('/api/costs', async (req, res) => {
  try {
    const costTracker = agentManager.getCostTracker();

    if (!costTracker) {
      return res.json({
        enabled: false,
        message: 'Cost tracking is not enabled',
      });
    }

    const now = new Date();
    const startOfDay = new Date(now);
    startOfDay.setHours(0, 0, 0, 0);

    const startOfWeek = new Date(now);
    startOfWeek.setDate(now.getDate() - now.getDay());
    startOfWeek.setHours(0, 0, 0, 0);

    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);

    const dailySummary = costTracker.getSummary(startOfDay);
    const weeklySummary = costTracker.getSummary(startOfWeek);
    const monthlySummary = costTracker.getSummary(startOfMonth);
    const allTimeSummary = costTracker.getSummary();

    const limits = config.costs?.limits || {};

    res.json({
      enabled: true,
      daily: {
        cost: dailySummary.totalCost,
        limit: limits.daily,
        percentage: limits.daily ? (dailySummary.totalCost / limits.daily) * 100 : 0,
        tokens: dailySummary.totalTokens,
        cacheEfficiency: dailySummary.cacheEfficiency,
        savings: dailySummary.cacheSavings,
        requests: dailySummary.requestCount,
      },
      weekly: {
        cost: weeklySummary.totalCost,
        limit: limits.weekly,
        percentage: limits.weekly ? (weeklySummary.totalCost / limits.weekly) * 100 : 0,
        savings: weeklySummary.cacheSavings,
      },
      monthly: {
        cost: monthlySummary.totalCost,
        limit: limits.monthly,
        percentage: limits.monthly ? (monthlySummary.totalCost / limits.monthly) * 100 : 0,
        savings: monthlySummary.cacheSavings,
      },
      allTime: {
        cost: allTimeSummary.totalCost,
        limit: limits.total,
        percentage: limits.total ? (allTimeSummary.totalCost / limits.total) * 100 : 0,
        savings: allTimeSummary.cacheSavings,
        requests: allTimeSummary.requestCount,
        averageCostPerRequest: allTimeSummary.averageCostPerRequest,
      },
      limits,
    });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

// Task Management Endpoints
app.get('/api/tasks', async (req, res) => {
  try {
    const taskManager = agentManager.getTaskManager();
    const tasks = taskManager.getTasks();
    const statistics = taskManager.getStatistics();
    res.json({ tasks, statistics });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.post('/api/tasks', async (req, res) => {
  try {
    const taskManager = agentManager.getTaskManager();
    const { title, description, storyPoints, dependencies, team, sprint } = req.body;
    if (!title || !description || storyPoints === undefined || team === undefined || sprint === undefined) {
      return res.status(400).json({ error: 'Missing required fields' });
    }
    const task = await taskManager.createTask({ title, description, storyPoints, dependencies: dependencies || [], team, sprint });
    res.json({ task });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.post('/api/tasks/:id/assign', async (req, res) => {
  try {
    const result = await agentManager.assignTaskFromManager(req.params.id);
    if (!result.success) return res.status(400).json({ error: result.message });
    res.json({ message: result.message, agent: result.agent?.name });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.post('/api/tasks/:id/execute', async (req, res) => {
  try {
    const result = await agentManager.executeTaskFromManager(req.params.id);
    if (!result.success) return res.status(400).json({ error: result.output });
    res.json({ output: result.output, cost: result.cost });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.delete('/api/tasks/:id', async (req, res) => {
  try {
    const taskManager = agentManager.getTaskManager();
    const deleted = await taskManager.deleteTask(req.params.id);
    if (!deleted) return res.status(404).json({ error: 'Task not found' });
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.post('/api/tasks/auto-assign', async (req, res) => {
  try {
    const result = await agentManager.autoAssignTasks();
    res.json(result);
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

// Epic Management Endpoints
app.get('/api/epics', async (req, res) => {
  try {
    const taskManager = agentManager.getTaskManager();
    const epics = taskManager.getEpics();

    // Add progress info for each epic
    const epicsWithProgress = epics.map(epic => ({
      ...epic,
      progress: taskManager.getEpicProgress(epic.id),
    }));

    res.json({ epics: epicsWithProgress });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.post('/api/epics', async (req, res) => {
  try {
    const taskManager = agentManager.getTaskManager();
    const { title, description, estimatedStoryPoints, team, sprint } = req.body;

    if (!title || !description || estimatedStoryPoints === undefined || team === undefined || sprint === undefined) {
      return res.status(400).json({ error: 'Missing required fields' });
    }

    const epic = await taskManager.createEpic({
      title,
      description,
      estimatedStoryPoints,
      team,
      sprint,
    });

    res.json({ epic });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.post('/api/epics/:id/breakdown', async (req, res) => {
  try {
    const taskManager = agentManager.getTaskManager();
    const epic = taskManager.getEpic(req.params.id);

    if (!epic) {
      return res.status(404).json({ error: 'Epic not found' });
    }

    if (epic.status !== 'pending') {
      return res.status(400).json({ error: 'Epic has already been broken down' });
    }

    const tasks = await epicBreakdownService.breakdownEpic(epic);

    res.json({ epic, tasks });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.delete('/api/epics/:id', async (req, res) => {
  try {
    const taskManager = agentManager.getTaskManager();
    const deleted = await taskManager.deleteEpic(req.params.id);

    if (!deleted) {
      return res.status(404).json({ error: 'Epic not found' });
    }

    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.post('/api/epics/:id/complete', async (req, res) => {
  try {
    const taskManager = agentManager.getTaskManager();
    const epic = await taskManager.completeEpic(req.params.id);

    if (!epic) {
      return res.status(404).json({ error: 'Epic not found' });
    }

    res.json({ epic });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

// Planning Agent Endpoints
app.post('/api/planning/suggest', async (req, res) => {
  try {
    const { sprint } = req.body;
    const currentSprint = sprint || config.orchestrator.currentSprint;

    const analysis = await planningAgent.analyzeProjectAndSuggest(currentSprint);

    res.json({ analysis });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.post('/api/planning/create-suggestion', async (req, res) => {
  try {
    const { suggestion } = req.body;

    if (!suggestion) {
      return res.status(400).json({ error: 'Missing suggestion data' });
    }

    const created = await planningAgent.createSuggestedItem(suggestion);

    res.json({ created });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

// Git Worktree Endpoints
app.get('/api/worktrees', async (req, res) => {
  try {
    const worktreeManager = agentManager.getWorktreeManager();

    if (!worktreeManager) {
      return res.json({
        enabled: false,
        message: 'Git worktrees are not enabled',
        worktrees: [],
      });
    }

    const statuses = await worktreeManager.getAllWorktreeStatuses();
    const agents = config.agents;

    // Combine worktree status with agent information
    const worktreesWithAgents = Array.from(statuses.entries()).map(([agentId, status]) => {
      const agent = agents.find((a) => a.id === agentId);
      const agentStatus = agentManager.getAgent(agentId)?.getStatus();

      return {
        agentId,
        agentName: agent?.name || agentId,
        team: agent?.team,
        ...status,
        currentTasks: agentStatus?.currentTasks || [],
        isWorking: (agentStatus?.currentTasks || []).length > 0,
      };
    });

    res.json({
      enabled: true,
      worktrees: worktreesWithAgents,
    });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.get('/api/worktrees/:agentId', async (req, res) => {
  try {
    const worktreeManager = agentManager.getWorktreeManager();

    if (!worktreeManager) {
      return res.status(404).json({ error: 'Git worktrees are not enabled' });
    }

    const status = await worktreeManager.getWorktreeStatus(req.params.agentId);
    const agent = config.agents.find((a) => a.id === req.params.agentId);
    const agentStatus = agentManager.getAgent(req.params.agentId)?.getStatus();

    res.json({
      agentId: req.params.agentId,
      agentName: agent?.name || req.params.agentId,
      team: agent?.team,
      ...status,
      currentTasks: agentStatus?.currentTasks || [],
      isWorking: (agentStatus?.currentTasks || []).length > 0,
    });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.post('/api/worktrees/:agentId/sync', async (req, res) => {
  try {
    const worktreeManager = agentManager.getWorktreeManager();

    if (!worktreeManager) {
      return res.status(404).json({ error: 'Git worktrees are not enabled' });
    }

    const { targetBranch = 'main' } = req.body;
    await worktreeManager.syncWorktreeWithMain(req.params.agentId, targetBranch);

    res.json({ success: true, message: `Synced ${req.params.agentId} with ${targetBranch}` });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

app.post('/api/worktrees/sync-all', async (req, res) => {
  try {
    const worktreeManager = agentManager.getWorktreeManager();

    if (!worktreeManager) {
      return res.status(404).json({ error: 'Git worktrees are not enabled' });
    }

    const { targetBranch = 'main' } = req.body;
    await worktreeManager.syncAllWorktrees(targetBranch);

    res.json({ success: true, message: `Synced all worktrees with ${targetBranch}` });
  } catch (error) {
    res.status(500).json({ error: error instanceof Error ? error.message : 'Unknown error' });
  }
});

// WebSocket connection for real-time updates
wss.on('connection', (ws) => {
  console.log('Client connected to dashboard');

  // Send initial status
  const sendStatus = async () => {
    try {
      const status = agentManager.getOverallStatus();
      ws.send(JSON.stringify({ type: 'status', data: status }));
    } catch (error) {
      console.error('Error sending status:', error);
    }
  };

  sendStatus();

  // Send updates every 5 seconds
  const interval = setInterval(sendStatus, 5000);

  ws.on('close', () => {
    console.log('Client disconnected from dashboard');
    clearInterval(interval);
  });

  ws.on('error', (error) => {
    console.error('WebSocket error:', error);
    clearInterval(interval);
  });
});

// Start server
const PORT = config?.monitoring?.dashboardPort || 3000;

initialize()
  .then(() => {
    server.listen(PORT, () => {
      console.log(`\n🎯 Dashboard running at http://localhost:${PORT}`);
      console.log(`📊 WebSocket server ready for real-time updates\n`);
    });
  })
  .catch((error) => {
    console.error('Failed to start dashboard:', error);
    process.exit(1);
  });
