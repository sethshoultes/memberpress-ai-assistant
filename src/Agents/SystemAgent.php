<?php
/**
 * System Agent
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Agents;

use MemberpressAiAssistant\Abstracts\AbstractAgent;

/**
 * Agent specialized in system operations
 */
class SystemAgent extends AbstractAgent {
    /**
     * {@inheritdoc}
     */
    public function getAgentName(): string {
        return 'System Agent';
    }

    /**
     * {@inheritdoc}
     */
    public function getAgentDescription(): string {
        return 'Specialized agent for handling system configuration, diagnostics, plugin management, and performance monitoring.';
    }

    /**
     * {@inheritdoc}
     */
    public function getSystemPrompt(): string {
        return <<<EOT
You are a specialized system operations assistant. Your primary responsibilities include:
1. Managing system configuration and settings
2. Running diagnostics and troubleshooting issues
3. Managing plugins and their settings
4. Monitoring system performance and providing optimization recommendations

Focus on maintaining system stability, security, and performance.
Prioritize actions that improve the overall health and efficiency of the system.
EOT;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerCapabilities(): void {
        $this->addCapability('get_system_info', [
            'description' => 'Get system information',
            'parameters' => [],
        ]);
        
        $this->addCapability('run_diagnostics', [
            'description' => 'Run system diagnostics',
            'parameters' => ['component'],
        ]);
        
        $this->addCapability('update_system_config', [
            'description' => 'Update system configuration',
            'parameters' => ['config_key', 'config_value'],
        ]);
        
        $this->addCapability('get_system_config', [
            'description' => 'Get system configuration',
            'parameters' => ['config_key'],
        ]);
        
        $this->addCapability('list_plugins', [
            'description' => 'List all plugins',
            'parameters' => ['status'],
        ]);
        
        $this->addCapability('activate_plugin', [
            'description' => 'Activate a plugin',
            'parameters' => ['plugin_slug'],
        ]);
        
        $this->addCapability('deactivate_plugin', [
            'description' => 'Deactivate a plugin',
            'parameters' => ['plugin_slug'],
        ]);
        
        $this->addCapability('update_plugin', [
            'description' => 'Update a plugin',
            'parameters' => ['plugin_slug'],
        ]);
        
        $this->addCapability('get_plugin_info', [
            'description' => 'Get plugin information',
            'parameters' => ['plugin_slug'],
        ]);
        
        $this->addCapability('monitor_performance', [
            'description' => 'Monitor system performance',
            'parameters' => ['metrics', 'duration'],
        ]);
        
        $this->addCapability('optimize_system', [
            'description' => 'Optimize system performance',
            'parameters' => ['component'],
        ]);
        
        $this->addCapability('clear_cache', [
            'description' => 'Clear system cache',
            'parameters' => ['cache_type'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function processRequest(array $request, array $context): array {
        $this->setContext($context);
        
        // Add request to short-term memory
        $this->remember('request', $request);
        
        // Log the request
        if ($this->logger) {
            $this->logger->info('Processing request with ' . $this->getAgentName(), [
                'request' => $request,
                'agent' => $this->getAgentName(),
            ]);
        }
        
        // Extract the intent from the request
        $intent = $request['intent'] ?? '';
        
        // Process based on intent
        switch ($intent) {
            case 'get_system_info':
                return $this->getSystemInfo($request);
            
            case 'run_diagnostics':
                return $this->runDiagnostics($request);
            
            case 'update_system_config':
                return $this->updateSystemConfig($request);
            
            case 'get_system_config':
                return $this->getSystemConfig($request);
            
            case 'list_plugins':
                return $this->listPlugins($request);
            
            case 'activate_plugin':
                return $this->activatePlugin($request);
            
            case 'deactivate_plugin':
                return $this->deactivatePlugin($request);
            
            case 'update_plugin':
                return $this->updatePlugin($request);
            
            case 'get_plugin_info':
                return $this->getPluginInfo($request);
            
            case 'monitor_performance':
                return $this->monitorPerformance($request);
            
            case 'optimize_system':
                return $this->optimizeSystem($request);
            
            case 'clear_cache':
                return $this->clearCache($request);
                
            case 'general':
                return $this->handleGeneralIntent($request);
                
            case 'greeting':
                return $this->handleGreeting($request);
            
            default:
                return [
                    'status' => 'error',
                    'message' => 'Unknown intent: ' . $intent,
                ];
        }
    }

    /**
     * Get system information
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function getSystemInfo(array $request): array {
        // Implementation would gather system information
        return [
            'status' => 'success',
            'message' => 'System information retrieved successfully',
            'data' => [
                'php_version' => PHP_VERSION,
                'wordpress_version' => '6.2.0', // Example value
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'database_version' => 'MySQL 8.0.23', // Example value
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'operating_system' => PHP_OS,
                'server_time' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Run system diagnostics
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function runDiagnostics(array $request): array {
        $component = $request['component'] ?? 'all';
        
        // Implementation would run diagnostics on the specified component
        return [
            'status' => 'success',
            'message' => 'Diagnostics completed successfully',
            'data' => [
                'component' => $component,
                'tests_run' => 5,
                'tests_passed' => 4,
                'tests_failed' => 1,
                'issues' => [
                    [
                        'severity' => 'warning',
                        'message' => 'Memory usage is approaching the limit',
                        'recommendation' => 'Consider increasing the memory limit or optimizing memory usage',
                    ],
                ],
                'run_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Update system configuration
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function updateSystemConfig(array $request): array {
        $configKey = $request['config_key'] ?? '';
        $configValue = $request['config_value'] ?? '';
        
        // Implementation would update the system configuration
        return [
            'status' => 'success',
            'message' => 'System configuration updated successfully',
            'data' => [
                'config_key' => $configKey,
                'config_value' => $configValue,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Get system configuration
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function getSystemConfig(array $request): array {
        $configKey = $request['config_key'] ?? '';
        
        // Implementation would retrieve the system configuration
        return [
            'status' => 'success',
            'message' => 'System configuration retrieved successfully',
            'data' => [
                'config_key' => $configKey,
                'config_value' => 'example_value', // Example value
                'last_updated' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
        ];
    }

    /**
     * List all plugins
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function listPlugins(array $request): array {
        $status = $request['status'] ?? 'all';
        
        // Ensure plugin functions are available
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Get plugins
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        
        $plugins = [];
        $active_count = 0;
        $inactive_count = 0;
        $update_available_count = 0;
        
        // Get update information
        $update_data = [];
        if (function_exists('wp_get_update_data')) {
            $update_data = wp_get_update_data();
        }
        
        // Process each plugin
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $is_active = in_array($plugin_path, $active_plugins);
            $plugin_status = $is_active ? 'active' : 'inactive';
            
            // Skip if filtering by status
            if ($status !== 'all' && $plugin_status !== $status) {
                continue;
            }
            
            // Count active/inactive plugins
            if ($is_active) {
                $active_count++;
            } else {
                $inactive_count++;
            }
            
            // Check for updates
            $update_available = false;
            $new_version = '';
            
            if (function_exists('get_site_transient')) {
                $update_plugins = get_site_transient('update_plugins');
                if (isset($update_plugins->response[$plugin_path])) {
                    $update_available = true;
                    $update_available_count++;
                    $new_version = $update_plugins->response[$plugin_path]->new_version ?? '';
                }
            }
            
            // Build plugin data
            $plugin_info = [
                'name' => $plugin_data['Name'] ?? 'Unknown',
                'slug' => dirname(plugin_basename($plugin_path)),
                'version' => $plugin_data['Version'] ?? '',
                'status' => $plugin_status,
                'update_available' => $update_available,
            ];
            
            // Add new version if available
            if ($update_available && !empty($new_version)) {
                $plugin_info['new_version'] = $new_version;
            }
            
            $plugins[] = $plugin_info;
        }
        
        // Build response
        return [
            'status' => 'success',
            'message' => 'Plugins retrieved successfully',
            'data' => [
                'plugins' => $plugins,
                'total' => count($plugins),
                'active' => $active_count,
                'inactive' => $inactive_count,
                'update_available' => $update_available_count,
            ],
        ];
    }

    /**
     * Activate a plugin
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function activatePlugin(array $request): array {
        $pluginSlug = $request['plugin_slug'] ?? '';
        
        // Implementation would activate the plugin
        return [
            'status' => 'success',
            'message' => 'Plugin activated successfully',
            'data' => [
                'plugin_slug' => $pluginSlug,
                'activated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Deactivate a plugin
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function deactivatePlugin(array $request): array {
        $pluginSlug = $request['plugin_slug'] ?? '';
        
        // Implementation would deactivate the plugin
        return [
            'status' => 'success',
            'message' => 'Plugin deactivated successfully',
            'data' => [
                'plugin_slug' => $pluginSlug,
                'deactivated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Update a plugin
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function updatePlugin(array $request): array {
        $pluginSlug = $request['plugin_slug'] ?? '';
        
        // Implementation would update the plugin
        return [
            'status' => 'success',
            'message' => 'Plugin updated successfully',
            'data' => [
                'plugin_slug' => $pluginSlug,
                'old_version' => '1.0.0', // Example value
                'new_version' => '1.1.0', // Example value
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Get plugin information
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function getPluginInfo(array $request): array {
        $pluginSlug = $request['plugin_slug'] ?? '';
        
        // Implementation would retrieve plugin information
        return [
            'status' => 'success',
            'message' => 'Plugin information retrieved successfully',
            'data' => [
                'name' => 'Example Plugin',
                'slug' => $pluginSlug,
                'version' => '1.0.0',
                'author' => 'Example Author',
                'description' => 'This is an example plugin description.',
                'requires_wp' => '5.0.0',
                'requires_php' => '7.2.0',
                'active' => true,
                'update_available' => false,
                'last_updated' => date('Y-m-d H:i:s', strtotime('-30 days')),
            ],
        ];
    }

    /**
     * Monitor system performance
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function monitorPerformance(array $request): array {
        $metrics = $request['metrics'] ?? ['cpu', 'memory', 'disk', 'database'];
        $duration = $request['duration'] ?? 60; // Default to 60 seconds
        
        // Implementation would monitor system performance
        return [
            'status' => 'success',
            'message' => 'Performance monitoring completed successfully',
            'data' => [
                'metrics' => $metrics,
                'duration' => $duration,
                'results' => [
                    'cpu' => [
                        'usage' => '45%',
                        'load_average' => [1.2, 1.5, 1.8],
                    ],
                    'memory' => [
                        'usage' => '65%',
                        'available' => '2.4GB',
                        'total' => '8GB',
                    ],
                    'disk' => [
                        'usage' => '72%',
                        'available' => '28GB',
                        'total' => '100GB',
                        'read_speed' => '120MB/s',
                        'write_speed' => '90MB/s',
                    ],
                    'database' => [
                        'connections' => 12,
                        'query_time_avg' => '0.023s',
                        'slow_queries' => 2,
                    ],
                ],
                'timestamp' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Optimize system performance
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function optimizeSystem(array $request): array {
        $component = $request['component'] ?? 'all';
        
        // Implementation would optimize the specified component
        return [
            'status' => 'success',
            'message' => 'System optimization completed successfully',
            'data' => [
                'component' => $component,
                'optimizations_performed' => [
                    'Cleaned up database tables',
                    'Removed transient options',
                    'Optimized database indexes',
                    'Compressed images',
                ],
                'performance_improvement' => '15%', // Example value
                'completed_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Clear system cache
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function clearCache(array $request): array {
        $cacheType = $request['cache_type'] ?? 'all';
        
        // Implementation would clear the specified cache
        return [
            'status' => 'success',
            'message' => 'Cache cleared successfully',
            'data' => [
                'cache_type' => $cacheType,
                'items_removed' => 156, // Example value
                'space_freed' => '24MB', // Example value
                'cleared_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }
    
    /**
     * Handle general intent
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function handleGeneralIntent(array $request): array {
        // For general intent, provide a helpful response
        return [
            'status' => 'success',
            'message' => 'Hello! I\'m the System Agent. I can help you with system configuration, diagnostics, plugin management, and performance monitoring. How can I assist you today?',
            'agent' => $this->getAgentName(),
            'timestamp' => time(),
        ];
    }
    
    /**
     * Handle greeting intent
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function handleGreeting(array $request): array {
        // For greeting intent, provide a friendly response
        return [
            'status' => 'success',
            'message' => 'Hello! I\'m the System Agent. I\'m here to help you with system-related tasks. How can I assist you today?',
            'agent' => $this->getAgentName(),
            'timestamp' => time(),
        ];
    }

    /**
     * Calculate intent match score
     *
     * @param array $request The request data
     * @return float Score between 0-30
     */
    protected function calculateIntentMatchScore(array $request): float {
        $score = 0.0;
        $message = strtolower($request['message'] ?? '');
        
        // Check for system-related keywords
        $systemKeywords = [
            'system', 'config', 'configuration', 'setting', 'diagnostic',
            'plugin', 'performance', 'monitor', 'optimize', 'cache',
            'update', 'activate', 'deactivate', 'install', 'uninstall',
            'memory', 'cpu', 'disk', 'database', 'server', 'php',
            'wordpress', 'wp', 'admin', 'dashboard', 'maintenance'
        ];
        
        foreach ($systemKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $score += 2.0; // Add 2 points for each keyword match
            }
        }
        
        // Cap at 30
        return min(30.0, $score);
    }

    /**
     * Calculate entity relevance score
     *
     * @param array $request The request data
     * @return float Score between 0-30
     */
    protected function calculateEntityRelevanceScore(array $request): float {
        $score = 0.0;
        $message = strtolower($request['message'] ?? '');
        
        // Check for system-specific entities
        $entities = [
            'system' => 5.0,
            'plugin' => 5.0,
            'configuration' => 4.0,
            'setting' => 4.0,
            'performance' => 5.0,
            'cache' => 4.0,
            'database' => 4.0,
            'server' => 4.0,
            'wordpress' => 3.0,
            'php' => 3.0,
            'memory' => 3.0,
            'cpu' => 3.0,
            'disk' => 3.0,
        ];
        
        foreach ($entities as $entity => $points) {
            if (strpos($message, $entity) !== false) {
                $score += $points;
            }
        }
        
        // Cap at 30
        return min(30.0, $score);
    }

    /**
     * Calculate capability match score
     *
     * @param array $request The request data
     * @return float Score between 0-20
     */
    protected function calculateCapabilityMatchScore(array $request): float {
        $score = 0.0;
        $message = strtolower($request['message'] ?? '');
        
        // Check if the request matches any of our capabilities
        foreach ($this->capabilities as $capability => $metadata) {
            if (strpos($message, strtolower($capability)) !== false) {
                $score += 5.0; // Add 5 points for each capability match
            }
        }
        
        // Check for action verbs related to our domain
        $actionVerbs = [
            'get' => 2.0,
            'run' => 3.0,
            'update' => 3.0,
            'list' => 2.0,
            'activate' => 3.0,
            'deactivate' => 3.0,
            'monitor' => 3.0,
            'optimize' => 3.0,
            'clear' => 3.0,
            'configure' => 3.0,
            'install' => 3.0,
            'uninstall' => 3.0,
        ];
        
        foreach ($actionVerbs as $verb => $points) {
            if (strpos($message, $verb) !== false) {
                $score += $points;
            }
        }
        
        // Cap at 20
        return min(20.0, $score);
    }

    /**
     * Calculate context continuity score
     *
     * @param array $request The request data
     * @return float Score between 0-20
     */
    protected function calculateContextContinuityScore(array $request): float {
        $score = 0.0;
        
        // Check if we have previous requests in memory
        $previousRequest = $this->recall('request');
        if ($previousRequest) {
            // If previous request was also about system operations, increase score
            if (isset($previousRequest['intent']) && 
                (strpos($previousRequest['intent'], 'system') !== false || 
                 strpos($previousRequest['intent'], 'plugin') !== false || 
                 strpos($previousRequest['intent'], 'config') !== false || 
                 strpos($previousRequest['intent'], 'performance') !== false || 
                 strpos($previousRequest['intent'], 'cache') !== false)) {
                $score += 10.0;
            }
            
            // If previous request used one of our capabilities, increase score
            foreach ($this->capabilities as $capability => $metadata) {
                if (isset($previousRequest['intent']) && 
                    $previousRequest['intent'] === $capability) {
                    $score += 10.0;
                    break;
                }
            }
        }
        
        // Cap at 20
        return min(20.0, $score);
    }

    /**
     * Apply score multipliers based on agent-specific criteria
     *
     * @param float $score The current score
     * @param array $request The request data
     * @return float The adjusted score
     */
    protected function applyScoreMultipliers(float $score, array $request): float {
        $message = strtolower($request['message'] ?? '');
        
        // Boost score if explicitly mentioning system operations
        if (strpos($message, 'system config') !== false || 
            strpos($message, 'plugin management') !== false || 
            strpos($message, 'performance monitoring') !== false || 
            strpos($message, 'system diagnostics') !== false) {
            $score *= 1.5;
        }
        
        // Reduce score if request seems to be about membership operations
        if (strpos($message, 'membership') !== false || 
            strpos($message, 'subscription') !== false || 
            strpos($message, 'payment') !== false || 
            strpos($message, 'access rule') !== false) {
            $score *= 0.7;
        }
        
        // Reduce score if request seems to be about content management
        if (strpos($message, 'content') !== false || 
            strpos($message, 'post') !== false || 
            strpos($message, 'page') !== false || 
            strpos($message, 'media') !== false) {
            $score *= 0.6;
        }
        
        return $score;
    }
}