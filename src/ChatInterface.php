<?php
/**
 * Chat Interface Handler
 *
 * Handles the registration, enqueuing, and rendering of the chat interface.
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant;

use MemberpressAiAssistant\Utilities\LoggingUtility;

/**
 * Class ChatInterface
 *
 * Manages the chat interface functionality.
 */
class ChatInterface {
    /**
     * Instance of this class
     *
     * @var ChatInterface
     */
    private static $instance = null;

    /**
     * Get the singleton instance of this class
     *
     * @return ChatInterface
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Register hooks
        add_action('wp_enqueue_scripts', [$this, 'registerAssets']);
        add_action('admin_enqueue_scripts', [$this, 'registerAdminAssets']);
        add_action('wp_footer', [$this, 'renderChatInterface']);
        add_action('admin_footer', [$this, 'renderAdminChatInterface']);
        
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        
        // Add filter to modify script tags for ES6 modules
        add_filter('script_loader_tag', [$this, 'addModuleTypeToScripts'], 10, 3);
    }
    
    /**
     * Add type="module" attribute to script tags for ES6 modules
     *
     * @param string $tag The script tag
     * @param string $handle The script handle
     * @param string $src The script source
     * @return string The modified script tag
     */
    public function addModuleTypeToScripts($tag, $handle, $src) {
        // List of script handles that should be loaded as modules
        $module_scripts = [
            'mpai-chat',
            'mpai-chat-admin',
            'mpai-chat-core',
            'mpai-state-manager',
            'mpai-ui-manager',
            'mpai-api-client',
            'mpai-event-bus',
            'mpai-logger',
            'mpai-storage-manager',
            'mpai-chat-core-admin',
            'mpai-state-manager-admin',
            'mpai-ui-manager-admin',
            'mpai-api-client-admin',
            'mpai-event-bus-admin',
            'mpai-logger-admin',
            'mpai-storage-manager-admin'
        ];
        
        // Check if this script should be loaded as a module
        if (in_array($handle, $module_scripts)) {
            // Replace the script tag with one that has type="module"
            $tag = str_replace(' src=', ' type="module" src=', $tag);
            
            // Log for debugging
            LoggingUtility::debug("Added type=module to script: $handle");
        }
        
        return $tag;
    }

    /**
     * Register chat interface assets for frontend
     */
    public function registerAssets() {
        // Only load on appropriate pages
        if (!$this->shouldLoadChatInterface()) {
            return;
        }

        // Register styles
        wp_register_style(
            'mpai-chat',
            MPAI_PLUGIN_URL . 'assets/css/chat.css',
            [],
            MPAI_VERSION
        );
        
        
        // Register blog post styles
        wp_register_style(
            'mpai-blog-post',
            MPAI_PLUGIN_URL . 'assets/css/blog-post.css',
            [],
            MPAI_VERSION
        );
        
        // Register table styles for WordPress tool outputs
        wp_register_style(
            'mpai-table-styles',
            MPAI_PLUGIN_URL . 'assets/css/mpai-table-styles.css',
            ['mpai-chat'],
            MPAI_VERSION
        );

        // Register scripts
        // Register response formatting modules first
        wp_register_script(
            'mpai-xml-processor',
            MPAI_PLUGIN_URL . 'assets/js/xml-processor.js',
            [],
            MPAI_VERSION,
            true
        );
        
        wp_register_script(
            'mpai-data-handler',
            MPAI_PLUGIN_URL . 'assets/js/data-handler-minimal.js',
            [],
            MPAI_VERSION,
            true
        );
        
        wp_register_script(
            'mpai-text-formatter',
            MPAI_PLUGIN_URL . 'assets/js/text-formatter.js',
            [],
            MPAI_VERSION,
            true
        );
        
        // Explicitly enqueue jQuery
        wp_enqueue_script('jquery');
        
        // Register blog formatter module
        wp_register_script(
            'mpai-blog-formatter',
            MPAI_PLUGIN_URL . 'assets/js/blog-formatter.js',
            ['jquery'],
            MPAI_VERSION,
            true
        );
        
        // Register main chat script as a module
        wp_register_script(
            'mpai-chat',
            MPAI_PLUGIN_URL . 'assets/js/chat.js',
            ['jquery'], // Add jQuery as dependency
            MPAI_VERSION,
            true
        );
        // Add the module type
        wp_script_add_data('mpai-chat', 'type', 'module');
        
        // Register module scripts
        $module_scripts = [
            'mpai-chat-core' => 'assets/js/chat/core/chat-core.js',
            'mpai-state-manager' => 'assets/js/chat/core/state-manager.js',
            'mpai-ui-manager' => 'assets/js/chat/core/ui-manager.js',
            'mpai-api-client' => 'assets/js/chat/core/api-client.js',
            'mpai-event-bus' => 'assets/js/chat/core/event-bus.js',
            'mpai-logger' => 'assets/js/chat/utils/logger.js',
            'mpai-storage-manager' => 'assets/js/chat/utils/storage-manager.js'
        ];

        foreach ($module_scripts as $handle => $path) {
            wp_register_script(
                $handle,
                MPAI_PLUGIN_URL . $path,
                [],
                MPAI_VERSION,
                true
            );
            wp_script_add_data($handle, 'type', 'module');
            wp_enqueue_script($handle); // Enqueue each module script
        }

        // Enqueue assets
        wp_enqueue_style('mpai-chat');
        wp_enqueue_style('mpai-blog-post');
        wp_enqueue_style('mpai-table-styles');
        wp_enqueue_script('mpai-xml-processor');
        wp_enqueue_script('mpai-data-handler');
        wp_enqueue_script('mpai-text-formatter');
        wp_enqueue_script('mpai-blog-formatter');
        wp_enqueue_script('mpai-chat');
        
        // Add WordPress dashicons for icons
        wp_enqueue_style('dashicons');

        // Localize script with configuration
        wp_localize_script('mpai-chat', 'mpai_chat_config', $this->getChatConfig());
        
        // Add REST API nonce
        wp_localize_script('mpai-chat', 'mpai_nonce', wp_create_nonce('wp_rest'));
        
        // Add AJAX nonce for the new modular system
        wp_localize_script('mpai-chat', 'mpai_ajax', [
            'nonce' => wp_create_nonce('mpai_ajax_nonce'),
            'url' => admin_url('admin-ajax.php')
        ]);
    }

    /**
     * Register chat interface assets for admin
     *
     * @param string $hook_suffix The current admin page
     */
    public function registerAdminAssets($hook_suffix) {
        // Simplified loading - always load on admin pages where chat interface might be needed
        // This ensures assets are available when the container is rendered
        if (!$this->shouldLoadAdminChatInterface($hook_suffix)) {
            return;
        }

        // Register styles
        wp_register_style(
            'mpai-chat-admin',
            MPAI_PLUGIN_URL . 'assets/css/chat.css',
            [],
            MPAI_VERSION
        );
        
        
        // Register blog post styles
        wp_register_style(
            'mpai-blog-post-admin',
            MPAI_PLUGIN_URL . 'assets/css/blog-post.css',
            [],
            MPAI_VERSION
        );
        
        // Register table styles for WordPress tool outputs
        wp_register_style(
            'mpai-table-styles-admin',
            MPAI_PLUGIN_URL . 'assets/css/mpai-table-styles.css',
            ['mpai-chat-admin'],
            MPAI_VERSION
        );

        // Register scripts
        // Register response formatting modules first
        wp_register_script(
            'mpai-xml-processor-admin',
            MPAI_PLUGIN_URL . 'assets/js/xml-processor.js',
            [],
            MPAI_VERSION,
            true
        );
        
        wp_register_script(
            'mpai-data-handler-admin',
            MPAI_PLUGIN_URL . 'assets/js/data-handler-minimal.js',
            [],
            MPAI_VERSION,
            true
        );
        
        wp_register_script(
            'mpai-text-formatter-admin',
            MPAI_PLUGIN_URL . 'assets/js/text-formatter.js',
            [],
            MPAI_VERSION,
            true
        );
        
        // Explicitly enqueue jQuery
        wp_enqueue_script('jquery');
        
        // Register blog formatter module
        wp_register_script(
            'mpai-blog-formatter-admin',
            MPAI_PLUGIN_URL . 'assets/js/blog-formatter.js',
            ['jquery'],
            MPAI_VERSION,
            true
        );
        
        // Register main chat script as a module for admin
        wp_register_script(
            'mpai-chat-admin',
            MPAI_PLUGIN_URL . 'assets/js/chat.js',
            ['jquery'], // Add jQuery as dependency
            MPAI_VERSION,
            true
        );
        // Add the module type
        wp_script_add_data('mpai-chat-admin', 'type', 'module');
        
        // Register module scripts for admin
        $module_scripts = [
            'mpai-chat-core-admin' => 'assets/js/chat/core/chat-core.js',
            'mpai-state-manager-admin' => 'assets/js/chat/core/state-manager.js',
            'mpai-ui-manager-admin' => 'assets/js/chat/core/ui-manager.js',
            'mpai-api-client-admin' => 'assets/js/chat/core/api-client.js',
            'mpai-event-bus-admin' => 'assets/js/chat/core/event-bus.js',
            'mpai-logger-admin' => 'assets/js/chat/utils/logger.js',
            'mpai-storage-manager-admin' => 'assets/js/chat/utils/storage-manager.js'
        ];

        foreach ($module_scripts as $handle => $path) {
            wp_register_script(
                $handle,
                MPAI_PLUGIN_URL . $path,
                [],
                MPAI_VERSION,
                true
            );
            wp_script_add_data($handle, 'type', 'module');
            wp_enqueue_script($handle); // Enqueue each module script
        }

        // Enqueue assets
        wp_enqueue_style('mpai-chat-admin');
        wp_enqueue_style('mpai-blog-post-admin');
        wp_enqueue_style('mpai-table-styles-admin');
        wp_enqueue_script('mpai-xml-processor-admin');
        wp_enqueue_script('mpai-data-handler-admin');
        wp_enqueue_script('mpai-text-formatter-admin');
        wp_enqueue_script('mpai-blog-formatter-admin');
        wp_enqueue_script('mpai-chat-admin');

        // Localize script with configuration
        wp_localize_script('mpai-chat-admin', 'mpai_chat_config', $this->getChatConfig(true));
        
        // Add REST API nonce
        wp_localize_script('mpai-chat-admin', 'mpai_nonce', wp_create_nonce('wp_rest'));
        
        // Add AJAX nonce for the new modular system
        wp_localize_script('mpai-chat-admin', 'mpai_ajax', [
            'nonce' => wp_create_nonce('mpai_ajax_nonce'),
            'url' => admin_url('admin-ajax.php')
        ]);
    }

    /**
     * Render the chat interface on frontend
     */
    public function renderChatInterface() {
        // Only render on appropriate pages
        if (!$this->shouldLoadChatInterface()) {
            return;
        }
        
        $this->renderChatContainerHTML();
    }

    /**
     * Render the chat interface on admin pages
     */
    public function renderAdminChatInterface() {
        // DIAGNOSTIC: Add comprehensive logging for chat container rendering
        $current_screen = get_current_screen();
        $screen_id = $current_screen ? $current_screen->id : 'unknown';
        $current_page = isset($_GET['page']) ? $_GET['page'] : 'none';
        $request_uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $is_ajax = wp_doing_ajax();
        $user_id = get_current_user_id();
        
        LoggingUtility::debug('[CHAT RENDER DIAGNOSIS] renderAdminChatInterface() called', [
            'screen_id' => $screen_id,
            'page' => $current_page,
            'request_uri' => $request_uri,
            'is_ajax' => $is_ajax,
            'user_id' => $user_id,
            'already_rendered_flag' => defined('MPAI_CHAT_INTERFACE_RENDERED'),
            'call_stack' => wp_debug_backtrace_summary()
        ]);
        
        // Check for duplicate rendering flag - PREVENT DUPLICATE RENDERING
        if (defined('MPAI_CHAT_INTERFACE_RENDERED')) {
            LoggingUtility::debug('ChatInterface: Chat interface already rendered, preventing duplicate');
            return;
        }
        
        // Only render on appropriate admin pages
        if (!$this->shouldLoadAdminChatInterface($screen_id)) {
            LoggingUtility::debug('ChatInterface: Not loading chat interface for screen: ' . $screen_id);
            return;
        }
        
        LoggingUtility::debug('ChatInterface: Rendering chat interface container');
        
        // Set flag to prevent duplicate rendering
        define('MPAI_CHAT_INTERFACE_RENDERED', true);
        
        // DIAGNOSTIC: Log before rendering chat container HTML
        LoggingUtility::debug('[CHAT RENDER DIAGNOSIS] About to render chat container HTML', [
            'user_id' => $user_id,
            'screen_id' => $screen_id,
            'page' => $current_page,
            'output_buffer_level' => ob_get_level(),
            'headers_sent' => headers_sent()
        ]);
        
        $this->renderChatContainerHTML();
        
        // DIAGNOSTIC: Log after rendering chat container HTML
        LoggingUtility::debug('[CHAT RENDER DIAGNOSIS] Chat container HTML rendering completed');
        
        LoggingUtility::debug('ChatInterface: Chat interface rendered successfully');
    }

    /**
     * Render the chat container HTML directly
     */
    private function renderChatContainerHTML() {
        LoggingUtility::debug('ChatInterface: Rendering chat container HTML directly');
        
        // DIAGNOSTIC: Get chat position setting for positioning bug diagnosis
        global $mpai_service_locator;
        $chat_position = 'bottom_right'; // Default fallback
        $position_source = 'default_fallback';
        
        // Try to get the chat position setting from the settings model
        if (isset($mpai_service_locator) && $mpai_service_locator->has('settings.model')) {
            try {
                $settings_model = $mpai_service_locator->get('settings.model');
                $chat_position = $settings_model->get_chat_position();
                $position_source = 'settings_model';
                LoggingUtility::debug('[POSITION DEBUG] Retrieved chat position from settings model: ' . $chat_position);
            } catch (\Exception $e) {
                LoggingUtility::warning('[POSITION DEBUG] Failed to get settings model: ' . $e->getMessage());
                $position_source = 'settings_model_error';
            }
        } else {
            // Fallback to direct option access
            $raw_settings = get_option('mpai_settings', []);
            if (isset($raw_settings['chat_position'])) {
                $chat_position = $raw_settings['chat_position'];
                $position_source = 'direct_option';
                LoggingUtility::debug('[POSITION DEBUG] Retrieved chat position from direct option: ' . $chat_position);
            } else {
                LoggingUtility::debug('[POSITION DEBUG] No chat position setting found, using default: ' . $chat_position);
                $position_source = 'no_setting_found';
            }
        }
        
        // Generate position CSS class
        $position_class = 'mpai-chat-position-' . str_replace('_', '-', $chat_position);
        
        LoggingUtility::debug('[POSITION DEBUG] Chat positioning diagnosis', [
            'setting_value' => $chat_position,
            'position_source' => $position_source,
            'css_class' => $position_class,
            'service_locator_available' => isset($mpai_service_locator),
            'settings_model_available' => isset($mpai_service_locator) && $mpai_service_locator->has('settings.model')
        ]);
        
        ?>
        <div class="mpai-chat-container <?php echo esc_attr($position_class); ?>" id="mpai-chat-container" data-position="<?php echo esc_attr($chat_position); ?>" data-position-source="<?php echo esc_attr($position_source); ?>">
            <div class="mpai-chat-header">
                <h3><?php esc_html_e('MemberPress AI Assistant', 'memberpress-ai-assistant'); ?></h3>
                <button class="mpai-chat-expand" id="mpai-chat-expand" aria-label="<?php esc_attr_e('Expand chat', 'memberpress-ai-assistant'); ?>" title="<?php esc_attr_e('Expand chat', 'memberpress-ai-assistant'); ?>">
                    <span class="dashicons dashicons-editor-expand"></span>
                </button>
                <button class="mpai-chat-close" id="mpai-chat-close" aria-label="<?php esc_attr_e('Close chat', 'memberpress-ai-assistant'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            
            <div class="mpai-chat-messages" id="mpai-chat-messages">
                <div class="mpai-chat-welcome">
                    <div class="mpai-chat-message mpai-chat-message-assistant">
                        <div class="mpai-chat-message-content">
                            <?php esc_html_e('Hello! I\'m your MemberPress AI Assistant. How can I help you today?', 'memberpress-ai-assistant'); ?>
                        </div>
                    </div>
                </div>
                <!-- Chat messages will be dynamically inserted here -->
            </div>
            
            <div class="mpai-chat-input-container">
                <div class="mpai-chat-input-wrapper">
                    <textarea
                        id="mpai-chat-input"
                        class="mpai-chat-input"
                        placeholder="<?php esc_attr_e('Type your message here...', 'memberpress-ai-assistant'); ?>"
                        rows="1"
                        aria-label="<?php esc_attr_e('Message input', 'memberpress-ai-assistant'); ?>"
                    ></textarea>
                    <button
                        id="mpai-chat-submit"
                        class="mpai-chat-submit"
                        aria-label="<?php esc_attr_e('Send message', 'memberpress-ai-assistant'); ?>"
                    >
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>
                <div class="mpai-chat-footer">
                    <span class="mpai-chat-powered-by">
                        <?php esc_html_e('Powered by MemberPress AI', 'memberpress-ai-assistant'); ?>
                    </span>
                    <div class="mpai-chat-footer-actions">
                        <a href="#" id="mpai-clear-conversation" class="mpai-clear-conversation">
                            <?php esc_html_e('Clear Conversation', 'memberpress-ai-assistant'); ?>
                        </a>
                        <button id="mpai-download-conversation" class="mpai-download-conversation" aria-label="<?php esc_attr_e('Download conversation', 'memberpress-ai-assistant'); ?>" title="<?php esc_attr_e('Download conversation', 'memberpress-ai-assistant'); ?>">
                            <span class="dashicons dashicons-download"></span>
                        </button>
                        <button id="mpai-run-command" class="mpai-run-command" aria-label="<?php esc_attr_e('Show common commands', 'memberpress-ai-assistant'); ?>" title="<?php esc_attr_e('Show common commands', 'memberpress-ai-assistant'); ?>">
                            <span class="dashicons dashicons-admin-tools"></span>
                        </button>
                    </div>
                </div>
                
                <!-- Add this after the chat-footer div -->
                <div id="mpai-export-format-menu" class="mpai-export-format-menu" style="display: none;">
                    <div class="mpai-export-format-title"><?php esc_html_e('Export Format', 'memberpress-ai-assistant'); ?></div>
                    <div class="mpai-export-format-options">
                        <button class="mpai-export-format-btn" data-format="html"><?php esc_html_e('HTML', 'memberpress-ai-assistant'); ?></button>
                        <button class="mpai-export-format-btn" data-format="markdown"><?php esc_html_e('Markdown', 'memberpress-ai-assistant'); ?></button>
                    </div>
                </div>
            </div>
            
            <!-- Command runner panel (initially hidden) -->
            <div id="mpai-command-runner" class="mpai-command-runner" style="display: none;">
                <div class="mpai-command-header">
                    <h4><?php esc_html_e('Common Commands', 'memberpress-ai-assistant'); ?></h4>
                    <button id="mpai-command-close" class="mpai-command-close" aria-label="<?php esc_attr_e('Close command panel', 'memberpress-ai-assistant'); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="mpai-command-body">
                    <div class="mpai-command-list">
                        <h5><?php esc_html_e('MemberPress', 'memberpress-ai-assistant'); ?></h5>
                        <ul>
                            <li><a href="#" class="mpai-command-item" data-command="List all active memberships">List all active memberships</a></li>
                            <li><a href="#" class="mpai-command-item" data-command="Show recent transactions">Show recent transactions</a></li>
                            <li><a href="#" class="mpai-command-item" data-command="Summarize membership data">Summarize membership data</a></li>
                        </ul>
                    </div>
                    <div class="mpai-command-list">
                        <h5><?php esc_html_e('WordPress', 'memberpress-ai-assistant'); ?></h5>
                        <ul>
                            <li><a href="#" class="mpai-command-item" data-command="wp plugin list">wp plugin list</a></li>
                            <li><a href="#" class="mpai-command-item" data-command="wp user list">wp user list</a></li>
                            <li><a href="#" class="mpai-command-item" data-command="wp post list">wp post list</a></li>
                        </ul>
                    </div>
                    <div class="mpai-command-list">
                        <h5><?php esc_html_e('Content Creation', 'memberpress-ai-assistant'); ?></h5>
                        <ul>
                            <li><a href="#" class="mpai-command-item" data-command="Create a blog post about">Create a blog post</a></li>
                            <li><a href="#" class="mpai-command-item" data-command="Create a page about">Create a page</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat toggle button (fixed position) -->
        <button id="mpai-chat-toggle" class="mpai-chat-toggle mpai-chat-toggle-<?php echo esc_attr(str_replace('_', '-', $chat_position)); ?>" data-position="<?php echo esc_attr($chat_position); ?>" aria-label="<?php esc_attr_e('Toggle chat', 'memberpress-ai-assistant'); ?>">
            <span class="dashicons dashicons-format-chat"></span>
        </button>

        <!-- Add direct script loading for blog formatter -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Function to process existing messages (reduced logging)
                function processExistingMessages() {
                    $('.mpai-chat-message-assistant').each(function() {
                        const $message = $(this);
                        const content = $message.find('.mpai-chat-message-content').text();
                        
                        if (content && (
                            content.includes('<wp-post>') ||
                            content.includes('</wp-post>') ||
                            content.includes('<post-title>') ||
                            content.includes('</post-title>') ||
                            content.includes('<post-content>') ||
                            content.includes('</post-content>')
                        )) {
                            if (window.MPAI_BlogFormatter) {
                                window.MPAI_BlogFormatter.processAssistantMessage($message, content);
                            }
                        }
                    });
                }
                
                // Check if blog formatter is available
                if (window.MPAI_BlogFormatter) {
                    window.MPAI_BlogFormatter.init();
                    setTimeout(processExistingMessages, 1000);
                } else {
                    // Create script element
                    var script = document.createElement('script');
                    script.src = '<?php echo esc_url(MPAI_PLUGIN_URL . 'assets/js/blog-formatter.js'); ?>';
                    script.onload = function() {
                        if (window.MPAI_BlogFormatter) {
                            window.MPAI_BlogFormatter.init();
                            setTimeout(processExistingMessages, 1000);
                        }
                    };
                    document.head.appendChild(script);
                }
                
                // Set up a mutation observer to watch for new messages (reduced logging)
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) {
                                    const $node = $(node);
                                    if ($node.hasClass('mpai-chat-message-assistant') || $node.find('.mpai-chat-message-assistant').length > 0) {
                                        setTimeout(processExistingMessages, 500);
                                    }
                                }
                            });
                        }
                    });
                });
                
                // Start observing the chat container
                const chatContainer = document.querySelector('.mpai-chat-messages');
                if (chatContainer) {
                    observer.observe(chatContainer, { childList: true, subtree: true });
                }
            });
        </script>
        <?php
        
        LoggingUtility::debug('ChatInterface: Chat container HTML rendered directly');
    }

    /**
     * Register REST API routes for the chat interface
     */
    public function registerRestRoutes() {
        register_rest_route('memberpress-ai/v1', '/chat', [
            'methods' => 'POST',
            'callback' => [$this, 'processChatRequest'],
            'permission_callback' => [$this, 'checkChatPermissions'],
            'args' => [
                'message' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'conversation_id' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /**
     * Process a chat request from the REST API
     *
     * @param \WP_REST_Request $request The REST request
     * @return \WP_REST_Response The REST response
     */
    public function processChatRequest($request) {
        // Get request parameters
        $message = $request->get_param('message');
        $conversation_id = $request->get_param('conversation_id');
        $load_history = (bool)$request->get_param('load_history');
        $clear_history = (bool)$request->get_param('clear_history');
        $user_logged_in = (bool)$request->get_param('user_logged_in');
        
        // Check if this is a blog post request
        $isBlogPostRequest = false;
        $isMembershipRequest = false;
        
        if ($message && (
            stripos($message, 'blog post') !== false ||
            stripos($message, 'create post') !== false ||
            stripos($message, 'write a post') !== false ||
            stripos($message, 'write a blog') !== false
        )) {
            $isBlogPostRequest = true;
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Blog post request detected: ' . $message);
            
            // Enhance the prompt with XML formatting instructions
            $message = $this->enhanceBlogPostPrompt($message);
            
            // Force using Anthropic for blog posts
            add_filter('mpai_override_provider', function() {
                return 'anthropic';
            });
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Using Anthropic for blog post generation');
        }
        // Check if this is a membership creation request
        elseif ($message && (
            stripos($message, 'create membership') !== false ||
            stripos($message, 'add membership') !== false ||
            stripos($message, 'new membership') !== false ||
            (stripos($message, 'membership') !== false && (
                stripos($message, 'create') !== false ||
                stripos($message, 'add') !== false ||
                stripos($message, 'new') !== false
            ))
        )) {
            $isMembershipRequest = true;
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Membership creation request detected: ' . $message);
            
            // Force using agent orchestrator for membership requests
            add_filter('mpai_force_agent_orchestrator', '__return_true');
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Forcing agent orchestrator for membership creation');
        }
        
        // Get the current user ID if logged in
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;

        // Enable error reporting for debugging
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        try {
            // Log the request for debugging
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Chat request received: ' . $message);
            
            // Handle clear history request
            if ($clear_history && !empty($conversation_id) && $user_id > 0) {
                \MemberpressAiAssistant\Utilities\LoggingUtility::info('Clearing history for conversation: ' . $conversation_id);
                $this->clearUserConversationHistory($user_id, $conversation_id);
                
                // Return success response
                return rest_ensure_response([
                    'status' => 'success',
                    'message' => 'History cleared successfully',
                    'conversation_id' => null,
                    'timestamp' => time()
                ]);
            }
            
            // For logged-in users, try to get their conversation ID from user metadata
            if ($user_id > 0 && empty($conversation_id)) {
                $saved_conversation_id = $this->getUserConversationId($user_id);
                if ($saved_conversation_id) {
                    $conversation_id = $saved_conversation_id;
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Using saved conversation ID for user: ' . $conversation_id);
                }
            }
            
            // Get the service locator
            global $mpai_service_locator;
            
            // Log service locator status
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Service locator available: ' . (isset($mpai_service_locator) ? 'Yes' : 'No'));
            
            if (!isset($mpai_service_locator)) {
                throw new \Exception('Service locator not available');
            }
            
            // Load context if conversation_id is provided
            if (!empty($conversation_id) && $mpai_service_locator->has('agent_orchestrator')) {
                $orchestrator = $mpai_service_locator->get('agent_orchestrator');
                $contextManager = $orchestrator->getContextManager();
                
                // Try to load context for this conversation
                $contextManager->loadContext('conversation_' . $conversation_id);
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Loaded context for conversation: ' . $conversation_id);
                
                // If this is just a history load request, return the history without processing a message
                if ($load_history) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Processing history load request for conversation: ' . $conversation_id);
                    
                    $history = [];
                    $rawHistory = $contextManager->getConversationHistory($conversation_id);
                    
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Raw history: ' . ($rawHistory ? 'Available' : 'Not available') .
                              ($rawHistory ? ' (' . count($rawHistory) . ' items)' : ''));
                    
                    if (is_array($rawHistory)) {
                        foreach ($rawHistory as $historyItem) {
                            if (isset($historyItem['sender'], $historyItem['content'])) {
                                $history[] = [
                                    'role' => $historyItem['sender'] === 'user' ? 'user' : 'assistant',
                                    'content' => $historyItem['content'],
                                    'timestamp' => $historyItem['timestamp'] ?? time()
                                ];
                            }
                        }
                        
                        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Processed history: ' . count($history) . ' items');
                        \MemberpressAiAssistant\Utilities\LoggingUtility::trace('First history item structure: ' . json_encode(array_keys(reset($rawHistory) ?: [])));
                    } else {
                        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('No raw history available to process');
                    }
                    
                    $response = [
                        'status' => 'success',
                        'message' => '',
                        'conversation_id' => $conversation_id,
                        'timestamp' => time(),
                        'history' => $history
                    ];
                    
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Returning history response with ' . count($history) . ' items');
                    return rest_ensure_response($response);
                }
            }
            
            // Check if we should use the agent orchestrator directly
            $useAgentOrchestrator = false;
            
            // Force agent orchestrator for membership requests
            if ($isMembershipRequest || apply_filters('mpai_force_agent_orchestrator', false)) {
                $useAgentOrchestrator = true;
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Forcing agent orchestrator for membership request');
            } else {
                // Always use LLM services first for non-membership requests
                $useAgentOrchestrator = false;
                \MemberpressAiAssistant\Utilities\LoggingUtility::info('Using LLM services for all queries, including WordPress-related ones.');
            }
            
            // Try to use the LLM services first
            if ($mpai_service_locator->has('llm.chat_adapter') && !$useAgentOrchestrator) {
                try {
                    // Get the LLM chat adapter
                    $chatAdapter = $mpai_service_locator->get('llm.chat_adapter');
                    
                    // Process the request with the LLM chat adapter
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Processing request with LLM chat adapter', [
                        'message' => $message,
                        'processing_path' => 'LLM_CHAT_ADAPTER'
                    ]);
                    $response = $chatAdapter->processRequest($message, $conversation_id);
                    
                    // Log raw LLM response before any formatting
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Raw LLM response received', [
                        'response_structure' => array_keys($response),
                        'has_data' => isset($response['data']),
                        'has_message' => isset($response['message']),
                        'message_preview' => isset($response['message']) ? substr($response['message'], 0, 200) : 'NO_MESSAGE',
                        'contains_json_wrapper' => isset($response['message']) ? (strpos($response['message'], '```json') !== false || strpos($response['message'], '```') !== false) : false,
                        'starts_with_json_brace' => isset($response['message']) ? (trim($response['message'])[0] === '{') : false
                    ]);
                    
                    // Check if the response contains an error message
                    if (isset($response['status']) && $response['status'] === 'error') {
                        \MemberpressAiAssistant\Utilities\LoggingUtility::error('LLM chat adapter returned error: ' . ($response['debug_message'] ?? 'Unknown error'));
                        throw new \Exception('LLM chat adapter error: ' . ($response['debug_message'] ?? 'Unknown error'));
                    }
                    
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('LLM chat adapter response received');
                    \MemberpressAiAssistant\Utilities\LoggingUtility::trace('Response: ' . json_encode($response));
                    
                    // Get the conversation ID from the response or use the existing one
                    $conversation_id = $response['conversation_id'] ?? $conversation_id;
                    
                    // Save conversation ID for logged-in users
                    if ($user_id > 0 && !empty($conversation_id)) {
                        $this->saveUserConversationId($user_id, $conversation_id);
                        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Saved conversation ID for user: ' . $conversation_id);
                    }
                    
                    // Get conversation history
                    $history = [];
                    if (!empty($conversation_id) && $mpai_service_locator->has('agent_orchestrator')) {
                        $orchestrator = $mpai_service_locator->get('agent_orchestrator');
                        $contextManager = $orchestrator->getContextManager();
                        
                        // Get conversation history
                        $rawHistory = $contextManager->getConversationHistory($conversation_id);
                        if (is_array($rawHistory)) {
                            foreach ($rawHistory as $historyItem) {
                                if (isset($historyItem['sender'], $historyItem['content'])) {
                                    $history[] = [
                                        'role' => $historyItem['sender'] === 'user' ? 'user' : 'assistant',
                                        'content' => $historyItem['content'],
                                        'timestamp' => $historyItem['timestamp'] ?? time()
                                    ];
                                }
                            }
                            
                            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Processed history items: ' . count($history));
                        }
                        
                        // Persist context after processing
                        $contextManager->persistContext('conversation_' . $conversation_id);
                        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Persisted context for conversation: ' . $conversation_id);
                    }
                    
                    // Format plugin list as a table if this is a plugin list response
                    if (isset($response['data']) && isset($response['data']['plugins']) && is_array($response['data']['plugins'])) {
                        $response['message'] = $this->formatPluginListAsTable($response['data']['plugins'], $response['data']);
                    }
                    // Format post list as a table if this is a post list response
                    else if (isset($response['data']) && isset($response['data']['posts']) && is_array($response['data']['posts'])) {
                        $response['message'] = $this->formatPostListAsTable($response['data']['posts'], $response['data']);
                    }
                    // Format page list as a table if this is a page list response
                    else if (isset($response['data']) && isset($response['data']['pages']) && is_array($response['data']['pages'])) {
                        $response['message'] = $this->formatPageListAsTable($response['data']['pages'], $response['data']);
                    }
                    // Format comment list as a table if this is a comment list response
                    else if (isset($response['data']) && isset($response['data']['comments']) && is_array($response['data']['comments'])) {
                        $response['message'] = $this->formatCommentListAsTable($response['data']['comments'], $response['data']);
                    }
                    // Format membership list as a table if this is a membership list response
                    else if (isset($response['data']) && isset($response['data']['memberships']) && is_array($response['data']['memberships'])) {
                        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Applying membership table formatting (LLM path)', [
                            'membership_count' => count($response['data']['memberships']),
                            'original_message_preview' => isset($response['message']) ? substr($response['message'], 0, 100) : 'NO_MESSAGE'
                        ]);
                        $response['message'] = $this->formatMembershipListAsTable($response['data']['memberships'], $response['data']);
                    }
                    // Format membership level list as a table if this is a membership level list response
                    else if (isset($response['data']) && isset($response['data']['levels']) && is_array($response['data']['levels'])) {
                        $response['message'] = $this->formatMembershipLevelListAsTable($response['data']['levels'], $response['data']);
                    }
                    // Format user list as a table if this is a user list response
                    else if (isset($response['data']) && isset($response['data']['users']) && is_array($response['data']['users'])) {
                        $response['message'] = $this->formatUserListAsTable($response['data']['users'], $response['data']);
                    }
                    
                    // Add history to the response
                    $response['history'] = $history;
                    
                    // Return the response
                    return rest_ensure_response($response);
                } catch (\Exception $e) {
                    // Log the error
                    \MemberpressAiAssistant\Utilities\LoggingUtility::warning('Error using LLM chat adapter: ' . $e->getMessage());
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Error details: ' . $e->getTraceAsString());
                    \MemberpressAiAssistant\Utilities\LoggingUtility::info('Falling back to agent orchestrator');
                    
                    // Fall back to the agent orchestrator
                    $useAgentOrchestrator = true;
                }
            }
            
            // Fall back to the agent orchestrator
            if ($useAgentOrchestrator || $mpai_service_locator->has('agent_orchestrator')) {
                // Get the orchestrator service
                $orchestrator = $mpai_service_locator->get('agent_orchestrator');
                
                // Process the request
                $request_data = [
                    'message' => $message,
                ];
                
                // Use the orchestrator to process the request
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Processing request with orchestrator', [
                    'message' => $message,
                    'processing_path' => 'AGENT_ORCHESTRATOR'
                ]);
                $response = $orchestrator->processUserRequest($request_data, $conversation_id);
                
                // Log raw orchestrator response before any formatting
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Raw orchestrator response received', [
                    'response_structure' => array_keys($response),
                    'has_data' => isset($response['data']),
                    'has_message' => isset($response['message']),
                    'message_preview' => isset($response['message']) ? substr($response['message'], 0, 200) : 'NO_MESSAGE'
                ]);
                \MemberpressAiAssistant\Utilities\LoggingUtility::trace('Orchestrator response: ' . json_encode($response));
                
                // Get the conversation ID from the response or use the existing one
                $conversation_id = $response['conversation_id'] ?? $conversation_id;
                
                // Save conversation ID for logged-in users
                if ($user_id > 0 && !empty($conversation_id)) {
                    $this->saveUserConversationId($user_id, $conversation_id);
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Saved conversation ID for user: ' . $conversation_id);
                }
                
                // Get conversation history
                $history = [];
                if (!empty($conversation_id)) {
                    $contextManager = $orchestrator->getContextManager();
                    
                    // Get conversation history
                    $rawHistory = $contextManager->getConversationHistory($conversation_id);
                    if (is_array($rawHistory)) {
                        foreach ($rawHistory as $historyItem) {
                            if (isset($historyItem['sender'], $historyItem['content'])) {
                                $history[] = [
                                    'role' => $historyItem['sender'] === 'user' ? 'user' : 'assistant',
                                    'content' => $historyItem['content'],
                                    'timestamp' => $historyItem['timestamp'] ?? time()
                                ];
                            }
                        }
                        
                        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Processed history items: ' . count($history));
                    }
                    
                    // Persist context after processing
                    $contextManager->persistContext('conversation_' . $conversation_id);
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Persisted context for conversation: ' . $conversation_id);
                }
                
                // Format plugin list as a table if this is a plugin list response
                $message = $response['message'] ?? $response['content'] ?? 'No response message';
                
                // Check if this is a plugin list response
                if (isset($response['data']) && isset($response['data']['plugins']) && is_array($response['data']['plugins'])) {
                    $message = $this->formatPluginListAsTable($response['data']['plugins'], $response['data']);
                }
                // Format post list as a table if this is a post list response
                else if (isset($response['data']) && isset($response['data']['posts']) && is_array($response['data']['posts'])) {
                    $message = $this->formatPostListAsTable($response['data']['posts'], $response['data']);
                }
                // Format page list as a table if this is a page list response
                else if (isset($response['data']) && isset($response['data']['pages']) && is_array($response['data']['pages'])) {
                    $message = $this->formatPageListAsTable($response['data']['pages'], $response['data']);
                }
                // Format comment list as a table if this is a comment list response
                else if (isset($response['data']) && isset($response['data']['comments']) && is_array($response['data']['comments'])) {
                    $message = $this->formatCommentListAsTable($response['data']['comments'], $response['data']);
                }
                // Format membership list as a table if this is a membership list response
                else if (isset($response['data']) && isset($response['data']['memberships']) && is_array($response['data']['memberships'])) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Applying membership table formatting (orchestrator path)', [
                        'membership_count' => count($response['data']['memberships']),
                        'original_message_preview' => substr($message, 0, 100)
                    ]);
                    $message = $this->formatMembershipListAsTable($response['data']['memberships'], $response['data']);
                }
                // Format membership level list as a table if this is a membership level list response
                else if (isset($response['data']) && isset($response['data']['levels']) && is_array($response['data']['levels'])) {
                    $message = $this->formatMembershipLevelListAsTable($response['data']['levels'], $response['data']);
                }
                // Format user list as a table if this is a user list response
                else if (isset($response['data']) && isset($response['data']['users']) && is_array($response['data']['users'])) {
                    $message = $this->formatUserListAsTable($response['data']['users'], $response['data']);
                }
                
                // Return the response with history
                return rest_ensure_response([
                    'status' => 'success',
                    'message' => $message,
                    'conversation_id' => $conversation_id,
                    'timestamp' => time(),
                    'history' => $history
                ]);
            } else {
                // Fallback to test response if no services are available
                \MemberpressAiAssistant\Utilities\LoggingUtility::warning('No chat services available, using fallback response');
                $response = [
                    'status' => 'success',
                    'message' => 'This is a test response from the chat interface. Your message was: ' . $message,
                    'conversation_id' => $conversation_id ?: 'new_conversation_' . time(),
                    'timestamp' => time(),
                ];
                
                return rest_ensure_response($response);
            }
        } catch (\Exception $e) {
            // Log the error
            \MemberpressAiAssistant\Utilities\LoggingUtility::error('Chat Error: ' . $e->getMessage());
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Error trace: ' . $e->getTraceAsString());

            // Return error response
            return new \WP_Error(
                'mpai_chat_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Check if the user has permission to use the chat
     *
     * @param \WP_REST_Request $request The REST request
     * @return bool|WP_Error True if the user has permission, WP_Error otherwise
     */
    public function checkChatPermissions($request) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'mpai_not_logged_in',
                __('You must be logged in to use the chat.', 'memberpress-ai-assistant'),
                ['status' => 401]
            );
        }

        // Check if user has access to MemberPress content
        // This can be customized based on your specific requirements
        if (function_exists('current_user_can') && !current_user_can('read')) {
            return new \WP_Error(
                'mpai_insufficient_permissions',
                __('You do not have permission to use the chat.', 'memberpress-ai-assistant'),
                ['status' => 403]
            );
        }
        
        LoggingUtility::debug('ChatInterface: REST API access granted - basic authentication passed');
        return true;
    }

    /**
     * Check if the chat interface should be loaded on the current page
     *
     * @return bool True if the chat interface should be loaded
     */
    private function shouldLoadChatInterface() {
        // Don't load in admin
        if (is_admin()) {
            return false;
        }

        // Don't load in REST API requests
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }

        // Don't load in AJAX requests
        if (wp_doing_ajax()) {
            return false;
        }

        // Check the chat location setting to determine if frontend loading is allowed
        global $mpai_service_locator;
        
        $chat_location = 'admin_only'; // Default fallback
        
        // Try to get the chat location setting from the settings model
        if (isset($mpai_service_locator) && $mpai_service_locator->has('settings.model')) {
            try {
                $settings_model = $mpai_service_locator->get('settings.model');
                $chat_location = $settings_model->get_chat_location();
            } catch (\Exception $e) {
                LoggingUtility::warning('ChatInterface: Failed to get settings model: ' . $e->getMessage());
            }
        } else {
            // Fallback to direct option access
            $raw_settings = get_option('mpai_settings', []);
            if (isset($raw_settings['chat_location'])) {
                $chat_location = $raw_settings['chat_location'];
            }
        }
        
        // Apply logic based on chat location setting
        switch ($chat_location) {
            case 'admin_only':
                // Don't show on frontend when "Admin area only" is selected
                return false;
                
            case 'frontend':
                // Show on frontend when "Frontend only" is selected
                return true;
                
            case 'both':
                // Show on frontend when "Both" is selected
                return true;
                
            default:
                // Unknown setting - default to not showing on frontend for security
                LoggingUtility::warning('ChatInterface: Unknown chat_location setting: ' . $chat_location);
                return false;
        }
    }

    /**
     * Check if the chat interface should be loaded on the current admin page
     *
     * @param string $hook_suffix The current admin page hook suffix
     * @return bool True if the chat interface should be loaded
     */
    private function shouldLoadAdminChatInterface($hook_suffix) {
        // First, check the chat location setting
        global $mpai_service_locator;
        
        $chat_location = 'admin_only'; // Default fallback
        $settings_available = false;
        
        // Try to get the chat location setting from the settings model
        if (isset($mpai_service_locator) && $mpai_service_locator->has('settings.model')) {
            try {
                $settings_model = $mpai_service_locator->get('settings.model');
                $chat_location = $settings_model->get_chat_location();
                $settings_available = true;
                
                LoggingUtility::debug('ChatInterface: Retrieved chat_location setting: ' . $chat_location);
            } catch (\Exception $e) {
                LoggingUtility::warning('ChatInterface: Failed to get settings model: ' . $e->getMessage());
            }
        }
        
        // If settings not available, try direct option access as fallback
        if (!$settings_available) {
            $raw_settings = get_option('mpai_settings', []);
            if (isset($raw_settings['chat_location'])) {
                $chat_location = $raw_settings['chat_location'];
                LoggingUtility::debug('ChatInterface: Retrieved chat_location from raw settings: ' . $chat_location);
            } else {
                LoggingUtility::debug('ChatInterface: No chat_location setting found, using default: ' . $chat_location);
            }
        }
        
        // Apply logic based on chat location setting
        switch ($chat_location) {
            case 'admin_only':
                // Show on all admin pages when "Admin area only" is selected
                $should_load = is_admin() && !wp_doing_ajax() && !defined('DOING_CRON');
                $reason = 'admin_only setting - allowed on all admin pages';
                break;
                
            case 'frontend':
                // Don't show on admin pages when "Frontend only" is selected
                $should_load = false;
                $reason = 'frontend setting - not allowed on admin pages';
                break;
                
            case 'both':
                // Show on admin pages when "Both" is selected
                $should_load = is_admin() && !wp_doing_ajax() && !defined('DOING_CRON');
                $reason = 'both setting - allowed on admin pages';
                break;
                
            default:
                // Fallback to hardcoded page restrictions for unknown settings
                $allowed_pages = [
                    'memberpress_page_mpai-settings',  // Settings page under MemberPress menu
                    'toplevel_page_mpai-settings',     // Settings page as top-level menu
                    'admin_page_mpai-welcome',         // Welcome page (hidden)
                    'memberpress_page_mpai-welcome',   // Welcome page under MemberPress menu
                ];
                
                // Also check current page parameter
                $current_page = isset($_GET['page']) ? $_GET['page'] : '';
                $allowed_page_params = ['mpai-settings', 'mpai-welcome'];
                
                $should_load = in_array($hook_suffix, $allowed_pages) || in_array($current_page, $allowed_page_params);
                $reason = 'unknown setting - using hardcoded page restrictions';
                break;
        }
        
        // Additional safety checks for admin context
        if ($should_load) {
            // Don't load during AJAX requests (except our own)
            if (wp_doing_ajax() && (!isset($_REQUEST['action']) || strpos($_REQUEST['action'], 'mpai_') !== 0)) {
                $should_load = false;
                $reason = 'blocked - AJAX request';
            }
            
            // Don't load during cron jobs
            if (defined('DOING_CRON') && DOING_CRON) {
                $should_load = false;
                $reason = 'blocked - cron job';
            }
            
            // Don't load during REST API requests
            if (defined('REST_REQUEST') && REST_REQUEST) {
                $should_load = false;
                $reason = 'blocked - REST API request';
            }
        }
        
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';
        
        LoggingUtility::debug('ChatInterface: shouldLoadAdminChatInterface() decision', [
            'hook_suffix' => $hook_suffix,
            'current_page' => $current_page,
            'chat_location_setting' => $chat_location,
            'should_load' => $should_load ? 'YES' : 'NO',
            'reason' => $reason,
            'is_admin' => is_admin(),
            'wp_doing_ajax' => wp_doing_ajax(),
            'settings_available' => $settings_available
        ]);
        
        return $should_load;
    }


    /**
     * Get the chat configuration
     *
     * @param bool $is_admin Whether the chat is being loaded in admin
     * @return array The chat configuration
     */
    private function getChatConfig($is_admin = false) {
        $config = [
            'apiEndpoint' => rest_url('memberpress-ai/v1/chat'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'maxMessages' => 50,
            'autoOpen' => false,
            'welcomePageUrl' => admin_url('admin.php?page=mpai-welcome'), // Add welcome page URL
        ];
        
        // Add user login status to the config
        wp_localize_script('mpai-chat', 'mpai_user_logged_in', is_user_logged_in());
        
        // If user is logged in, get their conversation ID
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $conversation_id = $this->getUserConversationId($user_id);
            if ($conversation_id) {
                $config['conversationId'] = $conversation_id;
            }
        }

        // Allow filtering
        return apply_filters('mpai_chat_config', $config, $is_admin);
    }
    
    /**
     * Get the user's conversation ID from user metadata
     *
     * @param int $user_id The user ID
     * @return string|null The conversation ID or null if not found
     */
    private function getUserConversationId($user_id) {
        return get_user_meta($user_id, 'mpai_conversation_id', true);
    }
    
    /**
     * Save the user's conversation ID to user metadata
     *
     * @param int $user_id The user ID
     * @param string $conversation_id The conversation ID
     * @return bool True if successful, false otherwise
     */
    private function saveUserConversationId($user_id, $conversation_id) {
        return update_user_meta($user_id, 'mpai_conversation_id', $conversation_id);
    }
    
    /**
     * Clear the user's conversation history
     *
     * @param int $user_id The user ID
     * @param string $conversation_id The conversation ID to clear
     * @return bool True if successful, false otherwise
     */
    private function clearUserConversationHistory($user_id, $conversation_id) {
        // Delete the user's conversation ID from metadata
        delete_user_meta($user_id, 'mpai_conversation_id');
        
        // If we have a context manager, clear the conversation context
        global $mpai_service_locator;
        if (isset($mpai_service_locator) && $mpai_service_locator->has('agent_orchestrator')) {
            $orchestrator = $mpai_service_locator->get('agent_orchestrator');
            $contextManager = $orchestrator->getContextManager();
            
            // Clear the conversation context
            return $contextManager->clearConversationContext($conversation_id);
        }
        
        return true;
    }
    
    /**
     * Format plugin list as a nice-looking table
     *
     * @param array $plugins List of plugins
     * @param array $summary Summary data
     * @return string Formatted table
     */
    /**
     * Format plugin list as a nice-looking table
     *
     * @param array $plugins List of plugins
     * @param array $summary Summary data
     * @return string Formatted table
     */
    private function formatPluginListAsTable(array $plugins, array $summary): string {
        return \MemberpressAiAssistant\Utilities\TableFormatter::formatPluginList($plugins, [
            'format' => \MemberpressAiAssistant\Utilities\TableFormatter::FORMAT_HTML,
            'summary' => $summary
        ]);
    }
    
    /**
     * Format post list as a nice-looking table
     *
     * @param array $posts List of posts
     * @param array $summary Summary data
     * @return string Formatted table
     */
    private function formatPostListAsTable(array $posts, array $summary): string {
        return \MemberpressAiAssistant\Utilities\TableFormatter::formatPostList($posts, [
            'format' => \MemberpressAiAssistant\Utilities\TableFormatter::FORMAT_HTML,
            'summary' => $summary
        ]);
    }
    
    /**
     * Format page list as a nice-looking table
     *
     * @param array $pages List of pages
     * @param array $summary Summary data
     * @return string Formatted table
     */
    private function formatPageListAsTable(array $pages, array $summary): string {
        return \MemberpressAiAssistant\Utilities\TableFormatter::formatPageList($pages, [
            'format' => \MemberpressAiAssistant\Utilities\TableFormatter::FORMAT_HTML,
            'summary' => $summary
        ]);
    }
    
    /**
     * Format comment list as a nice-looking table
     *
     * @param array $comments List of comments
     * @param array $summary Summary data
     * @return string Formatted table
     */
    private function formatCommentListAsTable(array $comments, array $summary): string {
        return \MemberpressAiAssistant\Utilities\TableFormatter::formatCommentList($comments, [
            'format' => \MemberpressAiAssistant\Utilities\TableFormatter::FORMAT_HTML,
            'summary' => $summary
        ]);
    }
    
    /**
     * Format membership list as a nice-looking table
     *
     * @param array $memberships List of memberships
     * @param array $summary Summary data
     * @return string Formatted table
     */
    private function formatMembershipListAsTable(array $memberships, array $summary): string {
        return \MemberpressAiAssistant\Utilities\TableFormatter::formatMembershipList($memberships, [
            'format' => \MemberpressAiAssistant\Utilities\TableFormatter::FORMAT_HTML,
            'summary' => $summary
        ]);
    }
    
    /**
     * Format user list as a nice-looking table
     *
     * @param array $users List of users
     * @param array $summary Summary data
     * @return string Formatted table
     */
    private function formatUserListAsTable(array $users, array $summary): string {
        return \MemberpressAiAssistant\Utilities\TableFormatter::formatUserList($users, [
            'format' => \MemberpressAiAssistant\Utilities\TableFormatter::FORMAT_HTML,
            'summary' => $summary
        ]);
    }
    
    /**
     * Format membership level list as a nice-looking table
     *
     * @param array $levels List of membership levels
     * @param array $summary Summary data
     * @return string Formatted table
     */
    private function formatMembershipLevelListAsTable(array $levels, array $summary): string {
        return \MemberpressAiAssistant\Utilities\TableFormatter::formatMembershipLevelList($levels, [
            'format' => \MemberpressAiAssistant\Utilities\TableFormatter::FORMAT_HTML,
            'summary' => $summary
        ]);
    }
/**
     * Enhance a blog post prompt with XML formatting instructions
     *
     * @param string $message The original message
     * @return string The enhanced message
     */
    private function enhanceBlogPostPrompt($message) {
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Enhancing blog post prompt: ' . $message);
        
        $enhancedPrompt = $message . "\n\n";
        $enhancedPrompt .= "I need you to write a blog post in XML format. This is VERY IMPORTANT - the output MUST be wrapped in XML tags EXACTLY as shown in this example. The format must be exactly like this, with no deviations:\n\n";
        $enhancedPrompt .= "```xml\n";
        $enhancedPrompt .= "<wp-post>\n";
        $enhancedPrompt .= "  <post-title>Title of the blog post</post-title>\n";
        $enhancedPrompt .= "  <post-content>\n";
        $enhancedPrompt .= "    <block type=\"paragraph\">Introduction paragraph here.</block>\n";
        $enhancedPrompt .= "    <block type=\"heading\" level=\"2\">First Section Heading</block>\n";
        $enhancedPrompt .= "    <block type=\"paragraph\">Content of the first section.</block>\n";
        $enhancedPrompt .= "    <block type=\"paragraph\">Another paragraph with content.</block>\n";
        $enhancedPrompt .= "    <block type=\"heading\" level=\"2\">Second Section Heading</block>\n";
        $enhancedPrompt .= "    <block type=\"paragraph\">Content for this section.</block>\n";
        $enhancedPrompt .= "    <block type=\"list\">\n";
        $enhancedPrompt .= "      <item>First list item</item>\n";
        $enhancedPrompt .= "      <item>Second list item</item>\n";
        $enhancedPrompt .= "      <item>Third list item</item>\n";
        $enhancedPrompt .= "    </block>\n";
        $enhancedPrompt .= "  </post-content>\n";
        $enhancedPrompt .= "  <post-excerpt>A brief summary of the post.</post-excerpt>\n";
        $enhancedPrompt .= "  <post-status>draft</post-status>\n";
        $enhancedPrompt .= "</wp-post>\n";
        $enhancedPrompt .= "```\n\n";
        $enhancedPrompt .= "The XML structure is required for proper WordPress integration. IMPORTANT: The opening and closing tags must be exactly <wp-post> and </wp-post>. Please ensure the XML is not inside any additional code blocks or formatting - just keep the exact format shown above, with the same indentation patterns. The content must be complete and well-formed.";
        
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('Enhanced blog post prompt created');
        
        return $enhancedPrompt;
    }
}