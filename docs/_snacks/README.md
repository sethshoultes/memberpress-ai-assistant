# MemberPress AI Assistant Scooby Snacks

**Status:** ✅ Maintained  
**Version:** 1.0.0  
**Last Updated:** April 3, 2025

## What are "Scooby Snacks"?

Scooby Snacks are detailed documentation files that capture the process and results of successful investigations that led to bug fixes, feature improvements, or technical insights. They serve as a knowledge repository for the development team and help prevent the same issues from recurring.

## Purpose

1. **Knowledge Preservation**: Document solutions to complex problems for future reference
2. **Onboarding Resource**: Help new developers understand common issues and their solutions
3. **Pattern Recognition**: Identify recurring issues by categorizing similar problems
4. **Solution Templates**: Provide reusable approaches to similar issues

## Scooby Snack Structure

Each Scooby Snack document follows a standardized structure:

```
# [Issue Title]

**Status:** ✅ Fixed  
**Version:** [Version number where fixed]  
**Date:** [Date of fix]  
**Categories:** [comma-separated list of categories]  
**Related Files:** [files involved in the issue]

## Problem Statement

[Detailed description of the problem, its symptoms, and impact]

## Investigation Process

[Step-by-step account of how the issue was investigated]

## Root Cause Analysis

[Technical explanation of what caused the issue]

## Solution Implemented

[Detailed explanation of the fix with code examples]

## Lessons Learned

[Key takeaways and preventive measures for the future]

## Related Issues

[Links to related GitHub issues, PRs, or other Scooby Snacks]
```

## Categories

Scooby Snacks are organized into these categories:

- **UI/UX**: User interface or experience issues
- **Performance**: Speed, memory, or resource optimization issues
- **API Integration**: Issues with OpenAI, Anthropic, or other external APIs
- **WordPress Integration**: Issues with WordPress core, hooks, or APIs
- **MemberPress Integration**: Issues specific to MemberPress functionality
- **Security**: Security vulnerabilities or improvements
- **Tool System**: Issues with the AI tool system
- **Agent System**: Issues with the AI agent system
- **Content System**: Issues with content generation or processing
- **JavaScript**: Issues with client-side JavaScript
- **PHP**: Issues with server-side PHP code

## Directory Structure

The Scooby Snacks documentation is organized as follows:

```
_snacks/
├── README.md                   # This file
├── index.md                    # Categorized index of all Scooby Snacks
├── architecture/               # Architecture related Scooby Snacks
│   └── independent-operation-implementation.md
├── content-system/             # Content system related Scooby Snacks
│   ├── blog-post-publishing-fix.md
│   ├── blog-post-xml-formatting-snack.md
│   └── xml-blog-post-formatting-fix.md
├── examples/                   # Example Scooby Snack documents
│   ├── console-logging-fix.md
│   └── duplicate-tool-execution.md
├── interface/                  # Interface related Scooby Snacks
│   └── chat-interface-copy-icon-fix.md
├── membership/                 # Membership related Scooby Snacks
│   └── best-selling-membership-implementation.md
├── tool-system/                # Tool system related Scooby Snacks
│   └── duplicate-tool-execution-snack.md
└── various other directories for future Scooby Snacks
```

## Creating a New Scooby Snack

When creating a new Scooby Snack:

1. Identify the appropriate category folder
2. Create a new markdown file with a descriptive name
3. Follow the Scooby Snack structure template
4. Include all relevant code snippets, error messages, and diagnostic information
5. Add the document to the index.md file in the appropriate category
6. Update the last updated date in the snack and in this README

## Using Scooby Snacks

Scooby Snacks are most valuable when used as:

1. **Reference Material**: When encountering a similar issue, check if a Scooby Snack exists
2. **Training Material**: For new developers to understand common pitfalls
3. **Diagnostic Tools**: Use the investigation processes as templates for troubleshooting
4. **Documentation**: Link to Scooby Snacks in commit messages when fixing related issues

## Scooby Snack Protocol

When given a "Scooby Snack" for a successful solution or implementation:

1. Create a detailed document following the structure above
2. Place it in the appropriate category folder
3. Update the index.md file with the new entry
4. Update any existing documentation that relates to the solution
5. Add an entry to the CHANGELOG.md file if it's a significant fix or feature
6. Include "🦴 Scooby Snack" in your commit message to track successful solutions
7. The commit should summarize what worked, why it worked, and any lessons learned