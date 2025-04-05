<?php
/**
 * Settings Diagnostic Tab
 *
 * Displays the diagnostic tab for MemberPress AI Assistant settings
 * 
 * !!! THIS FILE IS DEPRECATED !!!
 * This file is now deprecated and should not be included directly.
 * The diagnostic tab is now handled by class-mpai-diagnostics.php
 * and class-mpai-diagnostics-page.php
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// PREVENT DOUBLE INCLUSION: Check if this file is being included directly
$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
if (isset($backtrace[1]['file']) && 
    (basename($backtrace[1]['file']) === 'settings-page.php' ||
     strpos($backtrace[1]['file'], 'settings-page.php') !== false)) {
    error_log('MPAI ERROR: Attempted to include deprecated settings-diagnostic.php from settings-page.php');
    return; // Exit without rendering to prevent duplication
}

// Log that this deprecated file is being loaded
error_log('MPAI WARNING: Deprecated settings-diagnostic.php is being included from ' . 
    (isset($backtrace[1]['file']) ? basename($backtrace[1]['file']) : 'unknown file'));
?>

<?php
// Direct menu fix for diagnostic tab
global $parent_file, $submenu_file;
$parent_file = class_exists('MeprAppCtrl') ? 'memberpress' : 'memberpress-ai-assistant';
$submenu_file = 'memberpress-ai-assistant-settings';
?>

<div id="tab-diagnostic" class="mpai-settings-tab" style="display: none;">
    <h3><?php _e('System Diagnostics', 'memberpress-ai-assistant'); ?></h3>
    <p><?php _e('Run various diagnostic tests to check the health of your MemberPress AI Assistant installation.', 'memberpress-ai-assistant'); ?></p>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Fix menu highlighting function
        function fixMenu() {
            // Force menu to be visible and highlighted
            $('#toplevel_page_memberpress')
                .addClass('wp-has-current-submenu wp-menu-open')
                .removeClass('wp-not-current-submenu')
                .css('display', 'block');
            
            $('#toplevel_page_memberpress > a')
                .addClass('wp-has-current-submenu wp-menu-open')
                .removeClass('wp-not-current-submenu');
            
            // Force submenu to be visible
            $('#toplevel_page_memberpress .wp-submenu').css('display', 'block');
            
            // Highlight our submenu item
            $('#toplevel_page_memberpress .wp-submenu li a[href*="memberpress-ai-assistant-settings"]')
                .parent().addClass('current');
        }
        
        // Run immediately
        fixMenu();
        
        // Run again after a short delay
        setTimeout(fixMenu, 100);
    });
    </script>
    
    <div class="mpai-diagnostic-section">
        <h4><?php _e('System Information', 'memberpress-ai-assistant'); ?></h4>
        <p><?php _e('Information about your WordPress and server environment.', 'memberpress-ai-assistant'); ?></p>
        
        <div class="mpai-diagnostic-card">
            <div class="mpai-diagnostic-header">
                <h4><?php _e('System Cache Test', 'memberpress-ai-assistant'); ?></h4>
                <div class="mpai-status-indicator" id="system-cache-status-indicator">
                    <span class="mpai-status-dot mpai-status-unknown"></span>
                    <span class="mpai-status-text"><?php _e('Not Tested', 'memberpress-ai-assistant'); ?></span>
                </div>
            </div>
            <p><?php _e('Tests the system information cache functionality.', 'memberpress-ai-assistant'); ?></p>
            <div class="mpai-diagnostic-actions">
                <button type="button" id="run-system-cache-test" class="button"><?php _e('Run Test', 'memberpress-ai-assistant'); ?></button>
            </div>
            <div class="mpai-diagnostic-result" id="system-cache-result" style="display: none;"></div>
        </div>
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
                            <input type="checkbox" name="mpai_log_api_calls" id="mpai_log_api_calls" value="1" <?php checked(get_option('mpai_log_api_calls', true)); ?> />
                            <?php _e('API Calls (Anthropic & OpenAI)', 'memberpress-ai-assistant'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="mpai_log_tool_usage" id="mpai_log_tool_usage" value="1" <?php checked(get_option('mpai_log_tool_usage', true)); ?> />
                            <?php _e('Tool Usage', 'memberpress-ai-assistant'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="mpai_log_agent_activity" id="mpai_log_agent_activity" value="1" <?php checked(get_option('mpai_log_agent_activity', true)); ?> />
                            <?php _e('Agent Activity', 'memberpress-ai-assistant'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="mpai_log_timing" id="mpai_log_timing" value="1" <?php checked(get_option('mpai_log_timing', true)); ?> />
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
                </td>
            </tr>
        </table>
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
    
    <!-- System Cache Test Script -->
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // System Cache Test
        $('#run-system-cache-test').on('click', function() {
            var $resultContainer = $('#system-cache-result');
            var $statusIndicator = $('#system-cache-status-indicator');
            
            // Show loading state
            $resultContainer.html('<p>Running test...</p>');
            $resultContainer.show();
            
            // Update status indicator
            $statusIndicator.find('.mpai-status-dot')
                .removeClass('mpai-status-unknown mpai-status-success mpai-status-error')
                .addClass('mpai-status-unknown');
            $statusIndicator.find('.mpai-status-text').text('Running...');
            
            // Create form data for request
            var formData = new FormData();
            formData.append('action', 'test_system_cache');
            
            // Use direct AJAX handler
            var directHandlerUrl = '<?php echo plugin_dir_url(dirname(__FILE__)) . "includes/direct-ajax-handler.php"; ?>';
            
            // Make the request
            fetch(directHandlerUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    // Update status indicator for success
                    $statusIndicator.find('.mpai-status-dot')
                        .removeClass('mpai-status-unknown mpai-status-error')
                        .addClass('mpai-status-success');
                    $statusIndicator.find('.mpai-status-text').text('Success');
                    
                    // Format and display the result
                    var formattedResult = formatSystemCacheResult(data.data);
                    $resultContainer.html(formattedResult);
                } else {
                    // Update status indicator for failure
                    $statusIndicator.find('.mpai-status-dot')
                        .removeClass('mpai-status-unknown mpai-status-success')
                        .addClass('mpai-status-error');
                    $statusIndicator.find('.mpai-status-text').text('Failed');
                    
                    // Display error message
                    var errorMessage = data.message || 'Unknown error occurred';
                    var formattedError = '<div class="mpai-system-test-result mpai-test-error">';
                    formattedError += '<h4>Test Failed</h4>';
                    formattedError += '<p>' + errorMessage + '</p>';
                    formattedError += '</div>';
                    
                    $resultContainer.html(formattedError);
                }
            })
            .catch(function(error) {
                // Update status indicator for error
                $statusIndicator.find('.mpai-status-dot')
                    .removeClass('mpai-status-unknown mpai-status-success')
                    .addClass('mpai-status-error');
                $statusIndicator.find('.mpai-status-text').text('Error');
                
                // Display error message
                var formattedError = '<div class="mpai-system-test-result mpai-test-error">';
                formattedError += '<h4>Test Error</h4>';
                formattedError += '<p>Error executing test: ' + error.message + '</p>';
                formattedError += '</div>';
                
                $resultContainer.html(formattedError);
            });
        });
        
        // Console Logging Test
        $('#mpai-test-console-logging').on('click', function() {
            var $resultSpan = $('#mpai-console-test-result');
            $resultSpan.text('Testing console logging...');
            $resultSpan.show();
            
            // Check if mpaiLogger exists
            if (window.mpaiLogger) {
                try {
                    // Test each logging level
                    window.mpaiLogger.info('🚀 Console Logging Test: Info message', 'ui');
                    window.mpaiLogger.warn('🚀 Console Logging Test: Warning message', 'ui');
                    window.mpaiLogger.error('🚀 Console Logging Test: Error message', 'ui');
                    window.mpaiLogger.debug('🚀 Console Logging Test: Debug message', 'ui');
                    
                    $resultSpan.html('<span style="color: green;">✓ Test messages sent to console</span>');
                    
                    setTimeout(function() {
                        $resultSpan.fadeOut();
                    }, 3000);
                } catch (e) {
                    $resultSpan.html('<span style="color: red;">✗ Error: ' + e.message + '</span>');
                }
            } else {
                $resultSpan.html('<span style="color: red;">✗ mpaiLogger not found. Logger may not be initialized.</span>');
            }
        });
        
        // Format system cache test results
        function formatSystemCacheResult(data) {
            var output = '<div class="mpai-system-test-result mpai-test-success">';
            output += '<h4>System Information Cache Test Results</h4>';
            
            if (data.success) {
                output += '<p class="mpai-test-success-message">' + data.message + '</p>';
                
                // Add test details
                output += '<h5>Test Details:</h5>';
                output += '<table class="mpai-test-results-table">';
                output += '<tr><th>Test</th><th>Result</th><th>Timing</th></tr>';
                
                data.tests.forEach(function(test) {
                    var resultClass = test.success ? 'mpai-test-success' : 'mpai-test-error';
                    var resultText = test.success ? 'PASSED' : 'FAILED';
                    
                    output += '<tr>';
                    output += '<td>' + test.name + '</td>';
                    output += '<td class="' + resultClass + '">' + resultText + '</td>';
                    
                    // Format timing information
                    var timing = '';
                    if (typeof test.timing === 'object') {
                        timing = 'First Request: ' + test.timing.first_request + '<br>';
                        timing += 'Second Request: ' + test.timing.second_request + '<br>';
                        timing += 'Improvement: ' + test.timing.improvement;
                    } else {
                        timing = test.timing;
                    }
                    
                    output += '<td>' + timing + '</td>';
                    output += '</tr>';
                });
                
                output += '</table>';
                
                // Add cache hits
                output += '<p>Cache Hits: ' + data.cache_hits + '</p>';
            } else {
                output += '<p class="mpai-test-error-message">' + data.message + '</p>';
            }
            
            output += '</div>';
            return output;
        }
        
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
                        updateSummaryCounts(response.data.summary);
                        updatePagination(response.data.total);
                        
                        if (response.data.logs.length > 0) {
                            let html = '';
                            $.each(response.data.logs, function(index, log) {
                                html += buildLogRow(log);
                            });
                            $('#mpai-plugin-logs-table-body').html(html);
                            initDetailsButtons();
                        } else {
                            $('#mpai-plugin-logs-table-body').html('<tr><td colspan="6" class="mpai-plugin-logs-empty"><?php _e('No logs found matching your criteria.', 'memberpress-ai-assistant'); ?></td></tr>');
                        }
                    } else {
                        $('#mpai-plugin-logs-table-body').html('<tr><td colspan="6" class="mpai-plugin-logs-empty"><?php _e('Error loading logs:', 'memberpress-ai-assistant'); ?> ' + response.data + '</td></tr>');
                    }
                },
                error: function() {
                    $('#mpai-plugin-logs-table-body').html('<tr><td colspan="6" class="mpai-plugin-logs-empty"><?php _e('Error loading logs. Please try again.', 'memberpress-ai-assistant'); ?></td></tr>');
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
        function updatePagination(total) {
            pluginLogsTotalPages = Math.ceil(total / pluginLogsPerPage);
            
            // Update page info text
            $('#mpai-plugin-logs-page-info').text('<?php _e('Page', 'memberpress-ai-assistant'); ?> ' + pluginLogsPage + ' <?php _e('of', 'memberpress-ai-assistant'); ?> ' + pluginLogsTotalPages);
            
            // Enable/disable pagination buttons
            $('#mpai-plugin-logs-prev-page').prop('disabled', pluginLogsPage <= 1);
            $('#mpai-plugin-logs-next-page').prop('disabled', pluginLogsPage >= pluginLogsTotalPages);
        }
        
        // Function to build log row HTML
        function buildLogRow(log) {
            const date = new Date(log.date_time);
            const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            
            let actionClass = 'mpai-action-' + log.action;
            let actionText = log.action.charAt(0).toUpperCase() + log.action.slice(1);
            
            let row = '<tr data-log-id="' + log.id + '">';
            row += '<td>' + formattedDate + '</td>';
            row += '<td><span class="mpai-action-badge ' + actionClass + '">' + actionText + '</span></td>';
            row += '<td>' + log.plugin_name + '</td>';
            row += '<td>' + log.plugin_version + '</td>';
            row += '<td>' + (log.user_info ? log.user_info.display_name : log.user_login) + '</td>';
            row += '<td><button type="button" class="mpai-details-button" data-log-id="' + log.id + '"><?php _e('View Details', 'memberpress-ai-assistant'); ?></button></td>';
            row += '</tr>';
            
            return row;
        }
        
        // Function to initialize details buttons
        function initDetailsButtons() {
            $('.mpai-details-button').on('click', function() {
                const logId = $(this).data('log-id');
                
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
                    error: function() {
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
                error: function() {
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
                error: function() {
                    alert('Error: Failed to update setting. Please try again.');
                    // Revert the checkbox state
                    $('#mpai-enable-plugin-logging').prop('checked', !enabled);
                }
            });
        });
        
        // Initial load
        loadPluginLogs();
    });
    </script>
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
    background: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 3px;
    padding: 3px 8px;
    font-size: 12px;
    cursor: pointer;
}

.mpai-details-button:hover {
    background: #e0e0e0;
}

.mpai-details-popup {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999999;
    display: flex;
    justify-content: center;
    align-items: center;
}

.mpai-details-popup-content {
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.2);
    width: 80%;
    max-width: 600px;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.mpai-details-popup-header {
    padding: 15px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mpai-details-popup-header h3 {
    margin: 0;
    font-size: 18px;
}

.mpai-details-popup-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.mpai-details-popup-body {
    padding: 15px;
    overflow-y: auto;
}

.mpai-details-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}

.mpai-details-table th, 
.mpai-details-table td {
    padding: 8px;
    border-bottom: 1px solid #eee;
    text-align: left;
    vertical-align: top;
}

.mpai-details-table th {
    width: 30%;
    font-weight: 600;
    color: #23282d;
}

/* Console Logging Styles */
.console-logging-control {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
}

.logging-status-indicator {
    margin-left: 10px;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: bold;
    text-transform: uppercase;
}

.logging-status-indicator.active {
    background-color: #e6f6e6;
    color: #1e7e1e;
}

.logging-status-indicator.inactive {
    background-color: #f0f0f0;
    color: #666;
}

.mpai-test-result {
    margin-left: 10px;
    font-style: italic;
}

/* Switch Toggle */
.mpai-switch {
    position: relative;
    display: inline-flex;
    align-items: center;
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
    width: 38px;
    height: 20px;
    background-color: #ccc;
    border-radius: 34px;
    margin-right: 8px;
    transition: .4s;
}

.mpai-slider:before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: .4s;
}

input:checked + .mpai-slider {
    background-color: #2271b1;
}

input:focus + .mpai-slider {
    box-shadow: 0 0 1px #2271b1;
}

input:checked + .mpai-slider:before {
    transform: translateX(18px);
}

/* System Test Results */
.mpai-system-test-result {
    margin-bottom: 15px;
}

.mpai-system-test-result h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

.mpai-test-success-message {
    color: #4CAF50;
    font-weight: bold;
}

.mpai-test-error-message {
    color: #F44336;
    font-weight: bold;
}

.mpai-test-results-table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
}

.mpai-test-results-table th,
.mpai-test-results-table td {
    padding: 8px;
    border: 1px solid #ddd;
    text-align: left;
}

.mpai-test-results-table th {
    background-color: #f5f5f5;
    font-weight: 600;
}

.mpai-test-success {
    color: #4CAF50;
}

.mpai-test-error {
    color: #F44336;
}

@media screen and (max-width: 782px) {
    .mpai-diagnostic-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .mpai-status-indicator {
        margin-top: 10px;
    }
    
    .mpai-plugin-logs-controls {
        flex-direction: column;
    }
    
    .mpai-plugin-logs-filters,
    .mpai-plugin-logs-actions {
        margin-bottom: 10px;
    }
    
    .mpai-plugin-logs-table th, 
    .mpai-plugin-logs-table td {
        padding: 8px 5px;
    }
    
    .mpai-details-popup-content {
        width: 95%;
    }
}
</style>