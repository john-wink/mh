import { writeFile, readFile, mkdir } from 'fs/promises';
import { join } from 'path';
import { existsSync } from 'fs';

export interface TokenUsage {
  inputTokens: number;
  outputTokens: number;
  cachedTokens: number;
  timestamp: Date;
  model: string;
  agentId: string;
  taskId?: string;
}

export interface CostCalculation {
  inputCost: number;
  outputCost: number;
  cacheCost: number;
  totalCost: number;
  tokensSaved: number;
  costSaved: number;
}

export interface SpendingLimit {
  daily?: number;
  weekly?: number;
  monthly?: number;
  perTask?: number;
  perAgent?: number;
  total?: number;
}

// Pricing per 1M tokens (in USD)
const MODEL_PRICING = {
  'claude-opus-4': {
    input: 15.0,
    output: 75.0,
    cachedInput: 1.5, // 90% discount
  },
  'claude-sonnet-4-5': {
    input: 3.0,
    output: 15.0,
    cachedInput: 0.3, // 90% discount
  },
  'claude-haiku-4': {
    input: 0.25,
    output: 1.25,
    cachedInput: 0.025, // 90% discount
  },
};

export class CostTracker {
  private usage: TokenUsage[] = [];
  private storageDir: string;
  private limits: SpendingLimit;
  private alertCallbacks: Array<(message: string, current: number, limit: number) => void> = [];

  constructor(storageDir: string, limits: SpendingLimit = {}) {
    this.storageDir = storageDir;
    this.limits = limits;
    this.ensureStorageDir();
    this.loadUsage();
  }

  /**
   * Track token usage from an API call
   */
  async trackUsage(
    model: string,
    inputTokens: number,
    outputTokens: number,
    cachedTokens: number,
    agentId: string,
    taskId?: string
  ): Promise<CostCalculation> {
    const usage: TokenUsage = {
      inputTokens,
      outputTokens,
      cachedTokens,
      timestamp: new Date(),
      model,
      agentId,
      taskId,
    };

    this.usage.push(usage);

    // Calculate cost
    const cost = this.calculateCost(usage);

    // Save to disk
    await this.saveUsage();

    // Check limits
    await this.checkLimits();

    return cost;
  }

  /**
   * Calculate cost for a single usage
   */
  calculateCost(usage: TokenUsage): CostCalculation {
    const pricing = this.getPricing(usage.model);

    // Regular input tokens
    const regularInputTokens = Math.max(0, usage.inputTokens - usage.cachedTokens);

    // Costs
    const inputCost = (regularInputTokens / 1_000_000) * pricing.input;
    const cacheCost = (usage.cachedTokens / 1_000_000) * pricing.cachedInput;
    const outputCost = (usage.outputTokens / 1_000_000) * pricing.output;
    const totalCost = inputCost + cacheCost + outputCost;

    // Savings from cache
    const costWithoutCache = (usage.cachedTokens / 1_000_000) * pricing.input;
    const costSaved = costWithoutCache - cacheCost;

    return {
      inputCost,
      outputCost,
      cacheCost,
      totalCost,
      tokensSaved: usage.cachedTokens,
      costSaved,
    };
  }

  /**
   * Get total cost for a time period
   */
  getTotalCost(since?: Date): number {
    const relevantUsage = since
      ? this.usage.filter((u) => u.timestamp >= since)
      : this.usage;

    return relevantUsage.reduce((sum, usage) => {
      const cost = this.calculateCost(usage);
      return sum + cost.totalCost;
    }, 0);
  }

  /**
   * Get cost by agent
   */
  getCostByAgent(agentId: string, since?: Date): number {
    const agentUsage = this.usage.filter((u) => {
      const matchesAgent = u.agentId === agentId;
      const matchesTime = !since || u.timestamp >= since;
      return matchesAgent && matchesTime;
    });

    return agentUsage.reduce((sum, usage) => {
      const cost = this.calculateCost(usage);
      return sum + cost.totalCost;
    }, 0);
  }

  /**
   * Get cost by task
   */
  getCostByTask(taskId: string): number {
    const taskUsage = this.usage.filter((u) => u.taskId === taskId);

    return taskUsage.reduce((sum, usage) => {
      const cost = this.calculateCost(usage);
      return sum + cost.totalCost;
    }, 0);
  }

  /**
   * Get total tokens used
   */
  getTotalTokens(since?: Date): {
    input: number;
    output: number;
    cached: number;
    total: number;
  } {
    const relevantUsage = since
      ? this.usage.filter((u) => u.timestamp >= since)
      : this.usage;

    return relevantUsage.reduce(
      (totals, usage) => ({
        input: totals.input + usage.inputTokens,
        output: totals.output + usage.outputTokens,
        cached: totals.cached + usage.cachedTokens,
        total:
          totals.total +
          usage.inputTokens +
          usage.outputTokens,
      }),
      { input: 0, output: 0, cached: 0, total: 0 }
    );
  }

  /**
   * Get cache efficiency (percentage of tokens cached)
   */
  getCacheEfficiency(since?: Date): number {
    const tokens = this.getTotalTokens(since);
    if (tokens.input === 0) return 0;
    return (tokens.cached / tokens.input) * 100;
  }

  /**
   * Get total savings from cache
   */
  getCacheSavings(since?: Date): number {
    const relevantUsage = since
      ? this.usage.filter((u) => u.timestamp >= since)
      : this.usage;

    return relevantUsage.reduce((sum, usage) => {
      const cost = this.calculateCost(usage);
      return sum + cost.costSaved;
    }, 0);
  }

  /**
   * Check if spending limits are exceeded
   */
  async checkLimits(): Promise<void> {
    const now = new Date();

    // Daily limit
    if (this.limits.daily) {
      const startOfDay = new Date(now);
      startOfDay.setHours(0, 0, 0, 0);
      const dailyCost = this.getTotalCost(startOfDay);

      if (dailyCost >= this.limits.daily) {
        await this.triggerAlert(
          `Daily spending limit reached: $${dailyCost.toFixed(2)} / $${this.limits.daily}`,
          dailyCost,
          this.limits.daily
        );
        throw new Error(`Daily spending limit exceeded: $${this.limits.daily}`);
      }
    }

    // Weekly limit
    if (this.limits.weekly) {
      const startOfWeek = new Date(now);
      startOfWeek.setDate(now.getDate() - now.getDay());
      startOfWeek.setHours(0, 0, 0, 0);
      const weeklyCost = this.getTotalCost(startOfWeek);

      if (weeklyCost >= this.limits.weekly) {
        await this.triggerAlert(
          `Weekly spending limit reached: $${weeklyCost.toFixed(2)} / $${this.limits.weekly}`,
          weeklyCost,
          this.limits.weekly
        );
        throw new Error(`Weekly spending limit exceeded: $${this.limits.weekly}`);
      }
    }

    // Monthly limit
    if (this.limits.monthly) {
      const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
      const monthlyCost = this.getTotalCost(startOfMonth);

      if (monthlyCost >= this.limits.monthly) {
        await this.triggerAlert(
          `Monthly spending limit reached: $${monthlyCost.toFixed(2)} / $${this.limits.monthly}`,
          monthlyCost,
          this.limits.monthly
        );
        throw new Error(`Monthly spending limit exceeded: $${this.limits.monthly}`);
      }
    }

    // Total limit
    if (this.limits.total) {
      const totalCost = this.getTotalCost();

      if (totalCost >= this.limits.total) {
        await this.triggerAlert(
          `Total spending limit reached: $${totalCost.toFixed(2)} / $${this.limits.total}`,
          totalCost,
          this.limits.total
        );
        throw new Error(`Total spending limit exceeded: $${this.limits.total}`);
      }
    }
  }

  /**
   * Get cost summary
   */
  getSummary(since?: Date): {
    totalCost: number;
    totalTokens: { input: number; output: number; cached: number };
    cacheEfficiency: number;
    cacheSavings: number;
    averageCostPerRequest: number;
    requestCount: number;
  } {
    const relevantUsage = since
      ? this.usage.filter((u) => u.timestamp >= since)
      : this.usage;

    const totalCost = this.getTotalCost(since);
    const totalTokens = this.getTotalTokens(since);
    const cacheEfficiency = this.getCacheEfficiency(since);
    const cacheSavings = this.getCacheSavings(since);
    const requestCount = relevantUsage.length;
    const averageCostPerRequest = requestCount > 0 ? totalCost / requestCount : 0;

    return {
      totalCost,
      totalTokens,
      cacheEfficiency,
      cacheSavings,
      averageCostPerRequest,
      requestCount,
    };
  }

  /**
   * Register an alert callback
   */
  onAlert(callback: (message: string, current: number, limit: number) => void): void {
    this.alertCallbacks.push(callback);
  }

  /**
   * Update spending limits
   */
  updateLimits(limits: Partial<SpendingLimit>): void {
    this.limits = { ...this.limits, ...limits };
  }

  /**
   * Reset usage history
   */
  async reset(): Promise<void> {
    this.usage = [];
    await this.saveUsage();
  }

  /**
   * Export usage data
   */
  exportUsage(since?: Date): TokenUsage[] {
    return since ? this.usage.filter((u) => u.timestamp >= since) : [...this.usage];
  }

  // Private methods

  private getPricing(model: string): { input: number; output: number; cachedInput: number } {
    // Normalize model name
    const normalizedModel = model.includes('opus')
      ? 'claude-opus-4'
      : model.includes('sonnet')
      ? 'claude-sonnet-4-5'
      : 'claude-haiku-4';

    return MODEL_PRICING[normalizedModel] || MODEL_PRICING['claude-sonnet-4-5'];
  }

  private async ensureStorageDir(): Promise<void> {
    if (!existsSync(this.storageDir)) {
      await mkdir(this.storageDir, { recursive: true });
    }
  }

  private async saveUsage(): Promise<void> {
    const filePath = join(this.storageDir, 'cost-tracking.json');
    await writeFile(filePath, JSON.stringify(this.usage, null, 2), 'utf-8');
  }

  private async loadUsage(): Promise<void> {
    try {
      const filePath = join(this.storageDir, 'cost-tracking.json');
      if (existsSync(filePath)) {
        const data = await readFile(filePath, 'utf-8');
        this.usage = JSON.parse(data).map((u: any) => ({
          ...u,
          timestamp: new Date(u.timestamp),
        }));
      }
    } catch (error) {
      console.log('⚠️  Could not load usage history, starting fresh');
      this.usage = [];
    }
  }

  private async triggerAlert(message: string, current: number, limit: number): Promise<void> {
    console.log(`\n⚠️  SPENDING ALERT: ${message}\n`);

    for (const callback of this.alertCallbacks) {
      try {
        callback(message, current, limit);
      } catch (error) {
        console.error('Error in alert callback:', error);
      }
    }
  }
}
