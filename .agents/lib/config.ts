import { readFile } from 'fs/promises';
import { join } from 'path';
import { z } from 'zod';

const AgentCapabilitiesSchema = z.object({
  codeGeneration: z.boolean(),
  codeReview: z.boolean(),
  architectureDesign: z.boolean(),
  testing: z.boolean(),
  documentation: z.boolean(),
});

const AgentSchema = z.object({
  id: z.string(),
  team: z.number().nullable(),
  name: z.string(),
  role: z.string(),
  expertise: z.array(z.string()),
  capabilities: AgentCapabilitiesSchema,
  maxConcurrentTasks: z.number(),
  sprintCapacity: z.number().nullable(),
  reportsTo: z.string().optional(),
  workingDirectory: z.string(),
  boardDirectory: z.string(),
  modelPreference: z.string(),
  temperature: z.number(),
});

const OrchestratorConfigSchema = z.object({
  orchestrator: z.object({
    name: z.string(),
    version: z.string(),
    currentSprint: z.number(),
    sprintDuration: z.string(),
    projectRoot: z.string(),
    boardDirectory: z.string(),
    agentDirectory: z.string(),
  }),
  settings: z.object({
    maxConcurrentAgents: z.number(),
    autoAssignTasks: z.boolean(),
    requireHumanApproval: z.object({
      architectureChanges: z.boolean(),
      breakingChanges: z.boolean().optional(),
      securityChanges: z.boolean(),
      databaseMigrations: z.boolean(),
    }),
    codeReview: z.object({
      enabled: z.boolean(),
      automaticApproval: z.boolean(),
      minimumApprovals: z.number(),
      reviewerAgent: z.string(),
    }),
    testing: z.object({
      requireTests: z.boolean(),
      minimumCoverage: z.number(),
      runTestsBeforeMerge: z.boolean(),
    }),
    dailyStandup: z.object({
      enabled: z.boolean(),
      time: z.string(),
      timezone: z.string(),
      slackChannel: z.string(),
    }),
  }),
  monitoring: z.object({
    trackVelocity: z.boolean(),
    trackQuality: z.boolean(),
    alertOnBlockers: z.boolean(),
    dashboardPort: z.number(),
  }),
  collaboration: z.object({
    communicationProtocol: z.string(),
    sharedKnowledgeBase: z.string(),
    codeRepository: z.string(),
  }),
});

const AgentsConfigSchema = z.object({
  agents: z.array(AgentSchema),
});

export type Agent = z.infer<typeof AgentSchema>;
export type OrchestratorConfig = z.infer<typeof OrchestratorConfigSchema>;
export type AgentsConfig = z.infer<typeof AgentsConfigSchema>;

export interface Config {
  orchestrator: OrchestratorConfig['orchestrator'];
  settings: OrchestratorConfig['settings'];
  monitoring: OrchestratorConfig['monitoring'];
  collaboration: OrchestratorConfig['collaboration'];
  agents: Agent[];
}

export async function loadConfig(): Promise<Config> {
  const configDir = join(process.cwd(), 'config');

  // Load orchestrator config
  const orchestratorConfigRaw = await readFile(
    join(configDir, 'orchestrator.json'),
    'utf-8'
  );
  const orchestratorConfig = OrchestratorConfigSchema.parse(
    JSON.parse(orchestratorConfigRaw)
  );

  // Load agents config
  const agentsConfigRaw = await readFile(
    join(configDir, 'agents.json'),
    'utf-8'
  );
  const agentsConfig = AgentsConfigSchema.parse(JSON.parse(agentsConfigRaw));

  return {
    orchestrator: orchestratorConfig.orchestrator,
    settings: orchestratorConfig.settings,
    monitoring: orchestratorConfig.monitoring,
    collaboration: orchestratorConfig.collaboration,
    agents: agentsConfig.agents,
  };
}
