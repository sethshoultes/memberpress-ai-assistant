<?php
namespace MemberpressAiAssistant\Utilities;

class TableFormatter {
    // Output format constants
    const FORMAT_MARKDOWN = 'markdown';
    const FORMAT_HTML = 'html';
    const FORMAT_PLAIN = 'plain';
    
    /**
     * Format data as a table
     *
     * @param array $data Array of items to format
     * @param array $options Formatting options
     *        - format: Output format (markdown, html, plain)
     *        - headers: Custom headers to use (optional)
     *        - summary: Summary information (optional)
     *        - title: Table title (optional)
     * @return string Formatted table
     */
    public static function formatTable(array $data, array $options = []): string {
        // Default options
        $options = array_merge([
            'format' => self::FORMAT_MARKDOWN,
            'headers' => [],
            'summary' => [],
            'title' => '',
        ], $options);
        
        // Call the appropriate formatter based on format
        switch ($options['format']) {
            case self::FORMAT_HTML:
                return self::formatAsHtml($data, $options);
            case self::FORMAT_PLAIN:
                return self::formatAsPlain($data, $options);
            case self::FORMAT_MARKDOWN:
            default:
                return self::formatAsMarkdown($data, $options);
        }
    }
    
    /**
     * Format plugin list as a table
     *
     * @param array $plugins List of plugins
     * @param array $options Formatting options
     * @return string Formatted table
     */
    public static function formatPluginList(array $plugins, array $options = []): string {
        // Set default title and summary if not provided
        if (empty($options['title'])) {
            $options['title'] = 'Installed WordPress Plugins';
        }
        
        // Calculate summary if not provided
        if (empty($options['summary'])) {
            $active = 0;
            $inactive = 0;
            $update_available = 0;
            
            foreach ($plugins as $plugin) {
                if ($plugin['active']) {
                    $active++;
                } else {
                    $inactive++;
                }
                
                if (!empty($plugin['update_available']) && $plugin['update_available']) {
                    $update_available++;
                }
            }
            
            $options['summary'] = [
                'total' => count($plugins),
                'active' => $active,
                'inactive' => $inactive,
                'update_available' => $update_available,
            ];
        }
        
        // Set entity type
        $options['entity_type'] = 'plugins';
        
        return self::formatTable($plugins, $options);
    }
    
    /**
     * Format post list as a table
     *
     * @param array $posts List of posts
     * @param array $options Formatting options
     * @return string Formatted table
     */
    public static function formatPostList(array $posts, array $options = []): string {
        // Set default title and summary if not provided
        if (empty($options['title'])) {
            $options['title'] = 'WordPress Posts';
        }
        
        // Calculate summary if not provided
        if (empty($options['summary'])) {
            $published = 0;
            $draft = 0;
            $other = 0;
            
            foreach ($posts as $post) {
                if ($post['status'] === 'publish') {
                    $published++;
                } elseif ($post['status'] === 'draft') {
                    $draft++;
                } else {
                    $other++;
                }
            }
            
            $options['summary'] = [
                'total' => count($posts),
                'published' => $published,
                'draft' => $draft,
                'other' => $other,
            ];
        }
        
        // Set entity type
        $options['entity_type'] = 'posts';
        
        return self::formatTable($posts, $options);
    }
    
    /**
     * Format page list as a table
     *
     * @param array $pages List of pages
     * @param array $options Formatting options
     * @return string Formatted table
     */
    public static function formatPageList(array $pages, array $options = []): string {
        // Set default title and summary if not provided
        if (empty($options['title'])) {
            $options['title'] = 'WordPress Pages';
        }
        
        // Calculate summary if not provided
        if (empty($options['summary'])) {
            $published = 0;
            $draft = 0;
            $other = 0;
            
            foreach ($pages as $page) {
                if ($page['status'] === 'publish') {
                    $published++;
                } elseif ($page['status'] === 'draft') {
                    $draft++;
                } else {
                    $other++;
                }
            }
            
            $options['summary'] = [
                'total' => count($pages),
                'published' => $published,
                'draft' => $draft,
                'other' => $other,
            ];
        }
        
        // Set entity type
        $options['entity_type'] = 'posts'; // Use same format as posts
        
        return self::formatTable($pages, $options);
    }
    
    /**
     * Format comment list as a table
     *
     * @param array $comments List of comments
     * @param array $options Formatting options
     * @return string Formatted table
     */
    public static function formatCommentList(array $comments, array $options = []): string {
        // Set default title and summary if not provided
        if (empty($options['title'])) {
            $options['title'] = 'WordPress Comments';
        }
        
        // Calculate summary if not provided
        if (empty($options['summary'])) {
            $approved = 0;
            $pending = 0;
            $spam = 0;
            
            foreach ($comments as $comment) {
                if ($comment['status'] === 'approved') {
                    $approved++;
                } elseif ($comment['status'] === 'pending') {
                    $pending++;
                } elseif ($comment['status'] === 'spam') {
                    $spam++;
                }
            }
            
            $options['summary'] = [
                'total' => count($comments),
                'approved' => $approved,
                'pending' => $pending,
                'spam' => $spam,
            ];
        }
        
        // Set entity type
        $options['entity_type'] = 'comments';
        
        return self::formatTable($comments, $options);
    }
    
    /**
     * Format membership list as a table
     *
     * @param array $memberships List of memberships
     * @param array $options Formatting options
     * @return string Formatted table
     */
    public static function formatMembershipList(array $memberships, array $options = []): string {
        // Set default title and summary if not provided
        if (empty($options['title'])) {
            $options['title'] = 'MemberPress Memberships';
        }
        
        // Calculate summary if not provided
        if (empty($options['summary'])) {
            $active = 0;
            $expired = 0;
            $pending = 0;
            $other = 0;
            
            foreach ($memberships as $membership) {
                if ($membership['status'] === 'active') {
                    $active++;
                } elseif ($membership['status'] === 'expired') {
                    $expired++;
                } elseif ($membership['status'] === 'pending') {
                    $pending++;
                } else {
                    $other++;
                }
            }
            
            $options['summary'] = [
                'total' => count($memberships),
                'active' => $active,
                'expired' => $expired,
                'pending' => $pending,
                'other' => $other,
            ];
        }
        
        // Set entity type
        $options['entity_type'] = 'memberships';
        
        return self::formatTable($memberships, $options);
    }
    
    /**
     * Format membership level list as a table
     *
     * @param array $levels List of membership levels
     * @param array $options Formatting options
     * @return string Formatted table
     */
    public static function formatMembershipLevelList(array $levels, array $options = []): string {
        // Set default title and summary if not provided
        if (empty($options['title'])) {
            $options['title'] = 'MemberPress Membership Levels';
        }
        
        // Calculate summary if not provided
        if (empty($options['summary'])) {
            $active = 0;
            $inactive = 0;
            
            foreach ($levels as $level) {
                if (isset($level['active']) && $level['active']) {
                    $active++;
                } else {
                    $inactive++;
                }
            }
            
            $options['summary'] = [
                'total' => count($levels),
                'active' => $active,
                'inactive' => $inactive,
            ];
        }
        
        // Set entity type
        $options['entity_type'] = 'membership_levels';
        
        return self::formatTable($levels, $options);
    }
    
    /**
     * Format data as a markdown table
     *
     * @param array $data Array of items to format
     * @param array $options Formatting options
     * @return string Formatted markdown table
     */
    protected static function formatAsMarkdown(array $data, array $options): string {
        // Extract options
        $title = $options['title'] ?? '';
        $summary = $options['summary'] ?? [];
        $headers = $options['headers'] ?? [];
        
        // Start with a header
        $output = empty($title) ? '' : "# {$title}\n\n";
        
        // Add summary information if provided
        if (!empty($summary)) {
            foreach ($summary as $key => $value) {
                $formatted_key = ucwords(str_replace('_', ' ', $key));
                $output .= "**{$formatted_key}:** {$value}  \n";
            }
            $output .= "\n";
        }
        
        // If no data, return early
        if (empty($data)) {
            return $output . "No data available.\n";
        }
        
        // Determine headers if not provided
        if (empty($headers) && !empty($data)) {
            // Use keys from first item
            $first_item = reset($data);
            if (is_array($first_item)) {
                $headers = array_keys($first_item);
            }
        }
        
        // Create table header
        if (!empty($headers)) {
            $output .= "| " . implode(" | ", array_map('ucfirst', $headers)) . " |\n";
            $output .= "|" . str_repeat("------|", count($headers)) . "\n";
            
            // Add each item to the table
            foreach ($data as $item) {
                $row = [];
                foreach ($headers as $header) {
                    $value = isset($item[$header]) ? $item[$header] : '';
                    // Format boolean values
                    if (is_bool($value)) {
                        $value = $value ? 'Yes' : 'No';
                    }
                    $row[] = $value;
                }
                $output .= "| " . implode(" | ", $row) . " |\n";
            }
        } else {
            // Simple list for non-tabular data
            foreach ($data as $item) {
                $output .= "- " . (is_scalar($item) ? $item : json_encode($item)) . "\n";
            }
        }
        
        return $output;
    }
    
    /**
     * Format data as an HTML table
     *
     * @param array $data Array of items to format
     * @param array $options Formatting options
     * @return string Formatted HTML table
     */
    protected static function formatAsHtml(array $data, array $options): string {
        // Extract options
        $title = $options['title'] ?? '';
        $summary = $options['summary'] ?? [];
        $entity_type = $options['entity_type'] ?? '';
        
        // Start with container
        $output = '<div class="mpai-table-container">';
        
        // Add title if provided
        if (!empty($title)) {
            $output .= '<h2>' . esc_html($title) . '</h2>';
        }
        
        // Add summary information if provided
        if (!empty($summary)) {
            $output .= '<div class="mpai-table-summary">';
            foreach ($summary as $key => $value) {
                $formatted_key = ucwords(str_replace('_', ' ', $key));
                $output .= '<div><strong>' . esc_html($formatted_key) . ':</strong> ' . esc_html($value) . '</div>';
            }
            $output .= '</div>';
        }
        
        // If no data, return early
        if (empty($data)) {
            return $output . '<p>No data available.</p></div>';
        }
        
        // Detect entity type if not explicitly provided
        if (empty($entity_type) && !empty($data) && is_array($data[0])) {
            $first_item = reset($data);
            
            // Check for plugins
            if (isset($first_item['name']) && isset($first_item['active']) && isset($first_item['version'])) {
                $entity_type = 'plugins';
            }
            // Check for posts/pages
            else if (isset($first_item['title']) && isset($first_item['status']) && isset($first_item['date'])) {
                $entity_type = 'posts';
            }
            // Check for comments
            else if (isset($first_item['author']) && isset($first_item['content']) && isset($first_item['date'])) {
                $entity_type = 'comments';
            }
            // Check for memberships
            else if (isset($first_item['user']) && isset($first_item['status']) && isset($first_item['subscription'])) {
                $entity_type = 'memberships';
            }
            // Check for users
            else if (isset($first_item['login']) && isset($first_item['email']) && (isset($first_item['roles']) || isset($first_item['display_name']))) {
                $entity_type = 'users';
            }
            // Check for membership levels
            else if (isset($first_item['name']) && isset($first_item['price']) && isset($first_item['period'])) {
                $entity_type = 'membership_levels';
            }
        }
        
        // Create table based on entity type
        $output .= '<table class="mpai-table">';
        
        // Format based on entity type
        switch ($entity_type) {
            case 'plugins':
                // Create simplified table for plugins
                $output .= '<thead><tr>';
                $output .= '<th>Name</th>';
                $output .= '<th>Version</th>';
                $output .= '<th>Active</th>';
                $output .= '</tr></thead>';
                
                $output .= '<tbody>';
                foreach ($data as $item) {
                    $output .= '<tr>';
                    $output .= '<td>' . esc_html($item['name']) . '</td>';
                    $output .= '<td>' . esc_html($item['version'] ?? '') . '</td>';
                    $output .= '<td>' . esc_html($item['active'] ? 'Yes' : 'No') . '</td>';
                    $output .= '</tr>';
                }
                $output .= '</tbody>';
                break;
                
            case 'posts':
                // Create simplified table for posts/pages
                $output .= '<thead><tr>';
                $output .= '<th>Title</th>';
                $output .= '<th>Date</th>';
                $output .= '<th>Status</th>';
                $output .= '</tr></thead>';
                
                $output .= '<tbody>';
                foreach ($data as $item) {
                    $output .= '<tr>';
                    $output .= '<td>' . esc_html($item['title']) . '</td>';
                    $output .= '<td>' . esc_html($item['date'] ?? '') . '</td>';
                    $output .= '<td>' . esc_html($item['status'] ?? '') . '</td>';
                    $output .= '</tr>';
                }
                $output .= '</tbody>';
                break;
                
            case 'comments':
                // Create simplified table for comments
                $output .= '<thead><tr>';
                $output .= '<th>Author</th>';
                $output .= '<th>Comment</th>';
                $output .= '<th>Date</th>';
                $output .= '</tr></thead>';
                
                $output .= '<tbody>';
                foreach ($data as $item) {
                    $output .= '<tr>';
                    $output .= '<td>' . esc_html($item['author']) . '</td>';
                    // Truncate comment content to avoid very long cells
                    $comment = isset($item['content']) ? substr($item['content'], 0, 100) : '';
                    if (strlen($item['content'] ?? '') > 100) {
                        $comment .= '...';
                    }
                    $output .= '<td>' . esc_html($comment) . '</td>';
                    $output .= '<td>' . esc_html($item['date'] ?? '') . '</td>';
                    $output .= '</tr>';
                }
                $output .= '</tbody>';
                break;
                
            case 'memberships':
                // Create simplified table for memberships
                $output .= '<thead><tr>';
                $output .= '<th>User</th>';
                $output .= '<th>Subscription</th>';
                $output .= '<th>Status</th>';
                $output .= '</tr></thead>';
                
                $output .= '<tbody>';
                foreach ($data as $item) {
                    $output .= '<tr>';
                    $output .= '<td>' . esc_html($item['user']) . '</td>';
                    $output .= '<td>' . esc_html($item['subscription'] ?? '') . '</td>';
                    $output .= '<td>' . esc_html($item['status'] ?? '') . '</td>';
                    $output .= '</tr>';
                }
                $output .= '</tbody>';
                break;
                
            case 'users':
                // Create simplified table for users
                $output .= '<thead><tr>';
                $output .= '<th>Name</th>';
                $output .= '<th>Email</th>';
                $output .= '<th>Role</th>';
                $output .= '</tr></thead>';
                
                $output .= '<tbody>';
                foreach ($data as $item) {
                    $output .= '<tr>';
                    $output .= '<td>' . esc_html($item['display_name'] ?? $item['login']) . '</td>';
                    $output .= '<td>' . esc_html($item['email'] ?? '') . '</td>';
                    
                    // Format roles
                    $roles = '';
                    if (isset($item['roles']) && is_array($item['roles'])) {
                        $roles = implode(', ', array_map('ucfirst', $item['roles']));
                    }
                    
                    $output .= '<td>' . esc_html($roles) . '</td>';
                    $output .= '</tr>';
                }
                $output .= '</tbody>';
                break;
                
            case 'membership_levels':
                // Create simplified table for membership levels
                $output .= '<thead><tr>';
                $output .= '<th>Name</th>';
                $output .= '<th>Price</th>';
                $output .= '<th>Period</th>';
                $output .= '<th>Status</th>';
                $output .= '</tr></thead>';
                
                $output .= '<tbody>';
                foreach ($data as $item) {
                    $output .= '<tr>';
                    $output .= '<td>' . esc_html($item['name']) . '</td>';
                    $output .= '<td>' . esc_html($item['price'] ?? '') . '</td>';
                    $output .= '<td>' . esc_html($item['period'] ?? '') . '</td>';
                    $output .= '<td>' . esc_html(isset($item['active']) && $item['active'] ? 'Active' : 'Inactive') . '</td>';
                    $output .= '</tr>';
                }
                $output .= '</tbody>';
                break;
                
            default:
                // For other data, use the standard table format
                $headers = $options['headers'] ?? [];
                
                // Determine headers if not provided
                if (empty($headers) && !empty($data)) {
                    // Use keys from first item
                    $first_item = reset($data);
                    if (is_array($first_item)) {
                        $headers = array_keys($first_item);
                    }
                }
                
                // Add table header
                if (!empty($headers)) {
                    $output .= '<thead><tr>';
                    foreach ($headers as $header) {
                        $output .= '<th>' . esc_html(ucfirst($header)) . '</th>';
                    }
                    $output .= '</tr></thead>';
                    
                    // Add table body
                    $output .= '<tbody>';
                    foreach ($data as $item) {
                        $output .= '<tr>';
                        foreach ($headers as $header) {
                            $value = isset($item[$header]) ? $item[$header] : '';
                            // Format boolean values
                            if (is_bool($value)) {
                                $value = $value ? 'Yes' : 'No';
                            }
                            $output .= '<td>' . esc_html($value) . '</td>';
                        }
                        $output .= '</tr>';
                    }
                    $output .= '</tbody>';
                } else {
                    // Simple list for non-tabular data
                    $output .= '<ul>';
                    foreach ($data as $item) {
                        $output .= '<li>' . esc_html(is_scalar($item) ? $item : json_encode($item)) . '</li>';
                    }
                    $output .= '</ul>';
                }
                break;
        }
        
        $output .= '</table>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Format data as a plain text table
     *
     * @param array $data Array of items to format
     * @param array $options Formatting options
     * @return string Formatted plain text table
     */
    protected static function formatAsPlain(array $data, array $options): string {
        // Extract options
        $title = $options['title'] ?? '';
        $summary = $options['summary'] ?? [];
        $headers = $options['headers'] ?? [];
        
        // Start with a header
        $output = empty($title) ? '' : "{$title}\n" . str_repeat('=', strlen($title)) . "\n\n";
        
        // Add summary information if provided
        if (!empty($summary)) {
            foreach ($summary as $key => $value) {
                $formatted_key = ucwords(str_replace('_', ' ', $key));
                $output .= "{$formatted_key}: {$value}\n";
            }
            $output .= "\n";
        }
        
        // If no data, return early
        if (empty($data)) {
            return $output . "No data available.\n";
        }
        
        // Determine headers if not provided
        if (empty($headers) && !empty($data)) {
            // Use keys from first item
            $first_item = reset($data);
            if (is_array($first_item)) {
                $headers = array_keys($first_item);
            }
        }
        
        // Calculate column widths
        $widths = [];
        foreach ($headers as $header) {
            $widths[$header] = strlen(ucfirst($header));
            foreach ($data as $item) {
                $value = isset($item[$header]) ? $item[$header] : '';
                if (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                }
                $widths[$header] = max($widths[$header], strlen($value));
            }
        }
        
        // Create table header
        if (!empty($headers)) {
            // Header row
            foreach ($headers as $header) {
                $output .= str_pad(ucfirst($header), $widths[$header] + 2);
            }
            $output .= "\n";
            
            // Separator row
            foreach ($headers as $header) {
                $output .= str_repeat('-', $widths[$header] + 1) . ' ';
            }
            $output .= "\n";
            
            // Data rows
            foreach ($data as $item) {
                foreach ($headers as $header) {
                    $value = isset($item[$header]) ? $item[$header] : '';
                    if (is_bool($value)) {
                        $value = $value ? 'Yes' : 'No';
                    }
                    $output .= str_pad($value, $widths[$header] + 2);
                }
                $output .= "\n";
            }
        } else {
            // Simple list for non-tabular data
            foreach ($data as $item) {
                $output .= "- " . (is_scalar($item) ? $item : json_encode($item)) . "\n";
            }
        }
        
        return $output;
    }
    /**
     * Format user list as a table
     *
     * @param array $users List of users
     * @param array $options Formatting options
     * @return string Formatted table
     */
    public static function formatUserList(array $users, array $options = []): string {
        // Set default title and summary if not provided
        if (empty($options['title'])) {
            $options['title'] = 'WordPress Users';
        }
        
        // Calculate summary if not provided
        if (empty($options['summary'])) {
            $roles = [];
            
            foreach ($users as $user) {
                if (isset($user['roles']) && is_array($user['roles'])) {
                    foreach ($user['roles'] as $role) {
                        if (!isset($roles[$role])) {
                            $roles[$role] = 0;
                        }
                        $roles[$role]++;
                    }
                }
            }
            
            $options['summary'] = [
                'total' => count($users),
            ];
            
            // Add role counts to summary
            foreach ($roles as $role => $count) {
                $options['summary'][$role] = $count;
            }
        }
        
        // Set entity type
        $options['entity_type'] = 'users';
        
        return self::formatTable($users, $options);
    }
}