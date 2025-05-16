# Membership Operations Documentation

## Overview

The Membership Operations functionality in the MemberPress AI Assistant provides a comprehensive system for managing MemberPress memberships programmatically. This module enables the creation, reading, updating, and deletion of memberships, as well as managing pricing options and access rules through a structured API.

The implementation follows a layered architecture pattern:

1. **MemberPressTool**: A high-level interface that validates parameters and delegates operations to the service layer
2. **MemberPressService**: Handles business logic and interacts with MemberPress core functionality
3. **Adapters**: Connect to specific MemberPress entities (products, rules, etc.)
4. **Transformers**: Convert MemberPress objects to standardized formats
5. **MemberPressAgent**: Provides AI-powered assistance for membership operations

This architecture ensures separation of concerns, maintainability, and extensibility while providing a robust API for membership management.

## MemberPressTool and MemberPressService Interaction

### Architecture Overview

The Membership Operations functionality follows a tool-service pattern:

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│                 │     │                 │     │                 │     │                 │
│  Agent System   │────▶│ MemberPressTool │────▶│MemberPressService│────▶│    Adapters     │
│                 │     │                 │     │                 │     │                 │
└─────────────────┘     └─────────────────┘     └─────────────────┘     └─────────────────┘
                                                        │
                                                        ▼
                                               ┌─────────────────┐
                                               │                 │
                                               │  Transformers   │
                                               │                 │
                                               └─────────────────┘
```

### MemberPressTool

The `MemberPressTool` class extends `AbstractTool` and serves as the entry point for membership operations. It:

1. Defines valid operations and their parameters
2. Validates incoming parameters
3. Delegates execution to the appropriate method
4. Handles errors and returns standardized responses

### MemberPressService

The `MemberPressService` class extends `AbstractService` and implements the business logic for membership operations. It:

1. Registers adapters and transformers with the DI container
2. Provides methods for each membership operation
3. Handles interactions with MemberPress core functionality
4. Implements error handling and logging
5. Returns standardized responses

### Workflow Example

When a request to create a membership is received:

1. The `MemberPressTool` validates the parameters (name, price, terms)
2. The tool calls its `create_membership` method, which prepares the data
3. The tool delegates to `MemberPressService::createMembership()`
4. The service uses the `ProductAdapter` to create the membership
5. The service uses the `ProductTransformer` to format the response
6. The standardized response is returned through the tool

## Detailed Operation Documentation

### Membership Operations

#### Create Membership

Creates a new membership in MemberPress.

**Parameters:**
- `name` (string, required): The name of the membership
- `price` (number, required): The price of the membership
- `terms` (string, required): The billing terms (monthly, yearly, quarterly, lifetime, one-time)
- `access_rules` (array, optional): Access rules for the membership

**Example:**
```php
$parameters = [
    'operation' => 'create_membership',
    'name' => 'Premium Membership',
    'price' => 19.99,
    'terms' => 'monthly'
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'Membership created successfully',
    'data' => [
        'id' => 123,
        'name' => 'Premium Membership',
        'price' => 19.99,
        'terms' => 'monthly',
        'created_at' => '2025-04-25 13:45:00'
    ]
]
```

#### Get Membership

Retrieves details of a specific membership.

**Parameters:**
- `membership_id` (integer, required): The ID of the membership to retrieve

**Example:**
```php
$parameters = [
    'operation' => 'get_membership',
    'membership_id' => 123
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'Membership retrieved successfully',
    'data' => [
        'id' => 123,
        'name' => 'Premium Membership',
        'price' => 19.99,
        'terms' => 'monthly',
        'created_at' => '2025-04-25 13:45:00',
        'updated_at' => '2025-04-25 13:45:00'
    ]
]
```

#### Update Membership

Updates an existing membership.

**Parameters:**
- `membership_id` (integer, required): The ID of the membership to update
- `name` (string, optional): The new name of the membership
- `price` (number, optional): The new price of the membership
- `terms` (string, optional): The new billing terms

**Example:**
```php
$parameters = [
    'operation' => 'update_membership',
    'membership_id' => 123,
    'name' => 'Premium Plus Membership',
    'price' => 29.99
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'Membership updated successfully',
    'data' => [
        'id' => 123,
        'name' => 'Premium Plus Membership',
        'price' => 29.99,
        'terms' => 'monthly',
        'updated_at' => '2025-04-25 14:30:00'
    ]
]
```

#### Delete Membership

Deletes an existing membership.

**Parameters:**
- `membership_id` (integer, required): The ID of the membership to delete

**Example:**
```php
$parameters = [
    'operation' => 'delete_membership',
    'membership_id' => 123
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'Membership deleted successfully',
    'data' => [
        'id' => 123
    ]
]
```

#### List Memberships

Retrieves a list of all memberships.

**Parameters:**
- `limit` (integer, optional): Maximum number of memberships to return
- `offset` (integer, optional): Number of memberships to skip

**Example:**
```php
$parameters = [
    'operation' => 'list_memberships',
    'limit' => 10,
    'offset' => 0
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'Memberships retrieved successfully',
    'data' => [
        'memberships' => [
            [
                'id' => 123,
                'name' => 'Premium Membership',
                'price' => 29.99,
                'terms' => 'monthly'
            ],
            [
                'id' => 124,
                'name' => 'Basic Membership',
                'price' => 9.99,
                'terms' => 'monthly'
            ]
        ],
        'total' => 2,
        'limit' => 10,
        'offset' => 0
    ]
]
```

### Access Rule Operations

#### Create Access Rule

Creates a new access rule for a membership.

**Parameters:**
- `membership_id` (integer, required): The ID of the membership
- `content_type` (string, required): The type of content to protect (post, page, category, tag, custom_post_type)
- `content_ids` (array, required): IDs of content to protect
- `rule_type` (string, required): Type of access rule (include, exclude)

**Example:**
```php
$parameters = [
    'operation' => 'create_access_rule',
    'membership_id' => 123,
    'content_type' => 'post',
    'content_ids' => [45, 46, 47],
    'rule_type' => 'include'
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'Access rule created successfully',
    'data' => [
        'id' => 456,
        'membership_id' => 123,
        'content_type' => 'post',
        'content_ids' => [45, 46, 47],
        'rule_type' => 'include',
        'created_at' => '2025-04-25 15:00:00'
    ]
]
```

#### Update Access Rule

Updates an existing access rule.

**Parameters:**
- `rule_id` (integer, required): The ID of the access rule to update
- `membership_id` (integer, optional): The ID of the membership
- `content_type` (string, optional): The type of content to protect
- `content_ids` (array, optional): IDs of content to protect
- `rule_type` (string, optional): Type of access rule

**Example:**
```php
$parameters = [
    'operation' => 'update_access_rule',
    'rule_id' => 456,
    'content_ids' => [45, 46, 47, 48]
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'Access rule updated successfully',
    'data' => [
        'id' => 456,
        'membership_id' => 123,
        'content_type' => 'post',
        'content_ids' => [45, 46, 47, 48],
        'rule_type' => 'include',
        'updated_at' => '2025-04-25 15:30:00'
    ]
]
```

#### Delete Access Rule

Deletes an existing access rule.

**Parameters:**
- `rule_id` (integer, required): The ID of the access rule to delete

**Example:**
```php
$parameters = [
    'operation' => 'delete_access_rule',
    'rule_id' => 456
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'Access rule deleted successfully',
    'data' => [
        'id' => 456
    ]
]
```

### Pricing Management

#### Manage Pricing

Updates pricing settings for a membership.

**Parameters:**
- `membership_id` (integer, required): The ID of the membership
- `price` (number, required): The price of the membership
- `billing_type` (string, required): The type of billing (recurring or one-time)
- `billing_frequency` (string, required for recurring): The frequency of billing (monthly, yearly, quarterly)

**Example:**
```php
$parameters = [
    'operation' => 'manage_pricing',
    'membership_id' => 123,
    'price' => 39.99,
    'billing_type' => 'recurring',
    'billing_frequency' => 'monthly'
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'Pricing updated successfully',
    'data' => [
        'membership_id' => 123,
        'price' => 39.99,
        'billing_type' => 'recurring',
        'billing_frequency' => 'monthly',
        'updated_at' => '2025-04-25 16:00:00'
    ]
]
```

## Error Handling and Best Practices

### Error Handling

The Membership Operations system implements comprehensive error handling:

1. **Parameter Validation**: The `MemberPressTool` validates all parameters before execution
2. **Exception Handling**: All operations are wrapped in try-catch blocks
3. **Standardized Error Responses**: Errors return a consistent format with status and message
4. **Logging**: Errors are logged with context for debugging

Example error response:
```php
[
    'status' => 'error',
    'message' => 'Parameter validation failed',
    'errors' => [
        'Name is required for create_membership operation'
    ]
]
```

### Best Practices

When using the Membership Operations functionality:

1. **Always validate user input** before passing to the MemberPressTool
2. **Check response status** before assuming success
3. **Handle errors gracefully** and provide user-friendly messages
4. **Use appropriate error logging** for debugging
5. **Implement proper access control** to prevent unauthorized operations
6. **Consider performance implications** when performing bulk operations
7. **Use transactions** when performing multiple related operations
8. **Cache frequently accessed data** to improve performance

## Integration with the Agent System

The Membership Operations functionality integrates with the AI agent system through the `MemberPressAgent` class.

### MemberPressAgent

The `MemberPressAgent` extends `AbstractAgent` and specializes in MemberPress operations. It:

1. Provides a natural language interface for membership operations
2. Registers capabilities that map to MemberPressTool operations
3. Processes user requests and determines the appropriate action
4. Calculates relevance scores to determine if it should handle a request
5. Maintains context for multi-step operations

### Agent Capabilities

The MemberPressAgent registers the following capabilities:

- `create_membership`: Create a new membership
- `update_membership`: Update an existing membership
- `delete_membership`: Delete a membership
- `get_membership`: Get membership details
- `list_memberships`: List all memberships
- `create_access_rule`: Create a new access rule
- `update_access_rule`: Update an existing access rule
- `delete_access_rule`: Delete an access rule
- `manage_pricing`: Manage pricing for memberships

### Integration Workflow

When a user interacts with the AI assistant:

1. The request is routed to the `AgentOrchestrator`
2. The orchestrator calculates relevance scores for each agent
3. If the MemberPressAgent has the highest score, it processes the request
4. The agent determines the intent (e.g., create_membership)
5. The agent calls the appropriate method, which uses MemberPressTool
6. The response is formatted and returned to the user

### Example Agent Interaction

User request:
```
Create a new monthly membership called "Gold Plan" for $49.99
```

Agent processing:
1. MemberPressAgent identifies the intent as `create_membership`
2. It extracts parameters: name="Gold Plan", price=49.99, terms="monthly"
3. It calls its `createMembership` method
4. The method uses MemberPressTool to create the membership
5. The agent formats the response for the user

Response:
```
I've created a new monthly membership called "Gold Plan" priced at $49.99. The membership ID is 125.
```

## Conclusion

The Membership Operations functionality provides a robust, extensible system for managing MemberPress memberships programmatically. By following the layered architecture pattern and implementing comprehensive error handling, it ensures reliable and maintainable code.

Developers can leverage this functionality through direct API calls to the MemberPressTool, or through the natural language interface provided by the MemberPressAgent. Either approach provides full access to membership creation, management, pricing, and access rule operations.