<?php
/**
 * ES6 Module Bundle Generator
 * 
 * Generates optimized ES6 module bundles based on dependency analysis
 * and creates the necessary bundle files for improved loading performance.
 * 
 * @package MemberpressAiAssistant
 * @version 1.0.0
 */

class BundleGenerator {
    private $assets_dir;
    private $bundles_dir;
    private $dependency_data;
    private $bundle_configs;
    private $generated_bundles = [];

    public function __construct($assets_dir = null) {
        $this->assets_dir = $assets_dir ?: __DIR__ . '/../assets/js';
        $this->bundles_dir = $this->assets_dir . '/bundles';
        $this->initializeBundleConfigs();
    }

    /**
     * Initialize bundle configurations
     */
    private function initializeBundleConfigs() {
        $this->bundle_configs = [
            'core-bundle' => [
                'filename' => 'core-bundle.js',
                'description' => 'Essential core modules that must load first',
                'modules' => [
                    'js/chat/core/state-manager.js',
                    'js/chat/core/event-bus.js',
                    'js/chat/core/api-client.js',
                    'js/chat/utils/logger.js',
                    'js/chat/utils/storage-manager.js'
                ],
                'lazy_load' => false
            ],
            'ui-bundle' => [
                'filename' => 'ui-bundle.js',
                'description' => 'UI components and managers',
                'modules' => [
                    'js/chat/core/ui-manager.js',
                    'js/chat/ui/input-handler.js',
                    'js/chat/ui/ui-controls.js'
                ],
                'lazy_load' => true
            ],
            'messaging-bundle' => [
                'filename' => 'messaging-bundle.js',
                'description' => 'Message handling and rendering components',
                'modules' => [
                    'js/chat/messages/message-factory.js',
                    'js/chat/messages/message-renderer.js',
                    'js/chat/messages/handlers/base-message-handler.js'
                ],
                'lazy_load' => false
            ],
            'message-handlers-bundle' => [
                'filename' => 'message-handlers-bundle.js',
                'description' => 'Specific message type handlers (lazy loaded)',
                'modules' => [
                    'js/chat/messages/handlers/blog-post-message-handler.js',
                    'js/chat/messages/handlers/text-message-handler.js',
                    'js/chat/messages/handlers/system-message-handler.js',
                    'js/chat/messages/handlers/interactive-message-handler.js'
                ],
                'lazy_load' => true
            ]
        ];
    }

    /**
     * Generate all bundles
     */
    public function generateBundles($dependency_file = null) {
        $this->log("Starting bundle generation...");
        
        // Load dependency data if provided
        if ($dependency_file && file_exists($dependency_file)) {
            $this->dependency_data = json_decode(file_get_contents($dependency_file), true);
            $this->optimizeBundleConfigs();
        }
        
        // Create bundles directory
        $this->createBundlesDirectory();
        
        // Generate each bundle
        foreach ($this->bundle_configs as $bundle_name => $config) {
            $this->generateBundle($bundle_name, $config);
        }
        
        // Generate bundle index
        $this->generateBundleIndex();
        
        // Generate WordPress registration helper
        $this->generateWordPressRegistration();
        
        $this->log("Bundle generation complete!");
        return $this->generated_bundles;
    }

    /**
     * Optimize bundle configurations based on dependency analysis
     */
    private function optimizeBundleConfigs() {
        if (!$this->dependency_data) return;
        
        $this->log("Optimizing bundle configurations based on dependency analysis...");
        
        // Remove modules that are not actually used
        // Get used modules from dependency graph keys (all modules that have dependencies or are referenced)
        $used_modules = array_keys($this->dependency_data['dependency_graph'] ?? []);
        
        foreach ($this->bundle_configs as $bundle_name => &$config) {
            $config['modules'] = array_filter($config['modules'], function($module) use ($used_modules) {
                return in_array($module, $used_modules);
            });
        }
        
        // Add recommended modules from dependency analysis
        if (isset($this->dependency_data['bundle_recommendations'])) {
            foreach ($this->dependency_data['bundle_recommendations'] as $bundle_type => $modules) {
                $bundle_key = $bundle_type . '-bundle';
                if (isset($this->bundle_configs[$bundle_key])) {
                    $this->bundle_configs[$bundle_key]['modules'] = array_unique(
                        array_merge($this->bundle_configs[$bundle_key]['modules'], $modules)
                    );
                }
            }
        }
    }

    /**
     * Create bundles directory
     */
    private function createBundlesDirectory() {
        if (!is_dir($this->bundles_dir)) {
            mkdir($this->bundles_dir, 0755, true);
            $this->log("Created bundles directory: {$this->bundles_dir}");
        }
    }

    /**
     * Generate a single bundle
     */
    private function generateBundle($bundle_name, $config) {
        $this->log("Generating bundle: {$bundle_name}");
        
        $bundle_path = $this->bundles_dir . '/' . $config['filename'];
        $bundle_content = $this->createBundleContent($bundle_name, $config);
        
        file_put_contents($bundle_path, $bundle_content);
        
        $this->generated_bundles[$bundle_name] = [
            'path' => $bundle_path,
            'relative_path' => 'js/bundles/' . $config['filename'],
            'modules' => $config['modules'],
            'lazy_load' => $config['lazy_load'],
            'size' => filesize($bundle_path)
        ];
        
        $this->log("Generated: {$config['filename']} (" . $this->formatBytes(filesize($bundle_path)) . ")");
    }

    /**
     * Create bundle content
     */
    private function createBundleContent($bundle_name, $config) {
        $content = "/**\n";
        $content .= " * {$bundle_name}\n";
        $content .= " * {$config['description']}\n";
        $content .= " * \n";
        $content .= " * Generated bundle containing:\n";
        
        foreach ($config['modules'] as $module) {
            $content .= " * - {$module}\n";
        }
        
        $content .= " * \n";
        $content .= " * @generated " . date('Y-m-d H:i:s') . "\n";
        $content .= " * @package MemberpressAiAssistant\n";
        $content .= " */\n\n";
        
        // Generate exports for each module
        foreach ($config['modules'] as $module) {
            $module_path = $this->getRelativeModulePath($module);
            $export_name = $this->getExportName($module);
            
            $content .= "// Export from {$module}\n";
            $content .= "export { default as {$export_name} } from '{$module_path}';\n\n";
        }
        
        // Add named exports if needed
        if ($bundle_name === 'core-bundle') {
            $content .= "// Named exports for commonly used utilities\n";
            $content .= "export { Logger, LogLevel } from '../chat/utils/logger.js';\n";
        }
        
        return $content;
    }

    /**
     * Get relative module path for imports
     */
    private function getRelativeModulePath($module) {
        // Convert from 'js/chat/core/state-manager.js' to '../chat/core/state-manager.js'
        $path = str_replace('js/', '../', $module);
        return $path;
    }

    /**
     * Get export name for module
     */
    private function getExportName($module) {
        $basename = basename($module, '.js');
        // Convert kebab-case to PascalCase
        $name = str_replace(['-', '_'], ' ', $basename);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        return $name;
    }

    /**
     * Generate bundle index file
     */
    private function generateBundleIndex() {
        $index_content = "/**\n";
        $index_content .= " * Bundle Index\n";
        $index_content .= " * Central export point for all bundles\n";
        $index_content .= " * \n";
        $index_content .= " * @generated " . date('Y-m-d H:i:s') . "\n";
        $index_content .= " * @package MemberpressAiAssistant\n";
        $index_content .= " */\n\n";
        
        // Export all bundles
        foreach ($this->bundle_configs as $bundle_name => $config) {
            $bundle_export_name = $this->getBundleExportName($bundle_name);
            $index_content .= "export * as {$bundle_export_name} from './{$config['filename']}';\n";
        }
        
        $index_content .= "\n// Bundle loading utilities\n";
        $index_content .= "export const BundleLoader = {\n";
        
        foreach ($this->bundle_configs as $bundle_name => $config) {
            $bundle_export_name = $this->getBundleExportName($bundle_name);
            $method_name = lcfirst($bundle_export_name);
            
            if ($config['lazy_load']) {
                $index_content .= "  async load{$bundle_export_name}() {\n";
                $index_content .= "    return await import('./{$config['filename']}');\n";
                $index_content .= "  },\n";
            } else {
                $index_content .= "  {$method_name}: () => import('./{$config['filename']}'),\n";
            }
        }
        
        $index_content .= "};\n";
        
        file_put_contents($this->bundles_dir . '/index.js', $index_content);
        $this->log("Generated bundle index: index.js");
    }

    /**
     * Generate WordPress registration helper
     */
    private function generateWordPressRegistration() {
        $php_content = "<?php\n";
        $php_content .= "/**\n";
        $php_content .= " * Bundle Registration Helper\n";
        $php_content .= " * WordPress script registration for generated bundles\n";
        $php_content .= " * \n";
        $php_content .= " * @generated " . date('Y-m-d H:i:s') . "\n";
        $php_content .= " * @package MemberpressAiAssistant\n";
        $php_content .= " */\n\n";
        
        $php_content .= "/**\n";
        $php_content .= " * Register all generated bundles with WordPress\n";
        $php_content .= " * \n";
        $php_content .= " * Add this code to your ChatInterface::registerAssets() method\n";
        $php_content .= " */\n\n";
        
        $php_content .= "// Bundle registration code\n";
        $php_content .= "\$bundle_scripts = [\n";
        
        foreach ($this->bundle_configs as $bundle_name => $config) {
            $handle = 'mpai-' . $bundle_name;
            $php_content .= "    '{$handle}' => 'assets/js/bundles/{$config['filename']}',\n";
        }
        
        $php_content .= "];\n\n";
        
        $php_content .= "foreach (\$bundle_scripts as \$handle => \$path) {\n";
        $php_content .= "    wp_register_script(\n";
        $php_content .= "        \$handle,\n";
        $php_content .= "        MPAI_PLUGIN_URL . \$path,\n";
        $php_content .= "        [], // Dependencies handled by ES6 imports\n";
        $php_content .= "        MPAI_VERSION,\n";
        $php_content .= "        true\n";
        $php_content .= "    );\n";
        $php_content .= "    wp_script_add_data(\$handle, 'type', 'module');\n";
        $php_content .= "}\n\n";
        
        $php_content .= "// Enqueue core bundles (non-lazy)\n";
        foreach ($this->bundle_configs as $bundle_name => $config) {
            if (!$config['lazy_load']) {
                $handle = 'mpai-' . $bundle_name;
                $php_content .= "wp_enqueue_script('{$handle}');\n";
            }
        }
        
        file_put_contents($this->bundles_dir . '/wordpress-registration.php', $php_content);
        $this->log("Generated WordPress registration helper: wordpress-registration.php");
    }

    /**
     * Generate updated main entry point
     */
    public function generateUpdatedEntryPoint() {
        $this->log("Generating updated entry point...");
        
        $entry_content = "/**\n";
        $entry_content .= " * MemberPress AI Assistant Chat Interface - Optimized Entry Point\n";
        $entry_content .= " * \n";
        $entry_content .= " * This file has been optimized to use ES6 module bundles for better\n";
        $entry_content .= " * loading performance and reduced HTTP requests.\n";
        $entry_content .= " * \n";
        $entry_content .= " * @module chat\n";
        $entry_content .= " * @author MemberPress\n";
        $entry_content .= " * @version 1.0.0\n";
        $entry_content .= " * @optimized " . date('Y-m-d H:i:s') . "\n";
        $entry_content .= " */\n\n";
        
        // jQuery handling
        $entry_content .= "// Import jQuery as a module if it's not available in global scope\n";
        $entry_content .= "let \$;\n";
        $entry_content .= "if (typeof jQuery !== 'undefined') {\n";
        $entry_content .= "  \$ = jQuery;\n";
        $entry_content .= "} else {\n";
        $entry_content .= "  console.warn('[MPAI] jQuery not found, chat functionality may be limited');\n";
        $entry_content .= "}\n\n";
        
        // Import from bundles
        $entry_content .= "// Import core modules from bundles\n";
        $entry_content .= "import {\n";
        $entry_content .= "  StateManager,\n";
        $entry_content .= "  EventBus,\n";
        $entry_content .= "  ApiClient,\n";
        $entry_content .= "  Logger,\n";
        $entry_content .= "  LogLevel,\n";
        $entry_content .= "  StorageManager\n";
        $entry_content .= "} from './bundles/core-bundle.js';\n\n";
        
        $entry_content .= "import {\n";
        $entry_content .= "  MessageFactory,\n";
        $entry_content .= "  MessageRenderer\n";
        $entry_content .= "} from './bundles/messaging-bundle.js';\n\n";
        
        $entry_content .= "// Chat core is imported individually as it coordinates everything\n";
        $entry_content .= "import ChatCore from './chat/core/chat-core.js';\n\n";
        
        // Lazy loading setup
        $entry_content .= "// UI components are lazy loaded\n";
        $entry_content .= "let UIManager, InputHandler, UIControls;\n\n";
        
        // Initialization function
        $entry_content .= "/**\n";
        $entry_content .= " * Initialize the chat system when the DOM is ready\n";
        $entry_content .= " */\n";
        $entry_content .= "document.addEventListener('DOMContentLoaded', async () => {\n";
        $entry_content .= "  const chatContainer = document.getElementById('mpai-chat-container');\n";
        $entry_content .= "  \n";
        $entry_content .= "  if (!chatContainer) {\n";
        $entry_content .= "    console.warn('[MPAI Chat] Chat container not found, will try again later');\n";
        $entry_content .= "    setTimeout(() => {\n";
        $entry_content .= "      const delayedContainer = document.getElementById('mpai-chat-container');\n";
        $entry_content .= "      if (delayedContainer) {\n";
        $entry_content .= "        initializeChat();\n";
        $entry_content .= "      }\n";
        $entry_content .= "    }, 2000);\n";
        $entry_content .= "    return;\n";
        $entry_content .= "  }\n\n";
        
        $entry_content .= "  try {\n";
        $entry_content .= "    await initializeChat();\n";
        $entry_content .= "  } catch (error) {\n";
        $entry_content .= "    console.error('[MPAI Chat] Initialization error:', error);\n";
        $entry_content .= "  }\n";
        $entry_content .= "});\n\n";
        
        // Optimized initialization function
        $entry_content .= "/**\n";
        $entry_content .= " * Initialize the chat system with optimized module loading\n";
        $entry_content .= " */\n";
        $entry_content .= "async function initializeChat() {\n";
        $entry_content .= "  const config = window.mpai_chat_config || {};\n";
        $entry_content .= "  \n";
        $entry_content .= "  // Create logger first\n";
        $entry_content .= "  const logger = new Logger({\n";
        $entry_content .= "    minLevel: config.debug ? LogLevel.DEBUG : LogLevel.INFO,\n";
        $entry_content .= "    enableTimestamps: true\n";
        $entry_content .= "  });\n";
        $entry_content .= "  \n";
        $entry_content .= "  logger.info('Initializing optimized chat system');\n";
        $entry_content .= "  \n";
        $entry_content .= "  // Initialize core services\n";
        $entry_content .= "  const eventBus = new EventBus();\n";
        $entry_content .= "  const storageManager = new StorageManager({\n";
        $entry_content .= "    storagePrefix: 'mpai_',\n";
        $entry_content .= "    defaultExpiration: 30 * 24 * 60 * 60 * 1000\n";
        $entry_content .= "  });\n";
        $entry_content .= "  \n";
        $entry_content .= "  const stateManager = new StateManager({\n";
        $entry_content .= "    ui: {\n";
        $entry_content .= "      isChatOpen: localStorage.getItem('mpai_chat_open') === 'true',\n";
        $entry_content .= "      isExpanded: localStorage.getItem('mpai_chat_expanded') === 'true'\n";
        $entry_content .= "    }\n";
        $entry_content .= "  }, eventBus);\n";
        $entry_content .= "  \n";
        $entry_content .= "  const apiClient = new ApiClient({\n";
        $entry_content .= "    baseUrl: config.apiEndpoint || '/wp-json/memberpress-ai/v1/chat',\n";
        $entry_content .= "    timeout: config.timeout || 30000,\n";
        $entry_content .= "    retries: config.retries || 2\n";
        $entry_content .= "  }, eventBus);\n";
        $entry_content .= "  \n";
        $entry_content .= "  // Lazy load UI components\n";
        $entry_content .= "  logger.debug('Loading UI components...');\n";
        $entry_content .= "  const uiBundle = await import('./bundles/ui-bundle.js');\n";
        $entry_content .= "  UIManager = uiBundle.UiManager;\n";
        $entry_content .= "  \n";
        $entry_content .= "  const uiManager = new UIManager({\n";
        $entry_content .= "    typingDelay: config.typingDelay || 0,\n";
        $entry_content .= "    theme: config.theme || 'light'\n";
        $entry_content .= "  }, stateManager, eventBus);\n";
        $entry_content .= "  \n";
        $entry_content .= "  // Initialize chat core\n";
        $entry_content .= "  const chatCore = new ChatCore({\n";
        $entry_content .= "    maxMessages: config.maxMessages || 50,\n";
        $entry_content .= "    autoOpen: config.autoOpen || false,\n";
        $entry_content .= "    debug: config.debug || false\n";
        $entry_content .= "  });\n";
        $entry_content .= "  \n";
        $entry_content .= "  // Initialize all components\n";
        $entry_content .= "  await storageManager.initialize();\n";
        $entry_content .= "  await stateManager.initialize();\n";
        $entry_content .= "  await apiClient.initialize();\n";
        $entry_content .= "  await uiManager.initialize('#mpai-chat-container');\n";
        $entry_content .= "  \n";
        $entry_content .= "  // Connect dependencies\n";
        $entry_content .= "  chatCore._stateManager = stateManager;\n";
        $entry_content .= "  chatCore._uiManager = uiManager;\n";
        $entry_content .= "  chatCore._apiClient = apiClient;\n";
        $entry_content .= "  chatCore._eventBus = eventBus;\n";
        $entry_content .= "  \n";
        $entry_content .= "  await chatCore.initialize();\n";
        $entry_content .= "  await chatCore.start();\n";
        $entry_content .= "  \n";
        $entry_content .= "  // Global exposure\n";
        $entry_content .= "  window.mpaiChat = chatCore;\n";
        $entry_content .= "  \n";
        $entry_content .= "  logger.info('Optimized chat system ready');\n";
        $entry_content .= "  return chatCore;\n";
        $entry_content .= "}\n\n";
        
        // Exports
        $entry_content .= "// Export main chat functionality\n";
        $entry_content .= "export { ChatCore };\n";
        $entry_content .= "export default initializeChat;\n";
        
        // Save optimized entry point
        $optimized_entry = $this->assets_dir . '/chat-optimized.js';
        file_put_contents($optimized_entry, $entry_content);
        
        $this->log("Generated optimized entry point: chat-optimized.js");
        
        return $optimized_entry;
    }

    /**
     * Helper methods
     */
    private function getBundleExportName($bundle_name) {
        $name = str_replace(['-', '_'], ' ', $bundle_name);
        $name = ucwords($name);
        return str_replace(' ', '', $name);
    }

    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function log($message) {
        echo "[Bundle Generator] {$message}\n";
    }
}

// CLI execution
if (isset($argv[0]) && basename($argv[0]) === basename(__FILE__)) {
    $options = getopt('d:v', ['dependency-file:', 'verbose']);
    $dependency_file = $options['d'] ?? $options['dependency-file'] ?? null;
    $verbose = isset($options['v']) || isset($options['verbose']);
    
    $generator = new BundleGenerator();
    
    if ($verbose) {
        echo "ES6 Module Bundle Generator v1.0.0\n";
        echo "=======================================\n";
    }
    
    $bundles = $generator->generateBundles($dependency_file);
    $generator->generateUpdatedEntryPoint();
    
    if ($verbose) {
        echo "\nBundle Generation Complete!\n";
        echo "============================\n";
        echo "Generated " . count($bundles) . " bundles:\n";
        
        foreach ($bundles as $name => $info) {
            echo "- {$name}: " . $generator->formatBytes($info['size']) . " (" . count($info['modules']) . " modules)\n";
        }
        
        echo "\nNext steps:\n";
        echo "1. Update ChatInterface.php to use the generated WordPress registration\n";
        echo "2. Replace chat.js with chat-optimized.js in your registration\n";
        echo "3. Test the optimized chat system\n";
    }
}