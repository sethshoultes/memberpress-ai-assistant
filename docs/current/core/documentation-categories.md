# Documentation Categories

**Status:** ✅ Maintained  
**Version:** 1.0.0  
**Last Updated:** April 2024

This document provides a categorized view of all MemberPress AI Assistant documentation, organized by type and purpose. Use this guide to find the appropriate documentation for your needs.

## Table of Contents

- [Navigation and Overview](#navigation-and-overview)
- [Feature Documentation](#feature-documentation)
- [Integration Documentation](#integration-documentation)
- [Development Guides](#development-guides)
- [Technical Reference](#technical-reference)
- [Tutorials](#tutorials)
- [Architecture Documentation](#architecture-documentation)
- [Process Documentation](#process-documentation)

## Navigation and Overview

These documents provide high-level navigation and overview of the system:

| Document | Purpose | Target Audience |
|----------|---------|----------------|
| [_0_START_HERE_.md](../../_0_START_HERE_.md) | Primary entry point for new developers | New Developers |
| [documentation-map.md](documentation-map.md) | Visual map of all documentation | All Developers |
| [features-index.md](features-index.md) | Comprehensive list of all features | All Developers |
| [implementation-status.md](implementation-status.md) | Status of all features | Maintainers |
| [CHANGELOG.md](../../CHANGELOG.md) | History of changes | All Developers |

## Feature Documentation

These documents describe specific features of the system:

| Feature Area | Key Documents | Status |
|--------------|--------------|--------|
| **Agent System** | [unified-agent-system.md](../agent-system/unified-agent-system.md), [comprehensive-agent-system-guide.md](../agent-system/comprehensive-agent-system-guide.md) | ✅ |
| **XML Content System** | [unified-xml-content-system.md](unified-xml-content-system.md), [xml-content-system/README.md](../xml-content-system/README.md) | ✅ |
| **Tool System** | [tool-implementation-map.md](tool-implementation-map.md), [tool-call-detection.md](tool-call-detection.md) | ✅ |
| **UI Components** | [chat-interface-copy-icon.md](chat-interface-copy-icon.md), [js-modularization-plan.md](js-modularization-plan.md) | ✅ |
| **Logging System** | [console-logging-system.md](console-logging-system.md) | ✅ |

## Integration Documentation

These documents describe how the system integrates with other software:

| Integration Area | Key Documents | Status |
|------------------|--------------|--------|
| **MemberPress** | [class-mpai-memberpress-api.php](../../../includes/class-mpai-memberpress-api.php) | ✅ |
| **WordPress** | [class-mpai-wp-api-tool.php](../../../includes/tools/implementations/class-mpai-wp-api-tool.php) | ✅ |
| **AI Providers** | [class-mpai-anthropic.php](../../../includes/class-mpai-anthropic.php), [class-mpai-openai.php](../../../includes/class-mpai-openai.php) | ✅ |
| **CLI Integration** | [class-mpai-cli-commands.php](../../../includes/cli/class-mpai-cli-commands.php) | ✅ |

## Development Guides

These documents provide guidance for specific development tasks:

| Development Area | Key Documents | Status |
|-----------------|--------------|--------|
| **Agent Development** | [comprehensive-agent-system-guide.md](../agent-system/comprehensive-agent-system-guide.md) | ✅ |
| **Tool Development** | [tool-implementation-map.md](tool-implementation-map.md) | ✅ |
| **XML Content Development** | [unified-xml-content-system.md](unified-xml-content-system.md) | ✅ |
| **Documentation Development** | [templates/README.md](../templates/README.md) | ✅ |
| **JavaScript Development** | [js-modularization-plan.md](js-modularization-plan.md) | ✅ |

## Technical Reference

These documents provide detailed technical specifications:

| Reference Area | Key Documents | Status |
|----------------|--------------|--------|
| **System Architecture** | [system-map.md](system-map.md) | ✅ |
| **Agent System API** | [comprehensive-agent-system-guide.md](../agent-system/comprehensive-agent-system-guide.md) | ✅ |
| **Tool System API** | [tools/class-mpai-base-tool.php](../../../includes/tools/class-mpai-base-tool.php) | ✅ |
| **XML Formatting** | [xml-content-system/README.md](../xml-content-system/README.md) | ✅ |
| **Command System** | [command-system-rewrite-plan.md](command-system-rewrite-plan.md) | 🚧 |

## Tutorials

Step-by-step guides for specific tasks:

| Tutorial Area | Key Documents | Status |
|---------------|--------------|--------|
| **Tool Implementation** | [tool-implementation-map.md](tool-implementation-map.md) | ✅ |
| **Agent Creation** | [comprehensive-agent-system-guide.md](../agent-system/comprehensive-agent-system-guide.md) | ✅ |
| **XML Content Creation** | [unified-xml-content-system.md](unified-xml-content-system.md) | ✅ |
| **JavaScript Modules** | [js-modularization-plan.md](js-modularization-plan.md) | ✅ |

## Architecture Documentation

These documents describe the system architecture:

| Architecture Area | Key Documents | Status |
|-------------------|--------------|--------|
| **Overall Architecture** | [system-map.md](system-map.md) | ✅ |
| **Agent System Architecture** | [comprehensive-agent-system-guide.md](../agent-system/comprehensive-agent-system-guide.md) | ✅ |
| **Tool System Architecture** | [tool-implementation-map.md](tool-implementation-map.md) | ✅ |
| **XML Content Architecture** | [unified-xml-content-system.md](unified-xml-content-system.md) | ✅ |
| **JavaScript Architecture** | [js-modularization-plan.md](js-modularization-plan.md) | ✅ |

## Process Documentation

These documents describe development processes:

| Process Area | Key Documents | Status |
|--------------|--------------|--------|
| **Documentation Process** | [DOCUMENTATION_PLAN.md](../DOCUMENTATION_PLAN.md) | 🚧 |
| **Documentation Consolidation** | [documentation-consolidation-results.md](documentation-consolidation-results.md), [phase-2-documentation-consolidation-results.md](phase-2-documentation-consolidation-results.md) | ✅ |
| **Feature Development** | [agent-system-implementation.md](../archive/agent-system-implementation.md) | 🗄️ |
| **Testing Process** | [test-procedures.md](../../../test/test-procedures.md) | ✅ |

## Using This Guide

### For New Developers

New developers should start with:

1. [_0_START_HERE_.md](../../_0_START_HERE_.md) - Primary entry point
2. [documentation-map.md](documentation-map.md) - Navigation guide
3. [features-index.md](features-index.md) - Feature overview
4. [system-map.md](system-map.md) - System architecture

### For Feature Developers

Developers implementing new features should focus on:

1. Relevant feature documentation in the Feature Documentation section
2. [tool-implementation-map.md](tool-implementation-map.md) for tool development
3. [comprehensive-agent-system-guide.md](../agent-system/comprehensive-agent-system-guide.md) for agent development
4. [templates/README.md](../templates/README.md) for documentation templates

### For Maintainers

Maintainers managing the project should focus on:

1. [implementation-status.md](implementation-status.md) for feature status
2. [documentation-map.md](documentation-map.md) for documentation structure
3. [DOCUMENTATION_PLAN.md](../DOCUMENTATION_PLAN.md) for documentation improvement
4. [CHANGELOG.md](../../CHANGELOG.md) for version history