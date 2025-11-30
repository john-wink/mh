// Dashboard logic
let ws;
let reconnectInterval;

function connectWebSocket() {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const wsUrl = `${protocol}//${window.location.host}`;

    ws = new WebSocket(wsUrl);

    ws.onopen = () => {
        console.log('Connected to orchestrator');
        clearInterval(reconnectInterval);
    };

    ws.onmessage = (event) => {
        const message = JSON.parse(event.data);

        if (message.type === 'status') {
            updateDashboard(message.data);
        }
    };

    ws.onclose = () => {
        console.log('Disconnected from orchestrator. Reconnecting...');
        reconnectInterval = setInterval(connectWebSocket, 5000);
    };

    ws.onerror = (error) => {
        console.error('WebSocket error:', error);
    };
}

async function fetchInitialData() {
    try {
        const [statusRes, configRes, agentsRes, costsRes] = await Promise.all([
            fetch('/api/status'),
            fetch('/api/config'),
            fetch('/api/agents'),
            fetch('/api/costs')
        ]);

        const status = await statusRes.json();
        const config = await configRes.json();
        const agents = await agentsRes.json();
        const costs = await costsRes.json();

        renderOverallStats(status.overall);
        renderCostTracking(costs);
        renderConfig(config);
        renderTeams(status.teams, agents.agents);

        document.getElementById('loading').style.display = 'none';
        document.getElementById('dashboard').style.display = 'block';

        // Connect WebSocket for real-time updates
        connectWebSocket();

        // Refresh cost data every 30 seconds
        setInterval(async () => {
            const res = await fetch('/api/costs');
            const data = await res.json();
            renderCostTracking(data);
        }, 30000);
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
        document.getElementById('loading').innerHTML =
            `<p>❌ Failed to load dashboard</p><p>${error.message}</p>`;
    }
}

function renderOverallStats(overall) {
    const statsHtml = `
        <div class="stat-card">
            <div class="stat-label">Total Agents</div>
            <div class="stat-value">${overall.totalAgents}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active Agents</div>
            <div class="stat-value">${overall.activeAgents}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Current Load</div>
            <div class="stat-value">${overall.totalCurrentLoad} <span style="font-size: 0.5em;">SP</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Completed</div>
            <div class="stat-value">${overall.totalCompletedStoryPoints} <span style="font-size: 0.5em;">SP</span></div>
        </div>
    `;

    document.getElementById('overall-stats').innerHTML = statsHtml;
}

function renderCostTracking(costs) {
    if (!costs.enabled) {
        document.getElementById('cost-tracking').innerHTML = `
            <div class="cost-disabled">
                💤 Cost tracking is not enabled
            </div>
        `;
        return;
    }

    const getCostClass = (percentage) => {
        if (percentage >= 90) return 'danger';
        if (percentage >= 75) return 'warning';
        return '';
    };

    const getProgressClass = (percentage) => {
        if (percentage >= 90) return 'danger';
        if (percentage >= 75) return 'warning';
        return '';
    };

    const formatCurrency = (amount) => {
        return `$${amount.toFixed(2)}`;
    };

    const formatTokens = (tokens) => {
        if (tokens >= 1000000) return `${(tokens / 1000000).toFixed(2)}M`;
        if (tokens >= 1000) return `${(tokens / 1000).toFixed(1)}K`;
        return tokens.toString();
    };

    const dailyClass = getCostClass(costs.daily.percentage);
    const weeklyClass = getCostClass(costs.weekly.percentage);
    const monthlyClass = getCostClass(costs.monthly.percentage);
    const totalClass = getCostClass(costs.allTime.percentage);

    const costHtml = `
        <div class="cost-grid">
            <!-- Daily Costs -->
            <div class="cost-card ${dailyClass}">
                <div class="cost-header">
                    <div class="cost-period">📅 Today</div>
                    <div class="cost-amount">${formatCurrency(costs.daily.cost)}</div>
                </div>
                <div class="cost-details">
                    of ${formatCurrency(costs.daily.limit)} limit
                    ${costs.daily.savings > 0 ? `<span class="savings-badge">-${formatCurrency(costs.daily.savings)} saved</span>` : ''}
                </div>
                <div class="cost-progress">
                    <div class="cost-progress-fill ${getProgressClass(costs.daily.percentage)}"
                         style="width: ${Math.min(costs.daily.percentage, 100)}%"></div>
                </div>
                <div class="cost-info">
                    <div class="cost-info-item">
                        <div class="cost-info-label">Requests</div>
                        <div class="cost-info-value">${costs.daily.requests || 0}</div>
                    </div>
                    <div class="cost-info-item">
                        <div class="cost-info-label">Cache Hit</div>
                        <div class="cost-info-value">${costs.daily.cacheEfficiency.toFixed(1)}%</div>
                    </div>
                </div>
            </div>

            <!-- Weekly Costs -->
            <div class="cost-card ${weeklyClass}">
                <div class="cost-header">
                    <div class="cost-period">📊 This Week</div>
                    <div class="cost-amount">${formatCurrency(costs.weekly.cost)}</div>
                </div>
                <div class="cost-details">
                    of ${formatCurrency(costs.weekly.limit)} limit
                    ${costs.weekly.savings > 0 ? `<span class="savings-badge">-${formatCurrency(costs.weekly.savings)} saved</span>` : ''}
                </div>
                <div class="cost-progress">
                    <div class="cost-progress-fill ${getProgressClass(costs.weekly.percentage)}"
                         style="width: ${Math.min(costs.weekly.percentage, 100)}%"></div>
                </div>
            </div>

            <!-- Monthly Costs -->
            <div class="cost-card ${monthlyClass}">
                <div class="cost-header">
                    <div class="cost-period">📆 This Month</div>
                    <div class="cost-amount">${formatCurrency(costs.monthly.cost)}</div>
                </div>
                <div class="cost-details">
                    of ${formatCurrency(costs.monthly.limit)} limit
                    ${costs.monthly.savings > 0 ? `<span class="savings-badge">-${formatCurrency(costs.monthly.savings)} saved</span>` : ''}
                </div>
                <div class="cost-progress">
                    <div class="cost-progress-fill ${getProgressClass(costs.monthly.percentage)}"
                         style="width: ${Math.min(costs.monthly.percentage, 100)}%"></div>
                </div>
            </div>

            <!-- All-Time Costs -->
            <div class="cost-card ${totalClass}">
                <div class="cost-header">
                    <div class="cost-period">🔄 All Time</div>
                    <div class="cost-amount">${formatCurrency(costs.allTime.cost)}</div>
                </div>
                <div class="cost-details">
                    of ${formatCurrency(costs.allTime.limit)} total budget
                    ${costs.allTime.savings > 0 ? `<span class="savings-badge">-${formatCurrency(costs.allTime.savings)} saved</span>` : ''}
                </div>
                <div class="cost-progress">
                    <div class="cost-progress-fill ${getProgressClass(costs.allTime.percentage)}"
                         style="width: ${Math.min(costs.allTime.percentage, 100)}%"></div>
                </div>
                <div class="cost-info">
                    <div class="cost-info-item">
                        <div class="cost-info-label">Total Requests</div>
                        <div class="cost-info-value">${costs.allTime.requests}</div>
                    </div>
                    <div class="cost-info-item">
                        <div class="cost-info-label">Avg/Request</div>
                        <div class="cost-info-value">${formatCurrency(costs.allTime.averageCostPerRequest)}</div>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('cost-tracking').innerHTML = costHtml;
}

function renderConfig(config) {
    const configHtml = `
        <div class="config-item">
            <div class="config-label">Current Sprint</div>
            <div class="config-value">${config.currentSprint}</div>
        </div>
        <div class="config-item">
            <div class="config-label">Max Concurrent</div>
            <div class="config-value">${config.maxConcurrentAgents}</div>
        </div>
        <div class="config-item">
            <div class="config-label">Auto-Assign</div>
            <div class="config-value">${config.autoAssignTasks ? '✅ Yes' : '❌ No'}</div>
        </div>
        <div class="config-item">
            <div class="config-label">Code Review</div>
            <div class="config-value">${config.codeReview.enabled ? '✅ Enabled' : '❌ Disabled'}</div>
        </div>
        <div class="config-item">
            <div class="config-label">Min Coverage</div>
            <div class="config-value">${config.testing.minimumCoverage}%</div>
        </div>
        <div class="config-item">
            <div class="config-label">Min Approvals</div>
            <div class="config-value">${config.codeReview.minimumApprovals}</div>
        </div>
    `;

    document.getElementById('config-grid').innerHTML = configHtml;
}

function renderTeams(teams, allAgents) {
    const teamsHtml = teams.map(team => {
        const teamAgents = allAgents.filter(a => a.team === team.team);

        return `
            <div class="team-card">
                <div class="team-header">
                    <div class="team-name">Team ${team.team}</div>
                    <div class="team-badge">${team.agents} Agents</div>
                </div>

                <div class="team-stats">
                    <div class="team-stat">
                        <div class="team-stat-label">Current Load</div>
                        <div class="team-stat-value">${team.currentLoad} SP</div>
                    </div>
                    <div class="team-stat">
                        <div class="team-stat-label">Completed</div>
                        <div class="team-stat-value">${team.completedStoryPoints} SP</div>
                    </div>
                </div>

                <div class="agent-list">
                    ${teamAgents.map(agent => {
                        const agentStatus = team.agentStatuses.find(s => s.id === agent.id);
                        const isActive = agentStatus && agentStatus.currentTasks.length > 0;

                        return `
                            <div class="agent-item ${isActive ? 'active' : 'idle'}">
                                <div class="agent-name">
                                    <span class="status-indicator ${isActive ? 'online' : 'offline'}"></span>
                                    ${agent.name}
                                </div>
                                <div class="agent-role">${agent.role}</div>
                                <div class="agent-progress">
                                    <span>${agentStatus?.currentLoad || 0} SP (${agentStatus?.currentTasks.length || 0} tasks)</span>
                                    <span>✓ ${agentStatus?.completedStoryPoints || 0} SP</span>
                                </div>
                                ${agentStatus && agentStatus.currentTasks.length > 0 ? `
                                    <div class="task-list">
                                        ${agentStatus.currentTasks.map(task => `
                                            <div class="task-item">
                                                🔨 ${task.id}: ${task.title} (${task.storyPoints} SP)
                                            </div>
                                        `).join('')}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }).join('');

    document.getElementById('teams-grid').innerHTML = teamsHtml;
}

function updateDashboard(data) {
    // Update overall stats
    if (data.totalAgents !== undefined) {
        renderOverallStats({
            totalAgents: data.totalAgents,
            activeAgents: data.activeAgents,
            totalCurrentLoad: data.totalCurrentLoad,
            totalCompletedStoryPoints: data.totalCompletedStoryPoints
        });
    }
}

// Task Management Functions
async function fetchTasks() {
    try {
        const res = await fetch('/api/tasks');
        const data = await res.json();
        renderTasks(data.tasks, data.statistics);
    } catch (error) {
        console.error('Failed to fetch tasks:', error);
    }
}

function renderTasks(tasks, stats) {
    const statusColors = {
        pending: '#cbd5e0',
        in_progress: '#667eea',
        completed: '#48bb78',
        blocked: '#fc8181'
    };

    const statsHtml = `
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.85em; color: #666; margin-bottom: 5px;">Total Tasks</div>
                <div style="font-size: 1.8em; font-weight: bold; color: #333;">${stats.total}</div>
            </div>
            <div style="background: #fff8e1; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.85em; color: #666; margin-bottom: 5px;">Pending</div>
                <div style="font-size: 1.8em; font-weight: bold; color: #f6ad55;">${stats.pending}</div>
            </div>
            <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.85em; color: #666; margin-bottom: 5px;">In Progress</div>
                <div style="font-size: 1.8em; font-weight: bold; color: #667eea;">${stats.inProgress}</div>
            </div>
            <div style="background: #f0fff4; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.85em; color: #666; margin-bottom: 5px;">Completed</div>
                <div style="font-size: 1.8em; font-weight: bold; color: #48bb78;">${stats.completed}</div>
            </div>
            <div style="background: #fff5f5; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.85em; color: #666; margin-bottom: 5px;">Blocked</div>
                <div style="font-size: 1.8em; font-weight: bold; color: #fc8181;">${stats.blocked}</div>
            </div>
        </div>
    `;

    const tasksHtml = tasks.length === 0 ? '<div class="empty-state">No tasks yet. Create one to get started!</div>' : tasks.map(task => `
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid ${statusColors[task.status]};">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                <div>
                    <div style="font-weight: bold; font-size: 1.1em; color: #333; margin-bottom: 5px;">${task.id}: ${task.title}</div>
                    <div style="font-size: 0.9em; color: #666;">${task.description}</div>
                </div>
                <div style="display: flex; gap: 5px;">
                    <span style="background: ${statusColors[task.status]}; color: white; padding: 5px 12px; border-radius: 12px; font-size: 0.85em; font-weight: bold;">${task.status.replace('_', ' ').toUpperCase()}</span>
                    <span style="background: #667eea; color: white; padding: 5px 12px; border-radius: 12px; font-size: 0.85em; font-weight: bold;">${task.storyPoints} SP</span>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                <div style="font-size: 0.85em; color: #666;">
                    Team ${task.team} • Sprint ${task.sprint} ${task.assignedTo ? `• Assigned to ${task.assignedTo}` : ''}
                </div>
                <div style="display: flex; gap: 8px;">
                    ${task.status === 'pending' ? `<button onclick="assignTask('${task.id}')" style="background: #667eea; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85em;">Assign</button>` : ''}
                    ${task.status === 'in_progress' ? `<button onclick="executeTask('${task.id}')" style="background: #48bb78; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85em;">Execute</button>` : ''}
                    <button onclick="deleteTask('${task.id}')" style="background: #fc8181; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85em;">Delete</button>
                </div>
            </div>
        </div>
    `).join('');

    document.getElementById('task-management').innerHTML = statsHtml + tasksHtml;
}

async function assignTask(taskId) {
    try {
        const res = await fetch(`/api/tasks/${taskId}/assign`, { method: 'POST' });
        const data = await res.json();
        if (res.ok) {
            alert(`Task assigned to ${data.agent}`);
            fetchTasks();
        } else {
            alert(`Error: ${data.error}`);
        }
    } catch (error) {
        alert(`Failed to assign task: ${error.message}`);
    }
}

async function executeTask(taskId) {
    if (!confirm('Execute this task? This will use API credits.')) return;
    try {
        const res = await fetch(`/api/tasks/${taskId}/execute`, { method: 'POST' });
        const data = await res.json();
        if (res.ok) {
            alert(`Task executed!\nCost: $${data.cost?.totalCost?.toFixed(4) || 'N/A'}`);
            fetchTasks();
        } else {
            alert(`Error: ${data.error}`);
        }
    } catch (error) {
        alert(`Failed to execute task: ${error.message}`);
    }
}

async function deleteTask(taskId) {
    if (!confirm('Delete this task?')) return;
    try {
        const res = await fetch(`/api/tasks/${taskId}`, { method: 'DELETE' });
        if (res.ok) {
            fetchTasks();
        } else {
            alert('Failed to delete task');
        }
    } catch (error) {
        alert(`Failed to delete task: ${error.message}`);
    }
}

async function autoAssignTasks() {
    try {
        const res = await fetch('/api/tasks/auto-assign', { method: 'POST' });
        const data = await res.json();
        alert(`Auto-assigned ${data.assigned} tasks (${data.failed} failed)`);
        fetchTasks();
    } catch (error) {
        alert(`Failed to auto-assign: ${error.message}`);
    }
}

function showCreateTaskModal() {
    const title = prompt('Task title:');
    if (!title) return;
    const description = prompt('Task description:');
    if (!description) return;
    const storyPoints = parseInt(prompt('Story points:'));
    if (!storyPoints) return;
    const team = parseInt(prompt('Team number (0-6):'));
    if (team === null) return;
    const sprint = parseInt(prompt('Sprint number:'));
    if (!sprint) return;

    createTask(title, description, storyPoints, team, sprint);
}

async function createTask(title, description, storyPoints, team, sprint) {
    try {
        const res = await fetch('/api/tasks', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, description, storyPoints, dependencies: [], team, sprint })
        });
        if (res.ok) {
            alert('Task created!');
            fetchTasks();
        } else {
            const data = await res.json();
            alert(`Error: ${data.error}`);
        }
    } catch (error) {
        alert(`Failed to create task: ${error.message}`);
    }
}

// Initialize dashboard on page load
document.addEventListener('DOMContentLoaded', () => {
    fetchInitialData();
    fetchTasks();
    // Refresh tasks every 10 seconds
    setInterval(fetchTasks, 10000);
});
