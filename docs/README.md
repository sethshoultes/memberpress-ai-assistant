# MemberPress AI Assistant Documentation

Welcome to the MemberPress AI Assistant documentation hub. This directory contains comprehensive documentation organized by implementation status and purpose. For new developers, please see the [_0_START_HERE_.md](../_0_START_HERE_.md) and [_1_AGENTIC_SYSTEMS_.md](../_1_AGENTIC_SYSTEMS_.md) files in the project root for the most up-to-date developer guidance.

## Documentation Structure

The documentation is organized into the following main categories:

- [**Current Features**](current/) - Documentation for implemented and active features
- [**XML Content System**](xml-content-system/) - Comprehensive documentation for the XML content formatting system
- [**Scooby Snacks**](_snacks/) - Investigation results, fixes, and technical insights
- [**Feature Roadmap**](roadmap/) - Documentation for features in planning or development
- [**Archive**](archive/) - Historical documentation that has been superseded
- [**Templates**](templates/) - Standardized templates for creating new documentation

## Primary Entry Points

### For New Developers
- [_0_START_HERE_.md](../_0_START_HERE_.md) - Comprehensive entry point with development pathways
- [System Map](current/system-map.md) - Complete system architecture overview
- [Features Index](current/features-index.md) - Comprehensive list of all features
- [Documentation Map](current/documentation-map.md) - Visual guide to documentation structure

### For Specific Development Tasks
- [Tool Implementation Map](current/tool-implementation-map.md) - Guide for developing tools
- [_1_AGENTIC_SYSTEMS_.md](../_1_AGENTIC_SYSTEMS_.md) - Complete agent system documentation
- [Unified XML Content System](current/unified-xml-content-system.md) - XML content formatting system
- [Developer Quick Reference](current/developer-quick-reference.md) - Common patterns and tasks
- [Scooby Snacks Index](_snacks/index.md) - Solutions to complex problems

### By Feature Category
- [Documentation Categories](current/documentation-categories.md) - Documentation organized by type
- [Implementation Status](current/implementation-status.md) - Status of all features

## Documentation Standards

All documentation files include status indicators to help identify their current state:

- ✅ **Maintained**: Documentation is current and actively maintained
- 🚧 **In Progress**: Documentation is being actively developed
- 🔮 **Planned**: Documentation is planned but not yet created
- 🗄️ **Archived**: Documentation has been superseded or deprecated

## Recent Updates

The documentation has been completely reorganized and consolidated following a four-phase Documentation Consolidation Plan:

- **Phase 1**: Reorganized existing files into appropriate directories
- **Phase 2**: Consolidated content to reduce duplication ([Phase 2 Results](current/phase-2-documentation-consolidation-results.md))
- **Phase 3**: Added improved navigation aids ([Phase 3 Results](current/phase-3-documentation-consolidation-results.md))
- **Phase 4**: Standardized documentation formats ([Phase 4 Results](current/phase-4-documentation-consolidation-results.md))

A new "Scooby Snacks" documentation system has also been added to capture the results of technical investigations and fixes.

## Scooby Snack Protocol

When given a "Scooby Snack" for a successful solution or implementation:

1. Create a detailed document following the [Scooby Snack structure](_snacks/README.md)
2. Place it in the appropriate category folder within the [_snacks](_snacks/) directory
3. Update the [Scooby Snacks index](_snacks/index.md) with the new entry
4. Update any existing documentation that relates to the solution
5. Add an entry to the CHANGELOG.md file if it's a significant fix or feature
6. Include "🦴 Scooby Snack" in your commit message to track successful solutions

## Contributing to Documentation

When updating documentation:

1. Place files in the appropriate directory based on implementation status
2. Include metadata (status, version, date) at the top of each file
3. Follow the established format from the [templates](templates/)
4. Update the main README.md when adding significant new documentation
5. Update the [documentation map](current/documentation-map.md) to reflect new additions