# 🦴 Scooby Snack: Agent Specialization Scoring System Implementation

## Problem

The MemberPress AI Assistant needed a more sophisticated way to determine which specialized agent should handle user requests. The initial implementation relied on basic keyword matching, which was insufficient for accurately routing requests, especially when they contained ambiguous terms or required context-aware processing.

## Solution

We implemented a comprehensive Agent Specialization Scoring system with weighted confidence scoring, capability analysis, and contextual modifiers.

### Implementation Details

1. **Enhanced Base Agent Class**
   ```php
   // In class-mpai-base-agent.php
   public function evaluate_request($message, $context = []) {
       // Base implementation using weighted scoring algorithm
       $confidence_score = 0;
       $message_lower = strtolower($message);
       
       // 1. Keyword matching (basic matching)
       foreach ($this->keywords as $keyword => $weight) {
           if (strpos($message_lower, $keyword) !== false) {
               $confidence_score += $weight;
           }
       }
       
       // 2. Context awareness
       if (!empty($context)) {
           // Check for previous successful interactions
           // Add user preference bonuses
       }
       
       // 3. Capability-based scoring
       $capability_score = $this->evaluate_capability_match($message, $context);
       $confidence_score += $capability_score;
       
       // Cap at 100
       return min($confidence_score, 100);
   }
   ```

2. **Orchestrator Improvements**
   ```php
   // In class-mpai-agent-orchestrator.php
   private function determine_primary_intent($message, $context = []) {
       // Get agent confidence scores
       $agent_scores = $this->get_agent_confidence_scores($message, $context);
       
       // Apply weighted selection algorithm with confidence threshold
       $primary_agent = $this->select_agent_with_confidence($agent_scores, $message);
       
       return $primary_agent;
   }
   ```

3. **Weighted Keyword Dictionaries**
   ```php
   // In specialized agent classes
   $this->keywords = [
       // High weight for direct mentions
       'memberpress' => 35,
       'member press' => 35,
       
       // Medium weight for related terms
       'membership' => 25,
       'subscriptions' => 20,
       'transaction' => 20,
       
       // Lower weight for general concepts
       'license' => 10,
       'access' => 5,
       'user' => 3,
   ];
   ```

4. **Capability-Based Analysis**
   ```php
   protected function evaluate_capability_match($message, $context = []) {
       $capability_score = 0;
       $message_lower = strtolower($message);
       
       // Check each capability for relevance to the message
       foreach ($this->capabilities as $capability_key => $capability_description) {
           // Convert capability key and description to relevant terms
           $capability_terms = array_merge(
               $this->extract_terms($capability_key),
               $this->extract_terms($capability_description)
           );
           
           // Score based on term matching
           foreach ($capability_terms as $term) {
               if (strpos($message_lower, $term) !== false) {
                   $capability_score += 10;
                   break;
               }
           }
       }
       
       return min($capability_score, 50);
   }
   ```

5. **Contextual Modifiers**
   ```php
   private function apply_contextual_modifiers($agent_id, $base_score, $message, $context = []) {
       $modified_score = $base_score;
       
       // Conversation continuity bonus
       if (isset($context['memory']) && is_array($context['memory'])) {
           $last_memory = end($context['memory']);
           if ($last_memory && 
               isset($last_memory['result']['agent']) && 
               $last_memory['result']['agent'] === $agent_id) {
               $modified_score += 15;
           }
       }
       
       // Specialized agent bonus
       if ($agent_id !== 'memberpress') {
           $modified_score += 5;
       }
       
       // Performance history adjustment
       // ...
       
       return max(0, min($modified_score, 100));
   }
   ```

6. **Confidence Thresholds & Tiebreakers**
   ```php
   private function select_agent_with_confidence($agent_scores, $message) {
       // Find highest scoring agent
       $highest_score = 0;
       $primary_agent = 'memberpress';
       
       foreach ($agent_scores as $agent_id => $score) {
           if ($score > $highest_score) {
               $highest_score = $score;
               $primary_agent = $agent_id;
           }
       }
       
       // Apply confidence threshold
       $confidence_threshold = 30;
       if ($highest_score < $confidence_threshold) {
           return 'memberpress';
       }
       
       // Apply tiebreakers for similar scores
       // ...
       
       return $primary_agent;
   }
   ```

7. **Comprehensive Testing Framework**
   ```php
   function mpai_test_agent_specialization_scoring() {
       // Initialize the Agent Orchestrator
       $orchestrator = new MPAI_Agent_Orchestrator();
       
       // Define test cases for different agent types
       $test_messages = [
           // MemberPress agent test cases
           'memberpress' => [
               'Show me all MemberPress memberships',
               'How many active subscriptions do we have?',
               // ...
           ],
           
           // Command validation agent test cases
           'command_validation' => [
               'Validate this WP-CLI command: wp plugin activate memberpress',
               // ...
           ],
           
           // Generic/ambiguous test cases
           'generic' => [
               'What plugins are installed?',
               // ...
           ]
       ];
       
       // Test each message against each agent type
       // ...
   }
   ```

8. **Diagnostic Integration**
   ```javascript
   // In settings-diagnostic.php
   $('#run-agent-scoring-test').on('click', function() {
       runPhaseTest('test_agent_scoring', '#agent-scoring-result', '#agent-scoring-status-indicator', 'Phase Two');
   });
   
   // Handle the agent specialization scoring test
   if (result.formatted_html) {
       // If the test provides its own formatted HTML, use it
       html = result.formatted_html;
   } else if (result.success) {
       html += '<div class="success"><strong>✓ Agent Specialization Scoring successful!</strong></div>';
       // ...
   }
   ```

## Challenges Encountered

1. **Test Failures Due to ID Mismatch**: Initial tests had a pass rate of only 67% because test expectations were using `command_validation_agent` while the actual system used `command_validation`.

2. **Incorrect Phase Labeling**: Console logs were showing "Phase One test" even for Phase Two tests because they were using the same function.

3. **Ambiguous Message Handling**: Messages that could reasonably be handled by multiple agents needed tiebreaker logic to ensure consistent routing.

4. **Keyword Weight Balancing**: Determining appropriate weights for keywords required careful balancing to prevent overly specialized or generalized routing.

## Solutions Applied

1. **Fixed Agent ID in Tests**: Updated the test case definitions to match actual agent IDs in the system.

2. **Created Generic Test Function**: Replaced phase-specific functions with a generic `runPhaseTest` function that takes a phase label parameter.

3. **Added Tiered Scoring Logic**: Implemented multiple scoring layers with different weights and contextual modifiers.

4. **Enhanced Test Visualization**: Created comprehensive HTML test result visualization for easier debugging.

5. **Improved Console Logging**: Added consistent phase labeling and test identification in logs.

## Benefits

1. **Improved Routing Accuracy**: Tests show significantly better request routing to appropriate specialized agents.

2. **Contextual Awareness**: The system now considers conversation history and user preferences in routing decisions.

3. **Capability-Based Assignment**: Requests are matched to agents with the most relevant capabilities rather than just keyword matches.

4. **Comprehensive Testing**: The test suite provides visibility into scoring distribution and agent selection.

5. **Extensibility**: The system is designed to accommodate new specialized agents without requiring major changes to the core routing logic.

## Next Steps and Future Enhancements

1. **Machine Learning Integration**: Train models on past routing decisions for dynamic weight adjustment.

2. **Semantic Understanding**: Implement embeddings for better semantic matching beyond keyword detection.

3. **Performance Optimization**: Add caching for partial scores and reduced computation for common patterns.

4. **User Preference Learning**: Incorporate user feedback to personalize agent routing over time.

5. **Cross-Agent Collaboration**: Enhance the system to distribute complex requests across multiple agents when appropriate.

🦴 Scooby Snack: Phase Two agent specialization scoring successfully implemented and tested!