<?php
/**
 * MemberPress AI Assistant Post Handler
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin;

use MemberpressAiAssistant\Abstracts\AbstractService;

/**
 * Class for handling post creation from the AI Assistant
 */
class MPAIPostHandler extends AbstractService {
    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'post_handler', $logger = null) {
        parent::__construct($name, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function register($serviceLocator): void {
        // Register this service with the service locator
        $serviceLocator->register('post_handler', function() {
            return $this;
        });
        
        // Log registration
        $this->log('Post handler service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks
        $this->addHooks();
        
        // Log boot
        $this->log('Post handler service booted');
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Add AJAX handler for creating posts
        add_action('wp_ajax_mpai_create_post', [$this, 'handle_create_post']);
    }
    
    /**
     * Handle AJAX request to create a post
     *
     * @return void
     */
    public function handle_create_post(): void {
        $this->log('Processing create post AJAX submission');
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mpai_nonce')) {
            $this->log('Create post nonce verification failed', ['error' => true]);
            wp_send_json_error(['message' => __('Security check failed.', 'memberpress-ai-assistant')]);
            return;
        }
        
        // Check if user has permission to create posts
        if (!current_user_can('edit_posts')) {
            $this->log('User does not have permission to create posts', ['error' => true]);
            wp_send_json_error(['message' => __('You do not have permission to create posts.', 'memberpress-ai-assistant')]);
            return;
        }
        
        // Get post data
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        $excerpt = isset($_POST['excerpt']) ? sanitize_text_field($_POST['excerpt']) : '';
        $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'post';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'draft';
        
        // Validate post type
        if (!in_array($post_type, ['post', 'page'])) {
            $this->log('Invalid post type: ' . $post_type, ['error' => true]);
            wp_send_json_error(['message' => __('Invalid post type.', 'memberpress-ai-assistant')]);
            return;
        }
        
        // Validate status
        if (!in_array($status, ['draft', 'publish', 'pending'])) {
            $status = 'draft'; // Default to draft if invalid
        }
        
        // Create post
        $post_data = [
            'post_title'    => $title,
            'post_content'  => $content,
            'post_excerpt'  => $excerpt,
            'post_status'   => $status,
            'post_type'     => $post_type,
            'post_author'   => get_current_user_id(),
        ];
        
        $this->log('Creating ' . $post_type . ' with title: ' . $title);
        
        // Insert the post
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            $this->log('Error creating post: ' . $post_id->get_error_message(), ['error' => true]);
            wp_send_json_error(['message' => $post_id->get_error_message()]);
            return;
        }
        
        // Get the edit URL
        $edit_url = get_edit_post_link($post_id, 'raw');
        
        $this->log('Post created successfully with ID: ' . $post_id);
        
        // Return success response
        wp_send_json_success([
            'message' => sprintf(__('%s created successfully!', 'memberpress-ai-assistant'), ucfirst($post_type)),
            'post_id' => $post_id,
            'edit_url' => $edit_url,
        ]);
    }
    
    /**
     * Log a message
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return void
     */
    protected function log($message, $context = []): void {
        if (method_exists($this->logger, 'info')) {
            $this->logger->info($message, $context);
        } else {
            // Fallback to WordPress debug log
            if (defined('WP_DEBUG') && WP_DEBUG === true) {
                error_log('MPAI Post Handler: ' . $message);
            }
        }
    }
}