<?php
/**
 * MemberPress Copilot Welcome Page Template
 *
 * This template displays a simple welcome message and directs users to the settings page.
 *
 * @package MemberPressCopilot
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mpai-welcome-container wrap">
    <h1><?php _e('MemberPress Copilot', 'memberpress-copilot'); ?></h1>
    
    <?php
    // Display any admin notices
    settings_errors('mpai_messages');
    ?>
    
    <div class="mpai-welcome-content">
        <div class="mpai-welcome-message">
            <h2><?php _e('Welcome to MemberPress Copilot!', 'memberpress-copilot'); ?></h2>
            <p><?php _e('Your Copilot is ready to help you manage your MemberPress site. You can access the chat interface from the settings page.', 'memberpress-copilot'); ?></p>
            
            <div class="mpai-welcome-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mpai-settings')); ?>" class="button button-primary button-large">
                    <?php _e('Go to Copilot', 'memberpress-copilot'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .mpai-welcome-container {
        max-width: 800px;
        margin: 40px auto;
        background: #fff;
        padding: 30px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .mpai-welcome-container h1 {
        margin-top: 0;
        color: #1d2327;
        font-size: 24px;
    }
    
    .mpai-welcome-content {
        margin-top: 20px;
    }
    
    .mpai-welcome-message {
        text-align: center;
        padding: 20px 0;
    }
    
    .mpai-welcome-message h2 {
        color: #1d2327;
        font-size: 20px;
        margin-bottom: 15px;
    }
    
    .mpai-welcome-message p {
        color: #646970;
        font-size: 16px;
        line-height: 1.5;
        margin-bottom: 25px;
    }
    
    .mpai-welcome-actions {
        margin-top: 20px;
    }
    
    .mpai-welcome-actions .button-large {
        padding: 12px 24px;
        font-size: 16px;
        height: auto;
        line-height: 1.4;
    }
</style>