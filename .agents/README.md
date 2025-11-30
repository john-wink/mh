# Manhunt Agent Orchestrator

Multi-Agent Development System for the Manhunt SaaS Platform.

## Overview

This orchestrator manages a team of 30 AI agents (Claude models) that work collaboratively to build the Manhunt platform. Each agent has specific roles, expertise, and capabilities, mimicking a real development team.

## Architecture

```
.agents/
├── orchestrator/          # Main orchestrator logic
│   └── index.ts          # CLI entry point
├── lib/                   # Core libraries
│   ├── config.ts         # Configuration management
│   ├── agent.ts          # Agent class
│   └── task-parser.ts    # Parse sprint plans
├── runtime/               # Runtime components
│   ├── agent-manager.ts  # Manage agent lifecycle
│   ├── scheduler.ts      # Task scheduling
│   ├── monitoring.ts     # Progress tracking
│   └── collaboration.ts  # Inter-agent communication
├── dashboard/             # Web dashboard
│   ├── server.ts         # Dashboard server
│   └── public/           # Static assets
│       ├── index.html    # Dashboard UI
│       └── dashboard.js  # Client-side logic
├── config/                # Configuration files
│   ├── agents.json       # Agent definitions (30 agents)
│   └── orchestrator.json # Orchestrator settings
└── package.json
```

## Setup

1. Install dependencies:
```bash
cd .agents
npm install
```

2. Configure environment:
```bash
cp .env.example .env
# Edit .env and add your ANTHROPIC_API_KEY
```

3. Verify configuration:
```bash
npm run start -- agents
```

## Usage

### Start Dashboard (Recommended)
```bash
npm run dashboard
```

Then open http://localhost:3000 in your browser to see:
- Real-time agent status
- Team progress
- Task assignments
- Sprint metrics

### List all agents
```bash
npm run start -- agents
```

### Show current sprint status
```bash
npm run start -- status
```

### Start sprint execution (dry run)
```bash
npm run start -- start --dry-run
```

### Execute current sprint
```bash
npm run start -- start
```

### Execute specific sprint
```bash
npm run start -- start --sprint 2
```

## Agent Teams

### Team 1: Core Platform (4 agents)
- **team-1-lead**: Senior Laravel Developer (Sonnet 4.5)
  - Expertise: Multi-Tenancy, Laravel Architecture, API Design
  - Capacity: 25 SP/sprint

- **team-1-backend-1**: Mid-Level Backend Developer (Sonnet 4.5)
  - Expertise: Laravel Models, Migrations, Eloquent
  - Capacity: 20 SP/sprint

- **team-1-backend-2**: Mid-Level Backend Developer (Sonnet 4.5)
  - Expertise: Authentication, Authorization, Security
  - Capacity: 20 SP/sprint

- **team-1-junior**: Junior Developer (Haiku 4)
  - Expertise: Testing, Documentation, Bug Fixes
  - Capacity: 15 SP/sprint

### Team 2: GPS & Tracking (1 lead agent, more to be added)
- **team-2-lead**: Senior Backend Developer (Opus 4)
  - Expertise: Algorithms, Geo-Data, PostGIS, GPS-Fusion, ML
  - Capacity: 25 SP/sprint

### Special Agents
- **code-review-agent**: Code Review Specialist (Sonnet 4.5)
  - Reviews all code changes
  - No sprint capacity limit

- **orchestrator**: Project Manager / Tech Lead (Sonnet 4.5)
  - Manages task assignment
  - Handles cross-team dependencies
  - Escalates blockers

## How It Works

1. **Task Parsing**: Reads sprint plans from `.board/team-*/sprint-plan.md`
2. **Task Assignment**: Assigns tasks to agents based on:
   - Team membership
   - Current workload
   - Expertise match
   - Dependencies
3. **Execution**: Agents execute tasks using Claude API with:
   - File read/write capabilities
   - Bash command execution
   - Code generation and testing
4. **Monitoring**: Tracks progress, velocity, and blockers
5. **Collaboration**: Agents communicate via MCP protocol

## Configuration

### Orchestrator Settings (`config/orchestrator.json`)

- **maxConcurrentAgents**: Maximum agents working simultaneously (28)
- **autoAssignTasks**: Automatically assign tasks to agents (true)
- **requireHumanApproval**: Settings for human approval requirements
  - Architecture changes: true
  - Breaking changes: true
  - Security changes: true
  - Database migrations: false
- **codeReview**: Code review settings
  - Enabled: true
  - Minimum approvals: 1
  - Reviewer: code-review-agent
- **testing**: Testing requirements
  - Require tests: true
  - Minimum coverage: 90%
  - Run tests before merge: true

### Agent Definitions (`config/agents.json`)

Each agent has:
- **id**: Unique identifier
- **team**: Team number (1-9) or null
- **name**: Display name
- **role**: Role description
- **expertise**: Array of expertise areas
- **capabilities**: What the agent can do
- **maxConcurrentTasks**: Max parallel tasks
- **sprintCapacity**: Story points per sprint
- **reportsTo**: Team lead ID
- **modelPreference**: Claude model to use
- **temperature**: Model temperature (0.1-0.5)

## Development Workflow

1. **Sprint Planning**: Tasks are defined in `.board/team-*/sprint-plan.md`
2. **Sprint Start**: Run `npm run start -- start`
3. **Task Assignment**: Orchestrator assigns tasks to agents
4. **Execution**: Agents work on tasks in parallel
5. **Code Review**: code-review-agent reviews all changes
6. **Testing**: Agents write and run tests (90% coverage required)
7. **Monitoring**: Monitor tracks progress and alerts on blockers
8. **Sprint End**: Generate sprint report

## Monitoring

The orchestrator provides real-time monitoring:

- **Overall Progress**: Total SP completed vs planned
- **Team Progress**: Per-team velocity tracking
- **Agent Status**: What each agent is working on
- **Blockers**: Tasks blocked by dependencies or issues
- **Velocity**: Story points per day/sprint

## Inter-Agent Communication

Agents can:
- **Request Help**: Ask other agents for information
- **Report Blockers**: Escalate issues to team lead or orchestrator
- **Handoff Tasks**: Transfer tasks to more suitable agents
- **Share Info**: Broadcast information to the team

## Human Approval

Certain actions require human approval:
- Architecture changes
- Breaking changes
- Security-related changes

The orchestrator will pause and request approval before proceeding.

## Future Enhancements

- [ ] Web dashboard for real-time monitoring
- [ ] Slack integration for daily standups
- [ ] GitHub integration for automated PRs
- [ ] Machine learning for better task assignment
- [ ] Automatic sprint retrospectives
- [ ] Performance prediction and optimization

## License

MIT
