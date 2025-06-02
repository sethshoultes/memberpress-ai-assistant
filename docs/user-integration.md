# User Integration Documentation

## Overview

The User Integration functionality in the MemberPress AI Assistant provides a comprehensive system for managing the relationship between WordPress users and MemberPress memberships. This module enables associating and disassociating users with memberships, managing user roles and permissions, and retrieving user membership data through a structured API.

The implementation follows the same layered architecture pattern as the Membership Operations:

1. **MemberPressTool**: A high-level interface that validates parameters and delegates operations to the service layer
2. **MemberPressService**: Handles business logic and interacts with MemberPress core functionality
3. **UserAdapter**: Connects to WordPress and MemberPress user functionality
4. **UserTransformer**: Converts MemberPress user objects to standardized formats

This architecture ensures separation of concerns, maintainability, and extensibility while providing a robust API for user-membership management.

## User-Membership Operations

### Associate User with Membership

Associates a WordPress user with a MemberPress membership, granting them access to the membership's content.

**Parameters:**
- `user_id` (integer, required): The ID of the WordPress user
- `membership_id` (integer, required): The ID of the membership to associate with the user
- `transaction_data` (object, optional): Custom transaction data
- `subscription_data` (object, optional): Custom subscription data

**Example:**
```php
$parameters = [
    'operation' => 'associate_user_with_membership',
    'user_id' => 123,
    'membership_id' => 456
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'User associated with membership successfully',
    'data' => [
        'user_id' => 123,
        'membership_id' => 456,
        'has_access' => true
    ]
]
```

### Disassociate User from Membership

Removes a WordPress user's access to a MemberPress membership.

**Parameters:**
- `user_id` (integer, required): The ID of the WordPress user
- `membership_id` (integer, required): The ID of the membership to disassociate from the user

**Example:**
```php
$parameters = [
    'operation' => 'disassociate_user_from_membership',
    'user_id' => 123,
    'membership_id' => 456
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'User disassociated from membership successfully',
    'data' => [
        'user_id' => 123,
        'membership_id' => 456,
        'has_access' => false
    ]
]
```

### Get User Memberships

Retrieves all memberships associated with a WordPress user.

**Parameters:**
- `user_id` (integer, required): The ID of the WordPress user

**Example:**
```php
$parameters = [
    'operation' => 'get_user_memberships',
    'user_id' => 123
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'User memberships retrieved successfully',
    'data' => [
        'user_id' => 123,
        'memberships' => [
            [
                'id' => 456,
                'name' => 'Premium Membership',
                'price' => 19.99,
                'terms' => 'monthly'
            ],
            [
                'id' => 789,
                'name' => 'Basic Membership',
                'price' => 9.99,
                'terms' => 'monthly'
            ]
        ],
        'active_count' => 2
    ]
]
```

## User Role and Permission Operations

### Update User Role

Updates a WordPress user's role, which can affect their permissions within the site.

**Parameters:**
- `user_id` (integer, required): The ID of the WordPress user
- `role` (string, required): The WordPress role to assign
- `role_action` (string, optional): The action to perform (set, add, remove). Default is 'set'.

**Example:**
```php
$parameters = [
    'operation' => 'update_user_role',
    'user_id' => 123,
    'role' => 'editor',
    'role_action' => 'set'
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'User role set successfully',
    'data' => [
        'user_id' => 123,
        'roles' => [
            'editor' => 'Editor'
        ]
    ]
]
```

### Get User Permissions

Retrieves a WordPress user's roles and capabilities.

**Parameters:**
- `user_id` (integer, required): The ID of the WordPress user

**Example:**
```php
$parameters = [
    'operation' => 'get_user_permissions',
    'user_id' => 123
];

$result = $memberPressTool->execute($parameters);
```

**Response:**
```php
[
    'status' => 'success',
    'message' => 'User permissions retrieved successfully',
    'data' => [
        'user_id' => 123,
        'roles' => [
            'editor' => 'Editor'
        ],
        'capabilities' => [
            'edit_posts',
            'edit_pages',
            'publish_posts',
            'moderate_comments',
            // ... other capabilities
        ]
    ]
]
```

## Integration with the Agent System

The User Integration functionality integrates with the AI agent system through the `MemberPressAgent` class, which now includes additional capabilities for user-membership operations.

### Agent Capabilities

The MemberPressAgent now includes these additional capabilities:

- `associate_user_with_membership`: Associate a user with a membership
- `disassociate_user_from_membership`: Disassociate a user from a membership
- `get_user_memberships`: Get a user's memberships
- `update_user_role`: Update a user's role
- `get_user_permissions`: Get a user's permissions

### Example Agent Interaction

User request:
```
Add user John Smith (ID: 123) to the Gold Plan membership (ID: 456)
```

Agent processing:
1. MemberPressAgent identifies the intent as `associate_user_with_membership`
2. It extracts parameters: user_id=123, membership_id=456
3. It calls its `associateUserWithMembership` method
4. The method uses MemberPressTool to associate the user with the membership
5. The agent formats the response for the user

Response:
```
I've added John Smith to the Gold Plan membership. They now have access to all content included in this membership.
```

## Error Handling and Best Practices

### Error Handling

The User Integration system implements comprehensive error handling:

1. **Parameter Validation**: The `MemberPressTool` validates all parameters before execution
2. **Exception Handling**: All operations are wrapped in try-catch blocks
3. **Standardized Error Responses**: Errors return a consistent format with status and message
4. **Logging**: Errors are logged with context for debugging

Example error response:
```php
[
    'status' => 'error',
    'message' => 'User not found',
]
```

### Best Practices

When using the User Integration functionality:

1. **Always validate user input** before passing to the MemberPressTool
2. **Check response status** before assuming success
3. **Handle errors gracefully** and provide user-friendly messages
4. **Use appropriate error logging** for debugging
5. **Implement proper access control** to prevent unauthorized operations
6. **Consider performance implications** when performing bulk operations
7. **Use transactions** when performing multiple related operations
8. **Cache frequently accessed data** to improve performance

## Conclusion

The User Integration functionality provides a robust, extensible system for managing the relationship between WordPress users and MemberPress memberships. By following the layered architecture pattern and implementing comprehensive error handling, it ensures reliable and maintainable code.

Developers can leverage this functionality through direct API calls to the MemberPressTool, or through the natural language interface provided by the MemberPressAgent. Either approach provides full access to user-membership association, role management, and permission operations.