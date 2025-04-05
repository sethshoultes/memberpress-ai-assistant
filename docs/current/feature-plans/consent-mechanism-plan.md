# AI Assistant Terms & Conditions Consent Mechanism

## Problem Statement

Currently, the MemberPress AI Assistant chat interface is automatically available to all users who have access to the WordPress admin interface. There is no consent mechanism to ensure users understand the capabilities and limitations of AI-powered assistance before using the system. This creates potential legal and user experience concerns.

## Proposed Solution

Implement a terms and conditions consent mechanism that requires users to explicitly acknowledge and accept the terms of using the AI Assistant. The chat interface will only be available after users check a consent checkbox, which will persist their preference.

## Goals

1. Ensure users understand AI limitations before interacting with the chat
2. Implement a legally sound consent mechanism
3. Add a disclaimer about potential AI inaccuracies
4. Make the consent persistent but revocable
5. Integrate seamlessly with the current user interface

## Technical Design

### 1. Admin Page Modifications

Modify the welcome card on the main AI Assistant admin page to include a consent checkbox and notice:

```php
<div class="mpai-welcome-card">
    <h2><?php _e('Welcome to MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></h2>
    <p><?php _e('The AI assistant is available via the chat bubble in the bottom-right corner of your screen (or wherever you positioned it in settings).', 'memberpress-ai-assistant'); ?></p>
    <p><?php _e('You can use it to ask questions about your MemberPress site, get insights, and run commands.', 'memberpress-ai-assistant'); ?></p>
    
    <div class="mpai-consent-container">
        <?php
        // Check if user has already consented
        $user_id = get_current_user_id();
        $has_consented = get_user_meta($user_id, 'mpai_has_consented', true);
        ?>
        <label>
            <input type="checkbox" id="mpai-consent-checkbox" class="mpai-consent-checkbox" <?php checked($has_consented, true); ?>>
            <?php _e('I agree to the <a href="#" id="mpai-terms-link">MemberPress AI Terms & Conditions</a>', 'memberpress-ai-assistant'); ?>
        </label>
        
        <div class="mpai-consent-notice">
            <p><strong><?php _e('Important Notice:', 'memberpress-ai-assistant'); ?></strong> <?php _e('The MemberPress AI Assistant is an AI-powered tool. While it strives for accuracy, it may occasionally provide incorrect or incomplete information. Always verify important information before taking action.', 'memberpress-ai-assistant'); ?></p>
        </div>
    </div>
    
    <div class="mpai-welcome-buttons <?php echo !$has_consented ? 'consent-required' : ''; ?>" id="mpai-welcome-buttons">
        <button id="mpai-open-chat" class="button button-primary" <?php disabled(!$has_consented); ?>><?php _e('Open Chat', 'memberpress-ai-assistant'); ?></button>
        <a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-settings'); ?>" class="button"><?php _e('Settings', 'memberpress-ai-assistant'); ?></a>
    </div>
</div>
```

### 2. Chat Interface Conditional Loading

Add a user consent check to the chat interface loader:

```php
// Check if user has consented to terms and conditions
$user_id = get_current_user_id();
$has_consented = get_user_meta($user_id, 'mpai_has_consented', true);
if (!$has_consented) {
    return;
}
```

### 3. AJAX Consent Management

Implement an AJAX handler to save the user's consent preference:

```php
/**
 * Save user consent via AJAX
 */
public function save_consent_ajax() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpai_chat_nonce')) {
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
    
    // Update user meta
    update_user_meta($user_id, 'mpai_has_consented', $consent);
    
    // Return success
    wp_send_json_success(array(
        'message' => 'Consent saved',
        'consent' => $consent
    ));
}
```

### 4. Terms & Conditions Modal

Create a modal to display the detailed terms and conditions:

```javascript
// Handle terms link click (show terms modal)
$('#mpai-terms-link').on('click', function(e) {
    e.preventDefault();
    
    // Create and show modal if it doesn't exist
    if (!$('#mpai-terms-modal').length) {
        const $modal = $('<div>', {
            id: 'mpai-terms-modal',
            class: 'mpai-terms-modal'
        }).appendTo('body');
        
        const $modalContent = $('<div>', {
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
```

### 5. CSS Styling

Add styling for the consent UI elements:

```css
.mpai-consent-container {
    margin-top: 15px;
    padding: 15px;
    background-color: #f9f9f9;
    border: 1px solid #e3e3e3;
    border-radius: 4px;
}

.mpai-consent-checkbox {
    margin-right: 8px;
}

.mpai-consent-notice {
    font-size: 13px;
    color: #606060;
    margin-top: 10px;
}

.mpai-welcome-buttons.consent-required {
    opacity: 0.5;
    pointer-events: none;
}

.mpai-terms-modal {
    display: none;
    position: fixed;
    z-index: 99999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    overflow: auto;
}

.mpai-terms-modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 20px 30px;
    border-radius: 5px;
    max-width: 600px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.mpai-terms-content {
    max-height: 400px;
    overflow-y: auto;
    margin: 15px 0;
}
```

## Implementation Timeline

1. **Phase 1: Core Consent Mechanism (1-2 days)**
   - Add consent checkbox to admin page
   - Implement AJAX handler for saving consent
   - Update chat interface to respect consent setting

2. **Phase 2: Terms & Conditions Content (1 day)**
   - Draft detailed terms and conditions text
   - Create modal dialog for displaying full terms
   - Add styling for consent UI elements

3. **Phase 3: Testing & Integration (1 day)**
   - Test consent mechanism across different user roles
   - Verify persistence of consent preference
   - Ensure proper operation of the consent revocation feature

## Benefits

1. **Legal Protection**
   - Provides clear disclosure about AI limitations
   - Creates documented user consent
   - Reduces liability for AI-generated inaccuracies

2. **Enhanced User Awareness**
   - Helps users understand AI capabilities and limitations
   - Sets appropriate expectations about AI assistance
   - Encourages verification of important information

3. **User Control**
   - Gives users choice about using AI features
   - Allows for consent revocation
   - Respects user preferences across sessions

## Compatibility Considerations

- The implementation will respect existing user roles and capabilities
- Consent will be tracked on a per-user basis
- The system will work with both admin users and other roles with appropriate permissions
- The design will be responsive for different screen sizes