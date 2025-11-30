// Custom Modal System
function showAlert(message, title = 'Information', icon = 'ℹ️') {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML = `
            <div class="modal">
                <div class="modal-header">${icon} ${title}</div>
                <div class="modal-body">${message}</div>
                <div class="modal-buttons">
                    <button class="modal-button modal-button-primary">OK</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        setTimeout(() => overlay.classList.add('show'), 10);

        const closeModal = () => {
            overlay.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(overlay);
                resolve();
            }, 300);
        };

        overlay.querySelector('.modal-button').onclick = closeModal;
        overlay.onclick = (e) => { if (e.target === overlay) closeModal(); };
    });
}

function showConfirm(message, title = 'Confirm', icon = '❓') {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML = `
            <div class="modal">
                <div class="modal-header">${icon} ${title}</div>
                <div class="modal-body">${message}</div>
                <div class="modal-buttons">
                    <button class="modal-button modal-button-secondary cancel-btn">Cancel</button>
                    <button class="modal-button modal-button-primary confirm-btn">Confirm</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        setTimeout(() => overlay.classList.add('show'), 10);

        const closeModal = (result) => {
            overlay.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(overlay);
                resolve(result);
            }, 300);
        };

        overlay.querySelector('.confirm-btn').onclick = () => closeModal(true);
        overlay.querySelector('.cancel-btn').onclick = () => closeModal(false);
        overlay.onclick = (e) => { if (e.target === overlay) closeModal(false); };
    });
}

function showPrompt(message, defaultValue = '', title = 'Input Required', icon = '✏️') {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML = `
            <div class="modal">
                <div class="modal-header">${icon} ${title}</div>
                <div class="modal-body">${message}</div>
                <input type="text" class="modal-input" value="${defaultValue}" placeholder="Enter value...">
                <div class="modal-buttons">
                    <button class="modal-button modal-button-secondary cancel-btn">Cancel</button>
                    <button class="modal-button modal-button-success submit-btn">Submit</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        setTimeout(() => overlay.classList.add('show'), 10);

        const input = overlay.querySelector('.modal-input');
        input.focus();
        input.select();

        const closeModal = (result) => {
            overlay.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(overlay);
                resolve(result);
            }, 300);
        };

        const submit = () => {
            const value = input.value.trim();
            closeModal(value || null);
        };

        overlay.querySelector('.submit-btn').onclick = submit;
        overlay.querySelector('.cancel-btn').onclick = () => closeModal(null);
        input.onkeypress = (e) => { if (e.key === 'Enter') submit(); };
        overlay.onclick = (e) => { if (e.target === overlay) closeModal(null); };
    });
}

function showForm(fields, title = 'Form', icon = '📝') {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';

        const fieldsHtml = fields.map(field => {
            const inputType = field.type || 'text';
            const isTextarea = inputType === 'textarea';
            const inputHtml = isTextarea
                ? `<textarea class="modal-textarea" name="${field.name}" placeholder="${field.placeholder || ''}">${field.value || ''}</textarea>`
                : `<input type="${inputType}" class="modal-input" name="${field.name}" value="${field.value || ''}" placeholder="${field.placeholder || ''}">`;

            return `
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: var(--text-primary);">${field.label}</label>
                    ${inputHtml}
                </div>
            `;
        }).join('');

        overlay.innerHTML = `
            <div class="modal">
                <div class="modal-header">${icon} ${title}</div>
                <div class="modal-body">
                    ${fieldsHtml}
                </div>
                <div class="modal-buttons">
                    <button class="modal-button modal-button-secondary cancel-btn">Cancel</button>
                    <button class="modal-button modal-button-success submit-btn">Submit</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        setTimeout(() => overlay.classList.add('show'), 10);

        const firstInput = overlay.querySelector('.modal-input, .modal-textarea');
        if (firstInput) firstInput.focus();

        const closeModal = (result) => {
            overlay.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(overlay);
                resolve(result);
            }, 300);
        };

        const submit = () => {
            const formData = {};
            fields.forEach(field => {
                const input = overlay.querySelector(`[name="${field.name}"]`);
                formData[field.name] = input.value.trim();
            });
            closeModal(formData);
        };

        overlay.querySelector('.submit-btn').onclick = submit;
        overlay.querySelector('.cancel-btn').onclick = () => closeModal(null);
        overlay.onclick = (e) => { if (e.target === overlay) closeModal(null); };
    });
}

// Dark Mode Management
function initDarkMode() {
    // Check localStorage first
    const savedMode = localStorage.getItem('darkMode');

    if (savedMode !== null) {
        // Use saved preference
        if (savedMode === 'true') {
            enableDarkMode();
        }
    } else {
        // Detect system preference
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (prefersDark) {
            enableDarkMode();
        }
    }

    // Listen for system preference changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        // Only auto-switch if user hasn't manually set preference
        if (localStorage.getItem('darkMode') === null) {
            if (e.matches) {
                enableDarkMode();
            } else {
                disableDarkMode();
            }
        }
    });
}

function toggleDarkMode() {
    const isDark = document.body.classList.contains('dark-mode');

    if (isDark) {
        disableDarkMode();
        localStorage.setItem('darkMode', 'false');
    } else {
        enableDarkMode();
        localStorage.setItem('darkMode', 'true');
    }

    // Re-render dynamic content to apply dark mode colors
    fetchTasks();
    fetchEpics();
    if (currentAnalysis) {
        renderSuggestions(currentAnalysis);
    }
}

function enableDarkMode() {
    document.body.classList.add('dark-mode');
    document.getElementById('dark-mode-icon').textContent = '☀️';
    document.getElementById('dark-mode-text').textContent = 'Light Mode';
}

function disableDarkMode() {
    document.body.classList.remove('dark-mode');
    document.getElementById('dark-mode-icon').textContent = '🌙';
    document.getElementById('dark-mode-text').textContent = 'Dark Mode';
}

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

        // Set current sprint from config
        currentSprint = config.currentSprint;

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
    const isDark = document.body.classList.contains('dark-mode');

    const statusColors = {
        pending: '#cbd5e0',
        in_progress: '#667eea',
        completed: '#48bb78',
        blocked: '#fc8181'
    };

    const cardBg = isDark ? '#0a2647' : '#f8f9fa';
    const textPrimary = isDark ? '#e8e8e8' : '#333';
    const textSecondary = isDark ? '#b8b8b8' : '#666';
    const borderColor = isDark ? '#1e4976' : '#e2e8f0';

    // Stats background colors for dark mode
    const statsBg = {
        total: isDark ? '#0a2647' : '#f8f9fa',
        pending: isDark ? 'rgba(246, 173, 85, 0.15)' : '#fff8e1',
        inProgress: isDark ? 'rgba(102, 126, 234, 0.15)' : '#e3f2fd',
        completed: isDark ? 'rgba(72, 187, 120, 0.15)' : '#f0fff4',
        blocked: isDark ? 'rgba(252, 129, 129, 0.15)' : '#fff5f5'
    };

    const statsHtml = `
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div style="background: ${statsBg.total}; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.85em; color: ${textSecondary}; margin-bottom: 5px;">Total Tasks</div>
                <div style="font-size: 1.8em; font-weight: bold; color: ${textPrimary};">${stats.total}</div>
            </div>
            <div style="background: ${statsBg.pending}; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.85em; color: ${textSecondary}; margin-bottom: 5px;">Pending</div>
                <div style="font-size: 1.8em; font-weight: bold; color: #f6ad55;">${stats.pending}</div>
            </div>
            <div style="background: ${statsBg.inProgress}; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.85em; color: ${textSecondary}; margin-bottom: 5px;">In Progress</div>
                <div style="font-size: 1.8em; font-weight: bold; color: #667eea;">${stats.inProgress}</div>
            </div>
            <div style="background: ${statsBg.completed}; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.85em; color: ${textSecondary}; margin-bottom: 5px;">Completed</div>
                <div style="font-size: 1.8em; font-weight: bold; color: #48bb78;">${stats.completed}</div>
            </div>
            <div style="background: ${statsBg.blocked}; padding: 15px; border-radius: 8px; text-align: center;">
                <div style="font-size: 0.85em; color: ${textSecondary}; margin-bottom: 5px;">Blocked</div>
                <div style="font-size: 1.8em; font-weight: bold; color: #fc8181;">${stats.blocked}</div>
            </div>
        </div>
    `;

    const tasksHtml = tasks.length === 0 ? '<div class="empty-state">No tasks yet. Create one to get started!</div>' : tasks.map(task => `
        <div style="background: ${cardBg}; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid ${statusColors[task.status]};">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                <div>
                    <div style="font-weight: bold; font-size: 1.1em; color: ${textPrimary}; margin-bottom: 5px;">${task.id}: ${task.title}</div>
                    <div style="font-size: 0.9em; color: ${textSecondary};">${task.description}</div>
                </div>
                <div style="display: flex; gap: 5px;">
                    <span style="background: ${statusColors[task.status]}; color: white; padding: 5px 12px; border-radius: 12px; font-size: 0.85em; font-weight: bold;">${task.status.replace('_', ' ').toUpperCase()}</span>
                    <span style="background: #667eea; color: white; padding: 5px 12px; border-radius: 12px; font-size: 0.85em; font-weight: bold;">${task.storyPoints} SP</span>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid ${borderColor};">
                <div style="font-size: 0.85em; color: ${textSecondary};">
                    Team ${task.team} • Sprint ${task.sprint} ${task.assignedTo ? `• Assigned to ${task.assignedTo}` : ''}
                </div>
                <div style="display: flex; gap: 8px;">
                    ${task.status === 'pending' ? `<button onclick="assignTask('${task.id}')" style="background: #667eea; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85em;">Assign</button>` : ''}
                    ${task.status === 'in_progress' ? `<button onclick="executeTask('${task.id}')" style="background: #48bb78; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85em;">Execute</button>` : ''}
                    ${task.status === 'blocked' ? `<button onclick="unblockTask('${task.id}')" style="background: #f6ad55; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85em;">Unblock</button>` : ''}
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
            await showAlert(`Task assigned to ${data.agent}`, 'Success', '✅');
            fetchTasks();
        } else {
            await showAlert(`Error: ${data.error}`, 'Error', '❌');
        }
    } catch (error) {
        await showAlert(`Failed to assign task: ${error.message}`, 'Error', '❌');
    }
}

async function executeTask(taskId) {
    if (!await showConfirm('Execute this task? This will use API credits.', 'Confirm Execution', '💰')) return;
    try {
        const res = await fetch(`/api/tasks/${taskId}/execute`, { method: 'POST' });
        const data = await res.json();
        if (res.ok) {
            await showAlert(`Task executed!\nCost: $${data.cost?.totalCost?.toFixed(4) || 'N/A'}`, 'Success', '✅');
            fetchTasks();
        } else {
            await showAlert(`Error: ${data.error}`, 'Error', '❌');
        }
    } catch (error) {
        await showAlert(`Failed to execute task: ${error.message}`, 'Error', '❌');
    }
}

async function unblockTask(taskId) {
    if (!await showConfirm('Unblock this task? It will be reset to pending status.', 'Confirm Unblock', '🔓')) return;
    try {
        const res = await fetch(`/api/tasks/${taskId}/unblock`, { method: 'POST' });
        if (res.ok) {
            await showAlert('Task unblocked and reset to pending', 'Success', '✅');
            fetchTasks();
        } else {
            const data = await res.json();
            await showAlert(`Error: ${data.error}`, 'Error', '❌');
        }
    } catch (error) {
        await showAlert(`Failed to unblock task: ${error.message}`, 'Error', '❌');
    }
}

async function deleteTask(taskId) {
    if (!await showConfirm('Delete this task?', 'Confirm Delete', '🗑️')) return;
    try {
        const res = await fetch(`/api/tasks/${taskId}`, { method: 'DELETE' });
        if (res.ok) {
            fetchTasks();
        } else {
            await showAlert('Failed to delete task', 'Error', '❌');
        }
    } catch (error) {
        await showAlert(`Failed to delete task: ${error.message}`, 'Error', '❌');
    }
}

async function autoAssignTasks() {
    try {
        const res = await fetch('/api/tasks/auto-assign', { method: 'POST' });
        const data = await res.json();
        await showAlert(`Auto-assigned ${data.assigned} tasks (${data.failed} failed)`, 'Auto-Assign Complete', '🤖');
        fetchTasks();
    } catch (error) {
        await showAlert(`Failed to auto-assign: ${error.message}`, 'Error', '❌');
    }
}

async function showCreateTaskModal() {
    const formData = await showForm([
        { name: 'title', label: 'Task Title', placeholder: 'Enter task title...', type: 'text' },
        { name: 'description', label: 'Description', placeholder: 'Enter task description...', type: 'textarea' },
        { name: 'storyPoints', label: 'Story Points', placeholder: 'e.g., 3', type: 'number' },
        { name: 'team', label: 'Team Number (0-6)', placeholder: 'e.g., 0', type: 'number' },
        { name: 'sprint', label: 'Sprint Number', placeholder: 'e.g., 1', type: 'number' }
    ], 'Create New Task', '📋');

    if (!formData) return;

    const { title, description, storyPoints, team, sprint } = formData;

    if (!title || !description || !storyPoints || team === '' || !sprint) {
        await showAlert('Please fill in all fields', 'Validation Error', '⚠️');
        return;
    }

    createTask(title, description, parseInt(storyPoints), parseInt(team), parseInt(sprint));
}

async function createTask(title, description, storyPoints, team, sprint) {
    try {
        const res = await fetch('/api/tasks', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, description, storyPoints, dependencies: [], team, sprint })
        });
        if (res.ok) {
            await showAlert('Task created successfully!', 'Success', '✅');
            fetchTasks();
        } else {
            const data = await res.json();
            await showAlert(`Error: ${data.error}`, 'Error', '❌');
        }
    } catch (error) {
        await showAlert(`Failed to create task: ${error.message}`, 'Error', '❌');
    }
}

// Planning Agent Functions
let currentSprint = 1;
let currentAnalysis = null;

async function getSuggestions() {
    const button = event.target;
    button.disabled = true;
    button.innerHTML = '🔄 Analyzing...';

    try {
        const res = await fetch('/api/planning/suggest', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sprint: currentSprint })
        });

        const data = await res.json();

        if (res.ok) {
            currentAnalysis = data.analysis;
            renderSuggestions(data.analysis);
        } else {
            await showAlert(`Error: ${data.error}`, 'Error', '❌');
        }
    } catch (error) {
        await showAlert(`Failed to get suggestions: ${error.message}`, 'Error', '❌');
    } finally {
        button.disabled = false;
        button.innerHTML = '✨ Get AI Suggestions';
    }
}

function renderSuggestions(analysis) {
    const priorityColors = {
        high: '#fc8181',
        medium: '#f6ad55',
        low: '#68d391'
    };

    const typeIcons = {
        epic: '🎯',
        task: '📋'
    };

    const html = `
        <!-- Analysis Overview -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="color: #333; margin: 0 0 15px 0;">📊 Project Analysis</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <div style="font-weight: bold; color: #667eea; margin-bottom: 5px;">Project Overview</div>
                    <div style="font-size: 0.9em; color: #666;">${analysis.projectOverview}</div>
                </div>
                <div>
                    <div style="font-weight: bold; color: #667eea; margin-bottom: 5px;">Team Capabilities</div>
                    <div style="font-size: 0.9em; color: #666;">${analysis.teamCapabilities}</div>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <div style="font-weight: bold; color: #48bb78; margin-bottom: 5px;">Completed Work</div>
                    <div style="font-size: 0.9em; color: #666;">${analysis.completedWork}</div>
                </div>
                <div>
                    <div style="font-weight: bold; color: #f6ad55; margin-bottom: 5px;">Pending Work</div>
                    <div style="font-size: 0.9em; color: #666;">${analysis.pendingWork}</div>
                </div>
            </div>
        </div>

        <!-- Suggestions -->
        <div style="margin-bottom: 20px;">
            <h3 style="color: #333; margin: 0 0 15px 0;">💡 Recommendations (${analysis.suggestions.length})</h3>
            ${analysis.suggestions.length === 0 ?
                '<div class="empty-state">No suggestions available. The AI might need more project context.</div>' :
                analysis.suggestions.map((suggestion, index) => `
                    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid ${priorityColors[suggestion.priority]}; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <span style="font-size: 1.2em;">${typeIcons[suggestion.type]}</span>
                                    <span style="font-weight: bold; font-size: 1.1em; color: #333;">${suggestion.title}</span>
                                </div>
                                <div style="font-size: 0.9em; color: #666; margin-bottom: 10px;">${suggestion.description}</div>
                            </div>
                            <div style="display: flex; gap: 8px; flex-shrink: 0;">
                                <span style="background: ${priorityColors[suggestion.priority]}; color: white; padding: 5px 12px; border-radius: 12px; font-size: 0.85em; font-weight: bold; text-transform: uppercase;">${suggestion.priority}</span>
                                <span style="background: #667eea; color: white; padding: 5px 12px; border-radius: 12px; font-size: 0.85em; font-weight: bold;">${suggestion.estimatedStoryPoints || (suggestion.type === 'epic' ? 8 : 3)} SP</span>
                            </div>
                        </div>

                        <div style="background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 12px;">
                            <div style="font-weight: bold; color: #667eea; font-size: 0.85em; margin-bottom: 5px;">💭 Reasoning</div>
                            <div style="font-size: 0.85em; color: #555;">${suggestion.reasoning}</div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-size: 0.85em; color: #666;">
                                Team ${suggestion.team} • Sprint ${suggestion.sprint} • ${suggestion.type.toUpperCase()}
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="editSuggestion(${index})" style="background: #667eea; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85em;">✏️ Edit</button>
                                <button onclick="createSuggestion(${index})" style="background: #48bb78; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85em; font-weight: bold;">✓ Create</button>
                            </div>
                        </div>
                    </div>
                `).join('')
            }
        </div>

        <!-- Next Steps -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; color: white;">
            <div style="font-weight: bold; margin-bottom: 10px;">🎯 Recommended Next Steps</div>
            <div style="font-size: 0.95em; opacity: 0.95;">${analysis.nextSteps}</div>
        </div>
    `;

    document.getElementById('planning-section').innerHTML = html;
}

async function editSuggestion(index) {
    const suggestion = currentAnalysis.suggestions[index];

    const formData = await showForm([
        { name: 'title', label: 'Title', placeholder: 'Enter title...', type: 'text', value: suggestion.title },
        { name: 'description', label: 'Description', placeholder: 'Enter description...', type: 'textarea', value: suggestion.description },
        { name: 'storyPoints', label: 'Story Points', placeholder: 'e.g., 3', type: 'number', value: suggestion.estimatedStoryPoints || (suggestion.type === 'epic' ? 8 : 3) },
        { name: 'team', label: 'Team (0-6)', placeholder: 'e.g., 0', type: 'number', value: suggestion.team },
        { name: 'sprint', label: 'Sprint', placeholder: 'e.g., 1', type: 'number', value: suggestion.sprint }
    ], `Edit ${suggestion.type === 'epic' ? 'Epic' : 'Task'}`, '✏️');

    if (!formData) return; // User cancelled

    // Update the suggestion
    currentAnalysis.suggestions[index] = {
        ...suggestion,
        title: formData.title || suggestion.title,
        description: formData.description || suggestion.description,
        estimatedStoryPoints: parseInt(formData.storyPoints),
        team: parseInt(formData.team),
        sprint: parseInt(formData.sprint)
    };

    // Re-render
    renderSuggestions(currentAnalysis);
}

async function createSuggestion(index) {
    const suggestion = currentAnalysis.suggestions[index];

    if (!await showConfirm(
        `Create this ${suggestion.type}?\n\n${suggestion.title}\n\nThis will add it to your ${suggestion.type === 'epic' ? 'epics' : 'tasks'}.`,
        'Confirm Creation',
        '✨'
    )) {
        return;
    }

    try {
        const res = await fetch('/api/planning/create-suggestion', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ suggestion })
        });

        const data = await res.json();

        if (res.ok) {
            await showAlert(`${suggestion.type === 'epic' ? 'Epic' : 'Task'} created: ${data.created.id}`, 'Success', '✅');

            // Remove from suggestions
            currentAnalysis.suggestions.splice(index, 1);
            renderSuggestions(currentAnalysis);

            // Refresh the appropriate section
            if (suggestion.type === 'epic') {
                fetchEpics();
            } else {
                fetchTasks();
            }
        } else {
            await showAlert(`Error: ${data.error}`, 'Error', '❌');
        }
    } catch (error) {
        await showAlert(`Failed to create ${suggestion.type}: ${error.message}`, 'Error', '❌');
    }
}

// Epic Management Functions
async function fetchEpics() {
    try {
        const res = await fetch('/api/epics');
        const data = await res.json();
        renderEpics(data.epics);
    } catch (error) {
        console.error('Failed to fetch epics:', error);
    }
}

function renderEpics(epics) {
    const statusColors = {
        pending: '#cbd5e0',
        in_progress: '#667eea',
        completed: '#48bb78'
    };

    if (epics.length === 0) {
        document.getElementById('epic-management').innerHTML = '<div class="empty-state">No epics yet. Create one to get started!</div>';
        return;
    }

    const epicsHtml = epics.map(epic => `
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 12px; margin-bottom: 20px; color: white; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                <div>
                    <div style="font-weight: bold; font-size: 1.3em; margin-bottom: 8px;">${epic.id}: ${epic.title}</div>
                    <div style="font-size: 0.95em; opacity: 0.9; margin-bottom: 10px;">${epic.description}</div>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span style="background: rgba(255,255,255,0.2); color: white; padding: 6px 14px; border-radius: 12px; font-size: 0.9em; font-weight: bold;">${epic.status.replace('_', ' ').toUpperCase()}</span>
                    <span style="background: rgba(255,255,255,0.3); color: white; padding: 6px 14px; border-radius: 12px; font-size: 0.9em; font-weight: bold;">${epic.estimatedStoryPoints} SP</span>
                </div>
            </div>

            <!-- Progress Bar -->
            ${epic.progress && epic.progress.total > 0 ? `
                <div style="background: rgba(255,255,255,0.2); border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9em;">
                        <span>Progress: ${epic.progress.completed}/${epic.progress.total} tasks</span>
                        <span>${Math.round((epic.progress.completed / epic.progress.total) * 100)}%</span>
                    </div>
                    <div style="background: rgba(255,255,255,0.3); border-radius: 6px; overflow: hidden; height: 8px;">
                        <div style="background: #48bb78; height: 100%; width: ${(epic.progress.completed / epic.progress.total) * 100}%;"></div>
                    </div>
                    <div style="display: flex; gap: 15px; margin-top: 10px; font-size: 0.85em;">
                        <span>✅ ${epic.progress.completed} Done</span>
                        <span>🔨 ${epic.progress.inProgress} Working</span>
                        <span>📋 ${epic.progress.pending} Pending</span>
                        ${epic.progress.blocked > 0 ? `<span>🚫 ${epic.progress.blocked} Blocked</span>` : ''}
                    </div>
                </div>
            ` : ''}

            <!-- Actions -->
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.9em; opacity: 0.9;">
                    Team ${epic.team} • Sprint ${epic.sprint} ${epic.taskIds ? `• ${epic.taskIds.length} tasks` : ''}
                </div>
                <div style="display: flex; gap: 10px;">
                    ${epic.status === 'pending' ? `
                        <button onclick="breakdownEpic('${epic.id}')" style="background: white; color: #667eea; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 0.9em;">🔨 Breakdown into Tasks</button>
                    ` : ''}
                    ${epic.status === 'in_progress' && epic.progress && epic.progress.completed === epic.progress.total ? `
                        <button onclick="completeEpic('${epic.id}')" style="background: #48bb78; color: white; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 0.9em;">✓ Mark Complete</button>
                    ` : ''}
                    <button onclick="deleteEpic('${epic.id}')" style="background: rgba(252,129,129,0.9); color: white; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 0.9em;">Delete</button>
                </div>
            </div>
        </div>
    `).join('');

    document.getElementById('epic-management').innerHTML = epicsHtml;
}

async function breakdownEpic(epicId) {
    if (!await showConfirm('Break down this epic into tasks? This will use API credits.', 'Confirm Breakdown', '💰')) return;

    try {
        const res = await fetch(`/api/epics/${epicId}/breakdown`, { method: 'POST' });
        const data = await res.json();

        if (res.ok) {
            await showAlert(`Epic broken down into ${data.tasks.length} tasks!`, 'Success', '✅');
            fetchEpics();
            fetchTasks();
        } else {
            await showAlert(`Error: ${data.error}`, 'Error', '❌');
        }
    } catch (error) {
        await showAlert(`Failed to breakdown epic: ${error.message}`, 'Error', '❌');
    }
}

async function completeEpic(epicId) {
    try {
        const res = await fetch(`/api/epics/${epicId}/complete`, { method: 'POST' });
        if (res.ok) {
            fetchEpics();
        } else {
            await showAlert('Failed to complete epic', 'Error', '❌');
        }
    } catch (error) {
        await showAlert(`Failed to complete epic: ${error.message}`, 'Error', '❌');
    }
}

async function deleteEpic(epicId) {
    if (!await showConfirm('Delete this epic? Associated tasks will remain.', 'Confirm Delete', '🗑️')) return;

    try {
        const res = await fetch(`/api/epics/${epicId}`, { method: 'DELETE' });
        if (res.ok) {
            fetchEpics();
        } else {
            await showAlert('Failed to delete epic', 'Error', '❌');
        }
    } catch (error) {
        await showAlert(`Failed to delete epic: ${error.message}`, 'Error', '❌');
    }
}

async function showCreateEpicModal() {
    const formData = await showForm([
        { name: 'title', label: 'Epic Title', placeholder: 'Enter epic title...', type: 'text' },
        { name: 'description', label: 'Description', placeholder: 'Enter epic description...', type: 'textarea' },
        { name: 'estimatedStoryPoints', label: 'Estimated Story Points', placeholder: 'e.g., 13', type: 'number' },
        { name: 'team', label: 'Team Number (0-6)', placeholder: 'e.g., 0', type: 'number' },
        { name: 'sprint', label: 'Sprint Number', placeholder: 'e.g., 1', type: 'number' }
    ], 'Create New Epic', '🎯');

    if (!formData) return;

    const { title, description, estimatedStoryPoints, team, sprint } = formData;

    if (!title || !description || !estimatedStoryPoints || team === '' || !sprint) {
        await showAlert('Please fill in all fields', 'Validation Error', '⚠️');
        return;
    }

    createEpic(title, description, parseInt(estimatedStoryPoints), parseInt(team), parseInt(sprint));
}

async function createEpic(title, description, estimatedStoryPoints, team, sprint) {
    try {
        const res = await fetch('/api/epics', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, description, estimatedStoryPoints, team, sprint })
        });

        if (res.ok) {
            await showAlert('Epic created successfully!', 'Success', '✅');
            fetchEpics();
        } else {
            const data = await res.json();
            await showAlert(`Error: ${data.error}`, 'Error', '❌');
        }
    } catch (error) {
        await showAlert(`Failed to create epic: ${error.message}`, 'Error', '❌');
    }
}

// Worktree Management Functions
async function fetchWorktrees() {
    try {
        const res = await fetch('/api/worktrees');
        const data = await res.json();

        if (!data.enabled) {
            document.getElementById('worktrees-grid').innerHTML = `
                <p style="color: var(--text-secondary);">Git worktrees are not enabled</p>
            `;
            return;
        }

        renderWorktrees(data.worktrees);
    } catch (error) {
        console.error('Error fetching worktrees:', error);
        document.getElementById('worktrees-grid').innerHTML = `
            <p style="color: var(--text-secondary);">Error loading worktrees</p>
        `;
    }
}

function renderWorktrees(worktrees) {
    const grid = document.getElementById('worktrees-grid');

    if (!worktrees || worktrees.length === 0) {
        grid.innerHTML = '<p style="color: var(--text-secondary);">No worktrees found</p>';
        return;
    }

    grid.innerHTML = worktrees
        .map(wt => {
            const statusIcon = wt.isWorking ? '🔨' : (wt.hasChanges ? '📝' : '✅');
            const statusText = wt.isWorking ? 'Working' : (wt.hasChanges ? 'Has changes' : 'Clean');
            const statusColor = wt.isWorking ? '#ff9800' : (wt.hasChanges ? '#4caf50' : '#999');

            const totalChanges = wt.files.added.length + wt.files.modified.length + wt.files.deleted.length;

            return `
                <div style="background: var(--card-bg); border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px var(--card-shadow);">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div>
                            <h3 style="margin: 0; color: var(--text-primary); font-size: 1.2em;">${wt.agentName}</h3>
                            <p style="margin: 5px 0 0 0; color: var(--text-secondary); font-size: 0.9em;">
                                Team ${wt.team ?? 'N/A'} • ${wt.branch || 'No branch'}
                            </p>
                        </div>
                        <span style="background: ${statusColor}; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85em;">
                            ${statusIcon} ${statusText}
                        </span>
                    </div>

                    ${wt.currentTasks && wt.currentTasks.length > 0 ? `
                        <div style="background: #fff3cd; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                            <strong style="color: #856404;">Current Tasks:</strong>
                            ${wt.currentTasks.map(t => `<div style="color: #856404; margin-top: 5px;">📌 ${t.title}</div>`).join('')}
                        </div>
                    ` : ''}

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 15px;">
                        <div style="text-align: center; padding: 10px; background: var(--input-bg); border-radius: 8px;">
                            <div style="font-size: 1.5em; font-weight: bold; color: #4caf50;">${wt.ahead || 0}</div>
                            <div style="font-size: 0.8em; color: var(--text-secondary);">Ahead</div>
                        </div>
                        <div style="text-align: center; padding: 10px; background: var(--input-bg); border-radius: 8px;">
                            <div style="font-size: 1.5em; font-weight: bold; color: #f44336;">${wt.behind || 0}</div>
                            <div style="font-size: 0.8em; color: var(--text-secondary);">Behind</div>
                        </div>
                        <div style="text-align: center; padding: 10px; background: var(--input-bg); border-radius: 8px;">
                            <div style="font-size: 1.5em; font-weight: bold; color: #2196f3;">${totalChanges}</div>
                            <div style="font-size: 0.8em; color: var(--text-secondary);">Changes</div>
                        </div>
                    </div>

                    ${totalChanges > 0 ? `
                        <div style="margin-bottom: 15px; font-size: 0.9em;">
                            ${wt.files.added.length > 0 ? `<div style="color: #4caf50;">✚ ${wt.files.added.length} added</div>` : ''}
                            ${wt.files.modified.length > 0 ? `<div style="color: #ff9800;">✎ ${wt.files.modified.length} modified</div>` : ''}
                            ${wt.files.deleted.length > 0 ? `<div style="color: #f44336;">✖ ${wt.files.deleted.length} deleted</div>` : ''}
                        </div>
                    ` : ''}

                    ${wt.hasChanges || wt.behind > 0 ? `
                        <button onclick="syncWorktree('${wt.agentId}')"
                            style="width: 100%; padding: 10px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 0.95em;">
                            🔄 Sync with Main
                        </button>
                    ` : ''}
                </div>
            `;
        })
        .join('');
}

async function syncWorktree(agentId) {
    try {
        const res = await fetch(`/api/worktrees/${agentId}/sync`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ targetBranch: 'main' })
        });

        if (res.ok) {
            await showAlert('Worktree synced successfully!', 'Success', '✅');
            fetchWorktrees();
        } else {
            const data = await res.json();
            await showAlert(`Error: ${data.error}`, 'Error', '❌');
        }
    } catch (error) {
        await showAlert(`Failed to sync worktree: ${error.message}`, 'Error', '❌');
    }
}

async function syncAllWorktrees() {
    try {
        const res = await fetch('/api/worktrees/sync-all', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ targetBranch: 'main' })
        });

        if (res.ok) {
            await showAlert('All worktrees synced successfully!', 'Success', '✅');
            fetchWorktrees();
        } else {
            const data = await res.json();
            await showAlert(`Error: ${data.error}`, 'Error', '❌');
        }
    } catch (error) {
        await showAlert(`Failed to sync all worktrees: ${error.message}`, 'Error', '❌');
    }
}

// Initialize dashboard on page load
document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    fetchInitialData();
    fetchEpics();
    fetchTasks();
    fetchWorktrees();
    // Refresh epics, tasks, and worktrees every 10 seconds
    setInterval(() => {
        fetchEpics();
        fetchTasks();
        fetchWorktrees();
    }, 10000);
});
