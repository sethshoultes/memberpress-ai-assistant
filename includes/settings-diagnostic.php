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
        <h4><?php _e('Legacy Test Scripts', 'memberpress-ai-assistant'); ?></h4>
        <p><?php _e('These test scripts provide additional diagnostic capabilities.', 'memberpress-ai-assistant'); ?></p>
        <p>
            <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/debug-info.php'); ?>" class="button" target="_blank"><?php _e('Debug Info', 'memberpress-ai-assistant'); ?></a>
            <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/ajax-test.php'); ?>" class="button" target="_blank"><?php _e('AJAX Test', 'memberpress-ai-assistant'); ?></a>
            <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/openai-test.php'); ?>" class="button" target="_blank"><?php _e('OpenAI API Test', 'memberpress-ai-assistant'); ?></a>
            <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/memberpress-test.php'); ?>" class="button" target="_blank"><?php _e('MemberPress API Test', 'memberpress-ai-assistant'); ?></a>
            <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/anthropic-test.php'); ?>" class="button" target="_blank"><?php _e('Anthropic API Test', 'memberpress-ai-assistant'); ?></a>
        </p>
    </div>
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
        var data = {
            action: 'mpai_run_diagnostic',
            nonce: '<?php echo wp_create_nonce('mpai_nonce'); ?>',
            test_type: testType
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
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
            },
            error: function(xhr, status, error) {
                console.error('MPAI: AJAX error:', status, error);
                
                // Show error
                $(resultContainer).html('<p class="error">AJAX Error: ' + error + '</p>');
                
                // Update status indicator
                if (statusIndicator) {
                    $(statusIndicator + ' .mpai-status-dot').removeClass('mpai-status-unknown mpai-status-success')
                        .addClass('mpai-status-error');
                    $(statusIndicator + ' .mpai-status-text').text('Error');
                }
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
});
</script>