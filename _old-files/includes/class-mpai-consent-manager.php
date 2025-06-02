<?php
/**
 * Consent Manager
 *
 * Handles all consent-related functionality
 *
 * @package MemberPress_AI_Assistant
 * @subpackage MemberPress_AI_Assistant/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Consent Manager Class
 */
class MPAI_Consent_Manager {
    /**
     * Singleton instance
     *
     * @var MPAI_Consent_Manager
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return MPAI_Consent_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Register hooks
        add_action('admin_init', array($this, 'process_consent_form'));
        add_action('wp_ajax_mpai_save_consent', array($this, 'save_consent_ajax'));
        
        // Register hooks for extensions
        MPAI_Hooks::register_hook(
            'MPAI_HOOK_FILTER_consent_form_template',
            'Filter the consent form template path',
            ['template_path' => 'string'],
            '1.7.0',
            'consent'
        );
        
        MPAI_Hooks::register_hook(
            'MPAI_HOOK_ACTION_before_save_consent',
            'Fires before saving user consent',
            ['user_id' => 'int', 'consent_value' => 'bool'],
            '1.7.0',
            'consent'
        );
        
        MPAI_Hooks::register_hook(
            'MPAI_HOOK_ACTION_after_save_consent',
            'Fires after saving user consent',
            ['user_id' => 'int', 'consent_value' => 'bool'],
            '1.7.0',
            'consent'
        );
        
        MPAI_Hooks::register_hook(
            'MPAI_HOOK_FILTER_consent_redirect_url',
            'Filter the URL to redirect to after consent is given',
            ['redirect_url' => 'string', 'user_id' => 'int'],
            '1.7.0',
            'consent'
        );
    }

    /**
     * Check if user has given consent
     *
     * @param int|null $user_id User ID (optional, defaults to current user)
     * @return bool
     */
    public function has_user_consented($user_id = null) {
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }
        
        if (empty($user_id)) {
            return false;
        }
        
        // Check user meta
        $has_consented = get_user_meta($user_id, 'mpai_has_consented', true);
        
        return (bool) $has_consented;
    }

    /**
     * Save user consent
     *
     * @param int $user_id User ID
     * @param bool $consent_value Consent value
     * @return bool Success
     */
    public function save_user_consent($user_id, $consent_value) {
        if (empty($user_id)) {
            mpai_log_error('Cannot save consent - invalid user ID', 'consent');
            return false;
        }
        
        // Allow extensions to hook before saving
        do_action('MPAI_HOOK_ACTION_before_save_consent', $user_id, $consent_value);
        
        // Save to user meta
        $result = update_user_meta($user_id, 'mpai_has_consented', (bool) $consent_value);
        
        // Allow extensions to hook after saving
        do_action('MPAI_HOOK_ACTION_after_save_consent', $user_id, $consent_value);
        
        return $result;
    }

    /**
     * Process consent form submission
     */
    public function process_consent_form() {
        mpai_log_debug('Checking for consent form submission', 'consent');
        
        // Check if the consent form was submitted
        if (isset($_POST['mpai_save_consent']) && isset($_POST['mpai_consent'])) {
            // Verify nonce
            if (!isset($_POST['mpai_consent_nonce']) || !wp_verify_nonce($_POST['mpai_consent_nonce'], 'mpai_consent_nonce')) {
                mpai_log_error('Consent form nonce verification failed', 'consent');
                add_settings_error('mpai_messages', 'mpai_consent_error', __('Security check failed.', 'memberpress-ai-assistant'), 'error');
                return;
            }
            
            // Get current user ID
            $user_id = get_current_user_id();
            
            if (empty($user_id)) {
                mpai_log_error('Cannot save consent - no user ID available', 'consent');
                add_settings_error('mpai_messages', 'mpai_consent_error', __('User not logged in.', 'memberpress-ai-assistant'), 'error');
                return;
            }
            
            // Save consent
            $this->save_user_consent($user_id, true);
            
            mpai_log_info('User consent saved successfully', 'consent');
            
            // Add a transient message
            add_settings_error(
                'mpai_messages', 
                'mpai_consent_success', 
                __('Thank you for agreeing to the terms. You can now use the MemberPress AI Assistant.', 'memberpress-ai-assistant'), 
                'updated'
            );
            
            // Get redirect URL
            $redirect_url = admin_url('admin.php?page=memberpress-ai-assistant&consent=given');
            
            // Allow extensions to filter the redirect URL
            $redirect_url = apply_filters('MPAI_HOOK_FILTER_consent_redirect_url', $redirect_url, $user_id);
            
            // Redirect to remove POST data
            wp_redirect($redirect_url);
            exit;
        }
    }

    /**
     * AJAX handler for saving consent
     */
    public function save_consent_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpai_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        // Get the consent value
        $consent = isset($_POST['consent']) ? (bool) $_POST['consent'] : false;
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Save consent
        $result = $this->save_user_consent($user_id, $consent);
        
        if ($result) {
            // Return success
            wp_send_json_success(array(
                'message' => 'Consent saved',
                'consent' => $consent
            ));
        } else {
            wp_send_json_error('Failed to save consent');
        }
    }

    /**
     * Render consent form
     */
    public function render_consent_form() {
        // Get template path
        $template_path = MPAI_PLUGIN_DIR . 'includes/admin/views/consent-form.php';
        
        // Allow extensions to filter the template path
        $template_path = apply_filters('MPAI_HOOK_FILTER_consent_form_template', $template_path);
        
        // Check if template exists
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            mpai_log_error('Consent form template not found: ' . $template_path, 'consent');
            echo '<div class="notice notice-error"><p>' . __('Error: Consent form template not found.', 'memberpress-ai-assistant') . '</p></div>';
        }
    }

    /**
     * Reset all user consents
     * Used during plugin deactivation
     */
    public static function reset_all_consents() {
        global $wpdb;
        
        // Delete consent meta for all users
        $wpdb->delete(
            $wpdb->usermeta,
            array('meta_key' => 'mpai_has_consented')
        );
        
        mpai_log_info('All user consents have been reset', 'consent');
    }
}