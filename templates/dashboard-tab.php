<?php
/**
 * Dashboard Tab Template
 *
 * Displays the dashboard tab content for the MemberPress Copilot settings page.
 * This template shows status indicators, overview information, and statistics.
 *
 * @package MemberPressCopilot
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var MPAISettingsRenderer $renderer The settings renderer instance
 * @var MPAISettingsController $controller The settings controller instance
 * @var MPAISettingsStorage $storage The settings storage instance
 */

// Get current settings
$chat_enabled = $storage->is_chat_enabled();
$chat_location = $storage->get_chat_location();
$chat_position = $storage->get_chat_position();
$user_roles = $storage->get_user_roles();

// Check API connection status (this would be implemented in a real environment)
$api_connected = apply_filters('mpai_api_connected', false);

// Check if MemberPress is active
$memberpress_active = defined('MEPR_VERSION');

// Check if debug mode is enabled
$debug_mode = defined('WP_DEBUG') && WP_DEBUG;
?>

<div class="mpai-dashboard-tab">
    <div class="mpai-dashboard-header">
        <h2><?php esc_html_e('MemberPress Copilot Dashboard', 'memberpress-copilot'); ?></h2>
        <p class="mpai-dashboard-description">
            <?php esc_html_e('Welcome to the MemberPress Copilot. This tool helps you manage your MemberPress site more efficiently with AI-powered assistance.', 'memberpress-copilot'); ?>
        </p>
    </div>

    <div class="mpai-status-indicators">
        <h3><?php esc_html_e('System Status', 'memberpress-copilot'); ?></h3>
        
        <div class="mpai-status-grid">
            <!-- API Connection Status -->
            <div class="mpai-status-item">
                <span class="mpai-status-label"><?php esc_html_e('API Connection:', 'memberpress-copilot'); ?></span>
                <span class="mpai-status-indicator <?php echo $api_connected ? 'mpai-status-success' : 'mpai-status-error'; ?>">
                    <?php echo $api_connected 
                        ? esc_html__('Connected', 'memberpress-copilot') 
                        : esc_html__('Not Connected', 'memberpress-copilot'); 
                    ?>
                </span>
                <?php if (!$api_connected): ?>
                    <p class="mpai-status-message">
                        <?php esc_html_e('Please check your API settings to ensure proper connection.', 'memberpress-copilot'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- MemberPress Detection -->
            <div class="mpai-status-item">
                <span class="mpai-status-label"><?php esc_html_e('MemberPress:', 'memberpress-copilot'); ?></span>
                <span class="mpai-status-indicator <?php echo $memberpress_active ? 'mpai-status-success' : 'mpai-status-error'; ?>">
                    <?php echo $memberpress_active 
                        ? esc_html__('Detected', 'memberpress-copilot') 
                        : esc_html__('Not Detected', 'memberpress-copilot'); 
                    ?>
                </span>
                <?php if (!$memberpress_active): ?>
                    <p class="mpai-status-message">
                        <?php esc_html_e('MemberPress is required for full functionality. Please ensure it is installed and activated.', 'memberpress-copilot'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Debug Mode -->
            <div class="mpai-status-item">
                <span class="mpai-status-label"><?php esc_html_e('Debug Mode:', 'memberpress-copilot'); ?></span>
                <span class="mpai-status-indicator <?php echo $debug_mode ? 'mpai-status-warning' : 'mpai-status-success'; ?>">
                    <?php echo $debug_mode 
                        ? esc_html__('Enabled', 'memberpress-copilot') 
                        : esc_html__('Disabled', 'memberpress-copilot'); 
                    ?>
                </span>
                <?php if ($debug_mode): ?>
                    <p class="mpai-status-message">
                        <?php esc_html_e('Debug mode is enabled. This may affect performance in production environments.', 'memberpress-copilot'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Chat Status -->
            <div class="mpai-status-item">
                <span class="mpai-status-label"><?php esc_html_e('Chat Interface:', 'memberpress-copilot'); ?></span>
                <span class="mpai-status-indicator <?php echo $chat_enabled ? 'mpai-status-success' : 'mpai-status-warning'; ?>">
                    <?php echo $chat_enabled 
                        ? esc_html__('Enabled', 'memberpress-copilot') 
                        : esc_html__('Disabled', 'memberpress-copilot'); 
                    ?>
                </span>
                <?php if ($chat_enabled): ?>
                    <p class="mpai-status-message">
                        <?php 
                        $location_text = '';
                        switch ($chat_location) {
                            case 'admin_only':
                                $location_text = esc_html__('Admin Area Only', 'memberpress-copilot');
                                break;
                            case 'frontend':
                                $location_text = esc_html__('Frontend Only', 'memberpress-copilot');
                                break;
                            case 'both':
                                $location_text = esc_html__('Admin Area and Frontend', 'memberpress-copilot');
                                break;
                        }
                        printf(
                            esc_html__('Chat is available in: %s', 'memberpress-copilot'),
                            $location_text
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="mpai-dashboard-overview">
        <h3><?php esc_html_e('Overview', 'memberpress-copilot'); ?></h3>
        
        <div class="mpai-overview-content">
            <p>
                <?php esc_html_e('The MemberPress Copilot helps you manage your membership site more efficiently by providing AI-powered assistance for common tasks.', 'memberpress-copilot'); ?>
            </p>
            
            <h4><?php esc_html_e('Key Features', 'memberpress-copilot'); ?></h4>
            <ul class="mpai-features-list">
                <li><?php esc_html_e('Intelligent chat interface for quick assistance', 'memberpress-copilot'); ?></li>
                <li><?php esc_html_e('Automated content generation for membership pages', 'memberpress-copilot'); ?></li>
                <li><?php esc_html_e('Member data analysis and insights', 'memberpress-copilot'); ?></li>
                <li><?php esc_html_e('Subscription management assistance', 'memberpress-copilot'); ?></li>
            </ul>
            
            <h4><?php esc_html_e('Getting Started', 'memberpress-copilot'); ?></h4>
            <ol class="mpai-getting-started">
                <li><?php esc_html_e('Configure your API settings in the General tab', 'memberpress-copilot'); ?></li>
                <li><?php esc_html_e('Customize the chat interface in the Chat Settings tab', 'memberpress-copilot'); ?></li>
                <li><?php esc_html_e('Set up user access permissions in the Access Control tab', 'memberpress-copilot'); ?></li>
            </ol>
        </div>
    </div>

    <?php
    // Display usage statistics if available
    $usage_stats = apply_filters('mpai_usage_statistics', []);
    if (!empty($usage_stats)):
    ?>
    <div class="mpai-usage-statistics">
        <h3><?php esc_html_e('Usage Statistics', 'memberpress-copilot'); ?></h3>
        
        <div class="mpai-stats-grid">
            <?php foreach ($usage_stats as $stat_key => $stat_value): ?>
                <div class="mpai-stat-item">
                    <span class="mpai-stat-value"><?php echo esc_html($stat_value['value']); ?></span>
                    <span class="mpai-stat-label"><?php echo esc_html($stat_value['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="mpai-dashboard-footer">
        <p class="mpai-version-info">
            <?php 
            if (defined('MPAI_VERSION')) {
                printf(
                    esc_html__('MemberPress Copilot v%s', 'memberpress-copilot'),
                    esc_html(MPAI_VERSION)
                );
            }
            ?>
        </p>
        
        <div class="mpai-support-links">
            <a href="https://memberpress.com/support/" target="_blank" class="button">
                <?php esc_html_e('Get Support', 'memberpress-copilot'); ?>
            </a>
            <a href="https://docs.memberpress.com/" target="_blank" class="button">
                <?php esc_html_e('Documentation', 'memberpress-copilot'); ?>
            </a>
        </div>
    </div>
</div>

<?php
// Dashboard styles are now enqueued via MPAISettingsRenderer::enqueue_admin_styles()
?>