# Available Tools in the MemberPress AI Assistant

This document provides a comprehensive overview of the tools available in the MemberPress AI Assistant plugin, including their capabilities, operations, and architectural design.

## Tool Architecture Overview

The MemberPress AI Assistant implements a sophisticated tool system with these key components:

### Core Architecture

1. **Tool Interface and Base Class**:
   - [`ToolInterface`](../src/Interfaces/ToolInterface.php) defines the contract for all tools with methods for name, description, definition, and execution
   - [`AbstractTool`](../src/Abstracts/AbstractTool.php) provides a robust base implementation with parameter validation, error handling, and logging

2. **Tool Registry**:
   - [`ToolRegistry`](../src/Registry/ToolRegistry.php) manages tool registration and discovery
   - Supports finding tools by capability, parameter support, or task requirements
   - Implements automatic tool discovery from core, plugins, and custom directories

3. **Tool Caching System**:
   - [`CachedToolWrapper`](../src/Services/CachedToolWrapper.php) provides performance optimization through caching
   - Implements sophisticated caching strategies with configurable TTLs for different tool types
   - Supports cache invalidation and warming for frequently used operations

### Tool Execution Flow

The typical flow for tool execution is:

1. A tool is retrieved from the registry: `$tool = $toolRegistry->getTool('tool_name')`
2. Parameters are prepared for the specific operation
3. The tool is executed (often through the cached wrapper): `$result = $cachedToolWrapper->execute($tool, $parameters)`
4. Results are processed and used by the calling code

### Key Features

- **Parameter Validation**: Each tool validates input parameters before execution
- **Error Handling**: Comprehensive error handling with detailed error messages
- **Caching**: Performance optimization through configurable caching
- **Batch Processing**: Support for batch operations to handle multiple items efficiently
- **Logging**: Detailed logging of tool operations for debugging and auditing

## Available Tools

### 1. ContentTool

This tool handles content management operations with the following capabilities:

#### Operations

- **format_content**: Converts content between different formats (HTML, Markdown, plain text)
  - Parameters: `content`, `format_type`, `formatting_options`
  
- **organize_content**: Structures content into organized sections
  - Parameters: `content` or `sections`
  
- **manage_media**: Handles media embedding, linking, optimization, and captioning
  - Parameters: `media_type`, `media_action`, `media_url`, `media_caption`, `media_alt_text`
  
- **optimize_seo**: Analyzes and optimizes content for SEO
  - Parameters: `content`, `seo_analysis_type`, `seo_keywords`, `seo_meta_description`, `seo_title`
  
- **manage_revisions**: Manages content revisions
  - Parameters: `revision_action`, `post_id`, `revision_id`, `compare_with_revision_id`

### 2. WordPressTool

This tool provides comprehensive WordPress functionality:

#### Post Operations

- **create_post**: Creates new posts or pages
  - Parameters: `post_title`, `post_type`, `post_content`, `post_status`, etc.
  
- **get_post**: Retrieves post details
  - Parameters: `post_id`
  
- **update_post**: Updates existing posts
  - Parameters: `post_id`, `post_title`, `post_content`, etc.
  
- **delete_post**: Deletes posts
  - Parameters: `post_id`, `force_delete`
  
- **list_posts**: Lists posts with filtering options
  - Parameters: `post_type`, `post_status`, `limit`, `offset`, `orderby`, `order`, `search`

#### User Operations

- **create_user**: Creates new WordPress users
  - Parameters: `user_login`, `user_email`, `user_pass`, `role`, etc.
  
- **get_user**: Retrieves user details
  - Parameters: `user_id`
  
- **update_user**: Updates user information
  - Parameters: `user_id`, `user_email`, `user_pass`, etc.
  
- **list_users**: Lists users with filtering options
  - Parameters: `role`, `limit`, `offset`, `orderby`, `order`, `search`

#### Taxonomy Operations

- **create_term**: Creates new taxonomy terms
  - Parameters: `name`, `taxonomy`, `slug`, `description`, `parent`
  
- **get_term**: Retrieves term details
  - Parameters: `term_id`, `taxonomy`
  
- **update_term**: Updates existing terms
  - Parameters: `term_id`, `taxonomy`, `name`, `slug`, etc.
  
- **delete_term**: Deletes terms
  - Parameters: `term_id`, `taxonomy`
  
- **list_terms**: Lists terms with filtering options
  - Parameters: `taxonomy`, `hide_empty`, `limit`, `offset`, `orderby`, `order`, `parent`, `search`

#### Settings Operations

- **get_option**: Retrieves WordPress options
  - Parameters: `option_name`
  
- **update_option**: Updates WordPress options
  - Parameters: `option_name`, `option_value`

### 3. MemberPressTool

This tool integrates with MemberPress for membership management:

#### Membership Operations

- **create_membership**: Creates new membership products
  - Parameters: `name`, `price`, `terms`
  
- **get_membership**: Retrieves membership details
  - Parameters: `membership_id`
  
- **update_membership**: Updates existing memberships
  - Parameters: `membership_id`, `name`, `price`, `terms`
  
- **delete_membership**: Deletes memberships
  - Parameters: `membership_id`
  
- **list_memberships**: Lists all memberships
  - Parameters: `limit`, `offset`

#### Access Control

- **create_access_rule**: Creates content access rules
  - Parameters: `membership_id`, `content_type`, `content_ids`, `rule_type`
  
- **update_access_rule**: Updates access rules
  - Parameters: `rule_id`, `membership_id`, `content_type`, `content_ids`, `rule_type`
  
- **delete_access_rule**: Deletes access rules
  - Parameters: `rule_id`
  
- **manage_pricing**: Manages membership pricing and billing terms
  - Parameters: `membership_id`, `price`, `billing_type`, `billing_frequency`

#### User Management

- **associate_user_with_membership**: Assigns memberships to users
  - Parameters: `user_id`, `membership_id`, `transaction_data`, `subscription_data`
  
- **disassociate_user_from_membership**: Removes memberships from users
  - Parameters: `user_id`, `membership_id`
  
- **get_user_memberships**: Lists a user's memberships
  - Parameters: `user_id`
  
- **update_user_role**: Updates a user's WordPress role
  - Parameters: `user_id`, `role`, `role_action`
  
- **get_user_permissions**: Retrieves a user's access permissions
  - Parameters: `user_id`

#### Batch Operations

- **batch_get_memberships**: Retrieves multiple memberships in one operation
- **batch_update_memberships**: Updates multiple memberships in one operation
- **batch_delete_memberships**: Deletes multiple memberships in one operation
- **batch_create_memberships**: Creates multiple memberships in one operation
- **batch_create_access_rules**: Creates multiple access rules in one operation
- **batch_update_access_rules**: Updates multiple access rules in one operation
- **batch_delete_access_rules**: Deletes multiple access rules in one operation
- **batch_associate_users**: Associates multiple users with memberships in one operation
- **batch_disassociate_users**: Disassociates multiple users from memberships in one operation
- **batch_get_user_memberships**: Retrieves memberships for multiple users in one operation
- **batch_update_user_roles**: Updates roles for multiple users in one operation
- **batch_get_user_permissions**: Retrieves permissions for multiple users in one operation

## Extending the Tool System

The tool system is designed to be extensible. To create a new tool:

1. Create a class that extends `AbstractTool`
2. Implement the required methods:
   - `getParameters()`: Define the parameters your tool accepts
   - `executeInternal()`: Implement the tool's functionality
3. Register your tool with the `ToolRegistry`

Example:

```php
class MyCustomTool extends AbstractTool {
    public function __construct() {
        parent::__construct(
            'my_custom_tool',
            'Description of my custom tool',
            null
        );
    }
    
    protected function getParameters(): array {
        return [
            'type' => 'object',
            'properties' => [
                'operation' => [
                    'type' => 'string',
                    'description' => 'The operation to perform',
                    'enum' => ['operation1', 'operation2'],
                ],
                // Additional parameters...
            ],
            'required' => ['operation'],
        ];
    }
    
    protected function executeInternal(array $parameters): array {
        // Implement tool functionality
        $operation = $parameters['operation'];
        $result = $this->$operation($parameters);
        return $result;
    }
    
    // Implement operations...
}
```

## Using the Cached Tool Wrapper

For performance optimization, tools can be executed through the `CachedToolWrapper`:

```php
// Get the tool from the registry
$tool = $toolRegistry->getTool('tool_name');

// Define parameters
$parameters = [
    'operation' => 'operation_name',
    // Additional parameters...
];

// Execute with caching
$result = $cachedToolWrapper->execute($tool, $parameters);
```

The `CachedToolWrapper` provides:

- Automatic caching of tool results based on input parameters
- Configurable TTL for different tool types and operations
- Cache invalidation methods for specific tools or operations
- Cache warming for frequently used operations

## Conclusion

The tool-based architecture of the MemberPress AI Assistant provides a modular, extensible approach to implementing functionality. By leveraging this architecture, developers can easily add new capabilities to the system while maintaining consistency in parameter validation, error handling, and performance optimization.