# Admin Interface Documentation

## Overview

The Admin Interface of the MemberPress AI Assistant provides a comprehensive set of screens and controls for configuring and managing the plugin. This document covers the architecture, features, and usage of the admin interface components.

## Architecture

The Admin Interface follows a Model-View-Controller (MVC) pattern:

### Components

1. **Controllers**: Handle user input and manage data flow
   - `MPAIAdminMenu`: Manages the admin menu structure
   - `MPAISettingsController`: Handles settings operations
   - `MPAIAjaxHandler`: Processes AJAX requests
   - `MPAIConsentManager`: Manages user consent

2. **Models**: Manage data and business logic
   - `MPAISettingsModel`: Handles settings data storage and retrieval
   - Configuration services for various features

3. **Views**: Present data to the user
   - `MPAISettingsView`: Renders settings screens
   - Admin page templates in the `templates` directory

### File Structure

```
src/Admin/
├── MPAIAdminMenu.php           # Admin menu registration
├── MPAIAjaxHandler.php         # AJAX request handling
├── MPAIConsentManager.php      # User consent management
└── Settings/
    ├── MPAISettingsController.php  # Settings controller
    ├── MPAISettingsModel.php       # Settings data model
    └── MPAISettingsView.php        # Settings view renderer

templates/
├── admin-page.php              # Main admin page template
├── chat-interface.php          # Chat interface template
├── consent-form.php            # User consent form
├── dashboard-tab.php           # Dashboard tab template
└── settings-page.php           # Settings page template
```

## Admin Menu Structure

The MemberPress AI Assistant adds the following items to the WordPress admin menu:

1. **AI Assistant**: Main menu item
   - **Dashboard**: Overview of the plugin features and status
   - **Chat**: Access to the AI chat interface
   - **Settings**: Configuration options for the plugin
   - **Documentation**: Links to documentation resources

### Menu Registration

The admin menu is registered in `MPAIAdminMenu.php`:

```php
public function registerAdminMenu(): void {
    add_menu_page(
        'MemberPress AI Assistant',
        'AI Assistant',
        'manage_options',
        'memberpress-ai-assistant',
        [$this, 'renderDashboardPage'],
        'dashicons-superhero',
        58
    );
    
    add_submenu_page(
        'memberpress-ai-assistant',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'memberpress-ai-assistant',
        [$this, 'renderDashboardPage']
    );
    
    add_submenu_page(
        'memberpress-ai-assistant',
        'Chat',
        'Chat',
        'manage_options',
        'memberpress-ai-assistant-chat',
        [$this, 'renderChatPage']
    );
    
    add_submenu_page(
        'memberpress-ai-assistant',
        'Settings',
        'Settings',
        'manage_options',
        'memberpress-ai-assistant-settings',
        [$this, 'renderSettingsPage']
    );
    
    add_submenu_page(
        'memberpress-ai-assistant',
        'Documentation',
        'Documentation',
        'manage_options',
        'memberpress-ai-assistant-docs',
        [$this, 'renderDocsPage']
    );
}
```

## Dashboard Page

The Dashboard page provides an overview of the plugin's features and status.

### Features

1. **Status Overview**: Shows the status of key components
   - MemberPress integration status
   - System requirements check

2. **Quick Stats**: Displays key metrics
   - Number of active memberships
   - Recent transactions
   - User activity

3. **Recent Activity**: Shows recent interactions with the AI assistant
   - Recent queries
   - Commands executed
   - Content generated

4. **Quick Actions**: Provides shortcuts to common tasks
   - Open chat interface
   - Configure settings
   - View documentation

### Implementation

The Dashboard page is implemented in `templates/dashboard-tab.php` and rendered by the `renderDashboardPage` method in `MPAIAdminMenu.php`.

## Chat Page

The Chat page provides access to the AI chat interface within the WordPress admin.

### Features

1. **Chat Interface**: Full-featured chat interface
   - Message input
   - Conversation history
   - Interactive elements

2. **Context Panel**: Shows relevant context for the conversation
   - Current MemberPress status
   - Recent actions
   - Related resources

3. **Tools Panel**: Provides access to additional tools
   - Command suggestions
   - Content templates
   - Data visualization

### Implementation

The Chat page is implemented in `templates/chat-interface.php` and rendered by the `renderChatPage` method in `MPAIAdminMenu.php`. The chat functionality is provided by the `ChatInterfaceService` class.

## Settings Page

The Settings page allows administrators to configure the plugin.

### Settings Tabs

1. **Feature Settings**: Enable/disable features
   - Chat interface
   - Command execution
   - Content generation

3. **Security Settings**: Configure security options
   - User consent
   - Data retention
   - Command whitelist

4. **Advanced Settings**: Additional configuration options
   - Logging
   - Debug mode
   - Performance options

### Settings Architecture

The Settings page follows a structured approach:

1. **Registration**: Settings are registered with the WordPress Settings API
2. **Validation**: Input is validated before saving
3. **Sanitization**: Data is sanitized to prevent security issues
4. **Storage**: Settings are stored in the WordPress options table
5. **Retrieval**: Settings are retrieved using the Settings Model

### Implementation

The Settings page is implemented using the following components:

- `MPAISettingsController`: Handles settings operations
- `MPAISettingsModel`: Manages settings data
- `MPAISettingsView`: Renders settings forms
- `templates/settings-page.php`: Template for the settings page

## User Consent Management

The Admin Interface includes a system for managing user consent.

### Features

1. **Consent Settings**: Configure consent requirements
   - Enable/disable consent
   - Customize consent message
   - Set consent expiration

2. **Consent Tracking**: Track user consent status
   - Record consent grants
   - Track consent revocation
   - Manage consent history

### Implementation

User consent management is implemented in `MPAIConsentManager.php`, which provides methods for:

- Checking if consent is required
- Displaying consent forms
- Recording consent decisions
- Enforcing consent requirements

## AJAX Handling

The Admin Interface uses AJAX for asynchronous operations.

### Features

1. **Request Handling**: Process AJAX requests
2. **Response Formatting**: Format responses as JSON
3. **Error Handling**: Handle and report errors
4. **Security**: Validate nonces and permissions

### Implementation

AJAX handling is implemented in `MPAIAjaxHandler.php`, which registers AJAX endpoints and processes requests.

```php
public function registerAjaxEndpoints(): void {
    add_action('wp_ajax_mpai_save_settings', [$this, 'handleSaveSettings']);
    add_action('wp_ajax_mpai_chat_message', [$this, 'handleChatMessage']);
    add_action('wp_ajax_mpai_clear_chat_history', [$this, 'handleClearChatHistory']);
}
```

## Asset Management

The Admin Interface includes a system for managing CSS and JavaScript assets.

### Features

1. **Asset Registration**: Register CSS and JavaScript files
2. **Dependency Management**: Manage asset dependencies
3. **Conditional Loading**: Load assets only when needed
4. **Versioning**: Handle asset versioning for cache busting

### Implementation

Asset management is implemented in the admin controllers, which register and enqueue assets as needed.

```php
public function enqueueAdminAssets(): void {
    wp_enqueue_style(
        'mpai-admin-styles',
        MPAI_PLUGIN_URL . 'assets/css/admin.css',
        [],
        MPAI_VERSION
    );
    
    wp_enqueue_script(
        'mpai-admin-script',
        MPAI_PLUGIN_URL . 'assets/js/admin.js',
        ['jquery'],
        MPAI_VERSION,
        true
    );
    
    wp_localize_script('mpai-admin-script', 'mpaiData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mpai_admin_nonce'),
    ]);
}
```

## Customization

The Admin Interface can be customized through filters and actions:

### Available Filters

1. `mpai_admin_menu_capability`: Filter the capability required to access the admin menu
2. `mpai_settings_tabs`: Filter the settings tabs
3. `mpai_dashboard_widgets`: Filter the dashboard widgets
4. `mpai_admin_notices`: Filter admin notices

### Available Actions

1. `mpai_before_settings_page`: Action before rendering the settings page
2. `mpai_after_settings_page`: Action after rendering the settings page
3. `mpai_settings_saved`: Action after settings are saved
4. `mpai_admin_init`: Action during admin initialization

### Example Customization

```php
// Change the capability required to access the admin menu
add_filter('mpai_admin_menu_capability', function($capability) {
    return 'manage_memberpress';
});

// Add a custom settings tab
add_filter('mpai_settings_tabs', function($tabs) {
    $tabs['custom'] = [
        'label' => 'Custom Settings',
        'callback' => 'render_custom_settings_tab',
    ];
    return $tabs;
});

// Add custom content to the settings page
add_action('mpai_after_settings_page', function() {
    echo '<div class="mpai-custom-content">';
    echo '<h2>Custom Content</h2>';
    echo '<p>This is custom content added to the settings page.</p>';
    echo '</div>';
});
```

## Security Considerations

The Admin Interface implements several security measures:

1. **Capability Checks**: All pages require the `manage_options` capability
2. **Nonce Validation**: AJAX requests require valid nonces
3. **Input Sanitization**: All user input is sanitized
4. **Output Escaping**: All output is properly escaped

## Troubleshooting

### Common Issues

#### Settings Not Saving
- **Issue**: Settings changes are not being saved
- **Solution**: Check for JavaScript errors in the console
- **Solution**: Verify that the user has the required capabilities
- **Solution**: Check for PHP errors in the error log

#### Admin Pages Not Loading
- **Issue**: Admin pages display blank or with errors
- **Solution**: Check for PHP errors in the error log
- **Solution**: Verify that all required files are present
- **Solution**: Check for conflicts with other plugins

#### AJAX Requests Failing
- **Issue**: AJAX requests return errors
- **Solution**: Check the browser console for error messages
- **Solution**: Verify that the AJAX URL is correct
- **Solution**: Check that nonces are being generated and validated correctly

### Debugging

For developers, the Admin Interface includes debugging tools:

1. Enable WordPress debug mode in `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. Enable the plugin's debug mode in the Advanced Settings tab

3. Check the debug log for error messages

## Conclusion

The Admin Interface provides a comprehensive set of tools for configuring and managing the MemberPress AI Assistant plugin. By following the MVC pattern and implementing best practices for security and usability, it offers a robust and user-friendly experience for administrators.

For more information on specific components, refer to the following documentation:
- [Installation and Configuration](installation-configuration.md)
- [Chat Interface](chat-interface.md)
- [Dependency Injection](dependency-injection.md)
- [Agent Architecture](agent-architecture.md)