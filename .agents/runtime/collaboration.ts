import type { Agent } from '../lib/agent.js';
import type { Task } from '../lib/agent.js';

export interface Message {
  id: string;
  from: string;
  to: string;
  subject: string;
  content: string;
  timestamp: Date;
  type: 'question' | 'answer' | 'blocker' | 'handoff' | 'info';
  relatedTask?: string;
}

export class Collaboration {
  private messages: Message[] = [];

  sendMessage(message: Omit<Message, 'id' | 'timestamp'>): Message {
    const fullMessage: Message = {
      ...message,
      id: this.generateMessageId(),
      timestamp: new Date(),
    };

    this.messages.push(fullMessage);
    return fullMessage;
  }

  getMessagesFor(agentId: string): Message[] {
    return this.messages.filter((msg) => msg.to === agentId);
  }

  getMessagesBetween(agentId1: string, agentId2: string): Message[] {
    return this.messages.filter(
      (msg) =>
        (msg.from === agentId1 && msg.to === agentId2) ||
        (msg.from === agentId2 && msg.to === agentId1)
    );
  }

  getMessagesAboutTask(taskId: string): Message[] {
    return this.messages.filter((msg) => msg.relatedTask === taskId);
  }

  markAsRead(messageId: string): void {
    // In a real implementation, we'd track read status
    // For now, this is a placeholder
  }

  private generateMessageId(): string {
    return `msg-${Date.now()}-${Math.random().toString(36).substring(7)}`;
  }

  /**
   * Request information or assistance from another agent
   */
  requestHelp(
    from: Agent,
    to: Agent,
    task: Task,
    question: string
  ): Message {
    return this.sendMessage({
      from: from.id,
      to: to.id,
      subject: `Help needed: ${task.id}`,
      content: question,
      type: 'question',
      relatedTask: task.id,
    });
  }

  /**
   * Report a blocker that prevents task completion
   */
  reportBlocker(
    from: Agent,
    task: Task,
    blocker: string,
    needsHumanIntervention: boolean = false
  ): Message {
    const to = needsHumanIntervention ? 'orchestrator' : 'team-lead';

    return this.sendMessage({
      from: from.id,
      to,
      subject: `Blocker: ${task.id}`,
      content: blocker,
      type: 'blocker',
      relatedTask: task.id,
    });
  }

  /**
   * Hand off a task to another agent
   */
  handoffTask(
    from: Agent,
    to: Agent,
    task: Task,
    reason: string
  ): Message {
    return this.sendMessage({
      from: from.id,
      to: to.id,
      subject: `Task handoff: ${task.id}`,
      content: reason,
      type: 'handoff',
      relatedTask: task.id,
    });
  }

  /**
   * Share information with the team
   */
  shareInfo(
    from: Agent,
    to: string,
    subject: string,
    content: string,
    relatedTask?: string
  ): Message {
    return this.sendMessage({
      from: from.id,
      to,
      subject,
      content,
      type: 'info',
      relatedTask,
    });
  }

  /**
   * Get all unresolved blockers
   */
  getActiveBlockers(): Message[] {
    return this.messages.filter(
      (msg) => msg.type === 'blocker'
      // In a real implementation, we'd filter out resolved blockers
    );
  }

  /**
   * Get all pending questions for an agent
   */
  getPendingQuestions(agentId: string): Message[] {
    return this.messages.filter(
      (msg) => msg.to === agentId && msg.type === 'question'
      // In a real implementation, we'd filter out answered questions
    );
  }
}
