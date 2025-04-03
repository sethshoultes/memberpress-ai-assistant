# MemberPress AI Assistant Implementation Plan

## Overview
This plan outlines the implementation of blog post XML formatting and MemberPress-related features in the MemberPress AI Assistant plugin. The approach will leverage the existing agent system, content markers, and tool framework.

## 1. Blog Post XML Formatting Implementation

### 1.1. Create XML Format Parser Class
Create a new class `MPAI_XML_Content_Parser` that will handle the detection and parsing of XML-formatted content:

```php
class MPAI_XML_Content_Parser {
    /**
     * Parse XML formatted blog post into WordPress data
     */
    public function parse_xml_blog_post($xml_content) {
        // Extract content from wp-post tags
        if (!preg_match('/<wp-post>(.*?)<\/wp-post>/s', $xml_content, $matches)) {
            error_log('MPAI: Failed to find wp-post tags');
            return false;
        }
        
        $post_data = [];
        $xml = $matches[1];
        
        // Extract post components
        if (preg_match('/<post-title>(.*?)<\/post-title>/s', $xml, $title_match)) {
            $post_data['title'] = trim($title_match[1]);
        }
        
        if (preg_match('/<post-content>(.*?)<\/post-content>/s', $xml, $content_match)) {
            $blocks_content = $content_match[1];
            $post_data['content'] = $this->convert_xml_blocks_to_gutenberg($blocks_content);
        }
        
        if (preg_match('/<post-excerpt>(.*?)<\/post-excerpt>/s', $xml, $excerpt_match)) {
            $post_data['excerpt'] = trim($excerpt_match[1]);
        }
        
        if (preg_match('/<post-status>(.*?)<\/post-status>/s', $xml, $status_match)) {
            $post_data['status'] = trim($status_match[1]);
        }
        
        return $post_data;
    }

    /**
     * Convert XML block format to Gutenberg blocks
     */
    public function convert_xml_blocks_to_gutenberg($blocks_content) {
        $gutenberg_blocks = [];
        
        // Extract individual blocks
        preg_match_all('/<block type="([^"]+)"(?:\s+level="([^"]+)")?\s*>(.*?)<\/block>/s', $blocks_content, $blocks, PREG_SET_ORDER);
        
        foreach ($blocks as $block) {
            $block_type = $block[1];
            $block_content = trim($block[3]);
            
            switch ($block_type) {
                case 'paragraph':
                    $gutenberg_blocks[] = '<!-- wp:paragraph --><p>' . $block_content . '</p><!-- /wp:paragraph -->';
                    break;
                    
                case 'heading':
                    $level = isset($block[2]) ? $block[2] : '2';
                    $gutenberg_blocks[] = '<!-- wp:heading {"level":' . $level . '} --><h' . $level . '>' . $block_content . '</h' . $level . '><!-- /wp:heading -->';
                    break;
                    
                case 'list':
                    $gutenberg_blocks[] = '<!-- wp:list --><ul>' . $block_content . '</ul><!-- /wp:list -->';
                    break;
                    
                // Support for additional block types
                case 'quote':
                    $gutenberg_blocks[] = '<!-- wp:quote --><blockquote class="wp-block-quote"><p>' . $block_content . '</p></blockquote><!-- /wp:quote -->';
                    break;
                    
                case 'code':
                    $gutenberg_blocks[] = '<!-- wp:code --><pre class="wp-block-code"><code>' . htmlspecialchars($block_content) . '</code></pre><!-- /wp:code -->';
                    break;
                    
                default:
                    // Default to paragraph for unknown types
                    $gutenberg_blocks[] = '<!-- wp:paragraph --><p>' . $block_content . '</p><!-- /wp:paragraph -->';
            }
        }
        
        return implode("\n\n", $gutenberg_blocks);
    }
}
```

### 1.2. Enhance Content Detection in Chat Interface
Modify the `MPAI_Chat` class to detect XML formatted content and add appropriate content markers:

```php
/**
 * Enhanced content type detection
 */
private function detect_content_type($message_content) {
    // Check for XML post format first
    if (preg_match('/<wp-post>.*?<\/wp-post>/s', $message_content)) {
        $modified_content = $this->add_content_marker($message_content, 'blog-post');
        error_log('MPAI: Added blog-post marker to XML-formatted response');
        return $modified_content;
    }
    
    // Check for traditional blog post patterns (fallback)
    if (preg_match('/(?:#+\s*Title:?|Title:)\s*([^\n]+)/i', $message_content) ||
        (preg_match('/^#+\s*([^\n]+)/i', $message_content) && 
         preg_match('/introduction|summary|overview|content|body|conclusion/i', $message_content))) {
        
        $modified_content = $this->add_content_marker($message_content, 'blog-post');
        error_log('MPAI: Added blog-post marker to traditional response');
        return $modified_content;
    }
    
    return $message_content;
}
```

### 1.3. Enhance the WordPress API Tool
Modify the `MPAI_WP_API_Tool` class to handle XML-formatted blog posts in the `create_post` method:

```php
/**
 * Enhanced create_post method that handles XML formatted content
 */
private function create_post($parameters) {
    error_log('MPAI: Create post parameters: ' . json_encode($parameters));
    
    // Check if content is in XML format
    if (isset($parameters['content']) && strpos($parameters['content'], '<wp-post>') !== false) {
        error_log('MPAI: Detected XML formatted blog post');
        
        // Use the XML parser to extract post data
        $xml_parser = new MPAI_XML_Content_Parser();
        $parsed_data = $xml_parser->parse_xml_blog_post($parameters['content']);
        
        if ($parsed_data) {
            // Override parameters with parsed data
            foreach ($parsed_data as $key => $value) {
                $parameters[$key] = $value;
            }
            error_log('MPAI: Successfully parsed XML blog post format');
        }
    }
    
    // Proceed with normal post creation using the enhanced parameters
    $post_data = array(
        'post_title'   => isset($parameters['title']) ? $parameters['title'] : 'New Post',
        'post_content' => isset($parameters['content']) ? $parameters['content'] : '',
        'post_status'  => isset($parameters['status']) ? $parameters['status'] : 'draft',
        'post_type'    => isset($parameters['post_type']) ? $parameters['post_type'] : 'post',
        'post_author'  => isset($parameters['author_id']) ? $parameters['author_id'] : get_current_user_id(),
    );
    
    // Add excerpt if provided
    if (isset($parameters['excerpt'])) {
        $post_data['post_excerpt'] = $parameters['excerpt'];
    }
    
    // Insert the post
    $post_id = wp_insert_post($post_data);
    
    // Return success response with post details
    // ... (existing code)
}
```

### 1.4. Update Agent System Prompts
Modify the agent system prompt in `MPAI_Agent_Orchestrator` and/or `MPAI_Chat` to include instructions for the new XML format:

```php
/**
 * Content creation instructions for the agent
 */
private function get_content_creation_instructions() {
    return <<<EOT
When asked to create a blog post, always return it in the following XML format:

<wp-post>
  <post-title>Your Post Title Here</post-title>
  <post-content>
    <block type="paragraph">
      This is a paragraph block of content.
    </block>
    <block type="heading" level="2">
      This is a heading block
    </block>
    <block type="paragraph">
      Another paragraph block with content.
    </block>
    <!-- Add more blocks as needed -->
  </post-content>
  <post-excerpt>
    A brief summary of the post.
  </post-excerpt>
  <post-status>draft</post-status> <!-- draft or publish -->
</wp-post>

This format ensures the post will be correctly processed by WordPress Gutenberg.
EOT;
}
```

## 2. MemberPress-Related Features Implementation

### 2.1. Enhance MemberPress Agent
Extend the existing `MPAI_MemberPress_Agent` class to include more robust membership operations through the WP API tool:

```php
/**
 * Create membership using WP API
 * 
 * @param string $title Membership title
 * @param float $price Membership price
 * @param array $options Additional options
 * @return array Response from API
 */
public function create_membership_via_api($title, $price, $options = []) {
    // Prepare parameters for the WP API tool
    $parameters = array_merge([
        'action' => 'create_membership',
        'title' => $title,
        'price' => $price,
        'period' => isset($options['period']) ? $options['period'] : 1,
        'period_type' => isset($options['period_type']) ? $options['period_type'] : 'month',
        'billing_type' => isset($options['billing_type']) ? $options['billing_type'] : 'recurring',
        'status' => isset($options['status']) ? $options['status'] : 'publish',
    ], $options);
    
    // Execute the tool
    $result = $this->execute_tool('wp_api', $parameters);
    
    return $result;
}
```

### 2.2. Implement a Content Creation Tool
Create a new `MPAI_Content_Tool` class implementing the Content Tools specification:

```php
class MPAI_Content_Tool extends MPAI_Base_Tool {
    public function get_name() {
        return 'Content Tool';
    }
    
    public function get_description() {
        return 'Create and optimize content for WordPress';
    }
    
    public function get_tool_definition() {
        return [
            'name' => 'content_tool',
            'description' => 'Create and optimize content for WordPress',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'action' => [
                        'type' => 'string',
                        'enum' => [
                            'create_post',
                            'optimize_seo',
                            'suggest_headlines',
                            'improve_readability'
                        ],
                        'description' => 'Action to perform'
                    ],
                    'content' => [
                        'type' => 'string',
                        'description' => 'Content to work with (if optimizing existing content)'
                    ],
                    'topic' => [
                        'type' => 'string',
                        'description' => 'Topic for content creation'
                    ],
                    'keywords' => [
                        'type' => 'string',
                        'description' => 'Target keywords for SEO, comma separated'
                    ],
                    'length' => [
                        'type' => 'string',
                        'enum' => ['short', 'medium', 'long'],
                        'description' => 'Desired content length'
                    ],
                    'style' => [
                        'type' => 'string',
                        'enum' => ['informative', 'conversational', 'professional'],
                        'description' => 'Writing style'
                    ],
                ]
            ]
        ];
    }
    
    public function execute($parameters) {
        if (!isset($parameters['action'])) {
            throw new Exception('Action parameter is required');
        }
        
        $action = $parameters['action'];
        
        switch ($action) {
            case 'create_post':
                return $this->create_post($parameters);
            case 'optimize_seo':
                return $this->optimize_seo($parameters);
            case 'suggest_headlines':
                return $this->suggest_headlines($parameters);
            case 'improve_readability':
                return $this->improve_readability($parameters);
            default:
                throw new Exception('Unsupported action: ' . $action);
        }
    }
    
    // Implement the various content creation methods here
    private function create_post($parameters) {
        // Implementation for creating posts with AI...
    }
    
    private function optimize_seo($parameters) {
        // Implementation for SEO optimization...
    }
    
    private function suggest_headlines($parameters) {
        // Implementation for headline suggestions...
    }
    
    private function improve_readability($parameters) {
        // Implementation for readability improvements...
    }
}
```

### 2.3. Implement the Data Analysis Tool
Create a new Data Analysis Tool as specified in the content tools specification:

```php
class MPAI_Data_Analysis_Tool extends MPAI_Base_Tool {
    public function get_name() {
        return 'Data Analysis Tool';
    }
    
    public function get_description() {
        return 'Analyze site data and provide insights';
    }
    
    public function get_tool_definition() {
        return [
            'name' => 'data_analysis',
            'description' => 'Analyze site data and provide insights',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'data_type' => [
                        'type' => 'string',
                        'enum' => ['traffic', 'sales', 'comments', 'user_engagement'],
                        'description' => 'Type of data to analyze'
                    ],
                    'period' => [
                        'type' => 'string',
                        'enum' => ['last_week', 'last_month', 'last_year', 'custom'],
                        'description' => 'Time period to analyze'
                    ],
                    'start_date' => [
                        'type' => 'string',
                        'description' => 'Start date for custom period (YYYY-MM-DD)'
                    ],
                    'end_date' => [
                        'type' => 'string',
                        'description' => 'End date for custom period (YYYY-MM-DD)'
                    ],
                    'format' => [
                        'type' => 'string',
                        'enum' => ['text', 'html', 'json'],
                        'description' => 'Format for results'
                    ],
                ]
            ]
        ];
    }
    
    public function execute($parameters) {
        if (!isset($parameters['data_type'])) {
            throw new Exception('Data type parameter is required');
        }
        
        $data_type = $parameters['data_type'];
        
        switch ($data_type) {
            case 'traffic':
                return $this->analyze_traffic($parameters);
            case 'sales':
                return $this->analyze_sales($parameters);
            case 'comments':
                return $this->analyze_comments($parameters);
            case 'user_engagement':
                return $this->analyze_user_engagement($parameters);
            default:
                throw new Exception('Unsupported data type: ' . $data_type);
        }
    }
    
    // Implement the various data analysis methods here
}
```

## 3. Integration With Existing Systems

### 3.1. Register New Tools with Tool Registry
Modify the `MPAI_Tool_Registry` class to register the new tools:

```php
private function register_core_tools() {
    // Existing tools registration...
    
    // Register Content Tool
    $content_tool_instance = $this->get_content_tool_instance();
    if ($content_tool_instance) {
        $this->register_tool('content_tool', $content_tool_instance);
    }
    
    // Register Data Analysis Tool
    $data_analysis_tool_instance = $this->get_data_analysis_tool_instance();
    if ($data_analysis_tool_instance) {
        $this->register_tool('data_analysis', $data_analysis_tool_instance);
    }
    
    // Other tools...
}

private function get_content_tool_instance() {
    if (!class_exists('MPAI_Content_Tool')) {
        $tool_path = plugin_dir_path(__FILE__) . 'implementations/class-mpai-content-tool.php';
        if (file_exists($tool_path)) {
            require_once $tool_path;
            if (class_exists('MPAI_Content_Tool')) {
                return new MPAI_Content_Tool();
            }
        }
        return null;
    }
    
    return new MPAI_Content_Tool();
}

private function get_data_analysis_tool_instance() {
    if (!class_exists('MPAI_Data_Analysis_Tool')) {
        $tool_path = plugin_dir_path(__FILE__) . 'implementations/class-mpai-data-analysis-tool.php';
        if (file_exists($tool_path)) {
            require_once $tool_path;
            if (class_exists('MPAI_Data_Analysis_Tool')) {
                return new MPAI_Data_Analysis_Tool();
            }
        }
        return null;
    }
    
    return new MPAI_Data_Analysis_Tool();
}
```

### 3.2. Update Chat Interface for Command Parsing
Implement natural language command parsing in the chat interface as described in the content tools specification:

```php
/**
 * Parse natural language commands for content tools
 */
private function parse_commands($message) {
    $command_patterns = [
        // Content tool commands
        '/^!content\s+(create|optimize|headlines|improve)\s+(\w+)(?:\s+(.*))?$/i',
        // Analysis commands
        '/^!analyze\s+(traffic|sales|comments|engagement)(?:\s+for\s+(.*))?$/i',
        // Workflow commands
        '/^!workflow\s+(schedule|organize|manage)\s+(\w+)(?:\s+(.*))?$/i',
    ];
    
    foreach ($command_patterns as $pattern) {
        if (preg_match($pattern, $message, $matches)) {
            return $this->process_command_matches($matches);
        }
    }
    
    return $message;
}

/**
 * Process command matches and convert to tool parameters
 */
private function process_command_matches($matches) {
    $command_type = $matches[1];
    $entity = isset($matches[2]) ? $matches[2] : '';
    $params = isset($matches[3]) ? $this->parse_params($matches[3]) : [];
    
    // Create appropriate tool call based on the command
    // ...
}
```

### 3.3. Add UI Components for Tools
Add UI components to the chat interface for content tools access:

1. Create a tool menu button in the chat toolbar
2. Implement modal dialogs for tool configuration
3. Add styled display for tool results

## 4. Implementation Strategy & Timeline

### Phase 1 (Week 1-2): Blog Post XML Formatting
1. Create the XML parser class
2. Modify content detection in the chat interface
3. Update WP_API tool's create_post method
4. Enhance system prompts for XML formatting
5. Test with various content generation scenarios

### Phase 2 (Week 3-4): MemberPress Features
1. Extend the MemberPress agent with additional methods
2. Complete MemberPress API extensions
3. Test MemberPress integration with various scenarios
4. Document MemberPress-related functionality

### Phase 3 (Week 5-6): Content Tools
1. Implement the Content Tool class
2. Implement the Data Analysis Tool class
3. Extend the Tool Registry to include new tools
4. Create UI enhancements for tool access
5. Test tools with various content scenarios

### Phase 4 (Week 7-8): Integration & Documentation
1. Ensure all components work together
2. Add comprehensive error handling
3. Optimize performance
4. Complete user documentation
5. Create admin documentation for configuration

## 5. Testing Plan

1. **Unit Testing**: Test each function individually
2. **Integration Testing**: Test components working together
3. **User Flow Testing**: Test end-to-end user scenarios
4. **Edge Case Testing**: Test error handling and unexpected inputs
5. **Performance Testing**: Ensure the system works efficiently

## 6. Documentation

1. Update user guide with new functionality
2. Create admin documentation for configuration
3. Add developer documentation for extending tools
4. Update README and CHANGELOG

This implementation plan provides a comprehensive approach to implementing both the blog post XML formatting and MemberPress features while leveraging the existing agent system and tool framework.