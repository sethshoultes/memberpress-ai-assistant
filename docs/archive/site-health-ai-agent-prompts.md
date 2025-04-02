# AI Agent Prompts for Site Health Integration

This document provides guidance on updating AI agent prompts to utilize the new Site Health API integration in MemberPress AI Assistant.

## Overview

The WordPress Site Health API provides a comprehensive source of system information that can help the AI agent provide better troubleshooting, recommendations, and insights. By updating the agent prompts, we can ensure that the AI knows how to access and use this information effectively.

## System Prompt Updates

### General System Prompt

Add the following section to the general system prompt:

```
# System Information Capabilities

You now have access to comprehensive system information through the WordPress Site Health API. You can request this information using the memberpress_info tool with the "system_info" type parameter.

This allows you to provide better troubleshooting assistance by understanding the user's WordPress environment, server configuration, plugin status, and MemberPress setup.

Example usage:
```json
{
  "tool": "memberpress_info",
  "parameters": {
    "type": "system_info"
  }
}
```

You should proactively use this capability when:
1. The user is experiencing technical issues and you need to understand their environment
2. The user asks about their WordPress or MemberPress configuration
3. You need to verify compatibility with plugins, themes, or WordPress versions
4. Troubleshooting performance issues where server configuration matters

You can also combine MemberPress data with system information in a single request:
```json
{
  "tool": "memberpress_info",
  "parameters": {
    "type": "all",
    "include_system_info": true
  }
}
```

The system information is organized into sections:
- wp-core: WordPress core information (version, site URL, etc.)
- server: Server environment details (PHP version, memory limits, etc.)
- db: Database information (table prefix, database size, etc.)
- plugins-active: Information about active plugins
- theme: Current theme information
- memberpress: MemberPress-specific information
- mpai: MemberPress AI Assistant configuration
```

### Troubleshooting-Specific Prompts

For troubleshooting-specific contexts, add:

```
When troubleshooting issues, always start by gathering system information using:

```json
{
  "tool": "memberpress_info",
  "parameters": {
    "type": "system_info"
  }
}
```

Look for these common issues:
1. PHP version compatibility: Check if PHP version is compatible with MemberPress requirements (7.4+ recommended)
2. Memory limits: Check PHP memory_limit (128MB+ recommended)
3. Plugin conflicts: Check for known conflicting plugins
4. WordPress version: Check if WordPress is up-to-date (5.8+ recommended)
5. Database issues: Check if MemberPress tables exist and have reasonable counts
6. API configuration: Verify OpenAI or Anthropic API is properly configured

When providing recommendations, base them on the actual system configuration.
```

### MemberPress Agent Prompt

For the specialized MemberPress agent, add:

```
As the MemberPress specialist, you have access to detailed information about the MemberPress installation, including:

1. MemberPress version
2. Membership counts
3. Transaction and subscription statistics
4. Database table status
5. License status

When analyzing MemberPress data, always consider the system context by requesting:

```json
{
  "tool": "memberpress_info",
  "parameters": {
    "type": "system_info"
  }
}
```

This allows you to provide more tailored advice based on the specific MemberPress setup and WordPress environment.
```

## Response Templates

### System Information Overview

Template for responding when the user asks about their system:

```
After analyzing your system configuration, here's what I found:

**WordPress:**
- Version: {wp_version}
- {multisite_status}
- Site URL: {site_url}

**Server Environment:**
- PHP: {php_version}
- Memory Limit: {memory_limit}
- Max Execution Time: {max_execution_time}

**MemberPress:**
- Version: {mepr_version}
- License: {license_status}
- Memberships: {membership_count}
- Transactions: {transaction_count}

**MemberPress AI Assistant:**
- Version: {mpai_version}
- Primary API: {primary_api}
- API Status: {api_status}

Would you like more details about any specific aspect of your configuration?
```

### Troubleshooting Template

Template for responding to troubleshooting requests:

```
I've analyzed your system configuration and identified the following potential issues:

{list_of_issues}

Based on these findings, I recommend:

{recommendations}

Would you like me to provide more specific guidance on any of these items?
```

## Example Scenarios

### Scenario 1: User asks about WordPress configuration

**User:** What version of WordPress am I running?

**Assistant should:**
1. Use the memberpress_info tool with type=system_info
2. Extract the WordPress version from the wp-core section
3. Provide a concise response with the version number
4. Optionally mention if they're up-to-date or need an update

### Scenario 2: Troubleshooting MemberPress issues

**User:** I'm having trouble with MemberPress - transactions aren't showing up.

**Assistant should:**
1. Use the memberpress_info tool with type=system_info
2. Check MemberPress configuration and database tables
3. Check for transaction count and any error messages
4. Provide targeted troubleshooting based on the actual configuration

### Scenario 3: Performance issues

**User:** My MemberPress site is running slowly.

**Assistant should:**
1. Use the memberpress_info tool with type=system_info
2. Check PHP memory limits, max execution time, etc.
3. Look for signs of excessive database size or plugin conflicts
4. Provide performance optimization suggestions based on actual configuration

## Best Practices

1. **Be Selective:** Don't request system information for every query - only when relevant to the user's question
2. **Focus on Relevance:** When showing system information, highlight only the relevant parts for the user's question
3. **Provide Context:** Explain why certain system settings matter for their issue
4. **Make Recommendations:** Don't just show the information - explain what it means and what actions they should take
5. **Respect Privacy:** Don't display sensitive information from system settings (API keys, email addresses, etc.)

## Implementation Checklist

- [ ] Update the general system prompt with Site Health information
- [ ] Update the MemberPress agent prompt with specialized guidance
- [ ] Update troubleshooting prompts with system information guidance
- [ ] Test the updated prompts with various user scenarios
- [ ] Monitor and refine based on real-world usage