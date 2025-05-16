<?php
/**
 * Settings Page Template
 *
 * Renders the MemberPress AI Assistant settings page.
 * This template works with the MPAISettingsRenderer class to display
 * the settings form, tabs, and fields.
 *
 * @package MemberpressAiAssistant
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var MPAISettingsRenderer $renderer The settings renderer instance
 * @var MPAISettingsController $controller The settings controller instance
 * @var string $current_tab The current active tab
 * @var array $tabs Available tabs
 */

// Get current tab from the settings controller
$tabs = $controller->get_tabs();
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Ensure the tab is valid
if (!isset($tabs[$current_tab])) {
    $current_tab = 'general';
}
?>

<div class="wrap">
    <h1><?php esc_html_e('MemberPress AI Assistant Settings', 'memberpress-ai-assistant'); ?></h1>
    
    <?php
    // Display settings updated message if needed
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
        echo '<div class="notice notice-success is-dismissible"><p>' . 
            esc_html__('Settings saved successfully.', 'memberpress-ai-assistant') . 
            '</p></div>';
    }
    
    // Render tabs
    $renderer->render_settings_tabs($current_tab, $tabs);
    ?>
    
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php
        // Add hidden fields
        echo '<input type="hidden" name="action" value="mpai_update_settings" />';
        echo '<input type="hidden" name="tab" value="' . esc_attr($current_tab) . '" />';
        
        // Add WordPress nonce field
        wp_nonce_field($controller->get_page_slug() . '-options');
        
        // Render settings fields for the current tab
        $renderer->render_settings_fields($current_tab);
        
        // Render submit button
        $renderer->render_submit_button();
        ?>
    </form>
</div>