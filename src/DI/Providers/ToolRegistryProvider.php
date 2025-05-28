<?php
/**
 * Tool Registry Provider
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\DI\Providers;

use MemberpressAiAssistant\DI\ServiceLocator;
use MemberpressAiAssistant\DI\ServiceProvider;
use MemberpressAiAssistant\Registry\ToolRegistry;
use MemberpressAiAssistant\Tools\WordPressTool;
use MemberpressAiAssistant\Tools\MemberPressTool;
use MemberpressAiAssistant\Tools\ContentTool;

/**
 * Service provider for the Tool Registry
 */
class ToolRegistryProvider extends ServiceProvider {
    /**
     * Register services with the service locator
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    public function register(ServiceLocator $locator): void {
        // Register the tool registry
        $locator->register('tool_registry', function($locator) {
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            $registry = ToolRegistry::getInstance($logger);
            
            // Register built-in tools
            $this->registerBuiltInTools($registry, $locator);
            
            return $registry;
        });
    }

    /**
     * Register built-in tools with the registry
     *
     * @param ToolRegistry $registry The tool registry
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    protected function registerBuiltInTools(ToolRegistry $registry, ServiceLocator $locator): void {
        // Log registration
        if (function_exists('error_log')) {
            \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Registering built-in tools with the registry');
        }
        
        try {
            // Register WordPress tool
            if (class_exists('MemberpressAiAssistant\Tools\WordPressTool')) {
                try {
                    // FIXED: WordPressTool constructor doesn't accept logger parameter
                    $wpTool = new WordPressTool();
                    $registry->registerTool($wpTool);
                    
                    if (function_exists('error_log')) {
                        \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Registered WordPress tool');
                    }
                } catch (\Exception $e) {
                    if (function_exists('error_log')) {
                        \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Error registering WordPress tool: ' . $e->getMessage());
                        \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - WordPress tool error trace: ' . $e->getTraceAsString());
                    }
                }
            }
            
            // Register MemberPress tool
            if (class_exists('MemberpressAiAssistant\Tools\MemberPressTool')) {
                // Get MemberPress service if available
                $memberPressService = null;
                if ($locator->has('memberpress')) {
                    $memberPressService = $locator->get('memberpress');
                }
                
                if ($memberPressService) {
                    $mpTool = new MemberPressTool($memberPressService);
                    $registry->registerTool($mpTool);
                    
                    if (function_exists('error_log')) {
                        \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Registered MemberPress tool');
                    }
                } else {
                    if (function_exists('error_log')) {
                        \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - MemberPress service not available, skipping MemberPress tool registration');
                    }
                }
            }
            
            // Register Content tool
            if (class_exists('MemberpressAiAssistant\Tools\ContentTool')) {
                try {
                    // Check ContentTool constructor signature
                    $reflection = new \ReflectionClass('MemberpressAiAssistant\Tools\ContentTool');
                    $constructor = $reflection->getConstructor();
                    
                    if ($constructor && $constructor->getNumberOfParameters() > 0) {
                        // ContentTool accepts logger parameter
                        $logger = $locator->has('logger') ? $locator->get('logger') : null;
                        $contentTool = new ContentTool($logger);
                    } else {
                        // ContentTool doesn't accept parameters
                        $contentTool = new ContentTool();
                    }
                    
                    $registry->registerTool($contentTool);
                    
                    if (function_exists('error_log')) {
                        \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Registered Content tool');
                    }
                } catch (\Exception $e) {
                    if (function_exists('error_log')) {
                        \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Error registering Content tool: ' . $e->getMessage());
                        \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Content tool error trace: ' . $e->getTraceAsString());
                    }
                }
            }
        } catch (\Exception $e) {
            if (function_exists('error_log')) {
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Error registering built-in tools: ' . $e->getMessage());
                \MemberpressAiAssistant\Utilities\debug_log('MPAI Debug - Error trace: ' . $e->getTraceAsString());
            }
        }
    }

    /**
     * Boot the service provider
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    public function boot(ServiceLocator $locator): void {
        // Nothing to do here
    }

    /**
     * Get the services provided by this provider
     *
     * @return array
     */
    public function provides(): array {
        return [
            'tool_registry',
        ];
    }
}