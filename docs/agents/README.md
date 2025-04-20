# Agent System Documentation

The Agent System is a core component of the MemberPress AI Assistant plugin that provides specialized AI capabilities for different types of tasks. This documentation provides a comprehensive overview of the agent system, its architecture, and how to use and extend it.

## Contents

1. [Agent System Overview](./overview.md)
2. [Agent Orchestrator](./agent-orchestrator.md)
3. [Base Agent Class](./base-agent.md)
4. [Specialized Agents](./specialized-agents.md)
5. [Agent Specialization Scoring](./agent-specialization-scoring.md)
6. [Creating Custom Agents](./custom-agents.md)
7. [Agent Security](./agent-security.md)
8. [Troubleshooting](./troubleshooting.md)

## Agent System Overview

The Agent System allows the AI assistant to provide specialized capabilities for different types of tasks. It consists of several components:

- **Agent Orchestrator**: Routes requests to the appropriate agent based on the user's intent
- **Base Agent Class**: Provides a common interface for all agents
- **Specialized Agents**: Agents that provide specialized capabilities for specific tasks
- **Agent Specialization Scoring**: Determines which agent is best suited for a given request

## Agent Orchestrator

The Agent Orchestrator is responsible for routing requests to the appropriate agent based on the user's intent. It analyzes the user's message, determines the intent, and dispatches the request to the agent that is best suited to handle it.

[Learn more about the Agent Orchestrator](./agent-orchestrator.md)

## Specialized Agents

The MemberPress AI Assistant comes with several built-in specialized agents:

### MemberPress Agent

The MemberPress Agent provides specialized capabilities for MemberPress-related tasks, such as managing memberships, transactions, and subscriptions. It is implemented in the `MPAI_MemberPress_Agent` class.

[Learn more about the MemberPress Agent](./memberpress-agent.md)

### Command Validation Agent

The Command Validation Agent validates commands before execution to prevent errors and improve user experience. It is implemented in the `MPAI_Command_Validation_Agent` class.

[Learn more about the Command Validation Agent](./command-validation-agent.md)

## Agent Specialization Scoring

The Agent Specialization Scoring system determines which agent is best suited for a given request. It analyzes the user's message and assigns a score to each agent based on how well it matches the agent's specialization.

[Learn more about Agent Specialization Scoring](./agent-specialization-scoring.md)

## Creating Custom Agents

You can create custom agents to provide specialized AI capabilities for specific tasks. Custom agents must extend the `MPAI_Base_Agent` class and implement the required methods.

[Learn more about Creating Custom Agents](./custom-agents.md)

## Agent Security

The Agent System includes several security measures to prevent misuse:

- Command validation to prevent execution of dangerous commands
- Permission checks to ensure only authorized users can execute agents
- Rate limiting to prevent abuse
- Logging of all agent executions

[Learn more about Agent Security](./agent-security.md)

## Troubleshooting

If you encounter issues with the Agent System, check the [Troubleshooting](./troubleshooting.md) guide for common problems and their solutions.