# Consent Form Implementation Plan

## Problem Statement

The current consent form implementation has several issues:

1. **Duplicate Process Methods**: There are two process_consent_form methods registered to the same 'admin_init' hook - one in the main plugin class and one in the MPAI_Admin class.

2. **Multiple AJAX Handlers**: There are also multiple save_consent_ajax methods in different classes (main plugin and MPAI_Chat_Interface) with different nonce checks.

3. **Form Action Issue**: The form action is empty, which means it submits to the current page but might lose URL parameters.

4. **Inconsistent User Meta Handling**: Different methods handle the user meta differently - some only allow setting consent to true, others allow toggling.

5. **No Hooks for Extension**: There are no hooks for the consent process, making it difficult to extend or modify.

## Solution Overview

Create a dedicated Consent Manager class to centralize all consent-related functionality. This will eliminate duplicates, ensure consistent handling, and add proper hooks for extensibility.

## Implementation Details

### 1. Create a Dedicated Consent Manager Class

Create a new file: `includes/class-mpai-consent-manager.php` with the following structure:

```php
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
```

### 2. Update the Consent Form Template

Update the consent form template (`includes/admin/views/consent-form.php`) with the following content:

```php
<?php
/**
 * Consent Form Template
 * 
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="mpai-welcome-section mpai-consent-section">
    <h2><?php _e('Welcome to MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></h2>
    
    <div class="mpai-welcome-content">
        <p><?php _e('MemberPress AI Assistant leverages artificial intelligence to help you manage your membership site more effectively. Before you begin, please review and agree to the terms of use.', 'memberpress-ai-assistant'); ?></p>
        
        <div class="mpai-terms-box">
            <h3><?php _e('Terms of Use', 'memberpress-ai-assistant'); ?></h3>
            <ul>
                <li><?php _e('This AI assistant will access and analyze your MemberPress data to provide insights and recommendations.', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('Information processed by the AI is subject to the privacy policies of our AI providers (OpenAI and Anthropic).', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('The AI may occasionally provide incomplete or inaccurate information. Always verify important recommendations.', 'memberpress-ai-assistant'); ?></li>
                <li><?php _e('MemberPress is not liable for any actions taken based on AI recommendations.', 'memberpress-ai-assistant'); ?></li>
            </ul>
        </div>
        
        <div class="mpai-consent-form">
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=memberpress-ai-assistant')); ?>">
                <?php wp_nonce_field('mpai_consent_nonce', 'mpai_consent_nonce'); ?>
                
                <div class="mpai-consent-checkbox-wrapper">
                    <label for="mpai-consent-checkbox" id="mpai-consent-label">
                        <input type="checkbox" name="mpai_consent" id="mpai-consent-checkbox" value="1">
                        <span class="mpai-checkbox-text"><?php _e('I agree to the terms and conditions of using the MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></span>
                    </label>
                </div>
                
                <div id="mpai-welcome-buttons" class="consent-required">
                    <input type="submit" name="mpai_save_consent" id="mpai-open-chat" class="button button-primary" value="<?php esc_attr_e('Get Started', 'memberpress-ai-assistant'); ?>" disabled>
                    <a href="#" id="mpai-terms-link" class="button"><?php _e('Review Full Terms', 'memberpress-ai-assistant'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    console.log('Consent form script loaded');
    
    // Function to update button state
    function updateButtonState(isChecked) {
        console.log('Updating button state. Checked:', isChecked);
        if (isChecked) {
            $('#mpai-open-chat').prop('disabled', false).removeClass('disabled');
            $('#mpai-welcome-buttons').removeClass('consent-required');
        } else {
            $('#mpai-open-chat').prop('disabled', true).addClass('disabled');
            $('#mpai-welcome-buttons').addClass('consent-required');
        }
    }
    
    // Handle consent checkbox changes
    $('#mpai-consent-checkbox').on('change', function() {
        updateButtonState($(this).is(':checked'));
    });
    
    // Also handle clicks on the label
    $('#mpai-consent-label').on('click', function(e) {
        // Don't handle if the click was directly on the checkbox (it will trigger the change event)
        if (e.target.id !== 'mpai-consent-checkbox') {
            e.preventDefault();
            var checkbox = $('#mpai-consent-checkbox');
            checkbox.prop('checked', !checkbox.is(':checked')).trigger('change');
        }
    });
    
    // Handle form submission
    $('form').on('submit', function(e) {
        if (!$('#mpai-consent-checkbox').is(':checked')) {
            e.preventDefault();
            alert('<?php echo esc_js(__('Please agree to the terms and conditions before proceeding.', 'memberpress-ai-assistant')); ?>');
            return false;
        }
        return true;
    });
    
    // Handle terms link click
    $('#mpai-terms-link').on('click', function(e) {
        e.preventDefault();
        
        // Create modal if it doesn't exist
        if (!$('#mpai-terms-modal').length) {
            var $modal = $('<div>', {
                id: 'mpai-terms-modal',
                class: 'mpai-terms-modal'
            }).appendTo('body');
            
            var $modalContent = $('<div>', {
                class: 'mpai-terms-modal-content'
            }).appendTo($modal);
            
            $('<h2>').text('MemberPress AI Terms & Conditions').appendTo($modalContent);
            
            $('<div>', {
                class: 'mpai-terms-content'
            }).html(`
                <p>By using the MemberPress AI Assistant, you agree to the following terms:</p>
                <ol>
                    <li>The AI Assistant is provided "as is" without warranties of any kind.</li>
                    <li>The AI may occasionally provide incorrect or incomplete information.</li>
                    <li>You are responsible for verifying any information provided by the AI.</li>
                    <li>MemberPress is not liable for any actions taken based on AI recommendations.</li>
                    <li>Your interactions with the AI Assistant may be logged for training and improvement purposes.</li>
                </ol>
                <p>For complete terms, please refer to the MemberPress Terms of Service.</p>
            `).appendTo($modalContent);
            
            $('<button>', {
                class: 'button button-primary',
                text: 'Close'
            }).on('click', function() {
                $modal.hide();
            }).appendTo($modalContent);
        }
        
        $('#mpai-terms-modal').show();
    });
});
</script>

<style>
/* Consent Form Styles */
.mpai-welcome-section {
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 25px;
    margin-bottom: 25px;
}

.mpai-terms-box {
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    padding: 15px 20px;
    margin: 20px 0;
    max-height: 200px;
    overflow-y: auto;
}

.mpai-consent-form {
    margin-top: 25px;
}

.mpai-consent-checkbox-wrapper {
    margin-bottom: 20px;
    padding: 10px;
    background-color: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
}

#mpai-consent-label {
    display: flex;
    align-items: center;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
}

#mpai-consent-checkbox {
    margin-right: 10px;
    transform: scale(1.2);
}

.mpai-checkbox-text {
    flex: 1;
}

#mpai-welcome-buttons {
    margin-top: 20px;
}

.consent-required .button-primary {
    opacity: 0.7;
    cursor: not-allowed;
}

/* Modal Styles */
.mpai-terms-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.mpai-terms-modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 30px;
    border-radius: 5px;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
}

.mpai-terms-content {
    margin-bottom: 20px;
}
</style>
```

### 3. Update the Main Plugin Class

Update the main plugin class (`memberpress-ai-assistant.php`) with the following changes:

1. Add the Consent Manager to the load_dependencies method:

```php
/**
 * Load plugin dependencies
 */
private function load_dependencies() {
    // ... existing code ...
    
    // Load Consent Manager
    require_once MPAI_PLUGIN_DIR . 'includes/class-mpai-consent-manager.php';
    
    // ... existing code ...
}
```

2. Remove the duplicate process_consent_form method and save_consent_ajax method from the main plugin class.

3. Update the deactivate method to use the Consent Manager:

```php
/**
 * Plugin deactivation
 */
public function deactivate() {
    // Clear rewrite rules
    flush_rewrite_rules();
    
    // Reset all user consents upon deactivation
    MPAI_Consent_Manager::reset_all_consents();
}
```

4. Initialize the Consent Manager in the init_plugin_components method:

```php
/**
 * Initialize plugin components
 */
public function init_plugin_components() {
    // ... existing code ...
    
    // Initialize the consent manager
    MPAI_Consent_Manager::get_instance();
    
    // ... existing code ...
}
```

### 4. Update the MPAI_Admin Class

Update the MPAI_Admin class (`includes/admin/class-mpai-admin.php`) with the following changes:

1. Remove the duplicate process_consent_form method.

2. Update the display_dashboard method to use the Consent Manager:

```php
/**
 * Display dashboard page
 */
public function display_dashboard() {
    $consent_manager = MPAI_Consent_Manager::get_instance();
    
    // Check if user has consented
    if (!$consent_manager->has_user_consented()) {
        // Show consent form
        $consent_manager->render_consent_form();
        return;
    }
    
    // ... rest of the method (show dashboard) ...
}
```

### 5. Update the MPAI_Chat_Interface Class

Update the MPAI_Chat_Interface class (`includes/class-mpai-chat-interface.php`) with the following changes:

1. Remove the save_consent_ajax method.

2. Update the check_consent method to use the Consent Manager:

```php
/**
 * Check if user has consented to terms
 */
public function check_consent() {
    $consent_manager = MPAI_Consent_Manager::get_instance();
    return $consent_manager->has_user_consented();
}
```

## Benefits of This Solution

1. **Single Source of Truth**: All consent-related functionality is now in one class, eliminating duplicates.
2. **Consistent User Meta Handling**: The consent data is handled consistently across the plugin.
3. **Extensibility**: New hooks allow for extending and customizing the consent process.
4. **Improved Form**: The form action is properly set, and the JavaScript is more robust.
5. **Better Visual Feedback**: The checkbox and button styling is improved for better user experience.
6. **Proper Singleton Pattern**: The Consent Manager uses a proper singleton pattern for global access.
7. **Separation of Concerns**: Each class now has a clear responsibility, improving maintainability.

## Implementation Steps

1. Create the new Consent Manager class file
2. Update the consent form template
3. Update the main plugin class
4. Update the MPAI_Admin class
5. Update the MPAI_Chat_Interface class
6. Test the consent form functionality