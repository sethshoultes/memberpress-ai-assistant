<?php
/**
 * MemberPress AI Assistant Post Handler
 *
 * Handles AJAX requests for creating posts from the chat interface
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin;

use MemberpressAiAssistant\Utilities\LoggingUtility;

/**
 * Class MPAIPostHandler
 */
class MPAIPostHandler implements \MemberpressAiAssistant\Interfaces\ServiceInterface {
    /**
     * Service name
     *
     * @var string
     */
    private $name;

    /**
     * Logger instance
     *
     * @var mixed
     */
    private $logger;

    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct($name, $logger = null) {
        $this->name = $name;
        $this->logger = $logger;
    }

    /**
     * Register the service with the service locator
     *
     * @param \MemberpressAiAssistant\DI\ServiceLocator $serviceLocator The service locator
     * @return void
     */
    public function register($serviceLocator): void {
        $serviceLocator->register($this->name, function() {
            return $this;
        });
    }

    /**
     * Boot the service
     *
     * @return void
     */
    public function boot(): void {
        // Register AJAX handlers
        add_action('wp_ajax_mpai_create_post', [$this, 'handleCreatePost']);
        add_action('wp_ajax_nopriv_mpai_create_post', [$this, 'handleCreatePostNoPriv']);
        
        if ($this->logger) {
            $this->logger->info('Post handler service booted');
        }
    }
    
    /**
     * Get the service name
     *
     * @return string
     */
    public function getServiceName(): string {
        return $this->name;
    }

    /**
     * Get the service dependencies
     *
     * @return array
     */
    public function getDependencies(): array {
        return [];
    }

    /**
     * Handle create post AJAX request for logged-in users
     */
    public function handleCreatePost() {
        // Add debug logging
        LoggingUtility::debug('Create post AJAX request received');
        LoggingUtility::debug('POST data: ' . print_r($_POST, true));
        LoggingUtility::debug('REQUEST data: ' . print_r($_REQUEST, true));
        
        // Log all available nonces for debugging
        LoggingUtility::debug('_wpnonce: ' . (isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : 'not set'));
        LoggingUtility::debug('nonce: ' . (isset($_POST['nonce']) ? $_POST['nonce'] : 'not set'));
        LoggingUtility::debug('HTTP_X_WP_NONCE: ' . (isset($_SERVER['HTTP_X_WP_NONCE']) ? $_SERVER['HTTP_X_WP_NONCE'] : 'not set'));

        // Try different nonce verification approaches
        $nonce_verified = false;
        
        // Try with _wpnonce
        if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'wp_rest')) {
            LoggingUtility::debug('Nonce verification succeeded with _wpnonce');
            $nonce_verified = true;
        }
        // Try with nonce
        else if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wp_rest')) {
            LoggingUtility::debug('Nonce verification succeeded with nonce');
            $nonce_verified = true;
        }
        // Try with HTTP_X_WP_NONCE
        else if (isset($_SERVER['HTTP_X_WP_NONCE']) && wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'], 'wp_rest')) {
            LoggingUtility::debug('Nonce verification succeeded with HTTP_X_WP_NONCE');
            $nonce_verified = true;
        }
        
        // Skip nonce verification in development environment
        if (defined('WP_DEBUG') && WP_DEBUG) {
            LoggingUtility::debug('Debug mode enabled, skipping nonce verification');
            $nonce_verified = true;
        }
        
        if (!$nonce_verified) {
            LoggingUtility::debug('All nonce verification methods failed');
            wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.']);
            return;
        }

        // Check required fields
        if (!isset($_POST['title']) || empty($_POST['title'])) {
            wp_send_json_error(['message' => 'Post title is required.']);
            return;
        }

        if (!isset($_POST['content']) || empty($_POST['content'])) {
            wp_send_json_error(['message' => 'Post content is required.']);
            return;
        }

        // Get post data
        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        $excerpt = isset($_POST['excerpt']) ? sanitize_text_field($_POST['excerpt']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'draft';
        $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'post';

        // Validate post type
        $allowed_post_types = ['post', 'page'];
        if (!in_array($post_type, $allowed_post_types)) {
            $post_type = 'post';
        }

        // Validate post status
        $allowed_statuses = ['draft', 'publish', 'pending', 'private'];
        if (!in_array($status, $allowed_statuses)) {
            $status = 'draft';
        }

        // Create post
        $post_data = [
            'post_title' => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status' => $status,
            'post_type' => $post_type,
            'post_author' => get_current_user_id(),
        ];

        LoggingUtility::debug('Creating post with data: ' . print_r($post_data, true));

        // Insert post
        $post_id = wp_insert_post($post_data);

        // Check for errors
        if (is_wp_error($post_id)) {
            LoggingUtility::error('Error creating post: ' . $post_id->get_error_message());
            wp_send_json_error(['message' => $post_id->get_error_message()]);
            return;
        }

        // Success
        LoggingUtility::debug('Post created successfully with ID: ' . $post_id);
        wp_send_json_success([
            'message' => 'Post created successfully.',
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'view_url' => get_permalink($post_id),
        ]);
    }

    /**
     * Handle create post AJAX request for non-logged-in users
     */
    public function handleCreatePostNoPriv() {
        wp_send_json_error(['message' => 'You must be logged in to create posts.']);
    }
}