# MemberPress AI Assistant Documentation Reorganization Plan

## Current Status Analysis

After reviewing the current documentation structure, CHANGELOG.md, and analyzing the implementation status of various features, I've identified several documentation files that need reorganization to accurately reflect the current state of the plugin.

## Files to Move

### 1. Files to Move from "current" to "archive"

Since these features have been fully implemented and are now part of the stable release:

- `docs/current/best-selling-membership.md` → `docs/archive/best-selling-membership.md`
  - This feature was fully implemented in version 1.5.8 (2025-04-02) per CHANGELOG.md
  - The implementation is complete and stable

### 2. Files to Move from "roadmap" to "current"

Since these features have been implemented according to CHANGELOG.md:

- `docs/roadmap/support-routing-system-plan.md` → `docs/current/support-routing-system.md`
  - According to CHANGELOG.md version 1.5.8, the support routing system with Docsbot integration has been implemented
  - This should be renamed to remove the "-plan" suffix to reflect its completed status

## README Updates

### 1. Update Current Directory README

The current directory README.md needs updates to fix file paths and reflect recently implemented features:

- Add entry for Support Routing System, now moved from roadmap to current
- Remove entry for best-selling-membership or update link to archived version
- Fix incorrect file paths that use "../" instead of appropriate relative paths
- Update version information and implementation dates

### 2. Update Archive Directory README

The archive directory README.md needs to be updated to include recently archived files:

- Add entry for `best-selling-membership.md`
- Update the archival reason to reference implementation in version 1.5.8

### 3. Update Roadmap Directory README

The roadmap README.md needs updates to remove features that have been implemented:

- Remove or update the entry for Support Routing System as it has been implemented
- Adjust target versions for remaining features as needed

## Fix Cross-Document References

Some documents may contain references to files that will be moved. These references should be updated:

1. Check for references to `best-selling-membership.md` in other current documents
2. Update any references to `support-routing-system-plan.md` in other roadmap documents
3. Ensure any references from the main plugin README point to the correct locations

## Additional Documentation Needs

Based on the CHANGELOG.md, these recently implemented features should have proper documentation created or updated:

1. **Independent Operation Mode**: 
   - The CHANGELOG.md references this feature in version 1.5.8
   - The documentation is in `docs/archive/MEMBERPRESS_INDEPENDENT_OPERATION.md`
   - This should be moved to the current directory with an updated filename that follows naming conventions

2. **Copy Icon Functionality**:
   - The CHANGELOG.md mentions fixes to copy icon functionality in version 1.5.8
   - The documentation is in `docs/archive/CHAT_INTERFACE_COPY_ICON_FIX.md`
   - This should be updated to reflect the completed status and possibly moved to current

## Implementation Plan

1. Create copies of files to be moved (preserve originals until verification is complete)
2. Update READMEs and cross-references
3. Verify all links and references are valid
4. Commit changes with clear descriptive commit message

## Recommended File Structure After Reorganization

```
docs/
├── README.md (main documentation index)
├── current/
│   ├── README.md (updated)
│   ├── support-routing-system.md (moved from roadmap)
│   ├── independent-operation-mode.md (moved from archive with renamed file)
│   └── [other current feature docs]
├── roadmap/
│   ├── README.md (updated)
│   ├── blog-post-formatting-plan.md
│   ├── consent-mechanism-plan.md
│   └── [other roadmap features]
├── archive/
│   ├── README.md (updated)
│   ├── best-selling-membership.md (moved from current)
│   └── [other archived docs]
└── templates/
    └── feature-documentation-template.md
```

## Next Steps

After implementing these changes, the documentation structure will accurately reflect the current state of the plugin, making it easier for both developers and users to find the right information about features at their current implementation stage.