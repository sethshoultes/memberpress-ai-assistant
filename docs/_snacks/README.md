# MemberPress AI Assistant Scooby Snacks

**Status:** ✅ Maintained  
**Version:** 1.1.0  
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
├── investigations/             # Investigation-only Scooby Snacks (no implemented fix)
│   └── investigation-template.md
└── various other directories for future Scooby Snacks
```

## Scooby Mode: Investigation Protocol

The project implements a special investigation mode called "Scooby Mode" which follows a systematic approach to troubleshooting and problem diagnosis:

### Activating Scooby Mode

Scooby Mode is activated by using one of these trigger phrases:
- "Scooby Mode"
- "Scooby Doo"
- "Scooby"
- "Jinkies"

### Scooby Mode Process

When Scooby Mode is activated:

1. **Information Gathering Phase**
   - Stop active coding and shift to diagnostic/investigative approach
   - Gather detailed information about the issue:
     - Specific error messages and stack traces
     - Expected vs. actual behavior
     - Files and code paths involved
     - Recent changes that might affect functionality
     - User steps to reproduce the issue

2. **Methodical Investigation Phase**
   - Examine relevant code, logs, and test results
   - Look for patterns in failures
   - Identify potential root causes
   - Consider multiple failure scenarios
   - Test hypotheses systematically

3. **Documentation Phase**
   - Document findings in Scooby Snack format even if a solution is not implemented
   - For investigation-only documents, use the prefix "Investigation:" in the filename
   - Place investigation-only documents in the `investigations/` directory
   - Include all diagnostics, test steps, and findings

4. **Solution Phase (if applicable)**
   - If a solution is identified and implemented, follow the standard Scooby Snack protocol
   - Include "🦴 Scooby Snack" in the commit message
   - Document both the investigation process and the solution

### Investigation Template

For Scooby Mode investigations that don't yet have a solution, use this structure:

```
# Investigation: [Issue Title]

**Status:** 🔍 Investigating  
**Date:** [Date of investigation]  
**Categories:** [comma-separated list of categories]  
**Related Files:** [files involved in the issue]

## Problem Statement

[Detailed description of the problem, its symptoms, and impact]

## Investigation Steps

[Step-by-step account of the investigation process]

## Diagnostic Information

[Error messages, logs, test results, and other diagnostics]

## Hypotheses

[Potential causes being considered]

## Preliminary Findings

[Current understanding of the issue]

## Next Steps

[Recommended actions for further investigation or potential solutions]
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