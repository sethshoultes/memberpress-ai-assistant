# Getting Started with MemberPress AI Assistant

## Introduction

This guide will help you quickly get started with the MemberPress AI Assistant plugin. Whether you're a site administrator looking to leverage AI for your membership site or a developer extending the plugin's functionality, this guide provides the essential information to get you up and running.

## For Site Administrators

### Quick Start

1. **Install and Activate**
   - Install the MemberPress AI Assistant plugin from the WordPress plugin repository or by uploading the zip file
   - Activate the plugin through the WordPress admin interface
   - Navigate to AI Assistant > Settings to begin configuration

2. **Start Using the AI Assistant**
   - Navigate to AI Assistant > Chat in your WordPress admin
   - Type a question or command in the chat interface
   - Receive AI-generated insights and assistance
   - No API key configuration required - the system works out of the box!

### Common Tasks

#### Analyzing Membership Data

Ask questions like:
- "What are my top-selling memberships this month?"
- "How many active subscribers do I have?"
- "What's the average subscription duration?"
- "Show me membership revenue trends for the past 6 months"

#### Managing Memberships

Use commands like:
- "Create a new monthly membership called 'Premium Plan' for $29.99"
- "Update the price of the Gold membership to $39.99"
- "Add access to the 'Marketing Course' page for Silver members"
- "Show me all memberships with their access rules"

#### User Management

Ask for information like:
- "Show me users who joined in the last 30 days"
- "How many users have the Gold membership?"
- "List users with expiring subscriptions this month"
- "Find users who haven't logged in for 90 days"

#### Content Assistance

Request help with:
- "Draft a welcome email for new members"
- "Create a membership comparison table for my pricing page"
- "Suggest content ideas for my membership blog"
- "Help me write a cancellation survey"

### Tips for Effective Use

1. **Be Specific**: The more specific your questions or commands, the better the AI can assist you.
2. **Use Natural Language**: You don't need special syntax; ask questions as you would to a human assistant.
3. **Provide Context**: If your question relates to specific memberships or timeframes, include that information.
4. **Review Suggestions**: Always review AI-generated content before publishing or implementing changes.
5. **Iterative Refinement**: If the response isn't exactly what you need, ask follow-up questions to refine it.

## For Developers

### System Overview

The MemberPress AI Assistant is built on a modular architecture with several key components:

1. **Agent System**: Specialized AI agents that handle different types of requests
2. **Tool System**: Reusable operations with standardized interfaces
3. **Service Layer**: Business logic and integration with external systems
4. **Dependency Injection**: Component creation and wiring

For a comprehensive overview, see the [System Architecture](system-architecture.md) documentation.

### Setting Up a Development Environment

1. **Clone the Repository**
   ```bash
   git clone https://github.com/memberpress/memberpress-ai-assistant.git
   cd memberpress-ai-assistant
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure for Development**
   - Copy `.env.example` to `.env` and configure your development settings
   - Set up a local WordPress installation with MemberPress
   - Activate the plugin in development mode

4. **Run Tests**
   ```bash
   composer test      # Run PHP tests
   npm test           # Run JavaScript tests
   ```

### Extending the Plugin

#### Creating a Custom Agent

1. Create a new class that extends `AbstractAgent`:
   ```php
   <?php
   namespace YourNamespace\Agents;
   
   use MemberpressAiAssistant\Abstracts\AbstractAgent;
   
   class CustomAgent extends AbstractAgent {
       protected function registerCapabilities(): void {
           $this->addCapability('custom_capability', [
               'operations' => ['operation1', 'operation2'],
           ]);
       }
       
       protected function calculateIntentMatchScore(array $request): float {
           // Your scoring logic here
           return $score;
       }
       
       public function processRequest(array $request, array $context): array {
           // Your request processing logic here
           return $response;
       }
   }
   ```

2. Register your agent with the registry:
   ```php
   add_action('mpai_register_agents', function($registry) {
       $registry->registerAgent(CustomAgent::class);
   });
   ```

#### Creating a Custom Tool

1. Create a new class that extends `AbstractTool`:
   ```php
   <?php
   namespace YourNamespace\Tools;
   
   use MemberpressAiAssistant\Abstracts\AbstractTool;
   
   class CustomTool extends AbstractTool {
       protected function defineOperations(): array {
           return [
               'custom_operation' => [
                   'parameters' => [
                       'param1' => ['type' => 'string', 'required' => true],
                       'param2' => ['type' => 'integer', 'required' => false],
                   ],
               ],
           ];
       }
       
       public function executeOperation(string $operation, array $parameters): array {
           if ($operation === 'custom_operation') {
               return $this->customOperation($parameters);
           }
           
           return $this->createErrorResponse('Invalid operation');
       }
       
       protected function customOperation(array $parameters): array {
           // Your operation logic here
           return $this->createSuccessResponse($result);
       }
   }
   ```

2. Register your tool with the registry:
   ```php
   add_action('mpai_register_tools', function($registry) {
       $registry->registerTool('custom_tool', CustomTool::class);
   });
   ```

#### Using the Dependency Injection Container

1. Register your services with the container:
   ```php
   add_action('mpai_register_services', function($container) {
       $container->singleton('custom_service', function($container) {
           return new CustomService(
               $container->make('dependency1'),
               $container->make('dependency2')
           );
       });
   });
   ```

2. Use the container to resolve dependencies:
   ```php
   $service = $container->make('custom_service');
   ```

### API Integration

The plugin provides several APIs for integration:

#### WordPress Hooks

```php
// Filter agent selection scores
add_filter('mpai_agent_selection_scores', function($scores, $request) {
    // Modify scores
    return $scores;
}, 10, 2);

// Action when a chat message is processed
add_action('mpai_after_chat_message', function($message, $response) {
    // Do something with the message and response
}, 10, 2);
```

#### JavaScript Integration

```javascript
// Listen for chat events
document.addEventListener('mpai:chat:message', function(event) {
    const { message, response } = event.detail;
    // Do something with the message and response
});

// Send a message programmatically
window.MPAI.chat.sendMessage('Hello, AI Assistant!');
```

#### REST API

The plugin provides a REST API for programmatic access:

```
GET /wp-json/memberpress-ai/v1/memberships
POST /wp-json/memberpress-ai/v1/chat
```

For more details, see the API documentation (available to developers).

The system is designed to work without requiring users to provide their own API keys, making it simple and accessible for all users.

### Development Best Practices

1. **Follow the Architecture**: Understand and follow the plugin's architectural patterns
2. **Use Dependency Injection**: Avoid direct instantiation of dependencies
3. **Write Tests**: Add tests for your custom functionality
4. **Document Your Code**: Add PHPDoc comments and update documentation
5. **Use Type Hints**: Add type hints to method parameters and return values
6. **Follow WordPress Coding Standards**: Adhere to WordPress coding standards
7. **Handle Errors Gracefully**: Implement proper error handling
8. **Consider Performance**: Be mindful of performance implications

## Next Steps

### For Site Administrators

- Explore the [Chat Interface](chat-interface.md) documentation
- Learn about [Membership Operations](membership-operations.md)
- Understand [User Integration](user-integration.md)
- Configure the plugin using the [Installation and Configuration](installation-configuration.md) guide

### For Developers

- Study the [System Architecture](system-architecture.md)
- Understand the [Agent Architecture](agent-architecture.md)
- Learn about [Available Tools](available-tools.md)
- Explore the [Dependency Injection](dependency-injection.md) system

## Support and Resources

- **Documentation**: Comprehensive documentation is available in the `docs` directory
- **Support**: Contact support@memberpress.com for assistance
- **GitHub**: Report issues and contribute on GitHub
- **Developer Blog**: Stay updated with the latest developments on the MemberPress blog

## Conclusion

The MemberPress AI Assistant provides powerful AI capabilities to enhance your membership site. By following this guide, you can quickly get started with using or extending the plugin to meet your specific needs.

Whether you're analyzing membership data, managing users, or creating content, the AI assistant is designed to make your work easier and more efficient. As you become more familiar with the plugin, you'll discover even more ways to leverage its capabilities.