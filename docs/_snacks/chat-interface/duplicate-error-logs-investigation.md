# Investigation: Duplicate Error Logs on Session Start

## Problem Description

When opening any page in the WordPress admin area with the MemberPress AI Assistant plugin active, multiple identical error log entries are generated, even without any user interaction. These redundant logs create noise in the error log and make it harder to identify actual issues.

Example of redundant logs:
```
[04-Apr-2025 12:11:19 UTC] MPAI: Plugin logger table already exists
[04-Apr-2025 12:11:19 UTC] MPAI ORCHESTRATOR INFO: Error recovery system initialized
[04-Apr-2025 12:11:19 UTC] MPAI_WP_CLI_Tool: Loaded System Cache class
[04-Apr-2025 12:11:19 UTC] MPAI ADAPTER: Loaded MPAI_Command_Handler class
[04-Apr-2025 12:11:19 UTC] MPAI COMMAND: Initializing Command Handler
...repeated multiple times...
```

## Investigation

### Root Cause

The primary issue is in the `init_plugin_components` method in the main plugin file (`memberpress-ai-assistant.php`):

```php
public function init_plugin_components() {
    // Initialize plugin logger
    mpai_init_plugin_logger();
    
    // Force refresh of agent system and tools on every page load
    // This ensures the AI system picks up new tools like our plugin logger
    // In production, you'd want to be more selective with this
    if (class_exists('MPAI_Agent_Orchestrator')) {
        $orchestrator = new MPAI_Agent_Orchestrator();
        if (method_exists($orchestrator, 'get_available_agents')) {
            $orchestrator->get_available_agents();
        }
    }
}
```

This code:
1. Creates a new instance of `MPAI_Agent_Orchestrator` on every page load
2. Each new instance initializes its own Error Recovery System, Logger, and Tools
3. Each initialization generates several log entries
4. The initialization happens multiple times per page load due to WordPress's hook system

The comment in the code even acknowledges this is not ideal for production.

### Contributing Factors

1. **Multiple Initializations**: The Agent Orchestrator is created as a new instance rather than using a true singleton pattern.

2. **WordPress Hook System**: The initialization is triggered on the `init` hook, which can fire multiple times per page load, especially with AJAX requests.

3. **Debug Logging**: The orchestrator logs every initialization step, which is helpful for debugging but creates noise in a production environment.

4. **No Caching**: There's no caching mechanism to avoid redundant initialization of agents and tools.

## Proposed Solution

1. **Implement True Singleton for Agent Orchestrator**:
   ```php
   class MPAI_Agent_Orchestrator {
       private static $instance = null;
       
       public static function get_instance() {
           if (self::$instance === null) {
               self::$instance = new self();
           }
           return self::$instance;
       }
       
       // Make constructor private
       private function __construct() {
           // Initialization code...
       }
   }
   ```

2. **Selective Initialization**:
   ```php
   public function init_plugin_components() {
       // Initialize plugin logger
       mpai_init_plugin_logger();
       
       // Only initialize agent system when explicitly needed
       // Avoid auto-initialization on every page load
   }
   ```

3. **Reduced Logging in Production**:
   Add a configuration option to control the verbosity of logging, reducing it in production environments.

4. **Lazy Loading Tools**:
   Only load tools when they are actually needed rather than initializing everything upfront.

5. **Initialization Flag**:
   ```php
   private static $initialized = false;
   
   public function init_plugin_components() {
       if (self::$initialized) {
           return;
       }
       
       // Initialization code...
       
       self::$initialized = true;
   }
   ```

## Implementation Plan

1. Convert the Agent Orchestrator to a true singleton pattern
2. Add an initialization flag to prevent multiple initializations
3. Make tool registration lazy-loaded
4. Implement a debug mode toggle for detailed logging
5. Update all code that creates orchestrator instances to use the singleton instead