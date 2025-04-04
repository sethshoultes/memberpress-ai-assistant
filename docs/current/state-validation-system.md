# State Validation System

## Overview

The State Validation System provides a robust framework for ensuring system consistency and preventing state corruption throughout the MemberPress AI Assistant plugin. It implements a comprehensive approach to state validation with pre/post condition checking, invariant assertions, and state monitoring capabilities.

## Components

### System Invariants

Invariants are conditions that should always be true throughout the lifecycle of the application. The State Validation System maintains and verifies the following categories of invariants:

1. **System Invariants** - Core requirements for the plugin to function
   - `plugin_dir_exists` - Ensures MPAI_PLUGIN_DIR is defined and the directory exists
   - `plugin_url_defined` - Verifies MPAI_PLUGIN_URL is properly defined
   - `wp_includes_exists` - Checks that WordPress core includes are accessible

2. **API System Invariants** - Requirements for API functionality
   - `api_router_singleton` - Ensures the API Router maintains singleton behavior
   - `primary_api_setting_valid` - Verifies that the primary API setting contains a valid value

3. **Agent System Invariants** - Requirements for agent system integrity
   - `orchestrator_singleton` - Ensures the Agent Orchestrator maintains singleton behavior

4. **Tool System Invariants** - Requirements for tool system functionality
   - `tool_registry_singleton` - Ensures the Tool Registry maintains singleton behavior

### Component Validation

The system includes a validation framework for different component types:

1. **API Client Validation**
   - `api_key_not_empty` - Ensures API clients have valid authentication keys
   - `model_is_valid` - Verifies that selected models exist in available models list

2. **API Router Validation**
   - `primary_api_exists` - Checks that a primary API provider is configured
   - `fallback_api_exists` - Verifies that a fallback API is available

3. **Agent Orchestrator Validation**
   - `agent_count_valid` - Ensures at least one agent is registered
   - `agent_registry_valid` - Verifies the agent registry structure is intact

4. **Tool Registry Validation**
   - `tool_count_valid` - Checks that tools are properly registered

### Pre/Post Condition Framework

The system provides a framework for defining and checking pre- and post-conditions for operations:

- **Pre-conditions** - Verify that an operation's prerequisites are met before execution
- **Post-conditions** - Ensure that an operation produced valid results after execution

### State Monitoring

The State Validation System continuously monitors component state to detect inconsistencies:

- Tracks state changes over time for key components
- Detects unexpected changes to immutable properties
- Provides early warning of state corruption

## Usage

### Verifying Invariants

```php
// Verify all system invariants
$state_validator = mpai_init_state_validator();
$invariants_valid = $state_validator->verify_invariants();

// Verify invariants for a specific component
$api_invariants_valid = $state_validator->verify_invariants('api');
```

### Validating Components

```php
// Validate an API client
$api_client = new MPAI_OpenAI();
$validation_result = $state_validator->validate_component('api_client', $api_client);

if (is_wp_error($validation_result)) {
    // Handle validation failure
}
```

### Using Pre/Post Conditions

```php
// Define a function to verify with pre/post conditions
function process_request($request_data) {
    $state_validator = mpai_init_state_validator();
    
    return $state_validator->verify_operation(
        'api_router',
        'process_request',
        function($request_data) {
            // Operation implementation
            $result = do_process_request($request_data);
            return $result;
        },
        [$request_data]
    );
}
```

### Monitoring Component State

```php
// Monitor agent orchestrator state
$orchestrator = MPAI_Agent_Orchestrator::get_instance();
$state = [
    'instance_id' => spl_object_hash($orchestrator),
    'agent_count' => count($orchestrator->get_available_agents()),
    'version' => MPAI_VERSION
];

$state_validator->monitor_component_state('agent_orchestrator', $state);
```

### Adding Custom Invariants

```php
// Register a custom invariant
$state_validator->register_invariant(
    'my_component',
    'database_connection_valid',
    function() {
        global $wpdb;
        return $wpdb->check_connection();
    }
);
```

## Integration Points

The State Validation System integrates with other systems:

1. **Error Recovery System** - Uses standardized error creation for validation failures
2. **Plugin Logger** - Records validation issues for later analysis
3. **Agent Orchestrator** - Validates agent system integrity
4. **API Router** - Ensures API consistency and availability

## Error Handling

When validation fails, the system:

1. Logs detailed information about the failure
2. Returns standardized WP_Error objects with context
3. Triggers the Error Recovery System for potential recovery strategies

## Implementation Status

The State Validation System is fully implemented in the following files:

- `includes/class-mpai-state-validator.php` - Core implementation
- `memberpress-ai-assistant.php` - Initialization and integration

Future enhancements planned:

1. Additional tool-specific validation rules
2. Extended monitoring for more components
3. Integration with testing framework
4. Performance optimizations for validation operations