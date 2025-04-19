<?php
/**
 * XML Content Parser Class
 *
 * Handles parsing of XML-formatted blog posts for WordPress
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class MPAI_XML_Content_Parser {
    /**
     * Parse XML formatted blog post into WordPress data
     *
     * @param string $xml_content The XML-formatted blog post content
     * @return array|false Parsed post data or false on failure
     */
    public function parse_xml_blog_post($xml_content) {
        mpai_log_debug('Parsing XML blog post, content length: ' . strlen($xml_content), 'xml-parser');
        
        // Filter the content before parsing
        $xml_content = apply_filters('MPAI_HOOK_FILTER_generated_content', $xml_content, 'blog_post');
        
        // Handle empty content
        if (empty($xml_content)) {
            mpai_log_warning('Empty XML content received', 'xml-parser');
            return false;
        }
        
        // Handle HTML-escaped content (useful for direct AJAX requests)
        if (strpos($xml_content, '&lt;wp-post') !== false && strpos($xml_content, '&lt;/wp-post&gt;') !== false) {
            mpai_log_debug('HTML-escaped XML content detected, unescaping', 'xml-parser');
            $xml_content = html_entity_decode($xml_content);
        }
        
        // Sanitize input to ensure it doesn't contain invalid characters
        $xml_content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $xml_content);
        
        // Log the content for debugging
        mpai_log_debug('XML content starts with: ' . substr($xml_content, 0, 50) . '...', 'xml-parser');
        
        // Try multiple approaches to extract the XML content
        $xml = false;
        
        // Approach 1: Regular expression with DOTALL flag and non-greedy quantifier
        if (preg_match('/<wp-post[^>]*>(.*?)<\/wp-post>/s', $xml_content, $matches)) {
            $xml = $matches[1];
            mpai_log_debug('Successfully extracted XML content using regex, length: ' . strlen($xml), 'xml-parser');
        } 
        // Approach 2: Position-based extraction
        else {
            mpai_log_debug('Regex extraction failed, trying position-based approach', 'xml-parser');
            
            $start_pos = stripos($xml_content, '<wp-post');
            if ($start_pos !== false) {
                $start_tag_end = strpos($xml_content, '>', $start_pos);
                $end_pos = stripos($xml_content, '</wp-post>', $start_tag_end);
                
                if ($start_tag_end !== false && $end_pos !== false && $end_pos > $start_tag_end) {
                    $xml = substr($xml_content, $start_tag_end + 1, $end_pos - $start_tag_end - 1);
                    mpai_log_debug('Extracted XML content using position-based approach, length: ' . strlen($xml), 'xml-parser');
                } else {
                    // Last resort - try to work with the entire content including tags
                    mpai_log_warning('Position-based extraction failed, using entire content', 'xml-parser');
                    $xml = $xml_content;
                }
            } else {
                mpai_log_warning('Could not find wp-post tags in content', 'xml-parser');
                
                // Last resort - try to work with the content directly without wp-post tags
                // Check if it has any XML-like structure
                if (strpos($xml_content, '<post-title>') !== false || 
                    strpos($xml_content, '<post-content>') !== false) {
                    mpai_log_debug('Found post elements without wp-post tags, using content directly', 'xml-parser');
                    $xml = $xml_content;
                } else {
                    mpai_log_error('No XML structure found in content', 'xml-parser');
                    return false;
                }
            }
        }
        
        $post_data = [];
        
        // Filter content markers used in XML parsing
        $markers = apply_filters('MPAI_HOOK_FILTER_content_marker', [
            'title' => 'post-title',
            'content' => 'post-content',
            'excerpt' => 'post-excerpt',
            'status' => 'post-status'
        ]);
        
        // Extract post components with more robust patterns
        // Title extraction - allowing for attributes in the tag
        if (preg_match('/<' . $markers['title'] . '[^>]*>(.*?)<\/' . $markers['title'] . '>/s', $xml, $title_match)) {
            $post_data['title'] = trim($title_match[1]);
            mpai_log_debug('Extracted title: ' . substr($post_data['title'], 0, 50) . (strlen($post_data['title']) > 50 ? '...' : ''), 'xml-parser');
        } else {
            mpai_log_warning('No post title found in XML', 'xml-parser');
        }
        
        // Content extraction - allowing for attributes in the tag
        if (preg_match('/<' . $markers['content'] . '[^>]*>(.*?)<\/' . $markers['content'] . '>/s', $xml, $content_match)) {
            $blocks_content = $content_match[1];
            mpai_log_debug('Extracted content blocks, length: ' . strlen($blocks_content), 'xml-parser');
            $post_data['content'] = $this->convert_xml_blocks_to_gutenberg($blocks_content);
        } else {
            mpai_log_warning('No post content found in XML', 'xml-parser');
            // Fallback - treat the entire XML as content if no post-content tags found
            if (!empty($xml)) {
                mpai_log_debug('Using fallback - treating entire XML as content', 'xml-parser');
                $post_data['content'] = $this->convert_xml_blocks_to_gutenberg($xml);
            }
        }
        
        // Excerpt extraction - allowing for attributes in the tag
        if (preg_match('/<' . $markers['excerpt'] . '[^>]*>(.*?)<\/' . $markers['excerpt'] . '>/s', $xml, $excerpt_match)) {
            $post_data['excerpt'] = trim($excerpt_match[1]);
            mpai_log_debug('Extracted excerpt: ' . substr($post_data['excerpt'], 0, 50) . (strlen($post_data['excerpt']) > 50 ? '...' : ''), 'xml-parser');
        }
        
        // Status extraction - allowing for attributes in the tag
        if (preg_match('/<' . $markers['status'] . '[^>]*>(.*?)<\/' . $markers['status'] . '>/s', $xml, $status_match)) {
            $post_data['status'] = trim($status_match[1]);
            mpai_log_debug('Extracted status: ' . $post_data['status'], 'xml-parser');
        }
        
        // Add fallback values for required fields
        if (empty($post_data['title'])) {
            $post_data['title'] = 'New Blog Post';
            mpai_log_info('Using default title', 'xml-parser');
        }
        
        if (empty($post_data['content'])) {
            $post_data['content'] = '<!-- wp:paragraph --><p>Content not provided.</p><!-- /wp:paragraph -->';
            mpai_log_info('Using default content', 'xml-parser');
        }
        
        if (empty($post_data['status'])) {
            $post_data['status'] = 'draft';
            mpai_log_info('Using default status: draft', 'xml-parser');
        }
        
        // Filter the blog post content before returning
        $post_data = apply_filters('MPAI_HOOK_FILTER_blog_post_content', $post_data, $xml_content);
        
        return $post_data;
    }

    /**
     * Convert XML block format to Gutenberg blocks
     *
     * @param string $blocks_content The XML block content
     * @return string Gutenberg-formatted content
     */
    public function convert_xml_blocks_to_gutenberg($blocks_content) {
        $gutenberg_blocks = [];
        
        // Log the original content for debugging
        mpai_log_debug('Processing block content: ' . substr($blocks_content, 0, 100) . '...', 'xml-parser');
        
        // Filter content templates before processing
        $blocks_content = apply_filters('MPAI_HOOK_FILTER_content_template', $blocks_content);
        
        // Make sure blocks_content is a string
        if (!is_string($blocks_content)) {
            mpai_log_warning('blocks_content is not a string: ' . gettype($blocks_content), 'xml-parser');
            return '<!-- wp:paragraph --><p>Invalid content format.</p><!-- /wp:paragraph -->';
        }
        
        // Sanitize input to prevent issues with malformed XML
        $blocks_content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $blocks_content);
        
        // Handle empty content
        if (empty(trim($blocks_content))) {
            mpai_log_warning('Empty or whitespace-only content', 'xml-parser');
            return '<!-- wp:paragraph --><p>No content provided.</p><!-- /wp:paragraph -->';
        }
        
        // If no blocks are found, try to identify sections from raw text that might follow a specific pattern
        if (strpos($blocks_content, '<block') === false) {
            mpai_log_debug('No blocks found, attempting to extract structure from raw content', 'xml-parser');
            
            // Try to identify headings and paragraphs from raw text
            $blocks_content = $this->preprocess_raw_content($blocks_content);
            
            // Check again if preprocessing created block tags
            if (strpos($blocks_content, '<block') === false) {
                mpai_log_debug('No blocks found after preprocessing, treating as single paragraph', 'xml-parser');
                $gutenberg_blocks[] = '<!-- wp:paragraph --><p>' . esc_html($blocks_content) . '</p><!-- /wp:paragraph -->';
                return implode("\n\n", $gutenberg_blocks);
            }
        }
        
        try {
            // Extract individual blocks with a very permissive pattern
            // This pattern aims to be extremely forgiving with malformed XML
            preg_match_all('/<block[^>]*?(?:type\s*=\s*([\'"]?)([^\'"]+)\\1)?[^>]*?(?:level\s*=\s*([\'"]?)([^\'"]+)\\3)?[^>]*>(.*?)<\/block>/is', $blocks_content, $blocks, PREG_SET_ORDER);
            
            // Log the number of blocks found
            $block_count = count($blocks);
            mpai_log_debug('Found ' . $block_count . ' blocks using permissive pattern', 'xml-parser');
            
            // If we didn't find any blocks with our pattern, try an even more basic approach
            if ($block_count === 0) {
                mpai_log_debug('No blocks found with regex, trying position-based parsing', 'xml-parser');
                $blocks = $this->extract_blocks_by_position($blocks_content);
                $block_count = count($blocks);
                mpai_log_debug('Found ' . $block_count . ' blocks using position-based parsing', 'xml-parser');
            }
            
            // Process each block
            foreach ($blocks as $block) {
                // Different parsing based on the pattern match structure
                if (isset($block['type'])) {
                    // This is for position-based extraction that returns an associative array
                    $block_type = $block['type'];
                    $block_level = isset($block['level']) ? $block['level'] : '2';
                    $block_content = trim($block['content']);
                } else {
                    // This is for regex pattern matching that returns a numeric array
                    // The type may be in index 2 (if extracted from attributes)
                    $block_type = isset($block[2]) ? $block[2] : 'paragraph';
                    // Level may be in index 4 (if provided)
                    $block_level = isset($block[4]) ? $block[4] : '2';
                    // Content should be in the last position
                    $block_content = trim(end($block));
                    
                    // If type wasn't found in attributes, try to infer from the content
                    if (empty($block_type) || $block_type === 'paragraph') {
                        if (preg_match('/^#+\s+/m', $block_content)) {
                            $block_type = 'heading';
                            // Try to determine heading level from # count
                            $level_match = [];
                            if (preg_match('/^(#+)\s+/m', $block_content, $level_match)) {
                                $block_level = min(6, strlen($level_match[1]));
                                // Remove the heading marker
                                $block_content = preg_replace('/^#+\s+/m', '', $block_content);
                            }
                        } elseif (preg_match('/^[-*]\s+/m', $block_content)) {
                            $block_type = 'list';
                        } elseif (preg_match('/^\d+\.\s+/m', $block_content)) {
                            $block_type = 'ordered-list';
                        } elseif (preg_match('/^>\s+/m', $block_content)) {
                            $block_type = 'quote';
                            // Remove the quote marker
                            $block_content = preg_replace('/^>\s+/m', '', $block_content);
                        }
                    }
                }
                
                mpai_log_debug('Processing block type: ' . $block_type, 'xml-parser');
                
                // Skip empty blocks
                if (empty(trim($block_content))) {
                    mpai_log_debug('Skipping empty block', 'xml-parser');
                    continue;
                }
                
                // Get content formatting rules
                $formatting_rules = apply_filters('MPAI_HOOK_FILTER_content_formatting', [
                    'paragraph' => [
                        'tag' => 'p',
                        'wrapper' => '<!-- wp:paragraph --><p>%s</p><!-- /wp:paragraph -->'
                    ],
                    'heading' => [
                        'tag' => 'h',
                        'wrapper' => '<!-- wp:heading {"level":%d} --><h%d>%s</h%d><!-- /wp:heading -->'
                    ],
                    'list' => [
                        'tag' => 'ul',
                        'wrapper' => '<!-- wp:list --><ul>%s</ul><!-- /wp:list -->'
                    ],
                    'ordered-list' => [
                        'tag' => 'ol',
                        'wrapper' => '<!-- wp:list {"ordered":true} --><ol>%s</ol><!-- /wp:list -->'
                    ],
                    'quote' => [
                        'tag' => 'blockquote',
                        'wrapper' => '<!-- wp:quote --><blockquote class="wp-block-quote"><p>%s</p></blockquote><!-- /wp:quote -->'
                    ],
                    'code' => [
                        'tag' => 'code',
                        'wrapper' => '<!-- wp:code --><pre class="wp-block-code"><code>%s</code></pre><!-- /wp:code -->'
                    ],
                    'image' => [
                        'tag' => 'img',
                        'wrapper' => '<!-- wp:image {"src":"%s"} --><figure class="wp-block-image"><img src="%s" alt=""/></figure><!-- /wp:image -->'
                    ]
                ]);
                
                // Process based on block type
                switch (strtolower($block_type)) {
                    case 'paragraph':
                    case 'p':
                        $gutenberg_blocks[] = sprintf($formatting_rules['paragraph']['wrapper'], esc_html($block_content));
                        break;
                        
                    case 'heading':
                    case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6':
                        // Extract level from block_type if it's h1-h6
                        if (preg_match('/^h(\d)$/', $block_type, $matches)) {
                            $block_level = $matches[1];
                        }
                        
                        // Ensure level is between 1 and 6
                        $level = max(1, min(6, intval($block_level)));
                        $gutenberg_blocks[] = sprintf($formatting_rules['heading']['wrapper'], $level, $level, esc_html($block_content), $level);
                        break;
                        
                    case 'list':
                    case 'ul':
                        // For unordered lists
                        $items = $this->process_list_items($block_content);
                        $gutenberg_blocks[] = sprintf($formatting_rules['list']['wrapper'], $items);
                        break;
                    
                    case 'ordered-list':
                    case 'ol':
                        // For ordered lists
                        $items = $this->process_list_items($block_content);
                        $gutenberg_blocks[] = sprintf($formatting_rules['ordered-list']['wrapper'], $items);
                        break;
                        
                    case 'quote':
                    case 'blockquote':
                        $gutenberg_blocks[] = sprintf($formatting_rules['quote']['wrapper'], esc_html($block_content));
                        break;
                        
                    case 'code':
                    case 'pre':
                        $gutenberg_blocks[] = sprintf($formatting_rules['code']['wrapper'], htmlspecialchars($block_content));
                        break;
                        
                    case 'image':
                    case 'img':
                        // For image blocks - if content is a URL use it, otherwise check for src attribute
                        if (filter_var($block_content, FILTER_VALIDATE_URL)) {
                            $gutenberg_blocks[] = sprintf($formatting_rules['image']['wrapper'], esc_url($block_content), esc_url($block_content));
                        } elseif (preg_match('/src=([\'"]?)([^\'"]+)\\1/i', $block_content, $src_matches)) {
                            // Extract src from content that might contain an img tag
                            $src_url = $src_matches[2];
                            $gutenberg_blocks[] = sprintf($formatting_rules['image']['wrapper'], esc_url($src_url), esc_url($src_url));
                        } else {
                            // If not a URL, treat as a paragraph with the content
                            $gutenberg_blocks[] = sprintf($formatting_rules['paragraph']['wrapper'], esc_html($block_content));
                        }
                        break;
                        
                    default:
                        // Filter content type detection
                        $detected_type = apply_filters('MPAI_HOOK_FILTER_content_type', 'paragraph', $block_type, $block_content);
                        
                        // Use the detected type or default to paragraph
                        if (isset($formatting_rules[$detected_type])) {
                            $gutenberg_blocks[] = sprintf($formatting_rules[$detected_type]['wrapper'], esc_html($block_content));
                        } else {
                            $gutenberg_blocks[] = sprintf($formatting_rules['paragraph']['wrapper'], esc_html($block_content));
                        }
                }
            }
        } catch (Exception $e) {
            mpai_log_error('Error processing blocks: ' . $e->getMessage(), 'xml-parser', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            
            // Default fallback - split into paragraphs based on newlines and format each
            $paragraphs = preg_split('/\n\s*\n/', $blocks_content);
            mpai_log_debug('Fallback - processing as ' . count($paragraphs) . ' paragraphs', 'xml-parser');
            
            foreach ($paragraphs as $paragraph) {
                $paragraph = trim($paragraph);
                if (!empty($paragraph)) {
                    $gutenberg_blocks[] = '<!-- wp:paragraph --><p>' . esc_html($paragraph) . '</p><!-- /wp:paragraph -->';
                }
            }
            
            // If still empty, use a single paragraph with the entire content
            if (empty($gutenberg_blocks)) {
                $gutenberg_blocks[] = '<!-- wp:paragraph --><p>' . esc_html($blocks_content) . '</p><!-- /wp:paragraph -->';
            }
        }
        
        // If no blocks were processed, add a fallback paragraph
        if (empty($gutenberg_blocks)) {
            mpai_log_warning('No blocks processed, using fallback paragraph', 'xml-parser');
            $gutenberg_blocks[] = '<!-- wp:paragraph --><p>' . esc_html($blocks_content) . '</p><!-- /wp:paragraph -->';
        }
        
        return implode("\n\n", $gutenberg_blocks);
    }
    
    /**
     * Preprocess raw content to try to identify and mark structural elements
     *
     * @param string $content Raw content to process
     * @return string Content with block tags added if structure was identified
     */
    private function preprocess_raw_content($content) {
        // Check if content is already structure and we just missed the block tags
        if (strpos($content, '<h1>') !== false || strpos($content, '<h2>') !== false ||
            strpos($content, '<h3>') !== false || strpos($content, '<p>') !== false ||
            strpos($content, '<ul>') !== false || strpos($content, '<ol>') !== false) {
            
            mpai_log_debug('Content contains HTML tags, converting to block format', 'xml-parser');
            
            // Extract headings
            $content = preg_replace('/<h1>(.*?)<\/h1>/s', '<block type="heading" level="1">$1</block>', $content);
            $content = preg_replace('/<h2>(.*?)<\/h2>/s', '<block type="heading" level="2">$1</block>', $content);
            $content = preg_replace('/<h3>(.*?)<\/h3>/s', '<block type="heading" level="3">$1</block>', $content);
            $content = preg_replace('/<h4>(.*?)<\/h4>/s', '<block type="heading" level="4">$1</block>', $content);
            $content = preg_replace('/<h5>(.*?)<\/h5>/s', '<block type="heading" level="5">$1</block>', $content);
            $content = preg_replace('/<h6>(.*?)<\/h6>/s', '<block type="heading" level="6">$1</block>', $content);
            
            // Extract paragraphs
            $content = preg_replace('/<p>(.*?)<\/p>/s', '<block type="paragraph">$1</block>', $content);
            
            // Extract lists
            $content = preg_replace('/<ul>(.*?)<\/ul>/s', '<block type="list">$1</block>', $content);
            $content = preg_replace('/<ol>(.*?)<\/ol>/s', '<block type="ordered-list">$1</block>', $content);
            
            // Extract quotes
            $content = preg_replace('/<blockquote>(.*?)<\/blockquote>/s', '<block type="quote">$1</block>', $content);
            
            // Extract code
            $content = preg_replace('/<pre>(.*?)<\/pre>/s', '<block type="code">$1</block>', $content);
            $content = preg_replace('/<code>(.*?)<\/code>/s', '<block type="code">$1</block>', $content);
            
            return $content;
        }
        
        // Identify headers by markdown-like syntax and convert to block format
        $lines = explode("\n", $content);
        $processed_lines = [];
        $in_paragraph = false;
        $paragraph_lines = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                if ($in_paragraph) {
                    // End the current paragraph
                    $processed_lines[] = '<block type="paragraph">' . implode("\n", $paragraph_lines) . '</block>';
                    $paragraph_lines = [];
                    $in_paragraph = false;
                }
                continue;
            }
            
            // Check for heading pattern (# Heading)
            if (preg_match('/^(#+)\s+(.+)$/', $line, $matches)) {
                if ($in_paragraph) {
                    // End the current paragraph
                    $processed_lines[] = '<block type="paragraph">' . implode("\n", $paragraph_lines) . '</block>';
                    $paragraph_lines = [];
                    $in_paragraph = false;
                }
                
                $level = min(6, strlen($matches[1]));
                $heading_text = $matches[2];
                $processed_lines[] = '<block type="heading" level="' . $level . '">' . $heading_text . '</block>';
            }
            // Check for list items (- Item or * Item)
            elseif (preg_match('/^[-*]\s+(.+)$/', $line, $matches)) {
                if ($in_paragraph) {
                    // End the current paragraph
                    $processed_lines[] = '<block type="paragraph">' . implode("\n", $paragraph_lines) . '</block>';
                    $paragraph_lines = [];
                    $in_paragraph = false;
                }
                
                // Since we don't know if there are more list items, mark this as an individual item for now
                $processed_lines[] = '<block type="list"><item>' . $matches[1] . '</item></block>';
            }
            // Check for ordered list items (1. Item)
            elseif (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                if ($in_paragraph) {
                    // End the current paragraph
                    $processed_lines[] = '<block type="paragraph">' . implode("\n", $paragraph_lines) . '</block>';
                    $paragraph_lines = [];
                    $in_paragraph = false;
                }
                
                // Since we don't know if there are more list items, mark this as an individual item for now
                $processed_lines[] = '<block type="ordered-list"><item>' . $matches[1] . '</item></block>';
            }
            // Check for blockquotes (> Quote)
            elseif (preg_match('/^>\s+(.+)$/', $line, $matches)) {
                if ($in_paragraph) {
                    // End the current paragraph
                    $processed_lines[] = '<block type="paragraph">' . implode("\n", $paragraph_lines) . '</block>';
                    $paragraph_lines = [];
                    $in_paragraph = false;
                }
                
                $processed_lines[] = '<block type="quote">' . $matches[1] . '</block>';
            }
            // Otherwise, treat as paragraph content
            else {
                if (!$in_paragraph) {
                    $in_paragraph = true;
                }
                $paragraph_lines[] = $line;
            }
        }
        
        // Add final paragraph if there is one
        if ($in_paragraph && !empty($paragraph_lines)) {
            $processed_lines[] = '<block type="paragraph">' . implode("\n", $paragraph_lines) . '</block>';
        }
        
        // If we didn't identify any structure, return the original content
        if (empty($processed_lines)) {
            return $content;
        }
        
        return implode("\n", $processed_lines);
    }
    
    /**
     * Extract blocks from content based on position
     *
     * @param string $blocks_content Raw block content
     * @return array Array of blocks with type, level, and content keys
     */
    private function extract_blocks_by_position($blocks_content) {
        $blocks = [];
        $start_pos = 0;
        
        // Loop while we can find block tags
        while (($start_pos = strpos($blocks_content, '<block', $start_pos)) !== false) {
            // Find the end of the opening tag
            $tag_end = strpos($blocks_content, '>', $start_pos);
            if ($tag_end === false) break;
            
            // Extract tag attributes
            $tag_content = substr($blocks_content, $start_pos, $tag_end - $start_pos + 1);
            
            // Parse type attribute
            $type = 'paragraph'; // Default type
            if (preg_match('/type\s*=\s*([\'"]?)([^\'"]+)\\1/i', $tag_content, $type_match)) {
                $type = $type_match[2];
            }
            
            // Parse level attribute
            $level = '2'; // Default level
            if (preg_match('/level\s*=\s*([\'"]?)([^\'"]+)\\1/i', $tag_content, $level_match)) {
                $level = $level_match[2];
            }
            
            // Find the closing tag
            $close_tag = '</block>';
            $close_pos = strpos($blocks_content, $close_tag, $tag_end);
            if ($close_pos === false) {
                // No closing tag found, try to find the next opening tag
                $next_start = strpos($blocks_content, '<block', $tag_end);
                if ($next_start !== false) {
                    $content = substr($blocks_content, $tag_end + 1, $next_start - $tag_end - 1);
                } else {
                    // No more blocks, use rest of content
                    $content = substr($blocks_content, $tag_end + 1);
                }
            } else {
                $content = substr($blocks_content, $tag_end + 1, $close_pos - $tag_end - 1);
                // Move start_pos past this block for next iteration
                $start_pos = $close_pos + strlen($close_tag);
            }
            
            // Add the block to our results
            $blocks[] = [
                'type' => $type,
                'level' => $level,
                'content' => $content
            ];
            
            if ($close_pos === false) {
                // If we didn't find a closing tag, we've processed everything we can
                break;
            }
        }
        
        return $blocks;
    }
    
    /**
     * Process list items from content
     *
     * @param string $list_content The content containing list items
     * @return string Formatted list items HTML
     */
    private function process_list_items($list_content) {
        $html = '';
        
        try {
            // Handle explicitly defined list items
            if (preg_match_all('/<item>(.*?)<\/item>/s', $list_content, $items)) {
                mpai_log_debug('Found ' . count($items[1]) . ' explicit <item> tags', 'xml-parser');
                
                foreach ($items[1] as $item) {
                    $html .= '<li>' . esc_html(trim($item)) . '</li>';
                }
                return $html;
            }
            
            // Fall back to splitting by newlines if no <item> tags
            mpai_log_debug('No <item> tags found, splitting by newlines', 'xml-parser');
            $lines = preg_split('/\r\n|\r|\n/', $list_content);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    // Remove bullet points or numbers if they exist
                    $line = preg_replace('/^[\*\-•]|\d+\.?\s+/', '', $line);
                    $html .= '<li>' . esc_html(trim($line)) . '</li>';
                }
            }
            
            // If still no items found, just wrap the entire content as one item
            if (empty($html) && !empty($list_content)) {
                mpai_log_debug('No list items found via parsing, using entire content as one item', 'xml-parser');
                $html = '<li>' . esc_html(trim($list_content)) . '</li>';
            }
        } catch (Exception $e) {
            mpai_log_error('Error processing list items: ' . $e->getMessage(), 'xml-parser', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ));
            // Fallback - use the entire content as one list item
            $html = '<li>' . esc_html(trim($list_content)) . '</li>';
        }
        
        return $html;
    }
    
    /**
     * Check if content is in XML blog post format
     *
     * @param string $content The content to check
     * @return bool True if content is in XML blog post format
     */
    public function is_xml_blog_post($content) {
        return (preg_match('/<wp-post>.*?<\/wp-post>/s', $content) === 1);
    }
}