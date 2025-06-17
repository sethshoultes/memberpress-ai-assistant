<?php
/**
 * Admin page template for MemberPress Copilot settings
 *
 * @package MemberPressCopilot
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current tab and tabs from the controller
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
$tabs = $this->get_tabs();

// Ensure the tab is valid
if (!isset($tabs[$current_tab])) {
    $current_tab = 'general';
}
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
    ?>
    
    <!-- Tabs navigation -->
    <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_id => $tab_name) : 
            $active = ($current_tab === $tab_id) ? 'nav-tab-active' : '';
            $url = add_query_arg([
                'page' => 'mpai-settings',
                'tab' => $tab_id,
            ], admin_url('admin.php'));
        ?>
            <a href="<?php echo esc_url($url); ?>" class="nav-tab <?php echo esc_attr($active); ?>">
                <?php echo esc_html($tab_name); ?>
            </a>
        <?php endforeach; ?>
    </h2>
    
    <!-- Settings form -->
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="mpai_update_settings" />
        <input type="hidden" name="tab" value="<?php echo esc_attr($current_tab); ?>" />
        
        <?php
        // Add nonce for security
        wp_nonce_field($this->get_page_slug() . '-options');
        
        // Output settings fields based on current tab
        echo '<table class="form-table" role="presentation">';
        
        switch ($current_tab) {
            case 'general':
                do_settings_sections($this->get_page_slug());
                break;
                
            case 'chat':
                // Only show the chat section
                $this->render_section('mpai_chat_section');
                break;
                
            case 'access':
                // Only show the access section
                $this->render_section('mpai_access_section');
                break;
                
            default:
                do_settings_sections($this->get_page_slug());
                break;
        }
        
        echo '</table>';
        
        // Submit button
        submit_button();
        ?>
    </form>
</div>