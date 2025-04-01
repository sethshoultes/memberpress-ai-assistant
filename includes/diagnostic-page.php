<?php
/**
 * Standalone Diagnostic Page
 *
 * Provides a standalone diagnostic interface for MemberPress AI Assistant
 */

// Load WordPress core
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php');

// Check if user is logged in and is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Execute diagnostic test if requested
$result = array();
$test_type = isset($_GET['test']) ? sanitize_text_field($_GET['test']) : '';

if (!empty($test_type)) {
    // Validate the test type
    if (!in_array($test_type, ['openai_connection', 'anthropic_connection', 'memberpress_connection', 'wordpress_info', 'plugin_status', 'site_health', 'all'])) {
        $result = array(
            'success' => false,
            'message' => 'Invalid test type: ' . $test_type,
            'available_tests' => ['openai_connection', 'anthropic_connection', 'memberpress_connection', 'wordpress_info', 'plugin_status', 'site_health', 'all']
        );
    } else {
        // Create diagnostic tool instance
        if (!class_exists('MPAI_Diagnostic_Tool')) {
            $tool_path = MPAI_PLUGIN_DIR . 'includes/tools/implementations/class-mpai-diagnostic-tool.php';
            if (file_exists($tool_path)) {
                require_once $tool_path;
            }
        }
        
        if (class_exists('MPAI_Diagnostic_Tool')) {
            $diagnostic_tool = new MPAI_Diagnostic_Tool();
            
            // Execute the diagnostic test
            $result = $diagnostic_tool->execute(array(
                'test_type' => $test_type
            ));
        } else {
            $result = array(
                'success' => false,
                'message' => 'Diagnostic tool class not found'
            );
        }
    }
}

// Set page title
$page_title = 'MemberPress AI Assistant Diagnostics';
if (!empty($test_type)) {
    $page_title .= ' - ' . ucfirst(str_replace('_', ' ', $test_type));
}

// Determine next test
$next_test = '';
switch ($test_type) {
    case 'openai_connection': 
        $next_test = 'anthropic_connection'; 
        break;
    case 'anthropic_connection': 
        $next_test = 'memberpress_connection'; 
        break;
    case 'memberpress_connection': 
        $next_test = 'wordpress_info'; 
        break;
    case 'wordpress_info': 
        $next_test = 'plugin_status'; 
        break;
    case 'plugin_status': 
        $next_test = 'site_health'; 
        break;
    case 'site_health': 
        $next_test = ''; 
        break;
    default: 
        $next_test = 'openai_connection';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo esc_html($page_title); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 20px;
            background: #f0f0f1;
            line-height: 1.5;
            color: #3c434a;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #c3c4c7;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            border-radius: 4px;
        }
        h1 {
            color: #1d2327;
            font-size: 23px;
            font-weight: 400;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        h2 {
            font-size: 18px;
            color: #1d2327;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        .test-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .test-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .test-card:hover {
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        .test-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
            color: #1d2327;
        }
        .test-card p {
            margin-bottom: 15px;
            color: #50575e;
        }
        .test-card .button {
            display: inline-block;
            padding: 8px 12px;
            background: #2271b1;
            color: #fff;
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
            line-height: 1.5;
            border: none;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .test-card .button:hover {
            background: #135e96;
        }
        .result-card {
            margin-top: 25px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .result-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .result-header h2 {
            margin: 0;
            font-size: 18px;
        }
        .result-content {
            line-height: 1.6;
        }
        .success {
            color: #008a20;
        }
        .error {
            color: #d63638;
        }
        .warning {
            color: #dba617;
        }
        .result-content table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .result-content table th,
        .result-content table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .result-content table th {
            background-color: #f0f0f0;
            font-weight: 600;
        }
        .result-content table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .navigation {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .result-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-success {
            background-color: #e5f5e8;
            color: #008a20;
        }
        .badge-error {
            background-color: #fcebec;
            color: #d63638;
        }
        pre {
            white-space: pre-wrap;
            background: #f5f5f5;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo esc_html($page_title); ?></h1>
        
        <?php if (empty($test_type)): ?>
        <p>Welcome to the MemberPress AI Assistant Diagnostics page. This tool helps you troubleshoot and verify the health of your installation.</p>
        
        <div class="test-cards">
            <div class="test-card">
                <h3>OpenAI API Test</h3>
                <p>Tests connection to OpenAI API to verify your API key and access to AI models.</p>
                <a href="?test=openai_connection" class="button">Run Test</a>
            </div>
            
            <div class="test-card">
                <h3>Anthropic API Test</h3>
                <p>Tests connection to Anthropic API to verify your API key and access to Claude models.</p>
                <a href="?test=anthropic_connection" class="button">Run Test</a>
            </div>
            
            <div class="test-card">
                <h3>MemberPress API Test</h3>
                <p>Tests connection to MemberPress API to verify membership data access.</p>
                <a href="?test=memberpress_connection" class="button">Run Test</a>
            </div>
            
            <div class="test-card">
                <h3>WordPress Info</h3>
                <p>Gathers system information about your WordPress installation.</p>
                <a href="?test=wordpress_info" class="button">Run Test</a>
            </div>
            
            <div class="test-card">
                <h3>Plugin Status</h3>
                <p>Checks the status of the MemberPress AI Assistant plugin and its dependencies.</p>
                <a href="?test=plugin_status" class="button">Run Test</a>
            </div>
            
            <div class="test-card">
                <h3>WordPress Site Health</h3>
                <p>Get comprehensive system information using WordPress Site Health API.</p>
                <a href="?test=site_health" class="button">Run Test</a>
            </div>
            
            <div class="test-card">
                <h3>Complete System Check</h3>
                <p>Runs all diagnostic tests to provide a comprehensive health report.</p>
                <a href="?test=all" class="button">Run All Tests</a>
            </div>
        </div>
        
        <h2>Legacy Test Pages</h2>
        <p>These standalone test pages provide additional diagnostic capabilities for debugging specific issues:</p>
        
        <div class="action-buttons">
            <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/debug-info.php'); ?>" class="button" target="_blank">Debug Info</a>
            <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/ajax-test.php'); ?>" class="button" target="_blank">AJAX Test</a>
            <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/openai-test.php'); ?>" class="button" target="_blank">OpenAI API Test</a>
            <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/memberpress-test.php'); ?>" class="button" target="_blank">MemberPress API Test</a>
            <a href="<?php echo esc_url(plugin_dir_url(dirname(__FILE__)) . 'includes/anthropic-test.php'); ?>" class="button" target="_blank">Anthropic API Test</a>
        </div>
        
        <?php else: ?>
        
        <div class="result-card">
            <div class="result-header">
                <h2>Test Results: <?php echo esc_html(str_replace('_', ' ', ucfirst($test_type))); ?></h2>
                <span class="result-badge badge-<?php echo $result['success'] ? 'success' : 'error'; ?>">
                    <?php echo $result['success'] ? 'Success' : 'Error'; ?>
                </span>
            </div>
            
            <div class="result-content">
                <?php if ($test_type === 'openai_connection'): ?>
                    <?php if ($result['success']): ?>
                        <div class="success"><strong>✓ Connection successful!</strong></div>
                        <p><strong>Available Models:</strong> <?php echo esc_html($result['models_count']); ?></p>
                        
                        <?php if (!empty($result['chat_models'])): ?>
                            <p><strong>Chat Models:</strong></p>
                            <ul>
                                <?php 
                                $max_models = 5;
                                $model_count = 0;
                                foreach ($result['chat_models'] as $model): 
                                    if ($model_count < $max_models):
                                ?>
                                    <li><?php echo esc_html($model); ?></li>
                                <?php 
                                    $model_count++;
                                    endif;
                                endforeach; 
                                
                                if (count($result['chat_models']) > $max_models): 
                                ?>
                                <li>... and <?php echo (count($result['chat_models']) - $max_models); ?> more</li>
                                <?php endif; ?>
                            </ul>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="error"><strong>✗ Connection failed!</strong></div>
                        <p><?php echo esc_html($result['message']); ?></p>
                    <?php endif; ?>
                
                <?php elseif ($test_type === 'anthropic_connection'): ?>
                    <?php if ($result['success']): ?>
                        <div class="success"><strong>✓ Connection successful!</strong></div>
                        <p><strong>Current Model:</strong> <?php echo esc_html($result['current_model']); ?></p>
                        <p><strong>Response:</strong> <?php echo esc_html($result['response_text']); ?></p>
                        
                        <?php if (!empty($result['available_models'])): ?>
                            <p><strong>Available Models:</strong></p>
                            <ul>
                                <?php foreach ($result['available_models'] as $model): ?>
                                    <li><?php echo esc_html($model); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="error"><strong>✗ Connection failed!</strong></div>
                        <p><?php echo esc_html($result['message']); ?></p>
                    <?php endif; ?>
                
                <?php elseif ($test_type === 'memberpress_connection'): ?>
                    <?php if ($result['success']): ?>
                        <div class="success"><strong>✓ Connection successful!</strong></div>
                        <p><strong>Memberships:</strong> <?php echo esc_html($result['membership_count']); ?></p>
                        
                        <?php if (!empty($result['memberships'])): ?>
                            <table>
                                <tr>
                                    <th>Title</th>
                                    <th>Price</th>
                                </tr>
                                <?php foreach ($result['memberships'] as $membership): ?>
                                <tr>
                                    <td><?php echo esc_html($membership['title']); ?></td>
                                    <td><?php echo esc_html($membership['price']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="error"><strong>✗ Connection failed!</strong></div>
                        <p><?php echo esc_html($result['message']); ?></p>
                        
                        <?php if (isset($result['plugin_exists']) && $result['plugin_exists'] === false): ?>
                            <p class="error">MemberPress plugin is not active.</p>
                        <?php elseif (isset($result['dev_tools_active']) && $result['dev_tools_active'] === false): ?>
                            <p class="error">MemberPress Developer Tools plugin is not active.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                
                <?php elseif ($test_type === 'wordpress_info'): ?>
                    <?php if ($result['success']): ?>
                        <div class="success"><strong>✓ WordPress Information</strong></div>
                        
                        <h3>WordPress</h3>
                        <table>
                            <?php foreach ($result['wordpress'] as $key => $value): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></strong></td>
                                <td><?php echo esc_html($value); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <h3>PHP</h3>
                        <table>
                            <?php foreach ($result['php'] as $key => $value): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></strong></td>
                                <td><?php echo esc_html($value); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <h3>Database</h3>
                        <table>
                            <?php foreach ($result['database'] as $key => $value): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></strong></td>
                                <td><?php echo esc_html($value); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <h3>Server</h3>
                        <table>
                            <?php foreach ($result['server'] as $key => $value): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></strong></td>
                                <td><?php echo esc_html($value); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <div class="error"><strong>✗ Failed to get WordPress information</strong></div>
                        <p><?php echo esc_html($result['message']); ?></p>
                    <?php endif; ?>
                
                <?php elseif ($test_type === 'plugin_status'): ?>
                    <?php if ($result['success']): ?>
                        <div class="success"><strong>✓ Plugin Status</strong></div>
                        
                        <h3>Plugin</h3>
                        <table>
                            <?php foreach ($result['plugin'] as $key => $value): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></strong></td>
                                <td>
                                    <?php if (is_bool($value)): ?>
                                        <span class="<?php echo $value ? 'success' : 'error'; ?>">
                                            <?php echo $value ? 'Yes' : 'No'; ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo esc_html($value); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <h3>API Configuration</h3>
                        <table>
                            <?php foreach ($result['api_config'] as $key => $value): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></strong></td>
                                <td>
                                    <?php if (is_bool($value)): ?>
                                        <span class="<?php echo $value ? 'success' : 'error'; ?>">
                                            <?php echo $value ? 'Yes' : 'No'; ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo esc_html($value); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <h3>Tools</h3>
                        <table>
                            <?php foreach ($result['tools'] as $key => $value): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></strong></td>
                                <td>
                                    <?php if (is_bool($value)): ?>
                                        <span class="<?php echo $value ? 'success' : 'error'; ?>">
                                            <?php echo $value ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo esc_html($value); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <h3>Database</h3>
                        <table>
                            <?php foreach ($result['database'] as $key => $value): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></strong></td>
                                <td>
                                    <?php if (is_bool($value)): ?>
                                        <span class="<?php echo $value ? 'success' : 'error'; ?>">
                                            <?php echo $value ? 'Yes' : 'No'; ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo esc_html($value); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <div class="error"><strong>✗ Failed to get plugin status</strong></div>
                        <p><?php echo esc_html($result['message']); ?></p>
                    <?php endif; ?>
                
                <?php elseif ($test_type === 'site_health'): ?>
                    <?php if ($result['success']): ?>
                        <div class="success"><strong>✓ Site Health Information</strong></div>
                        
                        <?php foreach ($result['data'] as $section => $items): ?>
                            <h3><?php echo esc_html(ucwords(str_replace('-', ' ', $section))); ?></h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Setting</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $key => $item): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($item['label']); ?></strong></td>
                                        <td><?php echo esc_html($item['value']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="error"><strong>✗ Failed to get Site Health information</strong></div>
                        <p><?php echo esc_html($result['message']); ?></p>
                    <?php endif; ?>
                
                <?php elseif ($test_type === 'all'): ?>
                    <h3>Complete Diagnostic Results</h3>
                    
                    <h4>OpenAI API</h4>
                    <div class="result-subsection">
                        <?php if (isset($result['openai']) && $result['openai']['success']): ?>
                            <div class="success"><strong>✓ Connection successful!</strong></div>
                            <p><strong>Available Models:</strong> <?php echo esc_html($result['openai']['models_count']); ?></p>
                            
                            <?php if (!empty($result['openai']['chat_models'])): ?>
                                <p><strong>Chat Models:</strong> <?php echo esc_html(implode(', ', array_slice($result['openai']['chat_models'], 0, 3))); ?> and <?php echo (count($result['openai']['chat_models']) - 3); ?> more</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="error"><strong>✗ Connection failed!</strong></div>
                            <p><?php echo isset($result['openai']) ? esc_html($result['openai']['message']) : 'Test failed'; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <h4>Anthropic API</h4>
                    <div class="result-subsection">
                        <?php if (isset($result['anthropic']) && $result['anthropic']['success']): ?>
                            <div class="success"><strong>✓ Connection successful!</strong></div>
                            <p><strong>Current Model:</strong> <?php echo esc_html($result['anthropic']['current_model']); ?></p>
                        <?php else: ?>
                            <div class="error"><strong>✗ Connection failed!</strong></div>
                            <p><?php echo isset($result['anthropic']) ? esc_html($result['anthropic']['message']) : 'Test failed'; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <h4>MemberPress API</h4>
                    <div class="result-subsection">
                        <?php if (isset($result['memberpress']) && $result['memberpress']['success']): ?>
                            <div class="success"><strong>✓ Connection successful!</strong></div>
                            <p><strong>Memberships:</strong> <?php echo esc_html($result['memberpress']['membership_count']); ?></p>
                        <?php else: ?>
                            <div class="error"><strong>✗ Connection failed!</strong></div>
                            <p><?php echo isset($result['memberpress']) ? esc_html($result['memberpress']['message']) : 'Test failed'; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <h4>WordPress Information</h4>
                    <div class="result-subsection">
                        <?php if (isset($result['wordpress']) && $result['wordpress']['success']): ?>
                            <div class="success"><strong>✓ WordPress information available</strong></div>
                            <p><strong>WordPress Version:</strong> <?php echo esc_html($result['wordpress']['wordpress']['version']); ?></p>
                            <p><strong>PHP Version:</strong> <?php echo esc_html($result['wordpress']['php']['version']); ?></p>
                        <?php else: ?>
                            <div class="error"><strong>✗ Failed to get WordPress information</strong></div>
                            <p><?php echo isset($result['wordpress']) ? esc_html($result['wordpress']['message']) : 'Test failed'; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <h4>Plugin Status</h4>
                    <div class="result-subsection">
                        <?php if (isset($result['plugin_status']) && $result['plugin_status']['success']): ?>
                            <div class="success"><strong>✓ Plugin status available</strong></div>
                            <p><strong>Version:</strong> <?php echo esc_html($result['plugin_status']['plugin']['version']); ?></p>
                            <p><strong>MemberPress Active:</strong> 
                                <span class="<?php echo $result['plugin_status']['plugin']['memberpress_active'] ? 'success' : 'error'; ?>">
                                    <?php echo $result['plugin_status']['plugin']['memberpress_active'] ? 'Yes' : 'No'; ?>
                                </span>
                            </p>
                        <?php else: ?>
                            <div class="error"><strong>✗ Failed to get plugin status</strong></div>
                            <p><?php echo isset($result['plugin_status']) ? esc_html($result['plugin_status']['message']) : 'Test failed'; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <h4>Site Health</h4>
                    <div class="result-subsection">
                        <?php if (isset($result['site_health']) && $result['site_health']['success']): ?>
                            <div class="success"><strong>✓ Site Health information available</strong></div>
                            <p><strong>Sections:</strong> <?php echo esc_html(implode(', ', array_keys($result['site_health']['data']))); ?></p>
                            <p><a href="?test=site_health" class="button">View Full Site Health Report</a></p>
                        <?php else: ?>
                            <div class="error"><strong>✗ Failed to get Site Health information</strong></div>
                            <p><?php echo isset($result['site_health']) ? esc_html($result['site_health']['message']) : 'Test failed'; ?></p>
                        <?php endif; ?>
                    </div>
                
                <?php else: ?>
                    <pre><?php echo esc_html(print_r($result, true)); ?></pre>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="navigation">
            <a href="diagnostic-page.php" class="button">Back to All Tests</a>
            
            <?php if (!empty($next_test)): ?>
                <a href="?test=<?php echo esc_attr($next_test); ?>" class="button">Next Test: <?php echo esc_html(ucwords(str_replace('_', ' ', $next_test))); ?></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <p><a href="<?php echo admin_url('admin.php?page=memberpress-ai-assistant-settings'); ?>" class="button">Back to Plugin Settings</a></p>
    </div>
</body>
</html>