<?php
/**
 * MemberPress AI Assistant Consent Manager
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin;

// Note: WordPress functions are called directly in this class.
// IDE may show errors for these functions, but they will work correctly
// in a WordPress environment where these functions are globally available.

use MemberpressAiAssistant\Abstracts\AbstractService;

/**
 * MPAIConsentManager - Manages user consent for AI services
 * 
 * This class implements a consent management system that:
 * 1. Stores and retrieves user consent status
 * 2. Provides AJAX handlers for consent form submission
 * 3. Implements hooks for extensions to modify consent behavior
 * 4. Follows the singleton pattern for global access
 */
class MPAIConsentManager extends AbstractService {
    /**
     * Singleton instance
     *
     * @var MPAIConsentManager
     */
    private static $instance = null;

    /**
     * User meta key for storing consent
     */
    const CONSENT_META_KEY = 'mpai_has_consented';

    /**
     * Get singleton instance
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     * @return MPAIConsentManager
     */
    public static function getInstance(string $name = 'consent_manager', $logger = null) {
        if (null === self::$instance) {
            self::$instance = new self($name, $logger);
        }
        return self::$instance;
    }

    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container
        $container->register('consent_manager', function() {
            return $this;
        });
        
        // Log registration
        $this->log('Consent manager service registered');
    }
    
    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks
        $this->addHooks();
        
        // Log boot
        $this->log('Consent manager service booted');
    }
    
    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    protected function __construct(string $name = 'consent_manager', $logger = null) {
        parent::__construct($name, $logger);
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Register hooks for consent processing
        \add_action('admin_init', array($this, 'processConsentForm'), 10);
        \add_action('wp_ajax_mpai_save_consent', array($this, 'saveConsentAjax'));
        
        // Register hooks for extensions
        \add_filter('mpai_consent_form_template', array($this, 'filterConsentFormTemplate'), 10, 1);
        \add_action('mpai_before_save_consent', array($this, 'beforeSaveConsent'), 10, 2);
        \add_action('mpai_after_save_consent', array($this, 'afterSaveConsent'), 10, 2);
        \add_filter('mpai_consent_redirect_url', array($this, 'filterConsentRedirectUrl'), 10, 2);
    }

    /**
     * Check if user has given consent
     *
     * @param int|null $user_id User ID (optional, defaults to current user)
     * @return bool
     */
    public function hasUserConsented($user_id = null) {
        if (null === $user_id) {
            $user_id = \get_current_user_id();
        }
        
        if (empty($user_id)) {
            return false;
        }
        
        // Check user meta - consent is always required regardless of settings
        $has_consented = \get_user_meta($user_id, self::CONSENT_META_KEY, true);
        
        return (bool) $has_consented;
    }
    
    /**
     * Check if user has consented and redirect if not
     *
     * @param string $redirect_url URL to redirect to if user hasn't consented
     * @return bool True if user has consented, false otherwise
     */
    public function checkConsentAndRedirect($redirect_url = '') {
        // Check if user has consented
        if (!$this->hasUserConsented()) {
            // Set default redirect URL if not provided
            if (empty($redirect_url)) {
                $redirect_url = \admin_url('admin.php?page=mpai-welcome');
            }
            
            // Redirect to the welcome page
            \wp_redirect($redirect_url);
            exit;
        }
        
        return true;
    }

    /**
     * Save user consent
     *
     * @param int $user_id User ID
     * @param bool $consent_value Consent value
     * @return bool Success
     */
    public function saveUserConsent($user_id, $consent_value) {
        if (empty($user_id)) {
            $this->log('Cannot save consent - invalid user ID', ['error' => true]);
            return false;
        }
        
        // Allow extensions to hook before saving
        \do_action('mpai_before_save_consent', $user_id, $consent_value);
        
        // Save to user meta
        $result = \update_user_meta($user_id, self::CONSENT_META_KEY, (bool) $consent_value);
        
        // Allow extensions to hook after saving
        \do_action('mpai_after_save_consent', $user_id, $consent_value);
        
        return $result;
    }

    /**
     * Process consent form submission
     */
    public function processConsentForm() {
        $this->log('Checking for consent form submission');
        
        // Debug: Log all POST data
        $this->log('POST data: ' . print_r($_POST, true));
        
        // Check if the consent form was submitted
        if (isset($_POST['mpai_save_consent']) && isset($_POST['mpai_consent'])) {
            $this->log('Consent form submitted');
            
            // Verify nonce
            if (!isset($_POST['mpai_consent_nonce']) || !\wp_verify_nonce($_POST['mpai_consent_nonce'], 'mpai_consent_nonce')) {
                $this->log('Consent form nonce verification failed', ['error' => true]);
                \add_settings_error('mpai_messages', 'mpai_consent_error', \__('Security check failed.', 'memberpress-ai-assistant'), 'error');
                return;
            }
            
            // Get current user ID
            $user_id = \get_current_user_id();
            
            if (empty($user_id)) {
                $this->log('Cannot save consent - no user ID available', ['error' => true]);
                \add_settings_error('mpai_messages', 'mpai_consent_error', \__('User not logged in.', 'memberpress-ai-assistant'), 'error');
                return;
            }
            
            // Save consent
            $this->saveUserConsent($user_id, true);
            
            $this->log('User consent saved successfully');
            
            // Add a transient message
            \add_settings_error(
                'mpai_messages',
                'mpai_consent_success',
                \__('Thank you for agreeing to the terms. You can now use the MemberPress AI Assistant.', 'memberpress-ai-assistant'),
                'updated'
            );
            
            // Get redirect URL
            $redirect_url = \admin_url('admin.php?page=mpai-settings');
            
            // Allow extensions to filter the redirect URL
            $redirect_url = \apply_filters('mpai_consent_redirect_url', $redirect_url, $user_id);
            
            // Redirect to remove POST data
            $this->log('Redirecting to: ' . $redirect_url);
            \wp_redirect($redirect_url);
            exit;
        } else {
            $this->log('No consent form submission detected. POST keys: ' . implode(', ', array_keys($_POST)));
        }
    }

    /**
     * AJAX handler for saving consent
     */
    public function saveConsentAjax() {
        // Check nonce - support both nonce formats
        $nonce_valid = false;
        if (isset($_POST['nonce']) && \wp_verify_nonce($_POST['nonce'], 'mpai_nonce')) {
            $nonce_valid = true;
        } elseif (isset($_POST['mpai_consent_nonce']) && \wp_verify_nonce($_POST['mpai_consent_nonce'], 'mpai_consent_nonce')) {
            $nonce_valid = true;
        }
        
        if (!$nonce_valid) {
            \wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check if user is logged in
        if (!\is_user_logged_in()) {
            \wp_send_json_error('User not logged in');
            return;
        }
        
        // Get the consent value
        $consent = isset($_POST['consent']) ? (bool) $_POST['consent'] : false;
        
        // Get current user ID
        $user_id = \get_current_user_id();
        
        // Save consent
        $result = $this->saveUserConsent($user_id, $consent);
        
        if ($result) {
            // Return success
            \wp_send_json_success(array(
                'message' => 'Consent saved',
                'consent' => $consent
            ));
        } else {
            \wp_send_json_error('Failed to save consent');
        }
    }

    /**
     * Render consent form
     */
    public function renderConsentForm() {
        // Get template path
        $template_path = \plugin_dir_path(dirname(__DIR__)) . 'templates/consent-form.php';
        
        // Allow extensions to filter the template path
        $template_path = \apply_filters('mpai_consent_form_template', $template_path);
        
        // Check if template exists
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->log('Consent form template not found: ' . $template_path, ['error' => true]);
            echo '<div class="notice notice-error"><p>' . \__('Error: Consent form template not found.', 'memberpress-ai-assistant') . '</p></div>';
        }
    }

    /**
     * Reset user consent
     *
     * @param int|null $user_id User ID (optional, defaults to current user)
     * @return bool Success
     */
    public function resetUserConsent($user_id = null) {
        if (null === $user_id) {
            $user_id = \get_current_user_id();
        }
        
        if (empty($user_id)) {
            return false;
        }
        
        return \delete_user_meta($user_id, self::CONSENT_META_KEY);
    }

    /**
     * Reset all user consents
     * Used during plugin deactivation
     */
    public static function resetAllConsents() {
        global $wpdb;
        
        // Delete consent meta for all users
        $wpdb->delete(
            $wpdb->usermeta,
            array('meta_key' => self::CONSENT_META_KEY)
        );
        
        // We can't use $this->log here since this is a static method
        // In a real implementation, we would use a static logger
    }

    /**
     * Filter hook for consent form template
     *
     * @param string $template_path The template path
     * @return string
     */
    public function filterConsentFormTemplate($template_path) {
        // This is a hook for extensions to modify the template path
        return $template_path;
    }

    /**
     * Action hook before saving consent
     *
     * @param int $user_id User ID
     * @param bool $consent_value Consent value
     */
    public function beforeSaveConsent($user_id, $consent_value) {
        // This is a hook for extensions to perform actions before saving consent
        $this->log('Before save consent hook triggered', [
            'user_id' => $user_id,
            'consent' => $consent_value
        ]);
    }

    /**
     * Action hook after saving consent
     *
     * @param int $user_id User ID
     * @param bool $consent_value Consent value
     */
    public function afterSaveConsent($user_id, $consent_value) {
        // This is a hook for extensions to perform actions after saving consent
        $this->log('After save consent hook triggered', [
            'user_id' => $user_id,
            'consent' => $consent_value
        ]);
    }

    /**
     * Filter hook for consent redirect URL
     *
     * @param string $redirect_url The redirect URL
     * @param int $user_id User ID
     * @return string
     */
    public function filterConsentRedirectUrl($redirect_url, $user_id) {
        // This is a hook for extensions to modify the redirect URL
        return $redirect_url;
    }
}