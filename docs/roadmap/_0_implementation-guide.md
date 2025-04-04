# MemberPress AI Assistant Enhancement Implementation Guide

This document provides a step-by-step guide for implementing the enhancements outlined in the roadmap documents. Each section references the corresponding plan document and includes specific implementation steps.

## Overview of Implementation Order

1. **Agent System Foundation** (Primary focus)
2. **Core Performance Optimizations** (Secondary focus)
3. **Essential Testing Framework** (Supporting focus)

## Related Roadmap Documents

When implementing these enhancements, consider the following related roadmap documents:

1. **[agent-system-spec.md](./agent-system-spec.md)** - Original detailed specification for the agent system architecture
2. **[agentic-security-framework.md](./agentic-security-framework.md)** - Security considerations that must be incorporated
3. **[new-tools-enhancement-plan.md](./new-tools-enhancement-plan.md)** - New tools that will leverage the enhanced agent system
4. **[content-tools-specification.md](./content-tools-specification.md)** - Content-specific tools requiring agent system support
5. **[integrated-security-implementation-plan.md](./integrated-security-implementation-plan.md)** - Combined security approach
6. **[wp-security-integration-plan.md](./wp-security-integration-plan.md)** - WordPress security features to incorporate

Key considerations from these documents:

- **Security validation** must be integrated into agent discovery mechanism (from agentic-security-framework.md)
- **Tool registry enhancements** should anticipate new tools specified in new-tools-enhancement-plan.md
- **Performance optimizations** must account for the specialized content tools in content-tools-specification.md
- **Testing framework** should include security tests outlined in integrated-security-implementation-plan.md

## Implementation Steps

### Phase 1: Agent System Foundation (Weeks 1-2)

#### 1.1 Agent Discovery Mechanism
*Reference: [_1_agent-system-enhancement-plan.md](./_1_agent-system-enhancement-plan.md) - Section 1.1*

1. Create a dynamic discovery method in `class-mpai-agent-orchestrator.php`:
   ```php
   private function discover_agents() {
     $agents_dir = plugin_dir_path(__FILE__) . 'specialized/';
     $agent_files = glob($agents_dir . 'class-mpai-*.php');
     
     foreach ($agent_files as $agent_file) {
       // Load agent file
       require_once $agent_file;
       
       // Extract class name from filename
       $filename = basename($agent_file, '.php');
       $class_name = str_replace('class-', '', $filename);
       $class_name = str_replace('-', '_', $class_name);
       $class_name = strtoupper($class_name);
       
       // Create agent instance if class exists
       if (class_exists($class_name)) {
         $agent = new $class_name($this->tool_registry, $this->logger);
         $agent_id = strtolower(str_replace('MPAI_', '', $class_name));
         $agent_id = str_replace('_agent', '', $agent_id);
         
         // Apply security validation (from agentic-security-framework.md)
         if ($this->validate_agent($agent_id, $agent)) {
           $this->register_agent($agent_id, $agent);
         } else {
           $this->logger->warning("Agent failed security validation: " . $agent_id);
         }
       }
     }
     
     // Apply filter to allow modifications
     $this->agents = apply_filters('mpai_available_agents', $this->agents);
   }
   
   /**
    * Validate agent according to security framework
    * 
    * @param string $agent_id
    * @param object $agent
    * @return bool Whether agent passes validation
    */
   private function validate_agent($agent_id, $agent) {
     // Check agent has required methods
     if (!method_exists($agent, 'get_capabilities') ||
         !method_exists($agent, 'get_name') ||
         !method_exists($agent, 'get_description')) {
       return false;
     }
     
     // Check agent has valid capabilities structure
     $capabilities = $agent->get_capabilities();
     if (!is_array($capabilities)) {
       return false;
     }
     
     // More validation checks as per agentic-security-framework.md
     // ...
     
     return true;
   }
   ```

2. Replace static agent registration in `register_core_agents()` with the discovery method:
   ```php
   private function register_core_agents() {
     // Discover all agent files
     $this->discover_agents();
     
     // Manually register any core agents that require special handling
     // (only if they weren't discovered automatically)
     if (!isset($this->agents['memberpress'])) {
       $this->register_memberpress_agent();
     }
     
     if (!isset($this->agents['command_validation'])) {
       $this->register_command_validation_agent();
     }
   }
   ```

#### 1.2 Create Unit Test Setup ✅
*Reference: [_3_testing-stability-plan.md](./_3_testing-stability-plan.md) - Section 3.1*

1. **Implemented Phase One Tests**: ✅
   - Created `test/test-phase-one.php` for standalone testing
   - Extended `direct-ajax-handler.php` with test endpoints
   - Added System Diagnostics UI for running and viewing test results

2. **Phase One Test Implementation**:
   ```php
   // In direct-ajax-handler.php
   
   case 'test_agent_discovery':
       // Test agent discovery functionality
       try {
           // Create an orchestrator instance
           $orchestrator = new MPAI_Agent_Orchestrator();
           
           // Get discovered agents
           $agents = $orchestrator->get_available_agents();
           
           // Prepare result data
           $result = array(
               'success' => true,
               'agents_count' => count($agents),
               'agents' => array()
           );
           
           // Add information about each agent
           foreach ($agents as $agent_id => $agent_info) {
               $result['agents'][] = array(
                   'id' => $agent_id,
                   'name' => isset($agent_info['name']) ? $agent_info['name'] : 'Unknown',
                   'description' => isset($agent_info['description']) ? $agent_info['description'] : 'Not available',
                   'capabilities' => isset($agent_info['capabilities']) ? $agent_info['capabilities'] : array()
               );
           }
           
           echo json_encode(array(
               'success' => true,
               'data' => $result
           ));
       } catch (Exception $e) {
           echo json_encode(array(
               'success' => false,
               'message' => 'Agent Discovery Test failed: ' . $e->getMessage()
           ));
       }
       break;
   ```

3. **System Diagnostics Integration**:
   - Added AJAX test runners in settings-diagnostic.php
   - Created visual test card indicators for pass/fail status
   - Implemented "Run All Phase One Tests" functionality that displays all test results
   - Added formatting for clear test result presentation

#### 1.3 Implement Tool Lazy-Loading ✅
*Reference: [_2_performance-optimization-plan.md](./_2_performance-optimization-plan.md) - Section 2.1*

1. **Implementation Status**: ✅
   - Tool registry now supports lazy loading of tools
   - Implemented tested and verified in Phase One tests
   - System Diagnostics shows successful implementation

2. **Lazy Loading Test**: 
   ```php
   case 'test_lazy_loading':
       // Test tool lazy loading functionality
       try {
           // Check if the tool registry class exists
           if (!class_exists('MPAI_Tool_Registry')) {
               require_once(dirname(__FILE__) . '/tools/class-mpai-tool-registry.php');
           }
           
           // Create a registry instance
           $registry = new MPAI_Tool_Registry();
           
           // Register a test tool definition without loading it
           $test_tool_id = 'test_lazy_loading_tool';
           $test_tool_class = 'MPAI_Diagnostic_Tool';
           $test_tool_file = dirname(__FILE__) . '/tools/implementations/class-mpai-diagnostic-tool.php';
           
           $registry->register_tool_definition($test_tool_id, $test_tool_class, $test_tool_file);
           
           // Get all available tools (should include unloaded tools)
           $all_tools = $registry->get_available_tools();
           
           // Check if our test tool is in the available tools
           $tool_found = isset($all_tools[$test_tool_id]);
           
           // Try to get the tool (should load it on demand)
           $loaded_tool = $registry->get_tool($test_tool_id);
           $tool_loaded = ($loaded_tool !== null);
           
           // Prepare result data
           $result = array(
               'success' => ($tool_found && $tool_loaded),
               'tool_definition_registered' => $tool_found,
               'tool_loaded_on_demand' => $tool_loaded,
               'available_tools_count' => count($all_tools),
               'available_tools' => array_keys($all_tools)
           );
           
           echo json_encode(array(
               'success' => true,
               'data' => $result
           ));
       } catch (Exception $e) {
           echo json_encode(array(
               'success' => false,
               'message' => 'Tool Lazy Loading Test failed: ' . $e->getMessage()
           ));
       }
       break;
   ```

2. Update the `register_tools()` method in `class-mpai-agent-orchestrator.php` to use definitions and anticipate new tools:
   ```php
   private function register_tools() {
     // Register tools from new-tools-enhancement-plan.md
     
     // 1. Content Generation Tools
     $this->register_tool_definition(
       'content_generator',
       'MPAI_Content_Generator_Tool',
       plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-content-generator-tool.php'
     );
     
     // 2. Analytics Tools
     $this->register_tool_definition(
       'analytics',
       'MPAI_Analytics_Tool',
       plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-analytics-tool.php'
     );
     
     // Standard tools
     // Register CommandTool definition
     if (class_exists('MPAI_Command_Tool')) {
       $this->tool_registry->register_tool_definition(
         'command',
         'MPAI_Command_Tool'
       );
     }
     
     // Register WordPress Tool definition
     $wp_tool_path = plugin_dir_path(dirname(__FILE__)) . 'tools/implementations/class-mpai-wordpress-tool.php';
     $this->tool_registry->register_tool_definition(
       'wordpress',
       'MPAI_WordPress_Tool',
       $wp_tool_path
     );
     
     // Additional tool definitions...
   }
   ```

### Phase 2: Agent Communication and Scoring (Weeks 3-4) - COMPLETED ✅

#### 2.1 Agent Specialization Scoring
*Reference: [_1_agent-system-enhancement-plan.md](./_1_agent-system-enhancement-plan.md) - Section 1.2*

1. Add scoring method to `interface-mpai-agent.php`:
   ```php
   /**
    * Evaluate ability to handle this request
    *
    * @param string $message User message
    * @param array $context Additional context
    * @return int Score from 0-100
    */
   public function evaluate_request($message, $context = []);
   ```

2. Implement in `class-mpai-base-agent.php`:
   ```php
   /**
    * Evaluate ability to handle this request
    *
    * @param string $message User message
    * @param array $context Additional context
    * @return int Score from 0-100
    */
   public function evaluate_request($message, $context = []) {
     // Base implementation using keywords
     $score = 0;
     $message_lower = strtolower($message);
     
     // Check for agent-specific keywords
     foreach ($this->keywords as $keyword => $weight) {
       if (strpos($message_lower, $keyword) !== false) {
         $score += $weight;
       }
     }
     
     // Cap at 100
     return min($score, 100);
   }
   ```

3. Update agent selection in orchestrator:
   ```php
   private function determine_primary_intent($message, $context = []) {
     // Default to memberpress management
     if (empty($message)) {
       return 'memberpress_management';
     }
     
     // Use agent scoring
     $agent_scores = [];
     
     foreach ($this->agents as $agent_id => $agent) {
       $score = $agent->evaluate_request($message, $context);
       $agent_scores[$agent_id] = $score;
     }
     
     // Find highest scoring agent
     $highest_score = 0;
     $primary_agent = 'memberpress_management';
     
     foreach ($agent_scores as $agent_id => $score) {
       if ($score > $highest_score) {
         $highest_score = $score;
         $primary_agent = $agent_id;
       }
     }
     
     // Log scores for debugging
     $this->logger->debug("Agent scores: " . json_encode($agent_scores));
     
     return $primary_agent;
   }
   ```

#### 2.2 Inter-Agent Communication Protocol
*Reference: [_1_agent-system-enhancement-plan.md](./_1_agent-system-enhancement-plan.md) - Section 1.3*

1. Define message format in new file `includes/class-mpai-agent-message.php`:
   ```php
   class MPAI_Agent_Message {
     private $sender;
     private $receiver;
     private $message_type;
     private $content;
     private $metadata;
     private $timestamp;
     
     public function __construct($sender, $receiver, $message_type, $content, $metadata = []) {
       $this->sender = $sender;
       $this->receiver = $receiver;
       $this->message_type = $message_type;
       $this->content = $content;
       $this->metadata = $metadata;
       $this->timestamp = current_time('mysql');
     }
     
     // Getters and setters
     
     public function to_array() {
       return [
         'sender' => $this->sender,
         'receiver' => $this->receiver,
         'message_type' => $this->message_type,
         'content' => $this->content,
         'metadata' => $this->metadata,
         'timestamp' => $this->timestamp
       ];
     }
     
     public static function from_array($data) {
       $message = new self(
         $data['sender'],
         $data['receiver'],
         $data['message_type'],
         $data['content'],
         isset($data['metadata']) ? $data['metadata'] : []
       );
       
       if (isset($data['timestamp'])) {
         $message->timestamp = $data['timestamp'];
       }
       
       return $message;
     }
   }
   ```

2. Update `handle_handoff()` to use message format:
   ```php
   public function handle_handoff($from_agent_id, $to_agent_id, $handoff_data, $user_id = 0) {
     // Create agent message
     $message = new MPAI_Agent_Message(
       $from_agent_id,
       $to_agent_id,
       'handoff',
       isset($handoff_data['message']) ? $handoff_data['message'] : '',
       $handoff_data
     );
     
     // Security validation from agentic-security-framework.md
     if (!$this->validate_agent_message($message)) {
       $this->logger->error("Agent message failed security validation during handoff");
       throw new Exception("Security validation failed for agent message");
     }
     
     // Rest of handoff logic using message format
     // ...
   }
   
   /**
    * Validate agent message for security
    *
    * @param MPAI_Agent_Message $message
    * @return bool
    */
   private function validate_agent_message($message) {
     // Check required fields
     if (empty($message->get_sender()) || empty($message->get_receiver())) {
       return false;
     }
     
     // Check that agents exist
     if (!isset($this->agents[$message->get_sender()]) || 
         !isset($this->agents[$message->get_receiver()])) {
       return false;
     }
     
     // Check for dangerous content patterns
     $content = $message->get_content();
     if (preg_match('/(?:<script|javascript:|eval\(|base64)/i', $content)) {
       return false;
     }
     
     return true;
   }
   ```

3. Add agent message support to base agent class:
   ```php
   /**
    * Process an agent message
    *
    * @param MPAI_Agent_Message $message
    * @param array $context User context
    * @return array Response data
    */
   public function process_message($message, $context = []) {
     // Implementation in base agent
     $intent_data = [
       'intent' => $message->get_message_type(),
       'primary_agent' => $this->id,
       'original_message' => $message->get_content(),
       'metadata' => $message->get_metadata(),
       'from_agent' => $message->get_sender()
     ];
     
     return $this->process_request($intent_data, $context);
   }
   ```

#### 2.3 Response Caching Implementation
*Reference: [_2_performance-optimization-plan.md](./_2_performance-optimization-plan.md) - Section 1.1*

1. Create new response cache class `includes/class-mpai-response-cache.php`:
   ```php
   class MPAI_Response_Cache {
     private $cache = [];
     private $filesystem_cache_enabled = false;
     private $db_cache_enabled = false;
     private $cache_ttl = 3600; // 1 hour default
     
     public function __construct($config = []) {
       // Set configuration
       if (isset($config['filesystem_cache'])) {
         $this->filesystem_cache_enabled = (bool)$config['filesystem_cache'];
       }
       
       if (isset($config['db_cache'])) {
         $this->db_cache_enabled = (bool)$config['db_cache'];
       }
       
       if (isset($config['cache_ttl'])) {
         $this->cache_ttl = (int)$config['cache_ttl'];
       }
     }
     
     /**
      * Get cached response
      *
      * @param string $key Cache key
      * @return mixed|null Cached data or null if not found
      */
     public function get($key) {
       // Check memory cache first
       if (isset($this->cache[$key])) {
         $cached = $this->cache[$key];
         
         // Check if expired
         if (time() <= $cached['expires']) {
           return $cached['data'];
         }
         
         // Expired, remove from memory
         unset($this->cache[$key]);
       }
       
       // Check filesystem cache next
       if ($this->filesystem_cache_enabled) {
         $data = $this->get_from_filesystem($key);
         if ($data !== null) {
           // Store in memory for future use
           $this->cache[$key] = [
             'data' => $data,
             'expires' => time() + $this->cache_ttl
           ];
           
           return $data;
         }
       }
       
       // Check database cache last
       if ($this->db_cache_enabled) {
         $data = $this->get_from_database($key);
         if ($data !== null) {
           // Store in memory for future use
           $this->cache[$key] = [
             'data' => $data,
             'expires' => time() + $this->cache_ttl
           ];
           
           return $data;
         }
       }
       
       return null;
     }
     
     /**
      * Set cached response
      *
      * @param string $key Cache key
      * @param mixed $data Data to cache
      * @param int|null $ttl Optional TTL override
      * @return bool Success
      */
     public function set($key, $data, $ttl = null) {
       $expires = time() + ($ttl !== null ? $ttl : $this->cache_ttl);
       
       // Store in memory
       $this->cache[$key] = [
         'data' => $data,
         'expires' => $expires
       ];
       
       // Store in filesystem if enabled
       if ($this->filesystem_cache_enabled) {
         $this->set_in_filesystem($key, $data, $expires);
       }
       
       // Store in database if enabled
       if ($this->db_cache_enabled) {
         $this->set_in_database($key, $data, $expires);
       }
       
       return true;
     }
     
     // Implementation of filesystem and database methods
     // ...
   }
   ```

2. Integrate with Anthropic class and add content-specific caching rules:
   ```php
   // Add to class-mpai-anthropic.php
   
   private $cache;
   
   public function __construct() {
     // Existing constructor code
     
     // Initialize cache
     $cache_config = [
       'filesystem_cache' => true,
       'db_cache' => false,
       'cache_ttl' => 3600 // 1 hour
     ];
     
     $this->cache = new MPAI_Response_Cache($cache_config);
   }
   
   public function generate_completion($prompt, $options = []) {
     // Disable caching for content creation requests (from content-tools-specification.md)
     $skip_cache = false;
     if (isset($options['type']) && $options['type'] === 'content_creation') {
       $skip_cache = true;
     }
     
     if (!$skip_cache) {
       // Generate cache key
       $cache_key = 'anthropic_' . md5($prompt . json_encode($options));
       
       // Check cache
       $cached_response = $this->cache->get($cache_key);
       if ($cached_response !== null) {
         return $cached_response;
       }
     }
     
     // Existing API call code
     // ...
     
     // Cache the successful response if not skipped
     if (!$skip_cache && is_array($response) && isset($response['success']) && $response['success']) {
       $this->cache->set($cache_key, $response);
     }
     
     return $response;
   }
   ```

### Phase 3: Memory Management and Testing (Weeks 5-6) - COMPLETED ✅

#### 3.1 Agent Memory Management System
*Reference: [_1_agent-system-enhancement-plan.md](./_1_agent-system-enhancement-plan.md) - Section 1.4*

1. Create new memory manager class `includes/class-mpai-memory-manager.php`
2. Implement memory storage with importance-based retention
3. Add conversation context windows and retrieval capabilities

#### 3.2 Unit Tests for Tool Execution
*Reference: [_3_testing-stability-plan.md](./_3_testing-stability-plan.md) - Section 1.2*

1. Create test files for tool registry
2. Add tests for tool validation
3. Implement tests for tool discovery

#### 3.3 PHP Info & Plugin Status Caching
*Reference: [_2_performance-optimization-plan.md](./_2_performance-optimization-plan.md) - Section 5.1*

1. Create a dedicated system information cache
2. Add timed refresh mechanism
3. Preload common query results

### Phase 4: UI Improvements and Error Handling (Weeks 7-8) - CURRENT PHASE

#### 4.1 Standardized Error System ✅
*Reference: [_3_testing-stability-plan.md](./_3_testing-stability-plan.md) - Section 1.1*

1. Create unified error handling approach ✅
2. Implement standardized error objects ✅
3. Add context preservation in errors ✅
4. Implement Error Recovery System with retry and fallback capabilities ✅
5. Add Error Catalog System for consistent error reporting ✅

#### 4.2 UI Rendering Optimization
*Reference: [_2_performance-optimization-plan.md](./_2_performance-optimization-plan.md) - Section 3.2*

1. Implement virtual scrolling for chat history
2. Optimize DOM updates for message rendering
3. Add throttling/debouncing for event handlers

#### 4.3 Tool Result Caching
*Reference: [_2_performance-optimization-plan.md](./_2_performance-optimization-plan.md) - Section 5.3*

1. Add caching for WP-CLI commands with static output
2. Implement TTL-based invalidation for dynamic data
3. Create tool-specific cache strategies

## Starting the Implementation

To begin the implementation:

1. Start with the Agent Discovery Mechanism (1.1) as it provides the foundation for flexible agent management
2. Next, implement the Tool Lazy-Loading (1.3) to improve performance
3. Set up the basic Unit Test framework (1.2) to verify your changes

This sequence provides immediate benefits while establishing the core architecture for subsequent enhancements.

## Testing Your Changes

After each implementation step:

1. Verify the system works as expected with manual testing
2. Run relevant unit tests (once implemented)
3. Check for any performance regressions
4. Ensure all error cases are properly handled

## Security Considerations

Throughout implementation, incorporate security controls from `agentic-security-framework.md`:

1. **Agent validation** - Validate all discovered agents for required methods and capabilities
2. **Message validation** - Check inter-agent messages for unsafe content
3. **Command sanitization** - Carefully validate and sanitize all commands before execution
4. **Access restrictions** - Implement capability-based access controls for agent actions
5. **Logging and monitoring** - Add comprehensive logging of agent activities

## Documentation Updates

As you implement each feature:

1. Update the relevant planning documents with any changes to the implementation approach
2. Document any new configuration options in appropriate files
3. Add implementation notes to help future developers understand your changes