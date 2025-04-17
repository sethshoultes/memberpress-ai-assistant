# Adding State Validation System Test to System Diagnostics

To add the State Validation System test to the System Diagnostics page in the Phase Three Features Tests section, follow these steps:

## 1. Add Test Buttons

Add the following buttons after the Error Recovery System test buttons in the Phase Three Features Tests section:

```php
<button type="button" id="mpai-test-state-validation" class="button"><?php _e('Test State Validation System', 'memberpress-ai-assistant'); ?></button>
<a href="<?php echo plugins_url('test/test-state-validation.php', dirname(dirname(__FILE__))); ?>" class="button" target="_blank"><?php _e('State Validation Test Page', 'memberpress-ai-assistant'); ?></a>
```

## 2. Add JavaScript Handler

Add the following JavaScript handler within the document ready function:

```javascript
// State Validation System test
$('#mpai-test-state-validation').on('click', function() {
    var $resultsContainer = $('#mpai-phase-three-results');
    var $outputContainer = $('#mpai-phase-three-output');
    
    $resultsContainer.show();
    $outputContainer.html('<p>Running State Validation System tests, please wait...</p>');
    
    // Log that we're starting the test
    console.log('MPAI: Starting State Validation System test');
    
    // Redirect to the State Validation Test Page
    window.open('<?php echo plugins_url('test/test-state-validation.php', dirname(dirname(__FILE__))); ?>', '_blank');
    
    // Update the output container with a message
    $outputContainer.html('<div style="margin-bottom: 15px;">' +
        '<h4>State Validation System Test</h4>' +
        '<p>The State Validation System test has been opened in a new tab. Please check that tab for test results.</p>' +
        '<p><strong>Documentation:</strong> <a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant&view-doc=state-validation-system.md'); ?>" target="_blank">State Validation System Documentation</a></p>' +
        '</div>');
});
```

## Complete Example

Here's how the entire Phase Three Features Tests section should look after these changes:

```php
<!-- Phase Three Features Test Section -->
<div class="mpai-debug-section">
    <h4><?php _e('Phase Three Features Tests', 'memberpress-ai-assistant'); ?></h4>
    <p><?php _e('Test Phase Three features including Error Recovery System, AI Response Validation, and Connection Pooling.', 'memberpress-ai-assistant'); ?></p>
    
    <button type="button" id="mpai-test-error-recovery" class="button"><?php _e('Test Error Recovery System', 'memberpress-ai-assistant'); ?></button>
    <a href="<?php echo plugins_url('test/test-error-recovery-page.php', dirname(dirname(__FILE__))); ?>" class="button" target="_blank"><?php _e('Direct Test Page', 'memberpress-ai-assistant'); ?></a>
    
    <button type="button" id="mpai-test-state-validation" class="button"><?php _e('Test State Validation System', 'memberpress-ai-assistant'); ?></button>
    <a href="<?php echo plugins_url('test/test-state-validation.php', dirname(dirname(__FILE__))); ?>" class="button" target="_blank"><?php _e('State Validation Test Page', 'memberpress-ai-assistant'); ?></a>
    
    <div id="mpai-phase-three-results" class="mpai-debug-results" style="display: none;">
        <h4><?php _e('Test Results', 'memberpress-ai-assistant'); ?></h4>
        <div id="mpai-phase-three-output"></div>
    </div>
</div>
```

And make sure to include the JavaScript handler within the document ready function:

```javascript
jQuery(document).ready(function($) {
    // Error Recovery System test handler...
    
    // State Validation System test
    $('#mpai-test-state-validation').on('click', function() {
        var $resultsContainer = $('#mpai-phase-three-results');
        var $outputContainer = $('#mpai-phase-three-output');
        
        $resultsContainer.show();
        $outputContainer.html('<p>Running State Validation System tests, please wait...</p>');
        
        // Log that we're starting the test
        console.log('MPAI: Starting State Validation System test');
        
        // Redirect to the State Validation Test Page
        window.open('<?php echo plugins_url('test/test-state-validation.php', dirname(dirname(__FILE__))); ?>', '_blank');
        
        // Update the output container with a message
        $outputContainer.html('<div style="margin-bottom: 15px;">' +
            '<h4>State Validation System Test</h4>' +
            '<p>The State Validation System test has been opened in a new tab. Please check that tab for test results.</p>' +
            '<p><strong>Documentation:</strong> <a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant&view-doc=state-validation-system.md'); ?>" target="_blank">State Validation System Documentation</a></p>' +
            '</div>');
    });
    
    // Other handlers...
});
```