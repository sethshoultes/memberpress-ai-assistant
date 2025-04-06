# MemberPress AI Assistant: Administrator Troubleshooting Guide

**Version:** 1.0.0  
**Last Updated:** 2025-04-06  
**Status:** 🚧 In Progress  
**Audience:** 🛠️ Administrators  
**Difficulty:** 🟡 Intermediate  
**Reading Time:** ⏱️ 20 minutes

## Overview

This troubleshooting guide provides solutions for common issues administrators may encounter when managing the MemberPress AI Assistant. It includes diagnostic procedures, common problems, and their resolutions, organized by category for easy reference.

## Table of Contents

1. [Diagnostic Tools](#diagnostic-tools)
2. [Installation & Activation Issues](#installation--activation-issues)
3. [Configuration Problems](#configuration-problems)
4. [AI Service Connection Issues](#ai-service-connection-issues)
5. [Performance Issues](#performance-issues)
6. [Data Access & Integration Problems](#data-access--integration-problems)
7. [User Permission Issues](#user-permission-issues)
8. [API Quota & Usage Concerns](#api-quota--usage-concerns)
9. [Database & Storage Issues](#database--storage-issues)
10. [Advanced Troubleshooting](#advanced-troubleshooting)
11. [Getting Additional Support](#getting-additional-support)

## Diagnostic Tools

### Built-in Diagnostics

The MemberPress AI Assistant includes several built-in diagnostic tools to help identify issues:

1. **Access the Diagnostic Tools**:
   - Navigate to **MemberPress → Settings → AI Assistant → Diagnostics**
   - Click "Run Diagnostics" to perform a complete system check

2. **Available Diagnostic Tests**:

   | Test | Function | Troubleshooting Value |
   |------|----------|------------------------|
   | System Compatibility | Verifies WordPress, PHP, and server requirements | Identifies environment issues |
   | API Connection | Tests connectivity to AI service providers | Diagnoses network or authentication problems |
   | Database Health | Checks database tables and indexes | Identifies data storage issues |
   | Cache Status | Validates cache configuration and operation | Helps resolve performance issues |
   | Permission Verification | Tests role-based access control | Diagnoses user access problems |

3. **Diagnostic Report**:
   - After running diagnostics, you'll receive a detailed report
   - Export the report by clicking "Download Diagnostic Report"
   - Include this report when contacting support

### Log Files

Access and analyze log files for detailed troubleshooting:

1. **Accessing Logs**:
   - Navigate to **MemberPress → Settings → AI Assistant → Logs**
   - Select log type: System, API, User, or Debug
   - Set date range and severity level

2. **Important Log Files**:

   | Log File | Location | Contains |
   |----------|----------|----------|
   | ai-assistant-system.log | wp-content/uploads/memberpress-ai/logs/ | General system events and errors |
   | ai-assistant-api.log | wp-content/uploads/memberpress-ai/logs/ | API communication details |
   | ai-assistant-user.log | wp-content/uploads/memberpress-ai/logs/ | User interaction events |
   | ai-assistant-debug.log | wp-content/uploads/memberpress-ai/logs/ | Detailed debugging information (when enabled) |

3. **Enabling Debug Mode**:
   - Navigate to **MemberPress → Settings → AI Assistant → Advanced → Debugging**
   - Set "Debug Mode" to "Enabled"
   - Choose debug level (Basic, Detailed, or Verbose)
   - Remember to disable debug mode after troubleshooting to prevent large log files

## Installation & Activation Issues

### Plugin Won't Install

**Symptoms**: Installation fails with error message or silently fails.

**Potential Causes and Solutions**:

1. **Insufficient Server Permissions**:
   - Ensure WordPress has proper file permissions (typically 755 for directories, 644 for files)
   - Contact your hosting provider if permission issues persist

2. **Memory Limit Reached**:
   - Temporarily increase PHP memory limit in wp-config.php:
     ```php
     define('WP_MEMORY_LIMIT', '256M');
     ```
   - After installation, return to previous setting if needed

3. **Incompatible WordPress Version**:
   - Upgrade WordPress to version 6.0 or higher
   - If you cannot upgrade WordPress, consider using an older version of the plugin (contact support)

4. **Plugin File Corrupted**:
   - Re-download the plugin from your MemberPress account
   - Verify the ZIP file integrity before installation

### Activation Errors

**Symptoms**: Plugin installs but fails to activate with error message.

**Potential Causes and Solutions**:

1. **Missing Dependencies**:
   - Verify MemberPress core plugin is installed and activated
   - Check PHP version (minimum 8.0 required)
   - Install any missing PHP extensions:
     - cURL
     - JSON
     - OpenSSL

2. **Plugin Conflict**:
   - Temporarily deactivate all other plugins
   - Activate MemberPress AI Assistant
   - Re-activate other plugins one by one to identify the conflict

3. **Database Table Creation Failed**:
   - Check database permissions
   - Verify WordPress database user has CREATE TABLE privileges
   - Run manual table creation (contact support for SQL scripts)

### License Validation Issues

**Symptoms**: License activation fails or plugin shows "Invalid License" message.

**Potential Causes and Solutions**:

1. **Incorrect License Key**:
   - Verify license key is entered correctly (check for typos or extra spaces)
   - Copy and paste the license key directly from your MemberPress account

2. **License Usage Limit Reached**:
   - Check license usage in your MemberPress account
   - Deactivate license on unused sites
   - Upgrade to a license with more sites if needed

3. **Connection to License Server Failed**:
   - Verify your site can reach api.memberpress.com
   - Check if your server blocks outgoing connections
   - Temporarily disable firewall or security plugins

## Configuration Problems

### Settings Not Saving

**Symptoms**: Configuration changes don't persist after saving.

**Potential Causes and Solutions**:

1. **WordPress Nonce Issues**:
   - Clear browser cache and cookies
   - Try a different browser
   - Verify WordPress nonce configuration in wp-config.php

2. **Database Write Permissions**:
   - Check database user has UPDATE privileges
   - Verify wp_options table is not corrupted
   - Run `REPAIR TABLE wp_options` via phpMyAdmin if needed

3. **Object Cache Issues**:
   - If using object caching (Redis, Memcached), flush the cache
   - Temporarily disable object cache plugins
   - Add this to wp-config.php for troubleshooting:
     ```php
     define('WP_CACHE', false);
     ```

### Plugin Conflict with Configuration

**Symptoms**: Settings interface doesn't load correctly or functions improperly.

**Potential Causes and Solutions**:

1. **JavaScript Conflicts**:
   - Open browser developer console (F12) to check for JavaScript errors
   - Temporarily disable JavaScript optimization plugins
   - Try in different browsers

2. **Admin Page Hooks**:
   - Identify plugins that modify admin pages
   - Disable admin enhancement plugins
   - Check for plugins using the same settings page hooks

3. **Diagnostic Steps**:
   - Enable a default WordPress theme temporarily
   - Create a test admin user with a clean profile
   - Access settings with the test user

## AI Service Connection Issues

### Cannot Connect to AI Service

**Symptoms**: AI Assistant shows "Connection Failed" or doesn't respond to queries.

**Potential Causes and Solutions**:

1. **API Key Issues**:
   - Verify API key is entered correctly
   - Check if API key has expired or been revoked
   - Generate a new API key from the service provider

2. **Network Connectivity**:
   - Verify outbound connections aren't blocked
   - Check if your hosting provider blocks API connections
   - Try configuring an alternative endpoint URL (if available)

3. **Rate Limiting**:
   - Check if you've hit rate limits with the AI provider
   - Implement request throttling in settings
   - Consider upgrading to a higher API tier

### Connection Timeouts

**Symptoms**: Requests take too long and eventually fail with timeout errors.

**Potential Causes and Solutions**:

1. **Server Timeout Settings**:
   - Increase PHP max_execution_time in php.ini or .htaccess:
     ```
     max_execution_time = 120
     ```
   - Adjust timeout settings in the plugin configuration
   - Configure longer timeouts for complex queries

2. **Slow Network Connection**:
   - Check network latency to AI service endpoints
   - Optimize your server's DNS resolution
   - Consider a hosting provider with better connectivity

3. **Provider Service Issues**:
   - Check AI service status page for outages
   - Switch to alternative AI provider temporarily
   - Implement fallback provider in configuration

### Authentication Failures

**Symptoms**: Requests fail with authentication or authorization errors.

**Potential Causes and Solutions**:

1. **Invalid Credentials**:
   - Regenerate API keys and update configuration
   - Check if account billing is current
   - Verify correct API access tier is configured

2. **Clock Synchronization**:
   - Ensure server time is correct (API authentication often uses timestamps)
   - Configure NTP on your server
   - Check for timezone configuration issues

3. **IP Restrictions**:
   - Verify your server IP isn't blocked by the AI provider
   - Check if your AI service has geographic restrictions
   - Configure IP allowlisting with the provider if needed

## Performance Issues

### Slow Response Times

**Symptoms**: AI responses take more than 5 seconds to generate.

**Potential Causes and Solutions**:

1. **Insufficient Caching**:
   - Enable or optimize the response cache
   - Configure higher cache duration for stable content
   - Implement query similarity matching in cache config

2. **Resource Constraints**:
   - Increase PHP memory limit
   - Optimize database with proper indexes
   - Consider upgrading hosting resources

3. **AI Service Performance**:
   - Check if you're using the most efficient AI model
   - Reduce max tokens in configuration
   - Consider a faster (though possibly less sophisticated) model

### High Server Load

**Symptoms**: Server becomes slow or unresponsive when AI Assistant is active.

**Potential Causes and Solutions**:

1. **Inefficient Queries**:
   - Optimize database indexes
   - Limit the scope of data searches
   - Implement query result caching

2. **Background Processing Issues**:
   - Configure data indexing during off-peak hours
   - Implement rate limiting for concurrent requests
   - Use transient API for state management instead of database

3. **Resource Optimization**:
   - Reduce the size of the context window
   - Implement efficient data preprocessing
   - Enable "Economy Mode" in advanced settings

### Memory Exhaustion

**Symptoms**: PHP memory limit errors appear in logs, processes terminate unexpectedly.

**Potential Causes and Solutions**:

1. **Excessive Data Loading**:
   - Limit data fetched for each request
   - Implement pagination for large datasets
   - Use chunked processing for large operations

2. **Memory Leak**:
   - Update to the latest plugin version
   - Apply available patches
   - Enable memory usage logging for identification

3. **Configuration Adjustments**:
   - Increase PHP memory limit if possible
   - Reduce max context size in settings
   - Limit simultaneous requests

## Data Access & Integration Problems

### Data Not Available to AI Assistant

**Symptoms**: AI provides incomplete or outdated information, or cannot access certain data.

**Potential Causes and Solutions**:

1. **Indexing Issues**:
   - Manually rebuild the knowledge index
   - Check for indexing errors in logs
   - Verify indexing completion status

2. **Permission Configuration**:
   - Check data access settings in AI configuration
   - Verify database user has SELECT permissions
   - Ensure proper integration with MemberPress core

3. **Custom Field Integration**:
   - Register custom fields for AI access
   - Check custom field mapping configuration
   - Verify metadata retrieval is working

### Inconsistent Data Results

**Symptoms**: AI provides different answers to the same questions, or incorrect information.

**Potential Causes and Solutions**:

1. **Cache Inconsistency**:
   - Clear the AI response cache
   - Configure proper cache invalidation rules
   - Verify cache storage integrity

2. **Data Synchronization Issues**:
   - Check for data update hooks
   - Implement synchronous data updates
   - Verify real-time data access is configured

3. **Context Window Limitations**:
   - Adjust context window size
   - Prioritize recent and relevant information
   - Implement context pruning strategies

### Integration with MemberPress Core Failing

**Symptoms**: AI cannot access membership data or integration features fail.

**Potential Causes and Solutions**:

1. **Version Compatibility**:
   - Ensure MemberPress core and AI Assistant versions are compatible
   - Update both plugins to latest versions
   - Check for integration patches

2. **Hook Registration Issues**:
   - Verify hooks and filters are registering properly
   - Check for hook priority conflicts
   - Implement manual hook registration

3. **Database Structure Changes**:
   - Update AI Assistant after MemberPress schema changes
   - Verify database prefix configurations match
   - Check for custom database modifications

## User Permission Issues

### Users Cannot Access AI Assistant

**Symptoms**: Users report they cannot see or access the AI Assistant interface.

**Potential Causes and Solutions**:

1. **Role Configuration**:
   - Verify user roles have access in AI Assistant permissions
   - Check WordPress capability assignment
   - Configure appropriate visibility settings

2. **JavaScript Loading Issues**:
   - Check for JavaScript console errors
   - Verify script loading in page source
   - Troubleshoot script optimization plugins

3. **User-specific Settings**:
   - Check user meta for disabled preferences
   - Verify user browser compatibility
   - Test with a new user account

### Unauthorized Data Access

**Symptoms**: Users can access data they shouldn't be able to see through the AI.

**Potential Causes and Solutions**:

1. **Permission Misconfiguration**:
   - Review and adjust role-based data access permissions
   - Implement data access filters
   - Enable strict permission checking

2. **Context Leakage**:
   - Reduce context window size
   - Implement PII filtering
   - Configure separate AI instances for different roles

3. **Prompt Injection Protection**:
   - Enable prompt sanitization
   - Implement jailbreak detection
   - Configure system message hardening

### Varying Feature Availability

**Symptoms**: Different users report inconsistent feature availability or functionality.

**Potential Causes and Solutions**:

1. **Role-based Feature Configuration**:
   - Verify feature matrix in role settings
   - Implement consistent permission checks
   - Document expected feature availability

2. **Capability Inheritance Issues**:
   - Check WordPress capability inheritance
   - Verify custom role definitions
   - Implement capability debugging

3. **User Experience Personalization**:
   - Review personalization settings
   - Check for conflicting user preferences
   - Standardize core feature availability

## API Quota & Usage Concerns

### Exceeding API Quota

**Symptoms**: AI requests fail with quota limit errors or unexpected costs appear on AI service billing.

**Potential Causes and Solutions**:

1. **Usage Limits**:
   - Configure appropriate rate limiting
   - Implement user-based quotas
   - Enable quota alerts and monitoring

2. **Inefficient Prompting**:
   - Optimize prompt design to reduce token usage
   - Implement context compression
   - Use model-appropriate instructions

3. **Cache Optimization**:
   - Increase cache duration for common queries
   - Implement semantic caching for similar questions
   - Configure proper cache invalidation rules

### Unexpected Usage Patterns

**Symptoms**: Unusual spikes in API usage not correlated with user activity.

**Potential Causes and Solutions**:

1. **Automated Requests**:
   - Check for bots or crawlers
   - Implement CAPTCHA for suspicious activity
   - Configure IP-based rate limiting

2. **Background Processing**:
   - Review scheduled tasks
   - Check for inefficient background operations
   - Optimize batch processing jobs

3. **Plugin Conflicts**:
   - Identify plugins making automated API calls
   - Check for duplicate requests
   - Implement request deduplication

### Cost Control Issues

**Symptoms**: AI service costs exceed expectations or budget.

**Potential Causes and Solutions**:

1. **Model Selection**:
   - Use less expensive models for simple tasks
   - Implement model routing based on query complexity
   - Configure token limits by query type

2. **Response Length Optimization**:
   - Set appropriate max token limits
   - Configure concise response style
   - Implement response truncation

3. **Usage Analytics**:
   - Enable detailed usage tracking
   - Review usage patterns for optimization
   - Implement cost allocation by user group

## Database & Storage Issues

### Database Table Corruption

**Symptoms**: Errors when accessing AI features, missing data, or inconsistent behavior.

**Potential Causes and Solutions**:

1. **Table Repair**:
   - Run database table repair:
     ```sql
     REPAIR TABLE wp_mepr_ai_conversations, wp_mepr_ai_cache, wp_mepr_ai_logs;
     ```
   - Rebuild tables if repair fails
   - Restore from database backup if necessary

2. **Index Optimization**:
   - Verify indexes are properly created
   - Rebuild indexes if performance issues persist
   - Add missing indexes for frequent queries

3. **Database Maintenance**:
   - Implement regular optimization schedule
   - Configure automatic table maintenance
   - Monitor table fragmentation

### Storage Growth Issues

**Symptoms**: Database size grows rapidly, backups become unwieldy, performance degrades.

**Potential Causes and Solutions**:

1. **Log Management**:
   - Configure appropriate log rotation
   - Reduce log verbosity
   - Implement log archiving

2. **Cache Size Control**:
   - Set maximum cache size
   - Configure cache item expiration
   - Implement least-recently-used cache eviction

3. **Conversation History Pruning**:
   - Configure conversation retention policy
   - Implement automatic history pruning
   - Archive important conversations

### Backup and Restore Issues

**Symptoms**: Backups fail or restoration doesn't completely recover AI Assistant functionality.

**Potential Causes and Solutions**:

1. **Backup Configuration**:
   - Include all AI Assistant tables in backup
   - Back up file storage directories
   - Verify backup completeness

2. **Database Size Optimization**:
   - Prune unnecessary data before backup
   - Implement incremental backups
   - Configure compressed backups

3. **Restoration Process**:
   - Follow the complete restoration checklist
   - Verify table structure after restore
   - Rebuild indexes after restoration

## Advanced Troubleshooting

### Debugging Procedures

For complex issues, follow these advanced debugging steps:

1. **Enable Debug Mode**:
   - Add to wp-config.php:
     ```php
     define('WP_DEBUG', true);
     define('WP_DEBUG_LOG', true);
     define('WP_DEBUG_DISPLAY', false);
     define('SCRIPT_DEBUG', true);
     ```
   - Enable AI Assistant debug mode in advanced settings
   - Set debug verbosity to maximum

2. **Trace API Communications**:
   - Enable API request logging
   - Record full request and response pairs
   - Look for patterns in failed requests

3. **Isolate Components**:
   - Disable features one by one
   - Test minimal viable configuration
   - Re-enable features to identify problem area

### Plugin Conflicts

For suspected plugin conflicts, follow this systematic approach:

1. **Conflict Isolation**:
   - Disable all plugins except MemberPress and AI Assistant
   - Verify if issue persists
   - Re-enable plugins one by one until issue reappears

2. **Theme Compatibility**:
   - Switch to a default WordPress theme
   - Test functionality
   - Identify theme-specific incompatibilities

3. **Hook Debugging**:
   - Install a hook debugging plugin
   - Identify hook conflicts
   - Check action and filter execution order

### Performance Profiling

For persistent performance issues, use these profiling techniques:

1. **Query Monitoring**:
   - Install a database query monitor
   - Identify slow or repetitive queries
   - Optimize database access patterns

2. **Memory Profiling**:
   - Track memory usage throughout request lifecycle
   - Identify memory spikes
   - Look for memory leaks in loops or recursive functions

3. **Load Testing**:
   - Simulate multiple concurrent users
   - Identify breaking points
   - Determine optimal configuration for your traffic

## Getting Additional Support

If you've tried the troubleshooting steps above and still encounter issues:

### Preparing for Support

Before contacting support, gather this information:

1. **System Information**:
   - WordPress version
   - PHP version
   - MySQL/MariaDB version
   - Server environment (Apache, Nginx, etc.)
   - MemberPress and AI Assistant versions

2. **Diagnostic Reports**:
   - Run complete diagnostics
   - Export diagnostic report
   - Collect relevant log files
   - Take screenshots of error messages

3. **Issue Documentation**:
   - Detailed description of the problem
   - Steps to reproduce
   - When the issue started occurring
   - Recent changes to the site

### Support Channels

Choose the appropriate support channel based on your needs:

1. **Knowledge Base**:
   - Visit [memberpress.com/support/knowledgebase](https://memberpress.com/support/knowledgebase)
   - Search for your specific issue
   - Review advanced troubleshooting articles

2. **Email Support**:
   - Contact [support@memberpress.com](mailto:support@memberpress.com)
   - Include your license key and diagnostic report
   - Provide detailed issue description

3. **Priority Support**:
   - Available for Professional and Enterprise licenses
   - Access faster response times
   - Receive assistance with custom implementations

---

*This guide is regularly updated as new troubleshooting techniques are developed. Last updated: April 6, 2025.*