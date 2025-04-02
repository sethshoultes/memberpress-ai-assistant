# MemberPress Support Routing System

**STATUS: Implemented in version 1.5.8 (2025-04-02)**

## Overview

The Support Routing System implements a tiered support infrastructure for MemberPress. The system leverages Docsbot as a documentation resource first, then routes to MemberPress official customer support when needed for account-specific issues, billing questions, or complex technical problems.

## Key Features

1. **Tiered Support Approach**:
   - Tier 1: AI Assistant for basic questions and guidance
   - Tier 2: Docsbot integration for documentation-based answers
   - Tier 3: Human support escalation for complex issues

2. **Docsbot Integration**:
   - Seamless connection to Docsbot's Chat Agent API
   - Contextual documentation queries based on conversation history
   - Source retrieval and citation from MemberPress knowledge base

3. **Support Detection System**:
   - Intelligent analysis of when to escalate to human support
   - Detection of account-specific issues requiring admin access
   - Recognition of complex technical problems beyond AI capabilities

4. **Human Support Integration**:
   - Secure API connector to MemberPress support platform
   - Context-preserving handoff to support tickets
   - Inclusion of system information and conversation history

5. **Analytics and Tracking**:
   - Comprehensive logging of all support interactions
   - Reporting on resolution rates across support tiers
   - Feedback collection to improve AI and documentation

## Implementation Details

### Docsbot Connector

The `MPAI_Docsbot_Connector` class handles communication with the Docsbot API:

```php
class MPAI_Docsbot_Connector {
    private $team_id;
    private $bot_id;
    private $api_endpoint;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->team_id = get_option('mpai_docsbot_team_id', '');
        $this->bot_id = get_option('mpai_docsbot_bot_id', '');
        $this->api_endpoint = "https://api.docsbot.ai/teams/{$this->team_id}/bots/{$this->bot_id}/chat-agent";
    }
    
    /**
     * Send a query to Docsbot Chat Agent
     *
     * @param string $question User's question
     * @param string $conversation_id Unique conversation ID
     * @param array $options Additional options
     * @return array|WP_Error Response from Docsbot or error
     */
    public function query($question, $conversation_id = null, $options = []) {
        // Implementation details...
    }
    
    /**
     * Determine if Docsbot response requires human escalation
     *
     * @param array $response Docsbot response
     * @return bool True if human support is needed
     */
    public function needs_human_support($response) {
        // Implementation details...
    }
}
```

### Support Detector

The `MPAI_Support_Detector` class analyzes conversation context to determine when human support is needed:

```php
class MPAI_Support_Detector {
    /**
     * Check if a conversation should be routed to human support
     *
     * @param array $conversation The conversation history
     * @param array $docsbot_response The latest response from Docsbot
     * @return bool|array False if no support needed, or array with reason if support needed
     */
    public function should_route_to_support($conversation, $docsbot_response = null) {
        // Implementation details...
    }
}
```

### Support Connector

The `MPAI_Support_Connector` class handles the integration with MemberPress's support system:

```php
class MPAI_Support_Connector {
    private $api_key;
    private $api_endpoint;
    
    /**
     * Create a support ticket
     *
     * @param array $ticket_data Ticket information
     * @param array $conversation_history Full conversation history
     * @param array $docsbot_interactions Docsbot interaction history
     * @return array|WP_Error Result of ticket creation
     */
    public function create_ticket($ticket_data, $conversation_history = [], $docsbot_interactions = []) {
        // Implementation details...
    }
    
    /**
     * Generate a pre-filled support form URL
     *
     * @param array $data Form data to pre-fill
     * @param array $conversation_history Full conversation history
     * @param array $docsbot_interactions Docsbot interaction history
     * @return string Support form URL
     */
    public function get_support_form_url($data, $conversation_history = [], $docsbot_interactions = []) {
        // Implementation details...
    }
}
```

### AI Tools Integration

The system adds two new tools to the AI assistant:

1. `query_documentation` - For searching the MemberPress documentation via Docsbot
2. `route_to_support` - For escalating conversations to human support

```php
// query_documentation tool definition
private function get_documentation_tool_definition() {
    return [
        'name' => 'query_documentation',
        'description' => 'Query the MemberPress documentation using Docsbot to find answers from official docs',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The documentation query to send to Docsbot'
                ],
                'conversation_id' => [
                    'type' => 'string',
                    'description' => 'The unique conversation ID (optional)'
                ]
            ],
            'required' => ['query']
        ]
    ];
}

// route_to_support tool definition
private function get_support_routing_tool_definition() {
    return [
        'name' => 'route_to_support',
        'description' => 'Route the current conversation to MemberPress human support when documentation cannot resolve the issue',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'reason' => [
                    'type' => 'string',
                    'enum' => [
                        'docs_insufficient', 
                        'account_specific',
                        'repeated_attempts',
                        'user_requested',
                        'licensing_issue'
                    ],
                    'description' => 'The reason for routing to support'
                ],
                'summary' => [
                    'type' => 'string',
                    'description' => 'A brief summary of the issue for support staff'
                ],
                'priority' => [
                    'type' => 'string',
                    'enum' => ['low', 'medium', 'high'],
                    'default' => 'medium',
                    'description' => 'The priority level of the issue'
                ]
            ],
            'required' => ['reason', 'summary']
        ]
    ];
}
```

## Analytics Implementation

The system includes comprehensive analytics tracking through the `MPAI_Support_Analytics` class:

```php
class MPAI_Support_Analytics {
    /**
     * Log a Docsbot interaction
     */
    public function log_docsbot_interaction($data) {
        // Implementation details...
    }
    
    /**
     * Log a support routing event
     */
    public function log_routing_event($data) {
        // Implementation details...
    }
    
    /**
     * Get support routing statistics
     */
    public function get_support_stats($filters = []) {
        // Implementation details...
    }
    
    /**
     * Log user feedback
     */
    public function log_support_feedback($data) {
        // Implementation details...
    }
}
```

## Configuration Options

The Support Routing System adds the following configuration options in the MemberPress AI Assistant settings:

1. **Docsbot Integration**:
   - Enable/disable Docsbot integration
   - Docsbot Team ID
   - Docsbot Bot ID
   - Docsbot API Key

2. **Support Routing**:
   - Enable/disable human support routing
   - Support API Key for direct ticket creation
   - Enable automatic support detection
   - Default support email address

## User Experience

The system provides a seamless user experience with clear transitions between support tiers:

1. **Initial AI Assistance**:
   - User interacts with the MemberPress AI Assistant
   - AI attempts to answer questions directly when possible

2. **Documentation Integration**:
   - For questions requiring documentation, AI uses `query_documentation` tool
   - Documentation results are displayed with source references
   - User can see that information comes from official documentation

3. **Support Escalation**:
   - If documentation is insufficient, AI suggests human support
   - User is presented with support options dialog
   - Conversation context is preserved in the support handoff

## Benefits

1. **Reduced Support Burden**:
   - Many questions answered directly by AI or documentation
   - Only complex issues escalated to human support staff
   - Better context for support staff when issues are escalated

2. **Improved User Experience**:
   - Faster resolution for common questions
   - Seamless transitions between support tiers
   - Consistent experience across all support channels

3. **Data-Driven Improvements**:
   - Analytics identify documentation gaps
   - Support patterns help prioritize feature development
   - Feedback loop improves AI capabilities over time