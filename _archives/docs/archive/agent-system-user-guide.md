# [ARCHIVED] MemberPress AI Agent System User Guide

> **Note:** This document has been archived as it has been superseded by the comprehensive `_1_AGENTIC_SYSTEMS_.md` file in the project root. Please refer to that document for current implementation details.

## Introduction

The MemberPress AI Agent System enables you to interact with your WordPress site and MemberPress installation using natural language commands. Through a team of specialized AI agents, you can create content, manage your system, enhance security, analyze performance, and handle MemberPress-specific tasks—all through simple conversation.

This guide explains how to use the agent system effectively, with examples for each agent type.

## Getting Started

### Accessing the Agent System

The AI agents are accessible from anywhere in your WordPress admin through the chat bubble in the corner of your screen. Simply click the chat bubble to open the chat interface and start communicating with the agents.

### Basic Interaction

To use the agent system:

1. Click the chat bubble icon to open the chat interface
2. Type your request in natural language
3. The system will automatically determine which agent is best suited to handle your request
4. The appropriate agent will process your request and provide a response

You don't need to specify which agent you want to use—the system handles this automatically based on your request.

## Available Agents

### Content Agent

The Content Agent helps you create and manage website content.

**Capabilities:**
- Creating blog posts and pages
- Editing existing content
- Optimizing content for SEO
- Suggesting content topics

**Example requests:**
- "Write a blog post about the benefits of membership sites"
- "Create a new page about our refund policy"
- "Optimize my latest post for SEO with focus on 'recurring membership'"
- "Suggest 5 blog post ideas about membership strategies"

### System Agent

The System Agent helps you manage your WordPress installation.

**Capabilities:**
- Running WP-CLI commands
- Updating plugins and themes
- Managing backups
- Checking system status

**Example requests:**
- "Update all my plugins"
- "Show me a list of installed plugins"
- "Check if WordPress core is up to date"
- "Run a system health check"

### Security Agent

The Security Agent helps you maintain and enhance your site's security.

**Capabilities:**
- Performing security audits
- Identifying vulnerabilities
- Suggesting security improvements
- Reviewing permissions

**Example requests:**
- "Run a security scan on my site"
- "Check for plugin vulnerabilities"
- "Suggest ways to improve my site security"
- "Review user role permissions"

### Analytics Agent

The Analytics Agent helps you understand your site's performance.

**Capabilities:**
- Generating performance reports
- Analyzing user behavior
- Tracking conversion metrics
- Recommending optimizations

**Example requests:**
- "Show me membership sign-ups for the last month"
- "Create a report on my most popular content"
- "Analyze conversion rates for my pricing page"
- "What's my current membership renewal rate?"

### MemberPress Agent

The MemberPress Agent helps you manage your membership site.

**Capabilities:**
- Managing membership levels
- Handling transactions and subscriptions
- Working with membership rules
- Configuring MemberPress settings

**Example requests:**
- "Create a new membership level called 'Premium Annual'"
- "Show me recent transactions"
- "How many active subscribers do I have?"
- "Set up a coupon code for 20% off the Gold membership"

## Advanced Usage

### Complex Multi-Step Tasks

For complex tasks, the system will break them down into multiple steps and may ask clarifying questions.

**Example:**
```
You: "Create a new membership funnel with a landing page, thank you page, and follow-up email"

AI: "I'll help you create a membership funnel. Let's start by defining the membership details:

1. What should we call this membership level?
2. What will be the price point?
3. Will this be a recurring or one-time payment?

Once we have these details, I'll create the landing page, thank you page, and set up the follow-up email."
```

### Providing Additional Context

To help the agents work more effectively, you can provide additional context in your requests.

**Examples:**
- "Update the 'Gold Membership' page to include the new features we discussed last week"
- "Create a blog post about membership benefits targeting small business owners"
- "Set up a special offer for existing members who want to upgrade"

### Long-Running Tasks

Some tasks (like security audits or bulk updates) may take time to complete. For these tasks:

1. The system will acknowledge your request and provide a task ID
2. You can check the status of the task by asking "What's the status of my task?"
3. When the task is complete, you'll see the results in the chat

## Tips for Effective Use

### Be Specific

The more specific your request, the better the results. Compare:

- Less effective: "Create a blog post"
- More effective: "Create a 1000-word blog post about increasing membership renewals, targeting existing customers, with sections on value reminders, exclusive benefits, and early renewal incentives"

### Review and Refine

For content creation, always review the generated content and ask for refinements:

- "Make the intro more engaging"
- "Add more specific examples in the middle section"
- "Include more statistics about membership retention"

### Use Built-in Examples

If you're not sure what to ask, click on the "Examples" button in the chat interface to see suggestions for each agent.

## Customizing the Agent System

### Agent Settings

You can customize the behavior of agents through the Agent Settings page:

1. Go to MemberPress > AI Assistant > Agent Settings
2. Configure general settings that apply to all agents
3. Customize settings for individual agents
4. Save your preferences

### Permissions and Security

The agent system respects WordPress user roles:

- **Administrators** have full access to all agent capabilities
- **Editors** can use content-related features but have limited system access
- **Authors** can only use content creation features
- **Contributors and below** have no access to the agent system

## Troubleshooting

### Common Issues

**Issue:** Agent doesn't understand my request
**Solution:** Try rephrasing or being more specific about what you want to accomplish

**Issue:** Security agent can't perform certain actions
**Solution:** Some security actions require direct admin approval for safety reasons

**Issue:** Content creation seems limited
**Solution:** Check your OpenAI API usage limits and settings

### Getting Help

If you encounter any issues with the agent system:

1. Check the detailed logs at MemberPress > AI Assistant > Logs
2. Review the error message for specific guidance
3. Visit our knowledge base at [support.memberpress.com](https://support.memberpress.com)
4. Contact support if issues persist

## FAQ

**Q: Do I need a separate OpenAI account?**
A: Yes, the agent system uses your OpenAI API key, which you can enter in the plugin settings.

**Q: Is there a limit to how many requests I can make?**
A: The system uses your OpenAI account, so limits depend on your OpenAI subscription tier.

**Q: Will the agents modify my site without permission?**
A: For significant changes like plugin updates or content publishing, the agents will ask for confirmation before proceeding.

**Q: Can I use the agents for non-MemberPress tasks?**
A: Yes, many capabilities work with general WordPress tasks as well.

**Q: Are my conversations with the AI private?**
A: Yes, conversations are stored only on your server and are not shared with external services beyond the necessary API calls to OpenAI.

## Best Practices

1. **Start simple** - Begin with basic requests until you get comfortable with how the agents work
2. **Use regularly** - The more you use the system, the more it learns your preferences
3. **Provide feedback** - When an agent does something particularly helpful or unhelpful, let the system know
4. **Be security-conscious** - While the agents have safeguards, always review security recommendations before implementing them
5. **Check results** - Always verify the information and content produced by the agents

## Agent Capability Reference

| Task | Content | System | Security | Analytics | MemberPress |
|------|---------|--------|----------|-----------|-------------|
| Create blog post | ✓ | | | | |
| Create page | ✓ | | | | |
| Edit content | ✓ | | | | |
| SEO optimization | ✓ | | | ✓ | |
| Plugin management | | ✓ | | | |
| WP-CLI commands | | ✓ | | | |
| Security scans | | | ✓ | | |
| Vulnerability checks | | | ✓ | | |
| Performance reports | | | | ✓ | |
| User tracking | | | | ✓ | |
| Membership management | | | | | ✓ |
| Subscription handling | | | | | ✓ |
| Transaction processing | | | | | ✓ |
| Rule configuration | | | | | ✓ |