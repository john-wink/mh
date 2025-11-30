#!/usr/bin/env node
import { Command } from 'commander';
import chalk from 'chalk';
import { loadConfig } from '../lib/config.js';
import { TaskParser } from '../lib/task-parser.js';
import { AgentManager } from '../runtime/agent-manager.js';
import { Scheduler } from '../runtime/scheduler.js';
import { Monitor } from '../runtime/monitoring.js';

const program = new Command();

program
  .name('bountyops-orchestrator')
  .description('Multi-Agent Development Orchestrator for BountyOps SaaS')
  .version('1.0.0');

program
  .command('start')
  .description('Start the orchestrator for the current sprint')
  .option('-s, --sprint <number>', 'Sprint number to execute', '1')
  .option('-d, --dry-run', 'Simulate without actually spawning agents', false)
  .action(async (options) => {
    console.log(chalk.blue.bold('\n🚀 BountyOps Agent Orchestrator\n'));

    try {
      const config = await loadConfig();
      const sprint = parseInt(options.sprint) || config.orchestrator.currentSprint;

      console.log(chalk.gray(`Sprint: ${sprint}`));
      console.log(chalk.gray(`Board Directory: ${config.orchestrator.boardDirectory}\n`));

      // Parse tasks from sprint plans
      const taskParser = new TaskParser(config.orchestrator.boardDirectory);
      const tasks = await taskParser.parseSprintTasks(sprint);

      console.log(chalk.green(`✓ Parsed ${tasks.length} tasks from sprint ${sprint}`));

      // Initialize agent manager
      const agentManager = new AgentManager(config);

      // Initialize scheduler
      const scheduler = new Scheduler(config, agentManager);

      // Initialize monitor
      const monitor = new Monitor(config);

      if (options.dryRun) {
        console.log(chalk.yellow('\n⚠️  DRY RUN MODE - No agents will be spawned\n'));
        scheduler.simulate(tasks);
        return;
      }

      // Start sprint execution
      console.log(chalk.blue('\n🏃 Starting sprint execution...\n'));
      await scheduler.execute(sprint, tasks);

      // Monitor progress
      await monitor.track();

    } catch (error) {
      console.error(chalk.red(`\n❌ Error: ${error.message}\n`));
      process.exit(1);
    }
  });

program
  .command('status')
  .description('Show current sprint status and agent progress')
  .action(async () => {
    try {
      const config = await loadConfig();
      const monitor = new Monitor(config);
      await monitor.showStatus();
    } catch (error) {
      console.error(chalk.red(`\n❌ Error: ${error.message}\n`));
      process.exit(1);
    }
  });

program
  .command('agents')
  .description('List all configured agents')
  .action(async () => {
    try {
      const config = await loadConfig();
      console.log(chalk.blue.bold('\n👥 Configured Agents\n'));

      config.agents.forEach((agent) => {
        console.log(chalk.bold(`\n${agent.name}`));
        console.log(chalk.gray(`  ID: ${agent.id}`));
        console.log(chalk.gray(`  Team: ${agent.team || 'N/A'}`));
        console.log(chalk.gray(`  Role: ${agent.role}`));
        console.log(chalk.gray(`  Model: ${agent.modelPreference}`));
        console.log(chalk.gray(`  Sprint Capacity: ${agent.sprintCapacity || 'N/A'} SP`));
        console.log(chalk.gray(`  Max Concurrent Tasks: ${agent.maxConcurrentTasks}`));
        console.log(chalk.gray(`  Expertise: ${agent.expertise.join(', ')}`));
      });

      console.log('\n');
    } catch (error) {
      console.error(chalk.red(`\n❌ Error: ${error.message}\n`));
      process.exit(1);
    }
  });

program.parse();
