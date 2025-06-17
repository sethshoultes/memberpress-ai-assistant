<?php
/**
 * Settings Page Template
 *
 * Renders the MemberPress Copilot settings page.
 * This template is used by the MPAISettingsView class to display
 * the settings page content. It focuses on presentation only,
 * with all logic handled by the Controller and View components.
 *
 * @package MemberPressCopilot
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template variables:
 *
 * @var string $current_tab The current active tab
 * @var array $tabs Available tabs as associative array of tab_id => tab_name
 * @var string $page_slug The settings page slug
 * @var \MemberpressAiAssistant\Admin\Settings\MPAISettingsModel $model The settings model instance
 * 
 * For backward compatibility:
 * @var \MemberpressAiAssistant\Admin\MPAISettingsRenderer $renderer The old settings renderer (if provided)
 * @var \MemberpressAiAssistant\Interfaces\SettingsProviderInterface $provider The old settings provider (if provided)
 */

// Check for required variables
if (empty($tabs) || empty($current_tab) || empty($page_slug)) {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('MemberPress Copilot Settings', 'memberpress-copilot'); ?></h1>
        <div class="notice notice-error">
            <p><?php esc_html_e('Error: Required template variables are missing. Please try again later or contact support.', 'memberpress-copilot'); ?></p>
        </div>
    </div>
    <?php
    return;
}

// Ensure the tab is valid
if (!isset($tabs[$current_tab])) {
    $current_tab = 'general';
}

// Backward compatibility check - if we're using the old renderer
$using_legacy_renderer = isset($renderer) && isset($provider);
?>

<div class="wrap">
    <h1><?php esc_html_e('MemberPress Copilot Settings', 'memberpress-copilot'); ?></h1>
    
    <?php
    // Display settings updated message if needed
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
        echo '<div class="notice notice-success is-dismissible"><p>' .
            esc_html__('Settings saved successfully.', 'memberpress-copilot') .
            '</p></div>';
    }
    
    // Display any other admin notices
    settings_errors('mpai_messages');
    
    // Render tabs - use either the new View component or the legacy renderer
    if ($using_legacy_renderer) {
        $renderer->render_settings_tabs($current_tab, $tabs);
    } else {
        ?>
        <h2 class="nav-tab-wrapper">
            <?php foreach ($tabs as $tab_id => $tab_name) : 
                $active = ($current_tab === $tab_id) ? 'nav-tab-active' : '';
                $url = add_query_arg([
                    'page' => $page_slug,
                    'tab' => $tab_id,
                ], admin_url('admin.php'));
                ?>
                <a href="<?php echo esc_url($url); ?>" class="nav-tab <?php echo esc_attr($active); ?>">
                    <?php echo esc_html($tab_name); ?>
                </a>
            <?php endforeach; ?>
        </h2>
        <?php
    }
    ?>
    
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php
        // Add hidden fields
        echo '<input type="hidden" name="action" value="mpai_update_settings" />';
        echo '<input type="hidden" name="tab" value="' . esc_attr($current_tab) . '" />';
        
        // Add WordPress nonce field
        if ($using_legacy_renderer) {
            $nonce_slug = $provider->get_page_slug();
        } else {
            $nonce_slug = $page_slug;
        }
        wp_nonce_field($nonce_slug . '-options');
        
        // Render settings fields for the current tab
        if ($using_legacy_renderer) {
            // Use the legacy renderer for backward compatibility
            $renderer->render_settings_fields($current_tab);
        } else {
            // Output the table for settings fields
            echo '<table class="form-table" role="presentation">';
            
            // Let WordPress handle the rendering of registered settings fields
            do_settings_fields($page_slug, 'mpai_' . $current_tab . '_section');
            
            echo '</table>';
        }
        
        // Render submit button
        if ($using_legacy_renderer) {
            $renderer->render_submit_button();
        } else {
            ?>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" 
                       value="<?php esc_attr_e('Save Changes', 'memberpress-copilot'); ?>" />
            </p>
            <?php
        }
        ?>
    </form>
    
    <?php
    // Ensure jQuery is available for the chat interface
    wp_enqueue_script('jquery');
    
    // Always render the chat interface (consent system removed)
    $already_rendered = defined('MPAI_CHAT_INTERFACE_RENDERED');
    ?>
    
    <!-- MemberPress Copilot Container -->
    <div id="mpai-assistant-container">
        <?php if ($already_rendered): ?>
            <?php
            echo '<!-- Chat Interface: Already rendered, skipping duplicate -->';
            ?>
        <?php else: ?>
            <?php
            echo '<!-- Chat Interface: Always displayed (consent system removed) -->';
            
            // Set flag to prevent duplicate rendering
            define('MPAI_CHAT_INTERFACE_RENDERED', true);
            
            // Include the chat interface template
            $chat_template_path = MPAI_PLUGIN_DIR . 'templates/chat-interface.php';
            if (file_exists($chat_template_path)) {
                include $chat_template_path;
                echo '<!-- Chat Interface: Template included successfully -->';
            } else {
                echo '<!-- Chat Interface: Template not found at ' . esc_html($chat_template_path) . ' -->';
                // Fallback message
                echo '<div class="notice notice-info"><p>' .
                     esc_html__('Chat interface template not found. Please check the plugin installation.', 'memberpress-copilot') .
                     '</p></div>';
            }
            ?>
        <?php endif; ?>
    </div>
</div>