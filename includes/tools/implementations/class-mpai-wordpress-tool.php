<?php
/**
 * WordPress Tool Class
 *
 * Provides access to WordPress functionality.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * WordPress Tool for MemberPress AI Assistant
 */
class MPAI_WordPress_Tool extends MPAI_Base_Tool {
    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'WordPress Tool';
        $this->description = 'Provides access to WordPress functionality';
    }
    
    /**
     * Execute the tool
     *
     * @param array $parameters Tool parameters
     * @return mixed Execution result
     */
    public function execute($parameters) {
        // Check for required parameters
        if (!isset($parameters['action'])) {
            throw new Exception('The action parameter is required');
        }
        
        $action = $parameters['action'];
        
        switch ($action) {
            case 'get_posts':
                return $this->get_posts($parameters);
            case 'get_post':
                return $this->get_post($parameters);
            case 'get_users':
                return $this->get_users($parameters);
            case 'get_plugins':
                return $this->get_plugins($parameters);
            case 'get_themes':
                return $this->get_themes($parameters);
            case 'get_site_info':
                return $this->get_site_info();
            default:
                throw new Exception("Unknown action: {$action}");
        }
    }
    
    /**
     * Get posts based on parameters
     *
     * @param array $parameters Parameters
     * @return array Posts
     */
    private function get_posts($parameters) {
        $args = array(
            'post_type' => isset($parameters['post_type']) ? $parameters['post_type'] : 'post',
            'posts_per_page' => isset($parameters['limit']) ? intval($parameters['limit']) : 10,
            'post_status' => isset($parameters['status']) ? $parameters['status'] : 'publish',
            'orderby' => isset($parameters['orderby']) ? $parameters['orderby'] : 'date',
            'order' => isset($parameters['order']) ? $parameters['order'] : 'DESC',
        );
        
        if (isset($parameters['category'])) {
            $args['category_name'] = $parameters['category'];
        }
        
        if (isset($parameters['author'])) {
            $args['author'] = $parameters['author'];
        }
        
        if (isset($parameters['search'])) {
            $args['s'] = $parameters['search'];
        }
        
        $query = new WP_Query($args);
        $posts = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'excerpt' => get_the_excerpt(),
                    'date' => get_the_date('Y-m-d H:i:s'),
                    'modified' => get_the_modified_date('Y-m-d H:i:s'),
                    'author' => get_the_author(),
                    'permalink' => get_permalink(),
                    'featured_image' => get_the_post_thumbnail_url(),
                    'categories' => wp_get_post_categories(get_the_ID(), array('fields' => 'names')),
                    'tags' => wp_get_post_tags(get_the_ID(), array('fields' => 'names')),
                );
                $posts[] = $post;
            }
            wp_reset_postdata();
        }
        
        return array(
            'posts' => $posts,
            'total' => $query->found_posts,
            'max_pages' => $query->max_num_pages
        );
    }
    
    /**
     * Get a specific post by ID
     *
     * @param array $parameters Parameters
     * @return array Post data
     */
    private function get_post($parameters) {
        if (!isset($parameters['id'])) {
            throw new Exception('Post ID is required for get_post action');
        }
        
        $post_id = intval($parameters['id']);
        $post = get_post($post_id);
        
        if (!$post) {
            throw new Exception("Post with ID {$post_id} not found");
        }
        
        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'date' => $post->post_date,
            'modified' => $post->post_modified,
            'author' => get_the_author_meta('display_name', $post->post_author),
            'permalink' => get_permalink($post->ID),
            'featured_image' => get_the_post_thumbnail_url($post->ID),
            'categories' => wp_get_post_categories($post->ID, array('fields' => 'names')),
            'tags' => wp_get_post_tags($post->ID, array('fields' => 'names')),
            'meta' => get_post_meta($post->ID),
        );
    }
    
    /**
     * Get users based on parameters
     *
     * @param array $parameters Parameters
     * @return array Users
     */
    private function get_users($parameters) {
        // Only administrators can retrieve user data
        if (!current_user_can('manage_options')) {
            throw new Exception('Insufficient permissions to retrieve user data');
        }
        
        $args = array(
            'number' => isset($parameters['limit']) ? intval($parameters['limit']) : 10,
            'orderby' => isset($parameters['orderby']) ? $parameters['orderby'] : 'registered',
            'order' => isset($parameters['order']) ? $parameters['order'] : 'DESC',
        );
        
        if (isset($parameters['role'])) {
            $args['role'] = $parameters['role'];
        }
        
        if (isset($parameters['search'])) {
            $args['search'] = '*' . $parameters['search'] . '*';
        }
        
        $users = get_users($args);
        $user_data = array();
        
        foreach ($users as $user) {
            $user_data[] = array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'registered' => $user->user_registered,
                'roles' => $user->roles,
                'post_count' => count_user_posts($user->ID),
            );
        }
        
        return array(
            'users' => $user_data,
            'total' => count_users()['total_users']
        );
    }
    
    /**
     * Get installed plugins
     *
     * @param array $parameters Parameters
     * @return array Plugins
     */
    private function get_plugins($parameters) {
        // Only administrators can retrieve plugin data
        if (!current_user_can('manage_options')) {
            throw new Exception('Insufficient permissions to retrieve plugin data');
        }
        
        if (!function_exists('get_plugins')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', array());
        $plugin_data = array();
        
        foreach ($all_plugins as $plugin_path => $plugin) {
            $plugin_data[] = array(
                'name' => $plugin['Name'],
                'version' => $plugin['Version'],
                'description' => $plugin['Description'],
                'author' => $plugin['Author'],
                'active' => in_array($plugin_path, $active_plugins),
                'path' => $plugin_path,
            );
        }
        
        return array(
            'plugins' => $plugin_data,
            'total' => count($all_plugins),
            'active' => count($active_plugins)
        );
    }
    
    /**
     * Get installed themes
     *
     * @param array $parameters Parameters
     * @return array Themes
     */
    private function get_themes($parameters) {
        // Only administrators can retrieve theme data
        if (!current_user_can('manage_options')) {
            throw new Exception('Insufficient permissions to retrieve theme data');
        }
        
        $all_themes = wp_get_themes();
        $current_theme = wp_get_theme();
        $theme_data = array();
        
        foreach ($all_themes as $theme_name => $theme) {
            $theme_data[] = array(
                'name' => $theme->get('Name'),
                'version' => $theme->get('Version'),
                'description' => $theme->get('Description'),
                'author' => $theme->get('Author'),
                'active' => ($theme_name == $current_theme->get_template()),
                'path' => $theme->get_stylesheet_directory(),
            );
        }
        
        return array(
            'themes' => $theme_data,
            'total' => count($all_themes),
            'current_theme' => $current_theme->get('Name')
        );
    }
    
    /**
     * Get general site information
     *
     * @return array Site information
     */
    private function get_site_info() {
        return array(
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => get_bloginfo('url'),
            'admin_email' => get_bloginfo('admin_email'),
            'version' => get_bloginfo('version'),
            'language' => get_bloginfo('language'),
            'timezone' => wp_timezone_string(),
            'date_format' => get_option('date_format'),
            'time_format' => get_option('time_format'),
            'posts_per_page' => get_option('posts_per_page'),
            'users_count' => count_users()['total_users'],
            'posts_count' => wp_count_posts()->publish,
            'pages_count' => wp_count_posts('page')->publish,
        );
    }
    
    /**
     * Get parameters schema
     *
     * @return array Parameters schema
     */
    public function get_parameters_schema() {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'description' => 'The WordPress action to perform',
                    'enum' => ['get_posts', 'get_post', 'get_users', 'get_plugins', 'get_themes', 'get_site_info']
                ],
                'post_type' => [
                    'type' => 'string',
                    'description' => 'Post type to retrieve (for get_posts action)'
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of items to retrieve',
                    'minimum' => 1
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Post status (for get_posts action)'
                ],
                'orderby' => [
                    'type' => 'string',
                    'description' => 'Field to order results by'
                ],
                'order' => [
                    'type' => 'string',
                    'description' => 'Order direction (ASC or DESC)',
                    'enum' => ['ASC', 'DESC']
                ],
                'category' => [
                    'type' => 'string',
                    'description' => 'Category slug (for get_posts action)'
                ],
                'author' => [
                    'type' => 'integer',
                    'description' => 'Author ID (for get_posts action)'
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Search term'
                ],
                'id' => [
                    'type' => 'integer',
                    'description' => 'Post ID (for get_post action)'
                ],
                'role' => [
                    'type' => 'string',
                    'description' => 'User role (for get_users action)'
                ]
            ],
            'required' => ['action']
        ];
    }
}