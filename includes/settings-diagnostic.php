<?php
/**
 * Settings Diagnostic Tab
 *
 * Displays the diagnostic tab for MemberPress AI Assistant settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div id="tab-diagnostic" class="mpai-settings-tab" style="display: none;">
    <h3><?php _e('System Diagnostics', 'memberpress-ai-assistant'); ?></h3>
    <p><?php _e('Run various diagnostic tests to check the health of your MemberPress AI Assistant installation.', 'memberpress-ai-assistant'); ?></p>
    
    <!-- Super direct console test that doesn't rely on any external code -->
    <div style="margin: 15px 0; padding: 15px; background: #f8f8f8; border: 1px solid #ddd;">
        <h4 style="margin-top: 0;">Direct Console Test (No Dependencies)</h4>
        <p>Click the button below to test if console logging works in your browser.</p>
        <button type="button" onclick="
            console.log('💥 ULTRA DIRECT TEST: Button clicked at ' + new Date().toISOString());
            console.warn('💥 ULTRA DIRECT TEST: Warning message');
            console.error('💥 ULTRA DIRECT TEST: Error message');
            console.log('💥 ULTRA DIRECT TEST: Object test:', {test: 'value', number: 123});
            alert('Direct console log test executed - check your browser console (F12)');
        " class="button button-primary">Ultra Direct Console Test</button>
        <p><small>This test uses inline JavaScript that doesn't depend on any external code.</small></p>
    </div>
    
    <!-- Direct AJAX handler test for console logging -->
    <div style="margin: 15px 0; padding: 15px; background: #f0f9ff; border: 1px solid #c0d8e8;">
        <h4 style="margin-top: 0;">Direct AJAX Handler Console Test</h4>
        <p>Click the button below to test the Direct AJAX Handler for console logging.</p>
        <button type="button" id="mpai-direct-ajax-console-test" class="button button-secondary">Test via Direct AJAX Handler</button>
        <span id="direct-ajax-test-result" style="margin-left: 10px; font-style: italic;"></span>
        <p><small>This test uses the Direct AJAX Handler which bypasses WordPress's admin-ajax.php.</small></p>
        
        <script>
            jQuery(document).ready(function($) {
                $('#mpai-direct-ajax-console-test').on('click', function() {
                    var $resultSpan = $('#direct-ajax-test-result');
                    $resultSpan.text('Testing...');
                    
                    // Log that we're starting the test
                    console.group('🌐 Direct AJAX Handler Console Test');
                    console.log('Starting test at ' + new Date().toISOString());
                    
                    // Prepare the form data
                    var formData = new FormData();
                    formData.append('action', 'test_console_logging');
                    formData.append('log_level', $('#mpai_console_log_level').val());
                    formData.append('enable_logging', $('#mpai_enable_console_logging').is(':checked') ? '1' : '0');
                    formData.append('log_api_calls', $('#mpai_log_api_calls').is(':checked') ? '1' : '0');
                    formData.append('log_tool_usage', $('#mpai_log_tool_usage').is(':checked') ? '1' : '0');
                    formData.append('log_agent_activity', $('#mpai_log_agent_activity').is(':checked') ? '1' : '0');
                    formData.append('log_timing', $('#mpai_log_timing').is(':checked') ? '1' : '0');
                    formData.append('save_settings', '1');
                    
                    // Get the direct AJAX handler URL
                    var directHandlerUrl = '<?php echo plugin_dir_url(dirname(__FILE__)) . 'includes/direct-ajax-handler.php'; ?>';
                    
                    console.log('Sending request to: ' + directHandlerUrl);
                    console.log('Form data:', {
                        action: 'test_console_logging',
                        log_level: $('#mpai_console_log_level').val(),
                        enable_logging: $('#mpai_enable_console_logging').is(':checked')
                    });
                    
                    // Send the request to the direct AJAX handler
                    fetch(directHandlerUrl, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        console.log('Received response:', data);
                        
                        if (data.success) {
                            $resultSpan.html('<span style="color: green;">✓ Test completed successfully!</span>');
                            
                            // Log the test results
                            console.log('Test completed successfully');
                            console.log('Current settings:', data.current_settings);
                            console.log('Test result:', data.test_result);
                            
                            // Update the UI with the current settings
                            $('#mpai_enable_console_logging').prop('checked', data.current_settings.enabled === '1');
                            $('#mpai_console_log_level').val(data.current_settings.log_level);
                            $('#mpai_log_api_calls').prop('checked', data.current_settings.categories.api_calls === '1');
                            $('#mpai_log_tool_usage').prop('checked', data.current_settings.categories.tool_usage === '1');
                            $('#mpai_log_agent_activity').prop('checked', data.current_settings.categories.agent_activity === '1');
                            $('#mpai_log_timing').prop('checked', data.current_settings.categories.timing === '1');
                            
                            // Apply these settings to any existing logger
                            if (window.mpaiLogger) {
                                window.mpaiLogger.enabled = data.current_settings.enabled === '1';
                                window.mpaiLogger.logLevel = data.current_settings.log_level;
                                window.mpaiLogger.categories = {
                                    api_calls: data.current_settings.categories.api_calls === '1',
                                    tool_usage: data.current_settings.categories.tool_usage === '1',
                                    agent_activity: data.current_settings.categories.agent_activity === '1',
                                    timing: data.current_settings.categories.timing === '1',
                                    ui: true // Always enable UI logging
                                };
                                
                                console.log('Updated mpaiLogger with settings from server');
                            }
                            
                            // Save to localStorage as well
                            try {
                                localStorage.setItem('mpai_logger_settings', JSON.stringify({
                                    enabled: data.current_settings.enabled === '1',
                                    logLevel: data.current_settings.log_level,
                                    categories: {
                                        api_calls: data.current_settings.categories.api_calls === '1',
                                        tool_usage: data.current_settings.categories.tool_usage === '1',
                                        agent_activity: data.current_settings.categories.agent_activity === '1',
                                        timing: data.current_settings.categories.timing === '1',
                                        ui: true
                                    }
                                }));
                                console.log('Saved settings to localStorage');
                            } catch (e) {
                                console.error('Error saving to localStorage:', e);
                            }
                        } else {
                            $resultSpan.html('<span style="color: red;">✗ Test failed: ' + data.message + '</span>');
                            console.error('Test failed:', data.message);
                        }
                    })
                    .catch(function(error) {
                        console.error('Error:', error);
                        $resultSpan.html('<span style="color: red;">✗ Error: ' + error.message + '</span>');
                    })
                    .finally(function() {
                        console.groupEnd();
                    });
                });
            });
        </script>
    </div>

    <div class="mpai-diagnostic-section">
        <h4><?php _e('Console Logging', 'memberpress-ai-assistant'); ?></h4>
        <p><?php _e('Configure the browser console logging system to help with debugging and monitoring.', 'memberpress-ai-assistant'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="mpai_enable_console_logging"><?php _e('Enable Console Logging', 'memberpress-ai-assistant'); ?></label>
                </th>
                <td>
                    <div class="console-logging-control">
                        <label>
                            <input type="checkbox" name="mpai_enable_console_logging" id="mpai_enable_console_logging" value="1" <?php checked(get_option('mpai_enable_console_logging', '0'), '1'); ?> />
                            <?php _e('Log detailed information to the browser console', 'memberpress-ai-assistant'); ?>
                        </label>
                        <span id="mpai-console-logging-status" class="logging-status-indicator <?php echo get_option('mpai_enable_console_logging', '0') === '1' ? 'active' : 'inactive'; ?>">
                            <?php echo get_option('mpai_enable_console_logging', '0') === '1' ? 'ENABLED' : 'DISABLED'; ?>
                        </span>
                    </div>
                    <p class="description"><?php _e('Enable this option to log detailed information about AI Assistant operations to your browser console.', 'memberpress-ai-assistant'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="mpai_console_log_level"><?php _e('Log Level', 'memberpress-ai-assistant'); ?></label>
                </th>
                <td>
                    <select name="mpai_console_log_level" id="mpai_console_log_level">
                        <option value="error" <?php selected(get_option('mpai_console_log_level', 'info'), 'error'); ?>><?php _e('Error Only', 'memberpress-ai-assistant'); ?></option>
                        <option value="warning" <?php selected(get_option('mpai_console_log_level', 'info'), 'warning'); ?>><?php _e('Warning & Error', 'memberpress-ai-assistant'); ?></option>
                        <option value="info" <?php selected(get_option('mpai_console_log_level', 'info'), 'info'); ?>><?php _e('Info, Warning & Error', 'memberpress-ai-assistant'); ?></option>
                        <option value="debug" <?php selected(get_option('mpai_console_log_level', 'info'), 'debug'); ?>><?php _e('All (Debug)', 'memberpress-ai-assistant'); ?></option>
                    </select>
                    <p class="description"><?php _e('Select the level of detail to log in the browser console.', 'memberpress-ai-assistant'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php _e('Log Categories', 'memberpress-ai-assistant'); ?></label>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php _e('Log Categories', 'memberpress-ai-assistant'); ?></legend>
                        <label>
                            <input type="checkbox" name="mpai_log_api_calls" value="1" <?php checked(get_option('mpai_log_api_calls', true)); ?> />
                            <?php _e('API Calls (Anthropic & OpenAI)', 'memberpress-ai-assistant'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="mpai_log_tool_usage" value="1" <?php checked(get_option('mpai_log_tool_usage', true)); ?> />
                            <?php _e('Tool Usage', 'memberpress-ai-assistant'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="mpai_log_agent_activity" value="1" <?php checked(get_option('mpai_log_agent_activity', true)); ?> />
                            <?php _e('Agent Activity', 'memberpress-ai-assistant'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="mpai_log_timing" value="1" <?php checked(get_option('mpai_log_timing', true)); ?> />
                            <?php _e('Performance Timing', 'memberpress-ai-assistant'); ?>
                        </label>
                        <p class="description"><?php _e('Select which categories of events to log to the console.', 'memberpress-ai-assistant'); ?></p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php _e('Console Tester', 'memberpress-ai-assistant'); ?></label>
                </th>
                <td>
                    <button type="button" id="mpai-test-console-logging" class="button"><?php _e('Test Console Logging', 'memberpress-ai-assistant'); ?></button>
                    <span id="mpai-console-test-result" class="mpai-test-result" style="display: none;"></span>
                    <p class="description"><?php _e('Click to test console logging with your current settings. Check your browser\'s developer console (F12) to see the logs.', 'memberpress-ai-assistant'); ?></p>
                    
                    <!-- Ultra simple test button that tests the enabled state directly -->
                    <button type="button" onclick="testEnabledState()" class="button button-secondary" style="margin-top: 10px;">Check Enabled State Only</button>
                    <button type="button" onclick="testCacheRefresh()" class="button button-secondary" style="margin-top: 10px; margin-left: 5px;">Test Cache Refresh</button>
                    
                    <script>
                    /* 
                     * NOTE: The event handler for the test console logging button
                     * is now defined in admin.js in the initConsoleLoggingSettings() function.
                     * This ensures better separation of concerns and modular code.
                     */
                    
                    // Simple function to directly test if the logger is enabled
                    function testEnabledState() {
                        if (window.mpaiLogger) {
                            console.log('%c========== ENABLED STATE TEST ==========', 'background: #eee; color: #333; padding: 5px; font-weight: bold;');
                            console.log('→ The mpaiLogger.enabled value is: ' + window.mpaiLogger.enabled + ' (type: ' + typeof window.mpaiLogger.enabled + ')');
                            console.log('→ The checkbox is: ' + ($('#mpai_enable_console_logging').is(':checked') ? 'CHECKED' : 'UNCHECKED'));
                            console.log('→ Status indicator shows: ' + $('#mpai-console-logging-status').text());
                            console.log('→ Status indicator classes: ' + $('#mpai-console-logging-status').attr('class'));
                            console.log('→ Logging is: ' + (window.mpaiLogger.enabled ? 'ENABLED' : 'DISABLED'));
                            console.log('→ Testing a log message (should only appear if enabled):');
                            
                            // Try to log a message - this should only appear if enabled is true
                            window.mpaiLogger.info('This is a test message from enabled state test', 'ui');
                            
                            console.log('→ Settings in localStorage:', localStorage.getItem('mpai_logger_settings'));
                            
                            alert('Check your console. The enabled state is: ' + (window.mpaiLogger.enabled ? 'ENABLED' : 'DISABLED'));
                        } else {
                            console.log('mpaiLogger not found!');
                            alert('mpaiLogger not found! Check the console for more details.');
                        }
                    }
                    
                    // Function to test the cache refresh mechanism
                    function testCacheRefresh() {
                        if (!window.mpaiLogger) {
                            console.log('mpaiLogger not found!');
                            alert('mpaiLogger not found! Check the console for more details.');
                            return;
                        }
                        
                        console.log('%c========== CACHE REFRESH TEST ==========', 'background: #eef; color: #336; padding: 5px; font-weight: bold;');
                        
                        // Save current state
                        var currentState = window.mpaiLogger.enabled;
                        console.log('→ Current logger enabled state: ' + currentState);
                        
                        // Toggle the state in localStorage
                        try {
                            var settings = JSON.parse(localStorage.getItem('mpai_logger_settings')) || {};
                            settings.enabled = !currentState;
                            localStorage.setItem('mpai_logger_settings', JSON.stringify(settings));
                            console.log('→ Changed localStorage enabled setting to: ' + !currentState);
                            
                            // Force re-init
                            localStorage.removeItem('mpai_logger_last_init');
                            console.log('→ Removed init cache timestamp');
                            
                            // Reinitialize
                            window.mpaiLogger.initialize();
                            console.log('→ Reinitialized logger');
                            
                            // Check if state changed
                            console.log('→ New logger enabled state: ' + window.mpaiLogger.enabled + ' (should be ' + !currentState + ')');
                            console.log('→ Checkbox state is now: ' + ($('#mpai_enable_console_logging').is(':checked') ? 'CHECKED' : 'UNCHECKED'));
                            console.log('→ Status indicator now shows: ' + $('#mpai-console-logging-status').text());
                            
                            alert('Cache refresh test complete. Check console for results. New enabled state: ' + window.mpaiLogger.enabled);
                        } catch (e) {
                            console.error('Error in cache refresh test:', e);
                            alert('Error in cache refresh test: ' + e.message);
                        }
                    }
                    </script>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="mpai-diagnostic-section">
        <h4><?php _e('API Connections', 'memberpress-ai-assistant'); ?></h4>
        <div class="mpai-diagnostic-cards">
            <div class="mpai-diagnostic-card">
                <div class="mpai-diagnostic-header">
                    <h4><?php _e('OpenAI API', 'memberpress-ai-assistant'); ?></h4>
                    <div class="mpai-status-indicator" id="openai-status-indicator">
                        <span class="mpai-status-dot mpai-status-unknown"></span>
                        <span class="mpai-status-text"><?php _e('Unknown', 'memberpress-ai-assistant'); ?></span>
                    </div>
                </div>
                <div class="mpai-diagnostic-actions">
                    <button type="button" id="run-openai-diagnostic" class="button" data-test-type="openai_connection"><?php _e('Run Diagnostic', 'memberpress-ai-assistant'); ?></button>
                </div>
                <div class="mpai-diagnostic-result" id="openai-diagnostic-result" style="display: none;"></div>
            </div>
            
            <div class="mpai-diagnostic-card">
                <div class="mpai-diagnostic-header">
                    <h4><?php _e('Anthropic API', 'memberpress-ai-assistant'); ?></h4>
                    <div class="mpai-status-indicator" id="anthropic-status-indicator">
                        <span class="mpai-status-dot mpai-status-unknown"></span>
                        <span class="mpai-status-text"><?php _e('Unknown', 'memberpress-ai-assistant'); ?></span>
                    </div>
                </div>
                <div class="mpai-diagnostic-actions">
                    <button type="button" id="run-anthropic-diagnostic" class="button" data-test-type="anthropic_connection"><?php _e('Run Diagnostic', 'memberpress-ai-assistant'); ?></button>
                </div>
                <div class="mpai-diagnostic-result" id="anthropic-diagnostic-result" style="display: none;"></div>
            </div>
            
            <div class="mpai-diagnostic-card">
                <div class="mpai-diagnostic-header">
                    <h4><?php _e('MemberPress API', 'memberpress-ai-assistant'); ?></h4>
                    <div class="mpai-status-indicator" id="memberpress-status-indicator">
                        <span class="mpai-status-dot mpai-status-unknown"></span>
                        <span class="mpai-status-text"><?php _e('Unknown', 'memberpress-ai-assistant'); ?></span>
                    </div>
                </div>
                <div class="mpai-diagnostic-actions">
                    <button type="button" id="run-memberpress-diagnostic" class="button" data-test-type="memberpress_connection"><?php _e('Run Diagnostic', 'memberpress-ai-assistant'); ?></button>
                </div>
                <div class="mpai-diagnostic-result" id="memberpress-diagnostic-result" style="display: none;"></div>
            </div>
        </div>
    </div>
    
    <div class="mpai-diagnostic-section">
        <h4><?php _e('System Information', 'memberpress-ai-assistant'); ?></h4>
        <div class="mpai-diagnostic-cards">
            <div class="mpai-diagnostic-card">
                <div class="mpai-diagnostic-header">
                    <h4><?php _e('WordPress', 'memberpress-ai-assistant'); ?></h4>
                </div>
                <div class="mpai-diagnostic-actions">
                    <button type="button" id="run-wordpress-diagnostic" class="button" data-test-type="wordpress_info"><?php _e('Get WordPress Info', 'memberpress-ai-assistant'); ?></button>
                </div>
                <div class="mpai-diagnostic-result" id="wordpress-diagnostic-result" style="display: none;"></div>
            </div>
            
            <div class="mpai-diagnostic-card">
                <div class="mpai-diagnostic-header">
                    <h4><?php _e('Plugin Status', 'memberpress-ai-assistant'); ?></h4>
                </div>
                <div class="mpai-diagnostic-actions">
                    <button type="button" id="run-plugin-diagnostic" class="button" data-test-type="plugin_status"><?php _e('Check Plugin Status', 'memberpress-ai-assistant'); ?></button>
                </div>
                <div class="mpai-diagnostic-result" id="plugin-diagnostic-result" style="display: none;"></div>
            </div>
        </div>
    </div>
    
    <div class="mpai-diagnostic-section">
        <h4><?php _e('Complete System Check', 'memberpress-ai-assistant'); ?></h4>
        <p><?php _e('Run a comprehensive diagnostic to check all systems.', 'memberpress-ai-assistant'); ?></p>
        <button type="button" id="run-all-diagnostics" class="button button-primary" data-test-type="all"><?php _e('Run All Diagnostics', 'memberpress-ai-assistant'); ?></button>
        <div class="mpai-diagnostic-result" id="all-diagnostic-result" style="display: none;"></div>
    </div>
    
    <div class="mpai-diagnostic-section">
        <h4><?php _e('Agent System - Phase One Test', 'memberpress-ai-assistant'); ?></h4>
        <p><?php _e('Test the Phase One enhancements to the Agent System including dynamic discovery, lazy loading, and improved communication.', 'memberpress-ai-assistant'); ?></p>
        <div class="mpai-diagnostic-cards">
            <div class="mpai-diagnostic-card">
                <div class="mpai-diagnostic-header">
                    <h4><?php _e('Agent Discovery', 'memberpress-ai-assistant'); ?></h4>
                    <div class="mpai-status-indicator" id="agent-discovery-status-indicator">
                        <span class="mpai-status-dot mpai-status-unknown"></span>
                        <span class="mpai-status-text"><?php _e('Not Tested', 'memberpress-ai-assistant'); ?></span>
                    </div>
                </div>
                <div class="mpai-diagnostic-actions">
                    <button type="button" id="run-agent-discovery-test" class="button" data-test-type="agent_discovery"><?php _e('Run Test', 'memberpress-ai-assistant'); ?></button>
                </div>
                <div class="mpai-diagnostic-result" id="agent-discovery-result" style="display: none;"></div>
            </div>
            
            <div class="mpai-diagnostic-card">
                <div class="mpai-diagnostic-header">
                    <h4><?php _e('Tool Lazy Loading', 'memberpress-ai-assistant'); ?></h4>
                    <div class="mpai-status-indicator" id="lazy-loading-status-indicator">
                        <span class="mpai-status-dot mpai-status-unknown"></span>
                        <span class="mpai-status-text"><?php _e('Not Tested', 'memberpress-ai-assistant'); ?></span>
                    </div>
                </div>
                <div class="mpai-diagnostic-actions">
                    <button type="button" id="run-lazy-loading-test" class="button" data-test-type="lazy_loading"><?php _e('Run Test', 'memberpress-ai-assistant'); ?></button>
                </div>
                <div class="mpai-diagnostic-result" id="lazy-loading-result" style="display: none;"></div>
            </div>
            
            <div class="mpai-diagnostic-card">
                <div class="mpai-diagnostic-header">
                    <h4><?php _e('Response Cache', 'memberpress-ai-assistant'); ?></h4>
                    <div class="mpai-status-indicator" id="response-cache-status-indicator">
                        <span class="mpai-status-dot mpai-status-unknown"></span>
                        <span class="mpai-status-text"><?php _e('Not Tested', 'memberpress-ai-assistant'); ?></span>
                    </div>
                </div>
                <div class="mpai-diagnostic-actions">
                    <button type="button" id="run-response-cache-test" class="button" data-test-type="response_cache"><?php _e('Run Test', 'memberpress-ai-assistant'); ?></button>
                </div>
                <div class="mpai-diagnostic-result" id="response-cache-result" style="display: none;"></div>
            </div>
            
            <div class="mpai-diagnostic-card">
                <div class="mpai-diagnostic-header">
                    <h4><?php _e('Agent Messaging', 'memberpress-ai-assistant'); ?></h4>
                    <div class="mpai-status-indicator" id="agent-messaging-status-indicator">
                        <span class="mpai-status-dot mpai-status-unknown"></span>
                        <span class="mpai-status-text"><?php _e('Not Tested', 'memberpress-ai-assistant'); ?></span>
                    </div>
                </div>
                <div class="mpai-diagnostic-actions">
                    <button type="button" id="run-agent-messaging-test" class="button" data-test-type="agent_messaging"><?php _e('Run Test', 'memberpress-ai-assistant'); ?></button>
                </div>
                <div class="mpai-diagnostic-result" id="agent-messaging-result" style="display: none;"></div>
            </div>
        </div>
        
        <div class="mpai-phase-one-actions" style="margin-top: 15px;">
            <button type="button" id="run-all-phase-one-tests" class="button button-primary"><?php _e('Run All Phase One Tests', 'memberpress-ai-assistant'); ?></button>
        </div>
    </div>
    
    <div class="mpai-diagnostic-section">
        <h4><?php _e('Agent System - Phase Two Test', 'memberpress-ai-assistant'); ?></h4>
        <p><?php _e('Test the Phase Two enhancements to the Agent System including Agent Specialization Scoring.', 'memberpress-ai-assistant'); ?></p>
        <div class="mpai-diagnostic-cards">
            <div class="mpai-diagnostic-card">
                <div class="mpai-diagnostic-header">
                    <h4><?php _e('Agent Specialization Scoring', 'memberpress-ai-assistant'); ?></h4>
                    <div class="mpai-status-indicator" id="agent-scoring-status-indicator">
                        <span class="mpai-status-dot mpai-status-unknown"></span>
                        <span class="mpai-status-text"><?php _e('Not Tested', 'memberpress-ai-assistant'); ?></span>
                    </div>
                </div>
                <div class="mpai-diagnostic-actions">
                    <button type="button" id="run-agent-scoring-test" class="button" data-test-type="agent_scoring"><?php _e('Run Test', 'memberpress-ai-assistant'); ?></button>
                </div>
                <div class="mpai-diagnostic-result" id="agent-scoring-result" style="display: none;"></div>
            </div>
        </div>
    </div>
    
    <div class="mpai-diagnostic-section">
        <h4><?php _e('Plugin Logs', 'memberpress-ai-assistant'); ?></h4>
        <p><?php _e('Track and review plugin installation, activation, deactivation, and deletion events.', 'memberpress-ai-assistant'); ?></p>
        
        <div class="mpai-plugin-logs-controls">
            <div class="mpai-plugin-logs-filters">
                <select id="mpai-plugin-logs-action-filter">
                    <option value=""><?php _e('All Actions', 'memberpress-ai-assistant'); ?></option>
                    <option value="installed"><?php _e('Installed', 'memberpress-ai-assistant'); ?></option>
                    <option value="updated"><?php _e('Updated', 'memberpress-ai-assistant'); ?></option>
                    <option value="activated"><?php _e('Activated', 'memberpress-ai-assistant'); ?></option>
                    <option value="deactivated"><?php _e('Deactivated', 'memberpress-ai-assistant'); ?></option>
                    <option value="deleted"><?php _e('Deleted', 'memberpress-ai-assistant'); ?></option>
                </select>
                
                <input type="text" id="mpai-plugin-logs-plugin-filter" placeholder="<?php _e('Filter by plugin name...', 'memberpress-ai-assistant'); ?>">
                
                <select id="mpai-plugin-logs-date-filter">
                    <option value="7"><?php _e('Last 7 days', 'memberpress-ai-assistant'); ?></option>
                    <option value="30" selected><?php _e('Last 30 days', 'memberpress-ai-assistant'); ?></option>
                    <option value="90"><?php _e('Last 90 days', 'memberpress-ai-assistant'); ?></option>
                    <option value="365"><?php _e('Last year', 'memberpress-ai-assistant'); ?></option>
                    <option value="0"><?php _e('All time', 'memberpress-ai-assistant'); ?></option>
                </select>
                
                <button type="button" id="mpai-plugin-logs-refresh" class="button"><?php _e('Refresh', 'memberpress-ai-assistant'); ?></button>
            </div>
            
            <div class="mpai-plugin-logs-actions">
                <button type="button" id="mpai-plugin-logs-export" class="button"><?php _e('Export CSV', 'memberpress-ai-assistant'); ?></button>
                <label class="mpai-switch">
                    <input type="checkbox" id="mpai-enable-plugin-logging" name="mpai_enable_plugin_logging" value="1" <?php checked(get_option('mpai_enable_plugin_logging', true)); ?>>
                    <span class="mpai-slider"></span>
                    <?php _e('Enable Logging', 'memberpress-ai-assistant'); ?>
                </label>
            </div>
        </div>
        
        <div id="mpai-plugin-logs-container">
            <div class="mpai-plugin-logs-summary">
                <div class="mpai-summary-card">
                    <h5><?php _e('Recent Activity', 'memberpress-ai-assistant'); ?></h5>
                    <div class="mpai-summary-count" id="mpai-recent-activity-count">-</div>
                    <div class="mpai-summary-label"><?php _e('events in selected period', 'memberpress-ai-assistant'); ?></div>
                </div>
                
                <div class="mpai-summary-card">
                    <h5><?php _e('Installations', 'memberpress-ai-assistant'); ?></h5>
                    <div class="mpai-summary-count" id="mpai-installations-count">-</div>
                </div>
                
                <div class="mpai-summary-card">
                    <h5><?php _e('Updates', 'memberpress-ai-assistant'); ?></h5>
                    <div class="mpai-summary-count" id="mpai-updates-count">-</div>
                </div>
                
                <div class="mpai-summary-card">
                    <h5><?php _e('Activations', 'memberpress-ai-assistant'); ?></h5>
                    <div class="mpai-summary-count" id="mpai-activations-count">-</div>
                </div>
                
                <div class="mpai-summary-card">
                    <h5><?php _e('Deactivations', 'memberpress-ai-assistant'); ?></h5>
                    <div class="mpai-summary-count" id="mpai-deactivations-count">-</div>
                </div>
            </div>
            
            <div class="mpai-plugin-logs-table-container">
                <table class="widefat mpai-plugin-logs-table">
                    <thead>
                        <tr>
                            <th class="mpai-col-date"><?php _e('Date & Time', 'memberpress-ai-assistant'); ?></th>
                            <th class="mpai-col-action"><?php _e('Action', 'memberpress-ai-assistant'); ?></th>
                            <th class="mpai-col-plugin"><?php _e('Plugin', 'memberpress-ai-assistant'); ?></th>
                            <th class="mpai-col-version"><?php _e('Version', 'memberpress-ai-assistant'); ?></th>
                            <th class="mpai-col-user"><?php _e('User', 'memberpress-ai-assistant'); ?></th>
                            <th class="mpai-col-details"><?php _e('Details', 'memberpress-ai-assistant'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="mpai-plugin-logs-table-body">
                        <tr>
                            <td colspan="6" class="mpai-plugin-logs-loading"><?php _e('Loading plugin logs...', 'memberpress-ai-assistant'); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="mpai-plugin-logs-pagination">
                    <button type="button" id="mpai-plugin-logs-prev-page" class="button" disabled><?php _e('Previous', 'memberpress-ai-assistant'); ?></button>
                    <span id="mpai-plugin-logs-page-info"><?php _e('Page 1', 'memberpress-ai-assistant'); ?></span>
                    <button type="button" id="mpai-plugin-logs-next-page" class="button" disabled><?php _e('Next', 'memberpress-ai-assistant'); ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    // Check if any test files exist in the new location
    $test_files_exist = false;
    $test_files = array(
        'debug-info.php' => __('Debug Info', 'memberpress-ai-assistant'),
        'ajax-test.php' => __('AJAX Test', 'memberpress-ai-assistant'),
        'openai-test.php' => __('OpenAI API Test', 'memberpress-ai-assistant'),
        'memberpress-test.php' => __('MemberPress API Test', 'memberpress-ai-assistant'),
        'anthropic-test.php' => __('Anthropic API Test', 'memberpress-ai-assistant'),
        'test-validate-command.php' => __('Validate Command', 'memberpress-ai-assistant'),
        'test-best-selling.php' => __('Best Selling Test', 'memberpress-ai-assistant'),
        'test-plugin-logs.php' => __('Plugin Logs Test', 'memberpress-ai-assistant')
    );
    
    $test_dir = plugin_dir_path(dirname(__FILE__)) . 'test/';
    
    // Check if any test files exist
    foreach ($test_files as $file => $label) {
        if (file_exists($test_dir . $file)) {
            $test_files_exist = true;
            break;
        }
    }
    
    // Only show the section if test files exist
    if ($test_files_exist) :
    ?>
    <div class="mpai-diagnostic-section">
        <h4><?php _e('Legacy Test Scripts', 'memberpress-ai-assistant'); ?></h4>
        <p><?php _e('These test scripts provide additional diagnostic capabilities.', 'memberpress-ai-assistant'); ?></p>
        <p>
            <?php foreach ($test_files as $file => $label) : ?>
                <?php if (file_exists($test_dir . $file)) : ?>
                    <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'test/' . $file); ?>" class="button" target="_blank"><?php echo $label; ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<style>
/* Diagnostic Tab Styles */
.mpai-diagnostic-section {
    margin-bottom: 25px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 5px;
}

.mpai-diagnostic-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.mpai-diagnostic-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.mpai-test-passed {
    background: #f0fff0;
    border-color: #4CAF50;
    box-shadow: 0 1px 3px rgba(76,175,80,0.3);
}

.mpai-diagnostic-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.mpai-diagnostic-header h4 {
    margin: 0;
    font-size: 16px;
}

.mpai-status-indicator {
    display: flex;
    align-items: center;
}

.mpai-status-dot {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 5px;
}

.mpai-status-unknown {
    background-color: #999;
}

.mpai-status-success {
    background-color: #4CAF50;
}

.mpai-status-error {
    background-color: #F44336;
}

.mpai-status-warning {
    background-color: #FFA000;
}

.mpai-diagnostic-actions {
    margin-bottom: 15px;
}

.mpai-diagnostic-result {
    margin-top: 15px;
    padding: 10px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 3px;
    max-height: 300px;
    overflow-y: auto;
    display: none; /* Hide initially */
}

.mpai-diagnostic-card.mpai-test-passed .mpai-diagnostic-result {
    background: #f0fff0;
    border-color: #4CAF50;
}

/* Style for the toggle button */
.mpai-toggle-details {
    margin-left: 10px !important;
    font-size: 11px !important;
    padding: 0 8px !important;
    height: 24px !important;
    line-height: 22px !important;
}

.mpai-diagnostic-result pre {
    margin: 0;
    white-space: pre-wrap;
    font-family: monospace;
    font-size: 12px;
}

.mpai-diagnostic-result table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.mpai-diagnostic-result table th,
.mpai-diagnostic-result table td {
    padding: 5px;
    border: 1px solid #ddd;
    text-align: left;
}

.mpai-diagnostic-result table th {
    background-color: #f0f0f0;
}

.mpai-diagnostic-result .success {
    color: #4CAF50;
}

.mpai-diagnostic-result .error {
    color: #F44336;
}

.mpai-diagnostic-result .warning {
    color: #FFA000;
}

/* Plugin Logs Styles */
.mpai-plugin-logs-controls {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.mpai-plugin-logs-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}

.mpai-plugin-logs-actions {
    display: flex;
    gap: 15px;
    align-items: center;
}

.mpai-plugin-logs-summary {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.mpai-summary-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 12px;
    text-align: center;
}

.mpai-summary-card h5 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #23282d;
}

.mpai-summary-count {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
    margin-bottom: 4px;
}

.mpai-summary-label {
    font-size: 12px;
    color: #666;
}

.mpai-plugin-logs-table-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    overflow: hidden;
}

.mpai-plugin-logs-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.mpai-plugin-logs-table th,
.mpai-plugin-logs-table td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    vertical-align: top;
}

.mpai-plugin-logs-table th {
    text-align: left;
    background: #f0f0f0;
    font-weight: 600;
}

.mpai-plugin-logs-table tbody tr:hover {
    background-color: #f9f9f9;
}

.mpai-plugin-logs-table .mpai-col-date {
    width: 15%;
}

.mpai-plugin-logs-table .mpai-col-action {
    width: 10%;
}

.mpai-plugin-logs-table .mpai-col-plugin {
    width: 20%;
}

.mpai-plugin-logs-table .mpai-col-version {
    width: 10%;
}

.mpai-plugin-logs-table .mpai-col-user {
    width: 15%;
}

.mpai-plugin-logs-table .mpai-col-details {
    width: 30%;
}

.mpai-plugin-logs-loading {
    text-align: center;
    padding: 20px !important;
    color: #666;
}

.mpai-plugin-logs-empty {
    text-align: center;
    padding: 20px !important;
    color: #666;
}

.mpai-plugin-logs-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 10px;
    gap: 15px;
}

.mpai-plugin-logs-pagination span {
    font-size: 13px;
    color: #666;
}

.mpai-action-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: capitalize;
}

.mpai-action-installed {
    background-color: #e6f6e6;
    color: #1e7e1e;
}

.mpai-action-updated {
    background-color: #e6f0ff;
    color: #0044cc;
}

.mpai-action-activated {
    background-color: #e6fcf5;
    color: #00806b;
}

.mpai-action-deactivated {
    background-color: #fff2e6;
    color: #cc5200;
}

.mpai-action-deleted {
    background-color: #ffe6e6;
    color: #cc0000;
}

.mpai-details-button {
    background: none;
    border: none;
    color: #0073aa;
    text-decoration: underline;
    cursor: pointer;
    padding: 0;
}

.mpai-details-popup {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mpai-details-popup-content {
    background: #fff;
    border-radius: 5px;
    padding: 20px;
    max-width: 80%;
    max-height: 80%;
    overflow-y: auto;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.mpai-details-popup-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
}

.mpai-details-popup-header h3 {
    margin: 0;
}

.mpai-details-popup-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #666;
}

.mpai-details-popup-body {
    margin-bottom: 20px;
}

.mpai-details-table {
    width: 100%;
    border-collapse: collapse;
}

.mpai-details-table th,
.mpai-details-table td {
    padding: 8px;
    border: 1px solid #ddd;
    text-align: left;
}

.mpai-details-table th {
    background: #f0f0f0;
    font-weight: 600;
}

/* Console Logging Controls */
.console-logging-control {
    display: flex;
    align-items: center;
    gap: 15px;
}

.logging-status-indicator {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 0.5px;
}

.logging-status-indicator.active {
    background-color: #d6f0d6;
    color: #0a6b0a;
    border: 1px solid #a3d9a3;
}

.logging-status-indicator.inactive {
    background-color: #f2dede;
    color: #a94442;
    border: 1px solid #ebccd1;
}

/* Toggle Switch Styles */
.mpai-switch {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.mpai-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.mpai-slider {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 22px;
    background-color: #ccc;
    border-radius: 34px;
    transition: .4s;
}

.mpai-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    border-radius: 50%;
    transition: .4s;
}

input:checked + .mpai-slider {
    background-color: #2196F3;
}

input:focus + .mpai-slider {
    box-shadow: 0 0 1px #2196F3;
}

input:checked + .mpai-slider:before {
    transform: translateX(18px);
}
</style>

<script>
jQuery(document).ready(function($) {
    // Function to run diagnostic
    function runDiagnostic(testType, resultContainer, statusIndicator) {
        console.log('MPAI: Running diagnostic:', testType);
        
        // Show loading state
        $(resultContainer).html('<p>Running diagnostic...</p>');
        $(resultContainer).show();
        
        // Update status indicator
        if (statusIndicator) {
            $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
                .addClass('mpai-status-unknown');
            $(statusIndicator + ' .mpai-status-text').text('Running...');
        }
        
        // Make API request to run diagnostic
        var formData = new FormData();
        formData.append('action', 'mpai_run_diagnostic');
        formData.append('test_type', testType);
        formData.append('nonce', mpai_data.nonce);
        
        // Use direct AJAX handler instead of WordPress admin-ajax.php
        var directHandlerUrl = '<?php echo plugin_dir_url(dirname(__FILE__)) . 'includes/direct-ajax-handler.php'; ?>';
        
        console.log('MPAI: Running diagnostic via direct handler:', testType);
        fetch(directHandlerUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(function(response) {
            console.log('MPAI: Diagnostic response:', response);
            
            if (response.success) {
                // Format and display the result
                var result = response.data;
                var resultHtml = formatDiagnosticResult(result, testType);
                
                $(resultContainer).html(resultHtml);
                
                // Update status indicator
                if (statusIndicator) {
                    if (result.success) {
                        $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-error')
                            .addClass('mpai-status-success');
                        $(statusIndicator + ' .mpai-status-text').text('Connected');
                    } else {
                        $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success')
                            .addClass('mpai-status-error');
                        $(statusIndicator + ' .mpai-status-text').text('Error');
                    }
                }
            } else {
                // Show error
                $(resultContainer).html('<p class="error">Error: ' + response.data + '</p>');
                
                // Update status indicator
                if (statusIndicator) {
                    $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success')
                        .addClass('mpai-status-error');
                    $(statusIndicator + ' .mpai-status-text').text('Error');
                }
            }
        })
        .catch(function(error) {
            console.error('MPAI: Fetch error:', error);
            
            // Show error
            $(resultContainer).html('<p class="error">Error: ' + error.message + '</p>');
            
            // Update status indicator
            if (statusIndicator) {
                $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success')
                    .addClass('mpai-status-error');
                $(statusIndicator + ' .mpai-status-text').text('Error');
            }
        });
        
    }
    
    // Function to format diagnostic result
    function formatDiagnosticResult(result, testType) {
        if (!result) {
            return '<p class="error">No result data</p>';
        }
        
        var html = '';
        
        switch (testType) {
            case 'openai_connection':
                if (result.success) {
                    html += '<div class="success"><strong>✓ Connection successful!</strong></div>';
                    html += '<p><strong>Available Models:</strong> ' + result.models_count + '</p>';
                    
                    if (result.chat_models && result.chat_models.length > 0) {
                        html += '<p><strong>Chat Models:</strong></p>';
                        html += '<ul>';
                        result.chat_models.slice(0, 5).forEach(function(model) {
                            html += '<li>' + model + '</li>';
                        });
                        if (result.chat_models.length > 5) {
                            html += '<li>... and ' + (result.chat_models.length - 5) + ' more</li>';
                        }
                        html += '</ul>';
                    }
                } else {
                    html += '<div class="error"><strong>✗ Connection failed!</strong></div>';
                    html += '<p>' + result.message + '</p>';
                }
                break;
                
            case 'anthropic_connection':
                if (result.success) {
                    html += '<div class="success"><strong>✓ Connection successful!</strong></div>';
                    html += '<p><strong>Current Model:</strong> ' + result.current_model + '</p>';
                    html += '<p><strong>Response:</strong> ' + result.response_text + '</p>';
                    
                    if (result.available_models && result.available_models.length > 0) {
                        html += '<p><strong>Available Models:</strong></p>';
                        html += '<ul>';
                        result.available_models.forEach(function(model) {
                            html += '<li>' + model + '</li>';
                        });
                        html += '</ul>';
                    }
                } else {
                    html += '<div class="error"><strong>✗ Connection failed!</strong></div>';
                    html += '<p>' + result.message + '</p>';
                }
                break;
                
            case 'memberpress_connection':
                if (result.success) {
                    html += '<div class="success"><strong>✓ Connection successful!</strong></div>';
                    html += '<p><strong>Memberships:</strong> ' + result.membership_count + '</p>';
                    
                    if (result.memberships && result.memberships.length > 0) {
                        html += '<table>';
                        html += '<tr><th>Title</th><th>Price</th></tr>';
                        result.memberships.forEach(function(membership) {
                            html += '<tr><td>' + membership.title + '</td><td>' + membership.price + '</td></tr>';
                        });
                        html += '</table>';
                    }
                } else {
                    html += '<div class="error"><strong>✗ Connection failed!</strong></div>';
                    html += '<p>' + result.message + '</p>';
                    
                    if (result.plugin_exists === false) {
                        html += '<p class="error">MemberPress plugin is not active.</p>';
                    } else if (result.dev_tools_active === false) {
                        html += '<p class="error">MemberPress Developer Tools plugin is not active.</p>';
                    }
                }
                break;
                
            case 'wordpress_info':
                if (result.success) {
                    html += '<div class="success"><strong>✓ WordPress Information</strong></div>';
                    
                    // WordPress section
                    html += '<h4>WordPress</h4>';
                    html += '<table>';
                    for (var key in result.wordpress) {
                        html += '<tr><td><strong>' + formatKey(key) + '</strong></td><td>' + result.wordpress[key] + '</td></tr>';
                    }
                    html += '</table>';
                    
                    // PHP section
                    html += '<h4>PHP</h4>';
                    html += '<table>';
                    for (var key in result.php) {
                        html += '<tr><td><strong>' + formatKey(key) + '</strong></td><td>' + result.php[key] + '</td></tr>';
                    }
                    html += '</table>';
                    
                    // Database section
                    html += '<h4>Database</h4>';
                    html += '<table>';
                    for (var key in result.database) {
                        html += '<tr><td><strong>' + formatKey(key) + '</strong></td><td>' + result.database[key] + '</td></tr>';
                    }
                    html += '</table>';
                    
                    // Server section
                    html += '<h4>Server</h4>';
                    html += '<table>';
                    for (var key in result.server) {
                        html += '<tr><td><strong>' + formatKey(key) + '</strong></td><td>' + result.server[key] + '</td></tr>';
                    }
                    html += '</table>';
                } else {
                    html += '<div class="error"><strong>✗ Failed to get WordPress information</strong></div>';
                    html += '<p>' + result.message + '</p>';
                }
                break;
                
            case 'plugin_status':
                if (result.success) {
                    html += '<div class="success"><strong>✓ Plugin Status</strong></div>';
                    
                    // Plugin section
                    html += '<h4>Plugin</h4>';
                    html += '<table>';
                    for (var key in result.plugin) {
                        var value = result.plugin[key];
                        if (typeof value === 'boolean') {
                            value = value ? '<span class="success">Yes</span>' : '<span class="error">No</span>';
                        }
                        html += '<tr><td><strong>' + formatKey(key) + '</strong></td><td>' + value + '</td></tr>';
                    }
                    html += '</table>';
                    
                    // API Config section
                    html += '<h4>API Configuration</h4>';
                    html += '<table>';
                    for (var key in result.api_config) {
                        var value = result.api_config[key];
                        if (typeof value === 'boolean') {
                            value = value ? '<span class="success">Yes</span>' : '<span class="error">No</span>';
                        }
                        html += '<tr><td><strong>' + formatKey(key) + '</strong></td><td>' + value + '</td></tr>';
                    }
                    html += '</table>';
                    
                    // Tools section
                    html += '<h4>Tools</h4>';
                    html += '<table>';
                    for (var key in result.tools) {
                        var value = result.tools[key];
                        if (typeof value === 'boolean') {
                            value = value ? '<span class="success">Enabled</span>' : '<span class="error">Disabled</span>';
                        }
                        html += '<tr><td><strong>' + formatKey(key) + '</strong></td><td>' + value + '</td></tr>';
                    }
                    html += '</table>';
                    
                    // Database section
                    html += '<h4>Database</h4>';
                    html += '<table>';
                    for (var key in result.database) {
                        var value = result.database[key];
                        if (typeof value === 'boolean') {
                            value = value ? '<span class="success">Yes</span>' : '<span class="error">No</span>';
                        }
                        html += '<tr><td><strong>' + formatKey(key) + '</strong></td><td>' + value + '</td></tr>';
                    }
                    html += '</table>';
                } else {
                    html += '<div class="error"><strong>✗ Failed to get plugin status</strong></div>';
                    html += '<p>' + result.message + '</p>';
                }
                break;
                
            case 'all':
                html += '<h3>Complete Diagnostic Results</h3>';
                
                // OpenAI section
                html += '<div class="mpai-diagnostic-result-section">';
                html += '<h4>OpenAI API</h4>';
                html += formatDiagnosticResult(result.openai, 'openai_connection');
                html += '</div>';
                
                // Anthropic section
                html += '<div class="mpai-diagnostic-result-section">';
                html += '<h4>Anthropic API</h4>';
                html += formatDiagnosticResult(result.anthropic, 'anthropic_connection');
                html += '</div>';
                
                // MemberPress section
                html += '<div class="mpai-diagnostic-result-section">';
                html += '<h4>MemberPress API</h4>';
                html += formatDiagnosticResult(result.memberpress, 'memberpress_connection');
                html += '</div>';
                
                // WordPress section
                html += '<div class="mpai-diagnostic-result-section">';
                html += '<h4>WordPress Information</h4>';
                html += formatDiagnosticResult(result.wordpress, 'wordpress_info');
                html += '</div>';
                
                // Plugin Status section
                html += '<div class="mpai-diagnostic-result-section">';
                html += '<h4>Plugin Status</h4>';
                html += formatDiagnosticResult(result.plugin_status, 'plugin_status');
                html += '</div>';
                break;
                
            default:
                html += '<pre>' + JSON.stringify(result, null, 2) + '</pre>';
        }
        
        return html;
    }
    
    // Helper function to format key
    function formatKey(key) {
        return key.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
    }
    
    // Do not add the diagnostic tab here as it's already added in settings-page.php
    
    // Bind diagnostic buttons
    $('#run-openai-diagnostic').on('click', function() {
        runDiagnostic('openai_connection', '#openai-diagnostic-result', '#openai-status-indicator');
    });
    
    $('#run-anthropic-diagnostic').on('click', function() {
        runDiagnostic('anthropic_connection', '#anthropic-diagnostic-result', '#anthropic-status-indicator');
    });
    
    $('#run-memberpress-diagnostic').on('click', function() {
        runDiagnostic('memberpress_connection', '#memberpress-diagnostic-result', '#memberpress-status-indicator');
    });
    
    $('#run-wordpress-diagnostic').on('click', function() {
        runDiagnostic('wordpress_info', '#wordpress-diagnostic-result');
    });
    
    $('#run-plugin-diagnostic').on('click', function() {
        runDiagnostic('plugin_status', '#plugin-diagnostic-result');
    });
    
    $('#run-all-diagnostics').on('click', function() {
        runDiagnostic('all', '#all-diagnostic-result');
        
        // Also update individual status indicators
        $('#openai-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
            .addClass('mpai-status-unknown');
        $('#openai-status-indicator .mpai-status-text').text('Running...');
        
        $('#anthropic-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
            .addClass('mpai-status-unknown');
        $('#anthropic-status-indicator .mpai-status-text').text('Running...');
        
        $('#memberpress-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
            .addClass('mpai-status-unknown');
        $('#memberpress-status-indicator .mpai-status-text').text('Running...');
    });
    
    // Phase One Test Buttons
    function runPhaseOneTest(testType, resultContainer, statusIndicator) {
        console.log('MPAI: Running Phase One test:', testType);
        
        // Show loading state
        $(resultContainer).html('<p>Running test...</p>');
        $(resultContainer).show();
        
        // Update status indicator
        if (statusIndicator) {
            $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
                .addClass('mpai-status-unknown');
            $(statusIndicator + ' .mpai-status-text').text('Running...');
        }
        
        // Make API request to run diagnostic
        var formData = new FormData();
        formData.append('action', testType);
        
        // Use direct AJAX handler
        var directHandlerUrl = '<?php echo plugin_dir_url(dirname(__FILE__)) . 'includes/direct-ajax-handler.php'; ?>';
        
        console.log('MPAI: Running Phase One test via direct handler:', testType);
        fetch(directHandlerUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(function(response) {
            console.log('MPAI: Phase One test response:', response);
            
            if (response.success) {
                // Format and display the result
                var result = response.data;
                var resultHtml = formatPhaseOneResult(result, testType);
                
                $(resultContainer).html(resultHtml);
                
                // If this is the "all tests" run, update the indicators for all cards
                if (testType === 'test_all_phase_one' && result.results) {
                    // Update Agent Discovery card
                    if (result.results.agent_discovery && result.results.agent_discovery.success) {
                        // Update status indicators
                        $('#agent-discovery-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-error')
                            .addClass('mpai-status-success');
                        $('#agent-discovery-status-indicator .mpai-status-text').text('Passed');
                        $('#agent-discovery-status-indicator').closest('.mpai-diagnostic-card').addClass('mpai-test-passed');
                        
                        // Format and display individual test result if data is available
                        if (result.results.agent_discovery.data) {
                            var agentDiscoveryResultHtml = formatPhaseOneResult(result.results.agent_discovery.data, 'test_agent_discovery');
                            $('#agent-discovery-result').html(agentDiscoveryResultHtml).show();
                        }
                    } else {
                        $('#agent-discovery-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success')
                            .addClass('mpai-status-error');
                        $('#agent-discovery-status-indicator .mpai-status-text').text('Failed');
                    }
                    
                    // Update Lazy Loading card
                    if (result.results.lazy_loading && result.results.lazy_loading.success) {
                        // Update status indicators
                        $('#lazy-loading-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-error')
                            .addClass('mpai-status-success');
                        $('#lazy-loading-status-indicator .mpai-status-text').text('Passed');
                        $('#lazy-loading-status-indicator').closest('.mpai-diagnostic-card').addClass('mpai-test-passed');
                        
                        // Format and display individual test result if data is available
                        if (result.results.lazy_loading.data) {
                            var lazyLoadingResultHtml = formatPhaseOneResult(result.results.lazy_loading.data, 'test_lazy_loading');
                            $('#lazy-loading-result').html(lazyLoadingResultHtml).show();
                        }
                    } else {
                        $('#lazy-loading-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success')
                            .addClass('mpai-status-error');
                        $('#lazy-loading-status-indicator .mpai-status-text').text('Failed');
                    }
                    
                    // Update Response Cache card
                    if (result.results.response_cache && result.results.response_cache.success) {
                        $('#response-cache-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-error')
                            .addClass('mpai-status-success');
                        $('#response-cache-status-indicator .mpai-status-text').text('Passed');
                        $('#response-cache-status-indicator').closest('.mpai-diagnostic-card').addClass('mpai-test-passed');
                        
                        // Format and display individual test result if data is available
                        if (result.results.response_cache.data) {
                            var responseCacheResultHtml = formatPhaseOneResult(result.results.response_cache.data, 'test_response_cache');
                            $('#response-cache-result').html(responseCacheResultHtml).show();
                        }
                    } else {
                        $('#response-cache-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success')
                            .addClass('mpai-status-error');
                        $('#response-cache-status-indicator .mpai-status-text').text('Failed');
                    }
                    
                    // Update Agent Messaging card
                    if (result.results.agent_messaging && result.results.agent_messaging.success) {
                        $('#agent-messaging-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-error')
                            .addClass('mpai-status-success');
                        $('#agent-messaging-status-indicator .mpai-status-text').text('Passed');
                        $('#agent-messaging-status-indicator').closest('.mpai-diagnostic-card').addClass('mpai-test-passed');
                        
                        // Format and display individual test result if data is available
                        if (result.results.agent_messaging.data) {
                            var agentMessagingResultHtml = formatPhaseOneResult(result.results.agent_messaging.data, 'test_agent_messaging');
                            $('#agent-messaging-result').html(agentMessagingResultHtml).show();
                        }
                    } else {
                        $('#agent-messaging-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success')
                            .addClass('mpai-status-error');
                        $('#agent-messaging-status-indicator .mpai-status-text').text('Failed');
                    }
                }
                
                // Add a toggle button to show/hide details
                var $card = $(statusIndicator).closest('.mpai-diagnostic-card');
                if ($card.find('.mpai-toggle-details').length === 0) {
                    var $toggleBtn = $('<button>', {
                        'class': 'button button-small mpai-toggle-details',
                        'text': 'Show Results',
                        'style': 'margin-left: 10px;',
                        'click': function(e) {
                            e.preventDefault();
                            $(resultContainer).slideToggle(200);
                            
                            // Toggle button text
                            var $btn = $(this);
                            if ($btn.text() === 'Show Results') {
                                $btn.text('Hide Results');
                            } else {
                                $btn.text('Show Results');
                            }
                        }
                    });
                    
                    // Add the button after the test button
                    $card.find('.mpai-diagnostic-actions button:first').after($toggleBtn);
                }
                
                // Hide the results container initially (it will be shown via the toggle button)
                $(resultContainer).hide();
                
                // Update status indicator
                if (statusIndicator) {
                    if (result.success) {
                        $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-error')
                            .addClass('mpai-status-success');
                        $(statusIndicator + ' .mpai-status-text').text('Passed');
                        
                        // Highlight the card to make it more visible
                        $(statusIndicator).closest('.mpai-diagnostic-card').addClass('mpai-test-passed');
                    } else {
                        $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success')
                            .addClass('mpai-status-error');
                        $(statusIndicator + ' .mpai-status-text').text('Failed');
                    }
                }
            } else {
                // Show error
                $(resultContainer).html('<p class="error">Error: ' + response.message + '</p>');
                
                // Update status indicator
                if (statusIndicator) {
                    $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success')
                        .addClass('mpai-status-error');
                    $(statusIndicator + ' .mpai-status-text').text('Error');
                }
            }
        })
        .catch(function(error) {
            console.error('MPAI: Fetch error:', error);
            
            // Show error
            $(resultContainer).html('<p class="error">Error: ' + error.message + '</p>');
            
            // Update status indicator
            if (statusIndicator) {
                $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success')
                    .addClass('mpai-status-error');
                $(statusIndicator + ' .mpai-status-text').text('Error');
            }
        });
    }
    
    // Function to format Phase One test results
    function formatPhaseOneResult(result, testType) {
        if (!result) {
            return '<p class="error">No result data</p>';
        }
        
        var html = '';
        
        switch (testType) {
            case 'test_agent_discovery':
                if (result.success) {
                    html += '<div class="success"><strong>✓ Agent Discovery successful!</strong></div>';
                    html += '<p><strong>Agents found:</strong> ' + result.agents_count + '</p>';
                    
                    if (result.agents && result.agents.length > 0) {
                        html += '<table>';
                        html += '<tr><th>ID</th><th>Name</th><th>Description</th></tr>';
                        result.agents.forEach(function(agent) {
                            html += '<tr><td>' + agent.id + '</td><td>' + agent.name + '</td><td>' + agent.description + '</td></tr>';
                        });
                        html += '</table>';
                    }
                } else {
                    html += '<div class="error"><strong>✗ Agent Discovery failed!</strong></div>';
                    html += '<p>' + (result.message || 'Unknown error') + '</p>';
                }
                break;
                
            case 'test_lazy_loading':
                if (result.success) {
                    html += '<div class="success"><strong>✓ Tool Lazy Loading successful!</strong></div>';
                    html += '<p><strong>Tool Definition Registered:</strong> ' + (result.tool_definition_registered ? 'Yes' : 'No') + '</p>';
                    html += '<p><strong>Tool Loaded On Demand:</strong> ' + (result.tool_loaded_on_demand ? 'Yes' : 'No') + '</p>';
                    html += '<p><strong>Available Tools Count:</strong> ' + result.available_tools_count + '</p>';
                    
                    if (result.available_tools && result.available_tools.length > 0) {
                        html += '<p><strong>Available Tools:</strong></p>';
                        html += '<ul>';
                        result.available_tools.forEach(function(tool) {
                            html += '<li>' + tool + '</li>';
                        });
                        html += '</ul>';
                    }
                } else {
                    html += '<div class="error"><strong>✗ Tool Lazy Loading failed!</strong></div>';
                    html += '<p>' + (result.message || 'Unknown error') + '</p>';
                }
                break;
                
            case 'test_response_cache':
                if (result.success) {
                    html += '<div class="success"><strong>✓ Response Cache successful!</strong></div>';
                    html += '<p><strong>Set Operation:</strong> ' + (result.set_success ? 'Success' : 'Failed') + '</p>';
                    html += '<p><strong>Get Operation:</strong> ' + (result.get_success ? 'Success' : 'Failed') + '</p>';
                    html += '<p><strong>Delete Operation:</strong> ' + (result.delete_success ? 'Success' : 'Failed') + '</p>';
                    html += '<p><strong>Data Match:</strong> ' + (result.data_match ? 'Yes' : 'No') + '</p>';
                    html += '<p><strong>Test Key:</strong> ' + result.test_key + '</p>';
                } else {
                    html += '<div class="error"><strong>✗ Response Cache failed!</strong></div>';
                    html += '<p>' + (result.message || 'Unknown error') + '</p>';
                }
                break;
                
            case 'test_agent_messaging':
                if (result.success) {
                    html += '<div class="success"><strong>✓ Agent Messaging successful!</strong></div>';
                    html += '<p><strong>Message Created:</strong> ' + (result.message_created ? 'Yes' : 'No') + '</p>';
                    html += '<p><strong>Properties Match:</strong> ' + (result.properties_match ? 'Yes' : 'No') + '</p>';
                    html += '<p><strong>Serialization Works:</strong> ' + (result.serialization_works ? 'Yes' : 'No') + '</p>';
                    
                    if (result.original_message) {
                        html += '<p><strong>Original Message:</strong></p>';
                        html += '<ul>';
                        html += '<li><strong>Sender:</strong> ' + result.original_message.sender + '</li>';
                        html += '<li><strong>Receiver:</strong> ' + result.original_message.receiver + '</li>';
                        html += '<li><strong>Type:</strong> ' + (result.original_message.message_type || result.original_message.type || 'Unknown') + '</li>';
                        html += '<li><strong>Content:</strong> ' + result.original_message.content + '</li>';
                        html += '</ul>';
                    }
                } else {
                    html += '<div class="error"><strong>✗ Agent Messaging failed!</strong></div>';
                    html += '<p>' + (result.message || 'Unknown error') + '</p>';
                }
                break;
                
            case 'test_agent_scoring':
                // Handle the agent specialization scoring test
                if (result.formatted_html) {
                    // If the test provides its own formatted HTML, use it
                    html = result.formatted_html;
                } else if (result.success) {
                    html += '<div class="success"><strong>✓ Agent Specialization Scoring successful!</strong></div>';
                    
                    if (result.tests && result.tests.length > 0) {
                        html += '<p><strong>Test Results:</strong></p>';
                        html += '<table>';
                        html += '<tr><th>Message</th><th>Expected Agent</th><th>Selected Agent</th><th>Pass</th></tr>';
                        
                        result.tests.forEach(function(test) {
                            html += '<tr>';
                            html += '<td>' + test.message + '</td>';
                            html += '<td>' + test.expected_agent_type + '</td>';
                            html += '<td>' + test.actual_agent + '</td>';
                            html += '<td>' + (test.pass ? '✓' : '✗') + '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</table>';
                    }
                    
                    if (result.scores) {
                        html += '<p><strong>Agent Scoring Performance:</strong></p>';
                        html += '<table>';
                        html += '<tr><th>Agent</th><th>Average Score</th><th>Max Score</th></tr>';
                        
                        for (var agent_id in result.scores) {
                            var scores = result.scores[agent_id];
                            html += '<tr>';
                            html += '<td>' + agent_id + '</td>';
                            html += '<td>' + (scores.avg ? scores.avg.toFixed(1) : '0.0') + '</td>';
                            html += '<td>' + (scores.max ? scores.max.toFixed(1) : '0.0') + '</td>';
                            html += '</tr>';
                        }
                        
                        html += '</table>';
                    }
                } else {
                    html += '<div class="error"><strong>✗ Agent Specialization Scoring failed!</strong></div>';
                    html += '<p>' + (result.message || 'Unknown error') + '</p>';
                }
                break;
                
            case 'test_all_phase_one':
                html += '<h3>Phase One Test Results</h3>';
                var allPassed = result.overall_success;
                
                html += '<div class="' + (allPassed ? 'success' : 'error') + '">';
                html += '<strong>' + (allPassed ? '✓ All tests passed!' : '✗ Some tests failed!') + '</strong>';
                html += '</div>';
                
                // Agent Discovery section
                html += '<div class="mpai-diagnostic-result-section">';
                html += '<h4>Agent Discovery</h4>';
                if (result.results.agent_discovery && result.results.agent_discovery.success) {
                    if (result.results.agent_discovery.data) {
                        html += formatPhaseOneResult(result.results.agent_discovery.data, 'test_agent_discovery');
                    } else {
                        html += '<div class="success"><strong>✓ Agent Discovery test passed</strong></div>';
                    }
                } else {
                    html += '<div class="error"><strong>✗ Test failed to execute</strong></div>';
                    if (result.results.agent_discovery && result.results.agent_discovery.message) {
                        html += '<p>' + result.results.agent_discovery.message + '</p>';
                    }
                }
                html += '</div>';
                
                // Lazy Loading section
                html += '<div class="mpai-diagnostic-result-section">';
                html += '<h4>Tool Lazy Loading</h4>';
                if (result.results.lazy_loading && result.results.lazy_loading.success) {
                    if (result.results.lazy_loading.data) {
                        html += formatPhaseOneResult(result.results.lazy_loading.data, 'test_lazy_loading');
                    } else {
                        html += '<div class="success"><strong>✓ Tool Lazy Loading test passed</strong></div>';
                    }
                } else {
                    html += '<div class="error"><strong>✗ Test failed to execute</strong></div>';
                    if (result.results.lazy_loading && result.results.lazy_loading.message) {
                        html += '<p>' + result.results.lazy_loading.message + '</p>';
                    }
                }
                html += '</div>';
                
                // Response Cache section
                html += '<div class="mpai-diagnostic-result-section">';
                html += '<h4>Response Cache</h4>';
                if (result.results.response_cache && result.results.response_cache.success) {
                    if (result.results.response_cache.data) {
                        html += formatPhaseOneResult(result.results.response_cache.data, 'test_response_cache');
                    } else {
                        html += '<div class="success"><strong>✓ Response Cache test passed</strong></div>';
                    }
                } else {
                    html += '<div class="error"><strong>✗ Test failed to execute</strong></div>';
                    if (result.results.response_cache && result.results.response_cache.message) {
                        html += '<p>' + result.results.response_cache.message + '</p>';
                    }
                }
                html += '</div>';
                
                // Agent Messaging section
                html += '<div class="mpai-diagnostic-result-section">';
                html += '<h4>Agent Messaging</h4>';
                if (result.results.agent_messaging && result.results.agent_messaging.success) {
                    if (result.results.agent_messaging.data) {
                        html += formatPhaseOneResult(result.results.agent_messaging.data, 'test_agent_messaging');
                    } else {
                        html += '<div class="success"><strong>✓ Agent Messaging test passed</strong></div>';
                    }
                } else {
                    html += '<div class="error"><strong>✗ Test failed to execute</strong></div>';
                    if (result.results.agent_messaging && result.results.agent_messaging.message) {
                        html += '<p>' + result.results.agent_messaging.message + '</p>';
                    }
                }
                html += '</div>';
                break;
                
            default:
                html += '<pre>' + JSON.stringify(result, null, 2) + '</pre>';
        }
        
        return html;
    }
    
    // Bind Phase One test buttons
    $('#run-agent-discovery-test').on('click', function() {
        runPhaseOneTest('test_agent_discovery', '#agent-discovery-result', '#agent-discovery-status-indicator');
    });
    
    $('#run-lazy-loading-test').on('click', function() {
        runPhaseOneTest('test_lazy_loading', '#lazy-loading-result', '#lazy-loading-status-indicator');
    });
    
    $('#run-response-cache-test').on('click', function() {
        runPhaseOneTest('test_response_cache', '#response-cache-result', '#response-cache-status-indicator');
    });
    
    $('#run-agent-messaging-test').on('click', function() {
        runPhaseOneTest('test_agent_messaging', '#agent-messaging-result', '#agent-messaging-status-indicator');
    });
    
    $('#run-all-phase-one-tests').on('click', function() {
        runPhaseOneTest('test_all_phase_one', '#all-phase-one-result', null);
        
        // Also update individual status indicators
        $('#agent-discovery-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
            .addClass('mpai-status-unknown');
        $('#agent-discovery-status-indicator .mpai-status-text').text('Running...');
        
        $('#lazy-loading-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
            .addClass('mpai-status-unknown');
        $('#lazy-loading-status-indicator .mpai-status-text').text('Running...');
        
        $('#response-cache-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
            .addClass('mpai-status-unknown');
        $('#response-cache-status-indicator .mpai-status-text').text('Running...');
        
        $('#agent-messaging-status-indicator .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
            .addClass('mpai-status-unknown');
        $('#agent-messaging-status-indicator .mpai-status-text').text('Running...');
        
        // Create a result container if it doesn't exist
        if ($('#all-phase-one-result').length === 0) {
            $('.mpai-phase-one-actions').after('<div class="mpai-diagnostic-result" id="all-phase-one-result" style="display: none;"></div>');
        }
    });
    
    // Bind Phase Two test buttons
    $('#run-agent-scoring-test').on('click', function() {
        runPhaseOneTest('test_agent_scoring', '#agent-scoring-result', '#agent-scoring-status-indicator');
    });
    
    // Plugin Logs Functionality
    let pluginLogsPage = 1;
    let pluginLogsTotalPages = 1;
    let pluginLogsPerPage = 10;
    
    // Function to load plugin logs
    function loadPluginLogs() {
        $('#mpai-plugin-logs-table-body').html('<tr><td colspan="6" class="mpai-plugin-logs-loading"><?php _e('Loading plugin logs...', 'memberpress-ai-assistant'); ?></td></tr>');
        
        const action = $('#mpai-plugin-logs-action-filter').val();
        const pluginName = $('#mpai-plugin-logs-plugin-filter').val();
        const days = $('#mpai-plugin-logs-date-filter').val();
        
        // Prepare data for AJAX request
        const data = {
            action: 'mpai_get_plugin_logs',
            nonce: mpai_data.nonce,
            log_action: action,
            plugin_name: pluginName,
            days: days,
            page: pluginLogsPage,
            per_page: pluginLogsPerPage
        };
        
        // Make the AJAX request
        $.ajax({
            url: mpai_data.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Update summary counts
                    updateSummaryCounts(response.data.summary);
                    
                    // Update pagination
                    pluginLogsTotalPages = Math.ceil(response.data.total / pluginLogsPerPage);
                    updatePagination();
                    
                    // Update table with logs
                    if (response.data.logs.length > 0) {
                        let html = '';
                        
                        $.each(response.data.logs, function(index, log) {
                            html += buildLogRow(log);
                        });
                        
                        $('#mpai-plugin-logs-table-body').html(html);
                        
                        // Initialize details buttons
                        initDetailsButtons();
                    } else {
                        $('#mpai-plugin-logs-table-body').html('<tr><td colspan="6" class="mpai-plugin-logs-empty"><?php _e('No plugin logs found matching your criteria.', 'memberpress-ai-assistant'); ?></td></tr>');
                    }
                } else {
                    // Show error message
                    $('#mpai-plugin-logs-table-body').html('<tr><td colspan="6" class="mpai-plugin-logs-empty">Error: ' + response.data + '</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error('MPAI: Error fetching plugin logs:', error);
                $('#mpai-plugin-logs-table-body').html('<tr><td colspan="6" class="mpai-plugin-logs-empty">Error: Failed to fetch plugin logs. Please try again.</td></tr>');
            }
        });
    }
    
    // Function to update summary counts
    function updateSummaryCounts(summary) {
        $('#mpai-recent-activity-count').text(summary.total || 0);
        $('#mpai-installations-count').text(summary.installed || 0);
        $('#mpai-updates-count').text(summary.updated || 0);
        $('#mpai-activations-count').text(summary.activated || 0);
        $('#mpai-deactivations-count').text(summary.deactivated || 0);
    }
    
    // Function to update pagination
    function updatePagination() {
        $('#mpai-plugin-logs-page-info').text('<?php _e('Page', 'memberpress-ai-assistant'); ?> ' + pluginLogsPage + ' <?php _e('of', 'memberpress-ai-assistant'); ?> ' + pluginLogsTotalPages);
        
        if (pluginLogsPage <= 1) {
            $('#mpai-plugin-logs-prev-page').prop('disabled', true);
        } else {
            $('#mpai-plugin-logs-prev-page').prop('disabled', false);
        }
        
        if (pluginLogsPage >= pluginLogsTotalPages) {
            $('#mpai-plugin-logs-next-page').prop('disabled', true);
        } else {
            $('#mpai-plugin-logs-next-page').prop('disabled', false);
        }
    }
    
    // Function to build a log table row
    function buildLogRow(log) {
        const date = new Date(log.date_time);
        const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        
        let actionClass = 'mpai-action-' + log.action;
        let actionText = log.action.charAt(0).toUpperCase() + log.action.slice(1);
        
        let versionText = log.plugin_version;
        if (log.plugin_prev_version && log.action === 'updated') {
            versionText += ' <small>(from ' + log.plugin_prev_version + ')</small>';
        }
        
        let row = '<tr data-log-id="' + log.id + '">';
        row += '<td>' + formattedDate + '</td>';
        row += '<td><span class="mpai-action-badge ' + actionClass + '">' + actionText + '</span></td>';
        row += '<td>' + log.plugin_name + '</td>';
        row += '<td>' + versionText + '</td>';
        row += '<td>' + (log.user_info ? log.user_info.display_name : log.user_login) + '</td>';
        row += '<td><button type="button" class="mpai-details-button" data-log-id="' + log.id + '"><?php _e('View Details', 'memberpress-ai-assistant'); ?></button></td>';
        row += '</tr>';
        
        return row;
    }
    
    // Function to initialize details buttons
    function initDetailsButtons() {
        $('.mpai-details-button').on('click', function() {
            const logId = $(this).data('log-id');
            
            // Find the log data from the table row
            const row = $('tr[data-log-id="' + logId + '"]');
            if (row.length === 0) return;
            
            // Prepare data for AJAX request
            const data = {
                action: 'mpai_get_plugin_log_details',
                nonce: mpai_data.nonce,
                log_id: logId
            };
            
            // Make the AJAX request
            $.ajax({
                url: mpai_data.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        showDetailsPopup(response.data);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('MPAI: Error fetching log details:', error);
                    alert('Error: Failed to fetch log details. Please try again.');
                }
            });
        });
    }
    
    // Function to show details popup
    function showDetailsPopup(log) {
        const date = new Date(log.date_time);
        const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        
        let actionClass = 'mpai-action-' + log.action;
        let actionText = log.action.charAt(0).toUpperCase() + log.action.slice(1);
        
        // Create popup content
        let popupContent = '<div class="mpai-details-popup">';
        popupContent += '<div class="mpai-details-popup-content">';
        popupContent += '<div class="mpai-details-popup-header">';
        popupContent += '<h3>' + log.plugin_name + ' <span class="mpai-action-badge ' + actionClass + '">' + actionText + '</span></h3>';
        popupContent += '<button type="button" class="mpai-details-popup-close">&times;</button>';
        popupContent += '</div>';
        popupContent += '<div class="mpai-details-popup-body">';
        
        // Basic info table
        popupContent += '<table class="mpai-details-table">';
        popupContent += '<tr><th><?php _e('Date & Time', 'memberpress-ai-assistant'); ?></th><td>' + formattedDate + '</td></tr>';
        popupContent += '<tr><th><?php _e('Plugin', 'memberpress-ai-assistant'); ?></th><td>' + log.plugin_name + '</td></tr>';
        popupContent += '<tr><th><?php _e('Slug', 'memberpress-ai-assistant'); ?></th><td>' + log.plugin_slug + '</td></tr>';
        popupContent += '<tr><th><?php _e('Version', 'memberpress-ai-assistant'); ?></th><td>' + log.plugin_version + '</td></tr>';
        
        if (log.plugin_prev_version && log.action === 'updated') {
            popupContent += '<tr><th><?php _e('Previous Version', 'memberpress-ai-assistant'); ?></th><td>' + log.plugin_prev_version + '</td></tr>';
        }
        
        popupContent += '<tr><th><?php _e('User', 'memberpress-ai-assistant'); ?></th><td>' + (log.user_info ? log.user_info.display_name + ' (' + log.user_info.user_login + ')' : log.user_login) + '</td></tr>';
        popupContent += '</table>';
        
        // Additional data if available
        if (log.additional_data) {
            popupContent += '<h4><?php _e('Additional Information', 'memberpress-ai-assistant'); ?></h4>';
            popupContent += '<table class="mpai-details-table">';
            
            for (const [key, value] of Object.entries(log.additional_data)) {
                if (value) {
                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    popupContent += '<tr><th>' + label + '</th><td>' + value + '</td></tr>';
                }
            }
            
            popupContent += '</table>';
        }
        
        popupContent += '</div>'; // End popup body
        popupContent += '</div>'; // End popup content
        popupContent += '</div>'; // End popup
        
        // Add popup to the page
        $('body').append(popupContent);
        
        // Handle close button
        $('.mpai-details-popup-close').on('click', function() {
            $('.mpai-details-popup').remove();
        });
        
        // Close when clicking outside
        $('.mpai-details-popup').on('click', function(e) {
            if ($(e.target).hasClass('mpai-details-popup')) {
                $('.mpai-details-popup').remove();
            }
        });
    }
    
    // Function to export logs to CSV
    function exportLogsToCSV() {
        const action = $('#mpai-plugin-logs-action-filter').val();
        const pluginName = $('#mpai-plugin-logs-plugin-filter').val();
        const days = $('#mpai-plugin-logs-date-filter').val();
        
        // Prepare data for AJAX request
        const data = {
            action: 'mpai_export_plugin_logs',
            nonce: mpai_data.nonce,
            log_action: action,
            plugin_name: pluginName,
            days: days
        };
        
        // Make the AJAX request
        $.ajax({
            url: mpai_data.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Create file download
                    const blob = new Blob([response.data], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = 'plugin-logs-export.csv';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('MPAI: Error exporting plugin logs:', error);
                alert('Error: Failed to export plugin logs. Please try again.');
            }
        });
    }
    
    // Event Listeners for Plugin Logs
    $('#mpai-plugin-logs-refresh').on('click', function() {
        pluginLogsPage = 1;
        loadPluginLogs();
    });
    
    $('#mpai-plugin-logs-prev-page').on('click', function() {
        if (pluginLogsPage > 1) {
            pluginLogsPage--;
            loadPluginLogs();
        }
    });
    
    $('#mpai-plugin-logs-next-page').on('click', function() {
        if (pluginLogsPage < pluginLogsTotalPages) {
            pluginLogsPage++;
            loadPluginLogs();
        }
    });
    
    $('#mpai-plugin-logs-export').on('click', function() {
        exportLogsToCSV();
    });
    
    $('#mpai-enable-plugin-logging').on('change', function() {
        const enabled = $(this).is(':checked');
        
        // Save the setting
        const data = {
            action: 'mpai_update_plugin_logging_setting',
            nonce: mpai_data.nonce,
            enabled: enabled ? 1 : 0
        };
        
        $.ajax({
            url: mpai_data.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (!response.success) {
                    alert('Error: ' + response.data);
                    // Revert the checkbox state if failed
                    $('#mpai-enable-plugin-logging').prop('checked', !enabled);
                }
            },
            error: function(xhr, status, error) {
                console.error('MPAI: Error updating plugin logging setting:', error);
                alert('Error: Failed to update setting. Please try again.');
                // Revert the checkbox state
                $('#mpai-enable-plugin-logging').prop('checked', !enabled);
            }
        });
    });
    
    // Listen for the custom event from the admin.js tab handling
    $(document).on('mpai-load-plugin-logs', function() {
        console.log('MPAI: Plugin logs load event triggered');
        loadPluginLogs();
    });
    
    // Also listen for the more general tab change event
    $(document).on('mpai-tab-shown', function(e, tabId) {
        if (tabId === 'tab-diagnostic') {
            console.log('MPAI: Diagnostic tab shown event detected');
            loadPluginLogs();
        }
    });
    
    // Initial load regardless of visibility
    // This ensures logs are loaded when the page is loaded
    // even if the tab isn't visible right away
    setTimeout(function() {
        console.log('MPAI: Initial plugin logs load (delayed)');
        loadPluginLogs();
    }, 500);
    
    // If the tab is already visible, load immediately
    if ($('#tab-diagnostic').is(':visible')) {
        console.log('MPAI: Initial plugin logs load (immediate - tab visible)');
        loadPluginLogs();
    }
});
</script>