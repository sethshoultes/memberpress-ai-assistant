# MemberPress Copilot Extension Guidelines

## Overview

This guide provides comprehensive instructions for extending the MemberPress Copilot plugin. Whether you're creating custom agents, tools, services, or integrations, this document will help you follow best practices and maintain compatibility with the core system.

## Extension Architecture

```
Your Extension
├── Agents/              # Custom AI agents
├── Tools/               # Custom operation tools
├── Services/            # Custom business logic services
├── Integrations/        # Third-party integrations
└── Assets/              # Frontend resources
```

## Core Extension Patterns

### 1. Agent Extensions

Agents are the primary extension point for adding new AI capabilities.

#### Creating a Custom Agent

```php
<?php
namespace YourPlugin\Agents;

use MemberPressCopilot\Abstracts\AbstractAgent;

class CustomIntegrationAgent extends AbstractAgent {
    
    public function __construct() {
        parent::__construct('custom_integration_agent');
        $this->description = 'Handles third-party service integrations';
    }
    
    public function canHandle(array $context): bool {
        $message = strtolower($context['message'] ?? '');
        return strpos($message, 'mailchimp') !== false || 
               strpos($message, 'zapier') !== false;
    }
    
    public function execute(array $context): array {
        // Your implementation
        return [
            'success' => true,
            'response' => 'Integration completed',
            'agent' => $this->getName()
        ];
    }
    
    public function getScore(array $context): float {
        if (!$this->canHandle($context)) {
            return 0.0;
        }
        
        $message = strtolower($context['message'] ?? '');
        if (strpos($message, 'mailchimp') !== false) {
            return 0.85;
        }
        
        return 0.7;
    }
}

// Register your agent
add_action('init', function() {
    global $mpai_service_locator;
    $agent_registry = $mpai_service_locator->get('agent_registry');
    $agent_registry->register('custom_integration_agent', new CustomIntegrationAgent());
});
```

#### Agent Best Practices

1. **Specific Scope**: Focus on one domain or integration
2. **Clear Scoring**: Use consistent scoring logic
3. **Error Handling**: Always return structured responses
4. **Context Awareness**: Use available context data effectively

### 2. Tool Extensions

Tools provide reusable operations that agents can utilize.

#### Creating a Custom Tool

```php
<?php
namespace YourPlugin\Tools;

use MemberPressCopilot\Abstracts\AbstractTool;

class MailchimpTool extends AbstractTool {
    
    private $api_client;
    
    public function __construct() {
        $this->api_client = $this->initializeMailchimpClient();
    }
    
    public function execute(array $parameters): array {
        try {
            $action = $parameters['action'] ?? '';
            
            switch ($action) {
                case 'add_subscriber':
                    return $this->addSubscriber($parameters);
                case 'create_campaign':
                    return $this->createCampaign($parameters);
                default:
                    throw new \InvalidArgumentException('Unknown action: ' . $action);
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getSchema(): array {
        return [
            'name' => 'mailchimp_tool',
            'description' => 'Integrates with Mailchimp email marketing platform',
            'parameters' => [
                'action' => [
                    'type' => 'string',
                    'description' => 'Action to perform',
                    'enum' => ['add_subscriber', 'create_campaign'],
                    'required' => true
                ],
                'email' => [
                    'type' => 'string',
                    'format' => 'email',
                    'description' => 'Email address for subscriber actions'
                ],
                'list_id' => [
                    'type' => 'string',
                    'description' => 'Mailchimp list identifier'
                ]
            ]
        ];
    }
    
    private function addSubscriber(array $parameters): array {
        $email = $parameters['email'] ?? '';
        $list_id = $parameters['list_id'] ?? '';
        
        if (!$email || !$list_id) {
            throw new \InvalidArgumentException('Email and list_id are required');
        }
        
        // Mailchimp API integration
        $result = $this->api_client->lists->addListMember($list_id, [
            'email_address' => $email,
            'status' => 'subscribed'
        ]);
        
        return [
            'success' => true,
            'subscriber_id' => $result->id,
            'email' => $email
        ];
    }
    
    private function createCampaign(array $parameters): array {
        // Campaign creation logic
        return [
            'success' => true,
            'campaign_id' => 'new_campaign_id'
        ];
    }
    
    private function initializeMailchimpClient() {
        // Initialize Mailchimp client with API key
        $api_key = get_option('mpai_mailchimp_api_key', '');
        return new \MailchimpMarketing\ApiClient();
    }
}

// Register your tool
add_action('init', function() {
    global $mpai_service_locator;
    $tool_registry = $mpai_service_locator->get('tool_registry');
    $tool_registry->register('mailchimp_tool', new MailchimpTool());
});
```

#### Tool Best Practices

1. **Parameter Validation**: Always validate input parameters
2. **Error Handling**: Use structured error responses
3. **Caching**: Consider using CachedToolWrapper for expensive operations
4. **Documentation**: Provide clear schema definitions

### 3. Service Extensions

Services handle business logic and integrations.

#### Creating a Custom Service

```php
<?php
namespace YourPlugin\Services;

use MemberPressCopilot\Abstracts\AbstractService;

class IntegrationService extends AbstractService {
    
    private $enabled_integrations = [];
    
    public function __construct() {
        parent::__construct('integration_service');
        $this->loadEnabledIntegrations();
    }
    
    public function register($service_locator): void {
        // Register with service locator
        $service_locator->register($this->getName(), $this);
    }
    
    public function boot(): void {
        // Initialize integrations
        foreach ($this->enabled_integrations as $integration) {
            $this->initializeIntegration($integration);
        }
        
        // Add WordPress hooks
        add_action('mpai_after_membership_created', [$this, 'handleMembershipCreated']);
        add_action('mpai_after_user_subscribed', [$this, 'handleUserSubscribed']);
    }
    
    public function getIntegration(string $name): ?object {
        return $this->enabled_integrations[$name] ?? null;
    }
    
    public function addIntegration(string $name, object $integration): void {
        $this->enabled_integrations[$name] = $integration;
    }
    
    public function handleMembershipCreated(array $membership_data): void {
        // Sync with external services
        foreach ($this->enabled_integrations as $name => $integration) {
            if (method_exists($integration, 'onMembershipCreated')) {
                $integration->onMembershipCreated($membership_data);
            }
        }
    }
    
    public function handleUserSubscribed(array $user_data): void {
        // Handle user subscription events
        $this->syncToMailchimp($user_data);
        $this->updateCRM($user_data);
    }
    
    private function loadEnabledIntegrations(): void {
        $integrations = get_option('mpai_enabled_integrations', []);
        
        foreach ($integrations as $integration_name) {
            $class_name = "YourPlugin\\Integrations\\" . ucfirst($integration_name) . "Integration";
            
            if (class_exists($class_name)) {
                $this->enabled_integrations[$integration_name] = new $class_name();
            }
        }
    }
    
    private function syncToMailchimp(array $user_data): void {
        if (!isset($this->enabled_integrations['mailchimp'])) {
            return;
        }
        
        global $mpai_service_locator;
        $tool_registry = $mpai_service_locator->get('tool_registry');
        $mailchimp_tool = $tool_registry->get('mailchimp_tool');
        
        $mailchimp_tool->execute([
            'action' => 'add_subscriber',
            'email' => $user_data['email'],
            'list_id' => get_option('mpai_default_mailchimp_list')
        ]);
    }
    
    private function updateCRM(array $user_data): void {
        // CRM integration logic
    }
}

// Register your service
add_action('init', function() {
    global $mpai_service_locator;
    $integration_service = new IntegrationService();
    $integration_service->register($mpai_service_locator);
    $integration_service->boot();
});
```

#### Service Best Practices

1. **Single Responsibility**: Each service should have a clear purpose
2. **Event-Driven**: Use WordPress hooks for loose coupling
3. **Configuration**: Make services configurable
4. **Dependency Management**: Use the service locator properly

### 4. Frontend Extensions

Extend the chat interface and admin UI.

#### Custom Chat Interface Component

```javascript
// assets/js/custom-chat-component.js
class CustomChatComponent {
    constructor(chatInterface) {
        this.chatInterface = chatInterface;
        this.initializeComponent();
    }
    
    initializeComponent() {
        // Add custom buttons to chat interface
        this.addQuickActionButtons();
        
        // Listen for custom events
        document.addEventListener('mpai:chat_response', (event) => {
            this.handleChatResponse(event.detail);
        });
    }
    
    addQuickActionButtons() {
        const chatContainer = document.querySelector('.mpai-chat-container');
        if (!chatContainer) return;
        
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'mpai-quick-actions';
        buttonContainer.innerHTML = `
            <button class="button button-secondary" data-action="revenue-report">
                📊 Revenue Report
            </button>
            <button class="button button-secondary" data-action="member-stats">
                👥 Member Stats
            </button>
            <button class="button button-secondary" data-action="mailchimp-sync">
                📧 Sync Mailchimp
            </button>
        `;
        
        // Add event listeners
        buttonContainer.addEventListener('click', (event) => {
            if (event.target.matches('[data-action]')) {
                this.handleQuickAction(event.target.dataset.action);
            }
        });
        
        chatContainer.insertBefore(buttonContainer, chatContainer.firstChild);
    }
    
    handleQuickAction(action) {
        const messages = {
            'revenue-report': 'Generate a revenue report for this month',
            'member-stats': 'Show me member statistics and growth metrics',
            'mailchimp-sync': 'Sync our member list with Mailchimp'
        };
        
        const message = messages[action];
        if (message) {
            this.chatInterface.sendMessage(message);
        }
    }
    
    handleChatResponse(response) {
        // Custom handling for specific response types
        if (response.data && response.data.type === 'mailchimp_sync') {
            this.showMailchimpSyncResults(response.data);
        }
    }
    
    showMailchimpSyncResults(data) {
        // Display custom UI for Mailchimp sync results
        const notification = document.createElement('div');
        notification.className = 'notice notice-success';
        notification.innerHTML = `
            <p>Mailchimp sync completed: ${data.synced_count} members synced</p>
        `;
        
        document.querySelector('.mpai-chat-messages').appendChild(notification);
    }
}

// Initialize when chat interface is ready
document.addEventListener('DOMContentLoaded', function() {
    if (window.MPAIChatInterface) {
        new CustomChatComponent(window.MPAIChatInterface);
    }
});
```

#### CSS Styling for Extensions

```css
/* assets/css/custom-extensions.css */
.mpai-quick-actions {
    padding: 15px;
    border-bottom: 1px solid #ddd;
    background: #f9f9f9;
}

.mpai-quick-actions button {
    margin-right: 10px;
    margin-bottom: 5px;
}

.mpai-integration-status {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.mpai-integration-status.connected {
    background: #d4edda;
    color: #155724;
}

.mpai-integration-status.disconnected {
    background: #f8d7da;
    color: #721c24;
}
```

### 5. Configuration Extensions

Add custom settings and configuration options.

#### Settings Integration

```php
<?php
// Add custom settings tab
add_filter('mpai_settings_tabs', function($tabs) {
    $tabs['integrations'] = 'Integrations';
    return $tabs;
});

// Add settings fields
add_action('mpai_settings_tab_integrations', function() {
    ?>
    <h3>Third-Party Integrations</h3>
    
    <table class="form-table">
        <tr>
            <th scope="row">Mailchimp Integration</th>
            <td>
                <label>
                    <input type="checkbox" name="mpai_mailchimp_enabled" value="1" 
                           <?php checked(get_option('mpai_mailchimp_enabled')); ?>>
                    Enable Mailchimp integration
                </label>
                <p class="description">Automatically sync members to Mailchimp lists</p>
            </td>
        </tr>
        <tr>
            <th scope="row">Mailchimp API Key</th>
            <td>
                <input type="password" name="mpai_mailchimp_api_key" 
                       value="<?php echo esc_attr(get_option('mpai_mailchimp_api_key')); ?>"
                       class="regular-text">
                <p class="description">Your Mailchimp API key</p>
            </td>
        </tr>
        <tr>
            <th scope="row">Default List ID</th>
            <td>
                <input type="text" name="mpai_default_mailchimp_list" 
                       value="<?php echo esc_attr(get_option('mpai_default_mailchimp_list')); ?>"
                       class="regular-text">
                <p class="description">Default Mailchimp list for new members</p>
            </td>
        </tr>
    </table>
    <?php
});

// Save custom settings
add_action('mpai_save_settings', function() {
    if (isset($_POST['mpai_mailchimp_enabled'])) {
        update_option('mpai_mailchimp_enabled', 1);
    } else {
        update_option('mpai_mailchimp_enabled', 0);
    }
    
    if (isset($_POST['mpai_mailchimp_api_key'])) {
        update_option('mpai_mailchimp_api_key', sanitize_text_field($_POST['mpai_mailchimp_api_key']));
    }
    
    if (isset($_POST['mpai_default_mailchimp_list'])) {
        update_option('mpai_default_mailchimp_list', sanitize_text_field($_POST['mpai_default_mailchimp_list']));
    }
});
```

## Extension Packaging

### Plugin Structure

```
your-extension-plugin/
├── your-extension.php           # Main plugin file
├── includes/
│   ├── class-main.php          # Main plugin class
│   ├── agents/                 # Custom agents
│   ├── tools/                  # Custom tools
│   ├── services/               # Custom services
│   └── integrations/           # Third-party integrations
├── assets/
│   ├── css/                    # Stylesheets
│   └── js/                     # JavaScript files
├── languages/                  # Translations
├── readme.txt                  # WordPress plugin readme
└── composer.json               # Dependencies
```

### Main Plugin File Template

```php
<?php
/**
 * Plugin Name: MemberPress Copilot - Custom Extensions
 * Plugin URI: https://yoursite.com/
 * Description: Custom extensions for MemberPress Copilot
 * Version: 1.0.0
 * Author: Your Name
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Depends: memberpress-copilot
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MPAI_EXTENSIONS_VERSION', '1.0.0');
define('MPAI_EXTENSIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MPAI_EXTENSIONS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check if MemberPress Copilot is active
add_action('plugins_loaded', function() {
    if (!class_exists('MemberPressCopilot')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>';
            echo __('MemberPress Copilot Extensions requires MemberPress Copilot to be installed and activated.', 'mpai-extensions');
            echo '</p></div>';
        });
        return;
    }
    
    // Initialize the extension
    require_once MPAI_EXTENSIONS_PLUGIN_DIR . 'includes/class-main.php';
    new MPAIExtensionsMain();
});
```

### Extension Main Class

```php
<?php
class MPAIExtensionsMain {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
    }
    
    public function init() {
        // Load dependencies
        $this->loadDependencies();
        
        // Register components
        $this->registerAgents();
        $this->registerTools();
        $this->registerServices();
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }
    
    private function loadDependencies() {
        require_once MPAI_EXTENSIONS_PLUGIN_DIR . 'includes/agents/class-custom-integration-agent.php';
        require_once MPAI_EXTENSIONS_PLUGIN_DIR . 'includes/tools/class-mailchimp-tool.php';
        require_once MPAI_EXTENSIONS_PLUGIN_DIR . 'includes/services/class-integration-service.php';
    }
    
    private function registerAgents() {
        add_action('init', function() {
            global $mpai_service_locator;
            $agent_registry = $mpai_service_locator->get('agent_registry');
            $agent_registry->register('custom_integration_agent', new CustomIntegrationAgent());
        }, 20);
    }
    
    private function registerTools() {
        add_action('init', function() {
            global $mpai_service_locator;
            $tool_registry = $mpai_service_locator->get('tool_registry');
            $tool_registry->register('mailchimp_tool', new MailchimpTool());
        }, 20);
    }
    
    private function registerServices() {
        add_action('init', function() {
            global $mpai_service_locator;
            $integration_service = new IntegrationService();
            $integration_service->register($mpai_service_locator);
            $integration_service->boot();
        }, 20);
    }
    
    public function enqueueAssets($hook) {
        // Only load on MPAI pages
        if (strpos($hook, 'mpai') === false) {
            return;
        }
        
        wp_enqueue_style(
            'mpai-extensions-css',
            MPAI_EXTENSIONS_PLUGIN_URL . 'assets/css/custom-extensions.css',
            ['mpai-admin-css'],
            MPAI_EXTENSIONS_VERSION
        );
        
        wp_enqueue_script(
            'mpai-extensions-js',
            MPAI_EXTENSIONS_PLUGIN_URL . 'assets/js/custom-chat-component.js',
            ['mpai-chat-js'],
            MPAI_EXTENSIONS_VERSION,
            true
        );
    }
}
```

## Testing Extensions

### Unit Testing

```php
<?php
namespace YourPlugin\Tests;

use PHPUnit\Framework\TestCase;
use YourPlugin\Tools\MailchimpTool;

class MailchimpToolTest extends TestCase {
    
    private $tool;
    
    protected function setUp(): void {
        $this->tool = new MailchimpTool();
    }
    
    public function testAddSubscriber(): void {
        $parameters = [
            'action' => 'add_subscriber',
            'email' => 'test@example.com',
            'list_id' => 'test_list_123'
        ];
        
        $result = $this->tool->execute($parameters);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('subscriber_id', $result);
    }
    
    public function testInvalidAction(): void {
        $parameters = ['action' => 'invalid_action'];
        $result = $this->tool->execute($parameters);
        
        $this->assertFalse($result['success']);
        $this->assertStringContains('Unknown action', $result['error']);
    }
}
```

### Integration Testing

```php
public function testIntegrationWithMemberPressCopilot(): void {
    // Test that your extension works with the main plugin
    global $mpai_service_locator;
    
    $orchestrator = $mpai_service_locator->get('orchestrator');
    $response = $orchestrator->processRequest([
        'message' => 'Sync members to Mailchimp',
        'user_id' => 123
    ]);
    
    $this->assertTrue($response['success']);
    $this->assertEquals('custom_integration_agent', $response['agent']);
}
```

## Security Guidelines

### Input Validation

```php
private function validateParameters(array $parameters): array {
    $errors = [];
    
    // Validate email
    if (isset($parameters['email']) && !is_email($parameters['email'])) {
        $errors[] = 'Invalid email address';
    }
    
    // Validate required fields
    $required = ['action', 'list_id'];
    foreach ($required as $field) {
        if (empty($parameters[$field])) {
            $errors[] = "Field {$field} is required";
        }
    }
    
    return $errors;
}
```

### Capability Checks

```php
protected function checkUserPermissions(): bool {
    return current_user_can('manage_options') || 
           current_user_can('manage_memberpress_integrations');
}
```

### Sanitization

```php
private function sanitizeInput(array $input): array {
    return [
        'email' => sanitize_email($input['email'] ?? ''),
        'list_id' => sanitize_text_field($input['list_id'] ?? ''),
        'action' => sanitize_key($input['action'] ?? '')
    ];
}
```

## Performance Guidelines

### Caching

```php
// Use WordPress caching
$cache_key = 'mpai_mailchimp_lists_' . get_current_user_id();
$lists = get_transient($cache_key);

if ($lists === false) {
    $lists = $this->fetchMailchimpLists();
    set_transient($cache_key, $lists, HOUR_IN_SECONDS);
}
```

### Lazy Loading

```php
class LazyIntegration {
    private $client;
    
    private function getClient() {
        if (!$this->client) {
            $this->client = new ExpensiveApiClient();
        }
        return $this->client;
    }
}
```

### Background Processing

```php
// Use WordPress cron for heavy operations
wp_schedule_single_event(time() + 60, 'mpai_process_bulk_sync', [$user_data]);

add_action('mpai_process_bulk_sync', function($user_data) {
    // Process in background
});
```

## Documentation Guidelines

### Code Documentation

```php
/**
 * Synchronize MemberPress users with Mailchimp
 *
 * @param array $users Array of user data to sync
 * @param string $list_id Mailchimp list identifier
 * @param array $options Sync options
 * @return array Sync results with success/error counts
 * 
 * @throws InvalidArgumentException If list_id is invalid
 * @throws ApiException If Mailchimp API returns error
 */
public function syncUsers(array $users, string $list_id, array $options = []): array
```

### README Documentation

Include in your extension:

1. **Installation instructions**
2. **Configuration steps**
3. **Available features**
4. **API reference**
5. **Troubleshooting guide**

## Distribution

### WordPress.org Guidelines

1. Follow WordPress coding standards
2. Include proper plugin headers
3. Sanitize all inputs
4. Escape all outputs
5. Use WordPress APIs

### Composer Package

```json
{
    "name": "yourname/memberpress-ai-extensions",
    "type": "wordpress-plugin",
    "description": "Custom extensions for MemberPress Copilot",
    "require": {
        "php": ">=7.4",
        "memberpress/memberpress-copilot": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "YourPlugin\\": "includes/"
        }
    }
}
```

## Support and Community

### Getting Help

1. Check the [main documentation](../README.md)
2. Review the [API documentation](api/)
3. Join the developer community
4. Submit issues on GitHub

### Contributing Back

1. Share your extensions with the community
2. Contribute to the core plugin
3. Help improve documentation
4. Provide feedback and suggestions

---

**Ready to extend?** Start with the [Agent API](api/agent-api.md) or [Tool API](api/tool-api.md) documentation for detailed implementation guides.