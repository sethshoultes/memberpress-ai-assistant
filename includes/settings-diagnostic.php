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

    <div class="mpai-diagnostic-section">
        <h4><?php _e('Console Logging', 'memberpress-ai-assistant'); ?></h4>
        <p><?php _e('Configure the browser console logging system to help with debugging and monitoring.', 'memberpress-ai-assistant'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="mpai_enable_console_logging"><?php _e('Enable Console Logging', 'memberpress-ai-assistant'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="mpai_enable_console_logging" id="mpai_enable_console_logging" value="1" <?php checked(get_option('mpai_enable_console_logging', false)); ?> />
                        <?php _e('Log detailed information to the browser console', 'memberpress-ai-assistant'); ?>
                    </label>
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
                    <script>
                    jQuery(document).ready(function($) {
                        $('#mpai-test-console-logging').on('click', function() {
                            var $resultContainer = $('#mpai-console-test-result');
                            
                            // Show loading state
                            $resultContainer.html('<?php _e('Testing...', 'memberpress-ai-assistant'); ?>');
                            $resultContainer.show();
                            
                            // Debug log for the logger existence
                            console.log('MPAI: Test button clicked, checking for logger object');
                            console.log('MPAI: window.mpaiLogger exists:', typeof window.mpaiLogger !== 'undefined');
                            
                            // Check if window.mpaiLogger exists
                            if (typeof window.mpaiLogger === 'undefined') {
                                console.error('MPAI: Logger not initialized, creating a temporary logger for testing');
                                
                                // Create a temporary logger for testing
                                window.mpaiLogger = {
                                    enabled: true,
                                    logLevel: 'debug',
                                    categories: {
                                        api_calls: true,
                                        tool_usage: true,
                                        agent_activity: true,
                                        timing: true,
                                        ui: true
                                    },
                                    testLog: function() {
                                        console.group('MPAI Logger Test');
                                        console.log('Logger Status: TEMPORARY LOGGER CREATED FOR TESTING');
                                        console.log('Log Level: debug');
                                        console.log('Categories: all enabled');
                                        console.error('This is an ERROR test message from temporary logger');
                                        console.warn('This is a WARNING test message from temporary logger');
                                        console.info('This is an INFO test message from temporary logger');
                                        console.log('This is a DEBUG test message from temporary logger');
                                        console.groupEnd();
                                        
                                        return {
                                            enabled: true,
                                            logLevel: 'debug',
                                            categories: {
                                                api_calls: true,
                                                tool_usage: true,
                                                agent_activity: true,
                                                timing: true,
                                                ui: true
                                            }
                                        };
                                    }
                                };
                                
                                $resultContainer.html('<?php _e('Created a temporary logger for testing - check console. The normal logger is not initializing properly.', 'memberpress-ai-assistant'); ?>');
                            }
                            
                            // Always enable logger for testing
                            window.mpaiLogger.enabled = true;
                            console.log('MPAI: Temporarily enabling logger for test');
                            
                            // Run logger test with try/catch to handle any errors
                            var testResult;
                            try {
                                console.log('MPAI: Attempting to run testLog() function');
                                testResult = window.mpaiLogger.testLog();
                                console.log('MPAI: testLog() function completed successfully');
                            } catch (e) {
                                console.error('MPAI: Error running testLog() function:', e);
                                $resultContainer.html('<?php _e('Error running logger test. See console for details.', 'memberpress-ai-assistant'); ?>');
                                return;
                            }
                            
                            // Show test results
                            var resultHtml = '<span style="color: ' + (testResult.enabled ? 'green' : 'red') + '; font-weight: bold;">';
                            resultHtml += testResult.enabled ? '✓ Logger enabled' : '✗ Logger disabled';
                            resultHtml += '</span><br>';
                            resultHtml += '<br><strong>Settings:</strong><br>';
                            resultHtml += 'Log Level: ' + testResult.logLevel + '<br>';
                            
                            // Show enabled categories
                            resultHtml += 'Categories: ';
                            var enabledCategories = [];
                            for (var cat in testResult.categories) {
                                if (testResult.categories[cat]) {
                                    enabledCategories.push(cat);
                                }
                            }
                            
                            if (enabledCategories.length > 0) {
                                resultHtml += enabledCategories.join(', ');
                            } else {
                                resultHtml += 'None enabled';
                            }
                            
                            resultHtml += '<br><br>';
                            resultHtml += '<?php _e('Check your browser\'s console (F12) for test log messages.', 'memberpress-ai-assistant'); ?>';
                            
                            $resultContainer.html(resultHtml);
                            
                            // Apply the settings to the current logger
                            if (window.mpaiLogger) {
                                // Update logger settings
                                window.mpaiLogger.enabled = $('#mpai_enable_console_logging').is(':checked');
                                window.mpaiLogger.logLevel = $('#mpai_console_log_level').val();
                                window.mpaiLogger.categories = {
                                    api_calls: $('#mpai_log_api_calls').is(':checked'),
                                    tool_usage: $('#mpai_log_tool_usage').is(':checked'), 
                                    agent_activity: $('#mpai_log_agent_activity').is(':checked'),
                                    timing: $('#mpai_log_timing').is(':checked'),
                                    ui: true // Always enable UI logging for tests
                                };
                                
                                // Log the updated settings
                                console.log('MPAI: Updated logger settings:', window.mpaiLogger.enabled, window.mpaiLogger.logLevel, window.mpaiLogger.categories);
                            }
                            
                            // Save settings to localStorage for persistence
                            try {
                                localStorage.setItem('mpai_logger_settings', JSON.stringify({
                                    enabled: $('#mpai_enable_console_logging').is(':checked'),
                                    logLevel: $('#mpai_console_log_level').val(),
                                    categories: {
                                        api_calls: $('#mpai_log_api_calls').is(':checked'),
                                        tool_usage: $('#mpai_log_tool_usage').is(':checked'), 
                                        agent_activity: $('#mpai_log_agent_activity').is(':checked'),
                                        timing: $('#mpai_log_timing').is(':checked'),
                                        ui: true // Always enable UI logging
                                    }
                                }));
                                console.log('MPAI: Saved logger settings to localStorage');
                                
                                // Also save the settings in WordPress options
                                // We need to trigger the form submission to save settings on the server
                                $('#mpai-settings-form input[name="mpai_enable_console_logging"]').prop('checked', $('#mpai_enable_console_logging').is(':checked'));
                                $('#mpai-settings-form select[name="mpai_console_log_level"]').val($('#mpai_console_log_level').val());
                                $('#mpai-settings-form input[name="mpai_log_api_calls"]').prop('checked', $('#mpai_log_api_calls').is(':checked'));
                                $('#mpai-settings-form input[name="mpai_log_tool_usage"]').prop('checked', $('#mpai_log_tool_usage').is(':checked'));
                                $('#mpai-settings-form input[name="mpai_log_agent_activity"]').prop('checked', $('#mpai_log_agent_activity').is(':checked'));
                                $('#mpai-settings-form input[name="mpai_log_timing"]').prop('checked', $('#mpai_log_timing').is(':checked'));
                                $('#mpai-settings-form input[name="mpai_log_ui"]').prop('checked', true);
                            } catch(e) {
                                console.error('MPAI: Could not save logger settings to localStorage:', e);
                            }
                        });
                    });
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