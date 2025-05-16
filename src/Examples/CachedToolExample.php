<?php
/**
 * Cached Tool Example
 *
 * This file demonstrates how to use the CachedToolWrapper to cache tool execution results.
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Examples;

use MemberpressAiAssistant\Services\CachedToolWrapper;
use MemberpressAiAssistant\Registry\ToolRegistry;
use MemberpressAiAssistant\DI\Container;

/**
 * Example class demonstrating how to use the CachedToolWrapper
 */
class CachedToolExample {
    /**
     * Run the example
     *
     * @return void
     */
    public static function run(): void {
        // Get the container
        global $mpai_container;
        
        if (!$mpai_container) {
            error_log('Container not available');
            return;
        }
        
        // Get the tool registry
        $toolRegistry = ToolRegistry::getInstance();
        
        // Get the cache service
        $cacheService = $mpai_container->make('cache');
        
        // Get the logger
        $logger = $mpai_container->make('logger');
        
        // Create the cached tool wrapper
        $cachedToolWrapper = new CachedToolWrapper($cacheService, $logger);
        
        // Register the wrapper with the container
        $mpai_container->singleton('cached_tool_wrapper', function() use ($cachedToolWrapper) {
            return $cachedToolWrapper;
        });
        
        // Example 1: Execute a tool with caching
        $contentTool = $toolRegistry->getTool('content');
        
        if ($contentTool) {
            // Define parameters for the tool
            $parameters = [
                'operation' => 'format_content',
                'content' => 'This is some example content to format.',
                'format_type' => 'markdown',
            ];
            
            // Execute the tool with caching
            $result = $cachedToolWrapper->execute($contentTool, $parameters);
            
            // Log the result
            $logger->info('Tool execution result (first call):', [
                'result' => $result,
            ]);
            
            // Execute the same tool with the same parameters again
            // This should use the cached result
            $cachedResult = $cachedToolWrapper->execute($contentTool, $parameters);
            
            // Log the cached result
            $logger->info('Tool execution result (second call - should be cached):', [
                'result' => $cachedResult,
            ]);
        }
        
        // Example 2: Execute a tool with a non-cacheable operation
        $wordpressTool = $toolRegistry->getTool('wordpress');
        
        if ($wordpressTool) {
            // Add a non-cacheable operation
            $cachedToolWrapper->addNonCacheableOperation('WordPressTool.update_post');
            
            // Define parameters for a non-cacheable operation
            $parameters = [
                'operation' => 'update_post',
                'post_id' => 1,
                'post_data' => [
                    'post_title' => 'Updated Post Title',
                ],
            ];
            
            // Execute the tool (should not be cached)
            $result = $cachedToolWrapper->execute($wordpressTool, $parameters);
            
            // Log the result
            $logger->info('Tool execution result (non-cacheable operation):', [
                'result' => $result,
            ]);
        }
        
        // Example 3: Configure TTL for different tool types
        $memberPressTool = $toolRegistry->getTool('memberpress');
        
        if ($memberPressTool) {
            // Set custom TTL for MemberPressTool operations
            $cachedToolWrapper->setTtlConfig('MemberPressTool', [
                'default' => 600, // 10 minutes
                'get_membership' => 1800, // 30 minutes
                'list_memberships' => 1200, // 20 minutes
            ]);
            
            // Define parameters for the tool
            $parameters = [
                'operation' => 'get_membership',
                'membership_id' => 1,
            ];
            
            // Execute the tool with custom TTL
            $result = $cachedToolWrapper->execute($memberPressTool, $parameters);
            
            // Log the result
            $logger->info('Tool execution result (custom TTL):', [
                'result' => $result,
            ]);
        }
        
        // Example 4: Invalidate cache for a specific tool type
        $cachedToolWrapper->invalidateToolCache('ContentTool');
        $logger->info('Invalidated cache for ContentTool');
        
        // Example 5: Invalidate cache for a specific operation
        $cachedToolWrapper->invalidateOperationCache('MemberPressTool', 'get_membership');
        $logger->info('Invalidated cache for MemberPressTool.get_membership operation');
        
        // Example 6: Invalidate all tool caches
        $cachedToolWrapper->invalidateAllToolCaches();
        $logger->info('Invalidated all tool caches');
    }
}