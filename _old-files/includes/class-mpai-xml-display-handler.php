<?php
/**
 * XML Display Handler Class
 *
 * Handles the display of XML-formatted blog posts in the chat interface
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_XML_Display_Handler {
    /**
     * Initialize the class
     */
    public function __construct() {
        // Register hooks
        add_filter('MPAI_HOOK_FILTER_chat_message_content', array($this, 'process_xml_content'), 10, 2);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue necessary assets for XML display
     */
    public function enqueue_assets() {
        // Only enqueue on pages where the chat interface is loaded
        if (!$this->is_chat_page()) {
            return;
        }

        // Enqueue the CSS for blog post preview
        wp_enqueue_style(
            'mpai-blog-post-preview',
            MPAI_PLUGIN_URL . 'assets/css/blog-post-preview.css',
            array(),
            MPAI_VERSION
        );
    }

    /**
     * Check if current page has the chat interface
     *
     * @return bool True if chat interface is present
     */
    private function is_chat_page() {
        // Check if we're on an admin page
        if (is_admin()) {
            $screen = get_current_screen();
            if ($screen && ($screen->id === 'toplevel_page_memberpress-ai' || strpos($screen->id, 'memberpress-ai') !== false)) {
                return true;
            }
        }

        // Check for shortcode in content (for frontend)
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'memberpress_ai_chat')) {
            return true;
        }

        // Default to true to ensure assets are loaded when needed
        return true;
    }

    /**
     * Process XML content in chat messages
     *
     * @param string $content The message content
     * @param array $message The message data
     * @return string Processed content
     */
    public function process_xml_content($content, $message) {
        // Only process assistant messages
        if (empty($message) || !isset($message['role']) || $message['role'] !== 'assistant') {
            return $content;
        }

        // Check if content contains XML blog post format
        if ($this->contains_xml_blog_post($content)) {
            // Log that we're processing XML content
            if (function_exists('mpai_log_debug')) {
                mpai_log_debug('Processing XML content in message', 'xml-display-handler');
            }
            
            // Format the XML content
            $formatted_content = $this->format_xml_blog_post($content);
            
            // Add a script to ensure the preview card is displayed immediately
            $formatted_content .= '<script>
                (function() {
                    // Execute immediately
                    console.log("MPAI: XML content processed, ensuring preview card is displayed");
                    
                    // Force display of preview card
                    var cards = document.querySelectorAll(".mpai-post-preview-card");
                    if (cards.length > 0) {
                        var latestCard = cards[cards.length - 1];
                        latestCard.style.display = "block";
                        console.log("MPAI: Found and displayed preview card");
                    }
                })();
            </script>';
            
            return $formatted_content;
        }

        return $content;
    }

    /**
     * Check if content contains XML blog post format
     *
     * @param string $content The content to check
     * @return bool True if content contains XML blog post format
     */
    public function contains_xml_blog_post($content) {
        // Check for wp-post tags
        if (preg_match('/<wp-post>.*?<\/wp-post>/s', $content)) {
            return true;
        }

        // Check for XML in code blocks
        if (preg_match('/```xml\s*<wp-post>.*?<\/wp-post>\s*```/s', $content)) {
            return true;
        }

        // Check for post-title and post-content tags (partial XML)
        if (preg_match('/<post-title>.*?<\/post-title>/s', $content) && 
            preg_match('/<post-content>.*?<\/post-content>/s', $content)) {
            return true;
        }

        return false;
    }

    /**
     * Format XML blog post content for display
     *
     * @param string $content The content containing XML blog post
     * @return string Formatted content with preview card
     */
    private function format_xml_blog_post($content) {
        // Extract XML content
        $xml_content = $this->extract_xml_content($content);
        
        if (empty($xml_content)) {
            return $content;
        }

        // Extract post components
        $title = $this->extract_tag_content($xml_content, 'post-title', 'New Blog Post');
        $excerpt = $this->extract_tag_content($xml_content, 'post-excerpt', '');
        $post_type = $this->extract_tag_content($xml_content, 'post-type', 'post');
        
        // If no excerpt, try to get first paragraph from content
        if (empty($excerpt)) {
            $post_content = $this->extract_tag_content($xml_content, 'post-content', '');
            if (!empty($post_content)) {
                // Find first paragraph or block
                if (preg_match('/<block[^>]*>(.*?)<\/block>/is', $post_content, $block_match)) {
                    $excerpt = trim($block_match[1]);
                    // Limit length
                    if (strlen($excerpt) > 150) {
                        $excerpt = substr($excerpt, 0, 147) . '...';
                    }
                }
            }
        }
        
        // If still no excerpt, use a default
        if (empty($excerpt)) {
            $excerpt = 'Blog post content created with MemberPress AI Assistant';
        }
        
        // Normalize post type
        $post_type = strtolower(trim($post_type));
        if ($post_type !== 'page') {
            $post_type = 'post';
        }
        
        // Create preview card HTML
        $preview_card = $this->create_preview_card($title, $excerpt, $post_type, $xml_content);
        
        // Log the XML processing
        if (function_exists('mpai_log_debug')) {
            mpai_log_debug('Processing XML blog post: ' . $title, 'xml-display-handler');
        }
        
        // Completely replace the content with a simple message and the preview card
        // This ensures no XML content remains in the message
        $content = "I've created a " . ($post_type === 'page' ? 'page' : 'blog post') . " titled \"" . esc_html($title) . "\" for you:";
        
        // Add the preview card to the cleaned content
        $content .= $preview_card;
        
        // Add inline script to ensure the preview card is displayed immediately
        $content .= '<script>
            (function() {
                // Execute immediately to ensure preview card is displayed
                console.log("MPAI: Ensuring preview card is displayed immediately");
                
                // Force display of preview card
                var cards = document.querySelectorAll(".mpai-post-preview-card");
                if (cards.length > 0) {
                    var latestCard = cards[cards.length - 1];
                    latestCard.style.display = "block";
                    latestCard.style.opacity = "1";
                    latestCard.style.visibility = "visible";
                    console.log("MPAI: Found and displayed preview card immediately");
                }
                
                // Initialize event handlers immediately
                var toggleButtons = document.querySelectorAll(".mpai-toggle-xml-button");
                toggleButtons.forEach(function(button) {
                    button.addEventListener("click", function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var card = this.closest(".mpai-post-preview-card");
                        var xmlContent = card.querySelector(".mpai-post-xml-content");
                        
                        if (xmlContent.style.display === "block") {
                            xmlContent.style.display = "none";
                            this.textContent = "View XML";
                        } else {
                            xmlContent.style.display = "block";
                            this.textContent = "Hide XML";
                        }
                    });
                });
                
                var createButtons = document.querySelectorAll(".mpai-create-post-button");
                createButtons.forEach(function(button) {
                    button.addEventListener("click", function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var contentType = this.getAttribute("data-content-type");
                        var xmlContent = this.getAttribute("data-xml") || this.closest(".mpai-post-preview-card").getAttribute("data-xml-content");
                        
                        // Show loading indicator
                        this.disabled = true;
                        this.textContent = "Creating...";
                        
                        // Call the createPostFromXML function if available
                        if (window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.createPostFromXML === "function") {
                            window.MPAI_BlogFormatter.createPostFromXML(decodeURIComponent(xmlContent), contentType);
                        }
                    });
                });
            })();
        </script>';
        
        return $content;
    }
    
    /**
     * Extract XML content from message
     *
     * @param string $content The message content
     * @return string Extracted XML content
     */
    private function extract_xml_content($content) {
        // Try to extract from code blocks first
        if (preg_match('/```xml\s*(<wp-post>.*?<\/wp-post>)\s*```/s', $content, $matches)) {
            return $matches[1];
        }
        
        // Try to extract direct XML
        if (preg_match('/(<wp-post>.*?<\/wp-post>)/s', $content, $matches)) {
            return $matches[1];
        }
        
        // Try to reconstruct from partial XML
        $title_match = preg_match('/<post-title>(.*?)<\/post-title>/s', $content, $title_matches);
        $content_match = preg_match('/<post-content>(.*?)<\/post-content>/s', $content, $content_matches);
        
        if ($title_match && $content_match) {
            return '<wp-post>' . 
                   '<post-title>' . $title_matches[1] . '</post-title>' . 
                   '<post-content>' . $content_matches[1] . '</post-content>' . 
                   '</wp-post>';
        }
        
        return '';
    }
    
    /**
     * Extract content from XML tag
     *
     * @param string $xml_content The XML content
     * @param string $tag The tag name
     * @param string $default Default value if tag not found
     * @return string Extracted content
     */
    private function extract_tag_content($xml_content, $tag, $default = '') {
        if (preg_match('/<' . $tag . '[^>]*>(.*?)<\/' . $tag . '>/s', $xml_content, $matches)) {
            return trim($matches[1]);
        }
        return $default;
    }
    
    /**
     * Create preview card HTML
     *
     * @param string $title The post title
     * @param string $excerpt The post excerpt
     * @param string $post_type The post type
     * @param string $xml_content The XML content
     * @return string Preview card HTML
     */
    private function create_preview_card($title, $excerpt, $post_type, $xml_content) {
        // Escape the XML content for data attribute
        $escaped_xml = esc_attr($xml_content);
        
        // Create the preview card HTML
        $html = '<div class="mpai-post-preview-card" data-xml-content="' . $escaped_xml . '">';
        $html .= '<div class="mpai-post-preview-header">';
        $html .= '<div class="mpai-post-preview-type">' . ($post_type === 'page' ? 'Page' : 'Blog Post') . '</div>';
        $html .= '<div class="mpai-post-preview-icon">' . ($post_type === 'page' ? '<span class="dashicons dashicons-page"></span>' : '<span class="dashicons dashicons-admin-post"></span>') . '</div>';
        $html .= '</div>';
        $html .= '<h3 class="mpai-post-preview-title">' . esc_html($title) . '</h3>';
        $html .= '<div class="mpai-post-preview-excerpt">' . esc_html($excerpt) . '</div>';
        $html .= '<div class="mpai-post-preview-actions">';
        $html .= '<button class="mpai-create-post-button" data-content-type="' . esc_attr($post_type) . '" data-xml="' . $escaped_xml . '">';
        $html .= 'Create ' . ($post_type === 'page' ? 'Page' : 'Post');
        $html .= '</button>';
        $html .= '<button class="mpai-toggle-xml-button">View XML</button>';
        $html .= '</div>';
        $html .= '<div class="mpai-post-xml-content">';
        $html .= '<pre>' . esc_html($xml_content) . '</pre>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Add inline script to ensure the preview card is displayed immediately
        $html .= '<script>
            (function() {
                // Add event handlers for the buttons immediately
                document.addEventListener("DOMContentLoaded", function() {
                    // Find all toggle XML buttons and add click handlers
                    document.querySelectorAll(".mpai-toggle-xml-button").forEach(function(button) {
                        button.addEventListener("click", function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            var card = this.closest(".mpai-post-preview-card");
                            var xmlContent = card.querySelector(".mpai-post-xml-content");
                            
                            if (xmlContent.style.display === "block") {
                                xmlContent.style.display = "none";
                                this.textContent = "View XML";
                            } else {
                                xmlContent.style.display = "block";
                                this.textContent = "Hide XML";
                            }
                        });
                    });
                    
                    // Find all create post buttons and add click handlers
                    document.querySelectorAll(".mpai-create-post-button").forEach(function(button) {
                        button.addEventListener("click", function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            var contentType = this.getAttribute("data-content-type");
                            var xmlContent = this.getAttribute("data-xml") || this.closest(".mpai-post-preview-card").getAttribute("data-xml-content");
                            
                            // Show loading indicator
                            this.disabled = true;
                            this.textContent = "Creating...";
                            
                            // Call the createPostFromXML function if available
                            if (window.MPAI_BlogFormatter && typeof window.MPAI_BlogFormatter.createPostFromXML === "function") {
                                window.MPAI_BlogFormatter.createPostFromXML(decodeURIComponent(xmlContent), contentType);
                            }
                        });
                    });
                });
            })();
        </script>';
        
        return $html;
    }
}

// Initialize the class
new MPAI_XML_Display_Handler();