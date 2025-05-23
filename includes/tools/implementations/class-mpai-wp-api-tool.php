<?php
/**
 * WordPress API Tool
 *
 * Executes WordPress native functions for common operations
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress API Tool
 */
class MPAI_WP_API_Tool extends MPAI_Base_Tool {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = 'WordPress API Tool';
		$this->description = 'Executes WordPress native functions for common operations';
	}
	
	/**
	 * Get tool definition for AI function calling
	 *
	 * @return array Tool definition
	 */
	public function get_tool_definition() {
		return [
			'name' => 'wp_api',
			'description' => 'Executes WordPress API functions for common operations like creating posts and managing plugins and themes. Note: For MemberPress operations use the memberpress_info tool instead.',
			'parameters' => [
				'type' => 'object',
				'properties' => [
					'action' => [
						'type' => 'string',
						'enum' => [
							'create_post',
							'update_post',
							'get_post',
							'create_page',
							'create_user',
							'get_users',
							'activate_plugin',
							'deactivate_plugin',
							'get_plugins',
							'activate_theme',
							'get_themes',
						],
						'description' => 'The action to perform. Note: For MemberPress operations like create_membership, use the memberpress_info tool instead.'
					],
					'plugin' => [
						'type' => 'string',
						'description' => 'The plugin path to activate or deactivate (e.g. "memberpress-coachkit/memberpress-coachkit.php")'
					],
					'title' => [
						'type' => 'string',
						'description' => 'Title for post or page creation'
					],
					'content' => [
						'type' => 'string',
						'description' => 'Content for post or page creation'
					],
					'post_id' => [
						'type' => 'integer',
						'description' => 'Post ID for updating or retrieving a post'
					],
					'username' => [
						'type' => 'string',
						'description' => 'Username for user creation'
					],
					'email' => [
						'type' => 'string',
						'description' => 'Email for user creation'
					],
					'limit' => [
						'type' => 'integer',
						'description' => 'Number of items to retrieve for listing operations'
					]
				],
				'required' => ['action']
			]
		];
	}

	/**
	 * Get required parameters
	 *
	 * @return array List of required parameter names
	 */
	public function get_required_parameters() {
		return ['action'];
	}
	
	/**
	 * Execute the tool implementation with validated parameters
	 *
	 * @param array $parameters Validated parameters for the tool
	 * @return mixed Execution result
	 */
	protected function execute_tool( $parameters ) {
		try {
			// Log all incoming parameters for debugging
			mpai_log_debug('Full parameters received by execute: ' . json_encode($parameters), 'wp-api-tool');
			
			// Validate action parameter
			if (!isset($parameters['action']) || empty($parameters['action'])) {
				$debug_trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
				$caller = isset($debug_trace[1]['function']) ? $debug_trace[1]['function'] : 'unknown';
				$caller_file = isset($debug_trace[1]['file']) ? basename($debug_trace[1]['file']) : 'unknown';
				$caller_line = isset($debug_trace[1]['line']) ? $debug_trace[1]['line'] : 'unknown';
				
				mpai_log_error('Missing action parameter. Called from ' . $caller_file . ':' . $caller_line . ' in ' . $caller, 'wp-api-tool');
				throw new Exception('Action parameter is required but was missing or empty.');
			}
			
			$action = $parameters['action'];
			mpai_log_debug('Processing action: ' . $action);
			
			// IMPORTANT: Intercept and redirect MemberPress-related actions
			if ($this->is_memberpress_action($action)) {
				mpai_log_warning('MemberPress-related action detected in wp_api tool: ' . $action, 'wp-api-tool');
				$memberpress_guidance = $this->get_memberpress_guidance($action);
				throw new Exception($memberpress_guidance);
			}
			
			// Validate specific action parameters
			switch ($action) {
				case 'activate_plugin':
				case 'deactivate_plugin':
					if (!isset($parameters['plugin']) || empty($parameters['plugin'])) {
						mpai_log_error('Missing plugin parameter for ' . $action . ' action');
						throw new Exception('Plugin parameter is required for ' . $action . ' action');
					}
					break;
					
				case 'update_post':
				case 'get_post':
					if (!isset($parameters['post_id']) || empty($parameters['post_id'])) {
						mpai_log_error('Missing post_id parameter for ' . $action . ' action');
						throw new Exception('Post ID parameter is required for ' . $action . ' action');
					}
					break;
					
				case 'create_user':
					if (!isset($parameters['username']) || empty($parameters['username'])) {
						mpai_log_error('Missing username parameter for create_user action');
						throw new Exception('Username parameter is required for create_user action');
					}
					if (!isset($parameters['email']) || empty($parameters['email'])) {
						mpai_log_error('Missing email parameter for create_user action');
						throw new Exception('Email parameter is required for create_user action');
					}
					break;
			}
			
			// Execute the requested action
			switch ( $action ) {
				case 'create_post':
					// Check required parameters for create_post action
					if (!isset($parameters['title']) || empty($parameters['title'])) {
						mpai_log_warning('Missing title for create_post action');
					}
					if (!isset($parameters['content']) || empty($parameters['content'])) {
						mpai_log_warning('Missing content for create_post action');
					}
					return $this->create_post( $parameters );
				case 'update_post':
					return $this->update_post( $parameters );
				case 'get_post':
					return $this->get_post( $parameters );
				case 'get_posts':
					return $this->get_posts( $parameters );
				case 'create_page':
					$parameters['post_type'] = 'page';
					return $this->create_post( $parameters );
				case 'create_user':
					return $this->create_user( $parameters );
				case 'get_users':
					return $this->get_users( $parameters );
				case 'activate_plugin':
					return $this->activate_plugin( $parameters );
				case 'deactivate_plugin':
					return $this->deactivate_plugin( $parameters );
				case 'get_plugins':
					return $this->get_plugins( $parameters );
				case 'activate_theme':
					return $this->activate_theme( $parameters );
				case 'get_themes':
					return $this->get_themes( $parameters );
				default:
					throw new Exception( 'Unsupported action: ' . $action );
			}
		} catch (Exception $e) {
			mpai_log_error('execute_tool exception: ' . $e->getMessage(), 'wp-api-tool', array(
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString()
			));
			throw $e;
		}
	}
	
	/**
	 * Check if an action is MemberPress-related
	 *
	 * @param string $action Action to check
	 * @return bool Whether the action is MemberPress-related
	 */
	private function is_memberpress_action($action) {
		$memberpress_actions = array(
			'get_memberships',
			'create_membership',
			'get_transactions',
			'get_subscriptions',
			'update_membership',
			'delete_membership',
			'get_membership',
			'add_user_to_membership',
			'remove_user_from_membership',
			'create_coupon',
			'apply_coupon',
			'mepr_',  // Prefix for any other MemberPress actions
		);
		
		foreach ($memberpress_actions as $mp_action) {
			if (strpos($action, $mp_action) !== false) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Get guidance message for MemberPress actions
	 *
	 * @param string $action MemberPress action that was attempted
	 * @return string Guidance message with correct tool to use
	 */
	private function get_memberpress_guidance($action) {
		$base_message = "MemberPress operations should not use the wp_api tool. ";
		
		switch ($action) {
			case 'create_membership':
				return $base_message . "To create a membership, use: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"create\", \"name\": \"Gold Membership\", \"price\": 29.99}}";
				
			case 'get_memberships':
				return $base_message . "To get memberships, use: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"memberships\"}}";
				
			case 'get_transactions':
				return $base_message . "To get transactions, use: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"transactions\"}}";
				
			case 'get_subscriptions':
				return $base_message . "To get subscriptions, use: {\"tool\": \"memberpress_info\", \"parameters\": {\"type\": \"subscriptions\"}}";
				
			default:
				return $base_message . "Please use the memberpress_info tool for all MemberPress operations.";
		}
	}

	/**
	 * Create a post
	 *
	 * @param array $parameters Parameters for post creation
	 * @return array Created post data
	 */
	private function create_post( $parameters ) {
	    // Log the incoming parameters for debugging
	    mpai_log_debug('Create post parameters: ' . json_encode($parameters), 'wp-api-tool');
	    
	    // Check if content is in XML format
	    if (isset($parameters['content']) && strpos($parameters['content'], '<wp-post>') !== false) {
	        mpai_log_debug('Detected XML formatted blog post', 'wp-api-tool');
	        
	        // Include the XML parser class
	        if (!class_exists('MPAI_XML_Content_Parser')) {
	            require_once dirname(dirname(dirname(__FILE__))) . '/class-mpai-xml-content-parser.php';
	        }
	        
	        $xml_parser = new MPAI_XML_Content_Parser();
	        $parsed_data = $xml_parser->parse_xml_blog_post($parameters['content']);
	        
	        if ($parsed_data) {
	            mpai_log_debug('Successfully parsed XML blog post format', 'wp-api-tool');
	            // Override parameters with parsed data
	            foreach ($parsed_data as $key => $value) {
	                $parameters[$key] = $value;
	            }
	            
	            // Make sure we have required parameters
	            if (empty($parameters['title'])) {
	                mpai_log_debug('XML parsed but title is missing, using default', 'wp-api-tool');
	                $parameters['title'] = 'New ' . (isset($parameters['post_type']) && $parameters['post_type'] === 'page' ? 'Page' : 'Post');
	            }
	            
	            // Log the parsed parameters for debugging
	            mpai_log_debug('Parsed parameters: ' . json_encode(array_keys($parameters)), 'wp-api-tool');
	        } else {
	            mpai_log_warning('Failed to parse XML blog post format', 'wp-api-tool');
	            // Instead of failing silently, set a default title and content for better UX
	            if (empty($parameters['title'])) {
	                $parameters['title'] = 'New ' . (isset($parameters['post_type']) && $parameters['post_type'] === 'page' ? 'Page' : 'Post');
	            }
	            
	            // Keep the original content but wrap it in paragraph blocks
	            $parameters['content'] = '<!-- wp:paragraph --><p>' . esc_html($parameters['content']) . '</p><!-- /wp:paragraph -->';
	        }
	    }
	    
		$post_data = array(
			'post_title'   => isset( $parameters['title'] ) ? $parameters['title'] : 'New Post',
			'post_content' => isset( $parameters['content'] ) ? $parameters['content'] : '',
			'post_status'  => isset( $parameters['status'] ) ? $parameters['status'] : 'draft',
			'post_type'    => isset( $parameters['post_type'] ) ? $parameters['post_type'] : 'post',
			'post_author'  => isset( $parameters['author_id'] ) ? $parameters['author_id'] : get_current_user_id(),
		);
		
		// If content is empty, check if there's a message parameter that might contain content
		if (empty($post_data['post_content']) && isset($parameters['message'])) {
		    $post_data['post_content'] = $parameters['message'];
		    mpai_log_debug('Using message parameter as post content', 'wp-api-tool');
		}

		// Add excerpt if provided
		if ( isset( $parameters['excerpt'] ) ) {
			$post_data['post_excerpt'] = $parameters['excerpt'];
		}

		// Insert the post
		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			throw new Exception( 'Failed to create post: ' . $post_id->get_error_message() );
		}

		// Set categories if provided
		if ( isset( $parameters['categories'] ) && is_array( $parameters['categories'] ) ) {
			wp_set_post_categories( $post_id, $parameters['categories'] );
		}

		// Set tags if provided
		if ( isset( $parameters['tags'] ) && is_array( $parameters['tags'] ) ) {
			wp_set_post_tags( $post_id, $parameters['tags'] );
		}

		// Set featured image if provided
		if ( isset( $parameters['featured_image_id'] ) ) {
			set_post_thumbnail( $post_id, $parameters['featured_image_id'] );
		}

		// Get the created post
		$post = get_post( $post_id, ARRAY_A );
		$post_url = get_permalink( $post_id );
		$edit_url = get_edit_post_link( $post_id, 'raw' );

		return array(
			'success'  => true,
			'post_id'  => $post_id,
			'post'     => $post,
			'post_url' => $post_url,
			'edit_url' => $edit_url,
			'message'  => "Successfully created {$post_data['post_type']} with ID {$post_id}",
		);
	}

	/**
	 * Update a post
	 *
	 * @param array $parameters Parameters for post update
	 * @return array Updated post data
	 */
	private function update_post( $parameters ) {
		if ( ! isset( $parameters['post_id'] ) ) {
			throw new Exception( 'Post ID is required' );
		}

		$post_id = $parameters['post_id'];
		$post_data = array(
			'ID' => $post_id,
		);

		// Add fields to update
		if ( isset( $parameters['title'] ) ) {
			$post_data['post_title'] = $parameters['title'];
		}

		if ( isset( $parameters['content'] ) ) {
			$post_data['post_content'] = $parameters['content'];
		}

		if ( isset( $parameters['excerpt'] ) ) {
			$post_data['post_excerpt'] = $parameters['excerpt'];
		}

		if ( isset( $parameters['status'] ) ) {
			$post_data['post_status'] = $parameters['status'];
		}

		// Update the post
		$result = wp_update_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			throw new Exception( 'Failed to update post: ' . $result->get_error_message() );
		}

		// Set categories if provided
		if ( isset( $parameters['categories'] ) && is_array( $parameters['categories'] ) ) {
			wp_set_post_categories( $post_id, $parameters['categories'] );
		}

		// Set tags if provided
		if ( isset( $parameters['tags'] ) && is_array( $parameters['tags'] ) ) {
			wp_set_post_tags( $post_id, $parameters['tags'] );
		}

		// Set featured image if provided
		if ( isset( $parameters['featured_image_id'] ) ) {
			set_post_thumbnail( $post_id, $parameters['featured_image_id'] );
		}

		// Get the updated post
		$post = get_post( $post_id, ARRAY_A );
		$post_url = get_permalink( $post_id );
		$edit_url = get_edit_post_link( $post_id, 'raw' );

		return array(
			'success'  => true,
			'post_id'  => $post_id,
			'post'     => $post,
			'post_url' => $post_url,
			'edit_url' => $edit_url,
			'message'  => "Successfully updated post with ID {$post_id}",
		);
	}

	/**
	 * Get a post
	 *
	 * @param array $parameters Parameters for retrieving post
	 * @return array Post data
	 */
	private function get_post( $parameters ) {
		if ( ! isset( $parameters['post_id'] ) ) {
			throw new Exception( 'Post ID is required' );
		}

		$post_id = $parameters['post_id'];
		$post = get_post( $post_id, ARRAY_A );

		if ( ! $post ) {
			throw new Exception( 'Post not found' );
		}

		$post_url = get_permalink( $post_id );
		$edit_url = get_edit_post_link( $post_id, 'raw' );

		return array(
			'success'  => true,
			'post_id'  => $post_id,
			'post'     => $post,
			'post_url' => $post_url,
			'edit_url' => $edit_url,
		);
	}
	
	/**
	 * Get multiple posts
	 *
	 * @param array $parameters Parameters for retrieving posts
	 * @return array Posts data
	 */
	private function get_posts( $parameters ) {
		// Set up the query arguments
		$args = array(
			'post_type'      => isset( $parameters['post_type'] ) ? $parameters['post_type'] : 'post',
			'post_status'    => isset( $parameters['status'] ) ? $parameters['status'] : 'publish',
			'posts_per_page' => isset( $parameters['limit'] ) ? intval( $parameters['limit'] ) : 10,
			'orderby'        => isset( $parameters['orderby'] ) ? $parameters['orderby'] : 'date',
			'order'          => isset( $parameters['order'] ) ? $parameters['order'] : 'DESC',
		);
		
		// Add category filter if provided
		if ( isset( $parameters['category'] ) ) {
			if ( is_numeric( $parameters['category'] ) ) {
				$args['cat'] = intval( $parameters['category'] );
			} else {
				$args['category_name'] = $parameters['category'];
			}
		}
		
		// Add tag filter if provided
		if ( isset( $parameters['tag'] ) ) {
			$args['tag'] = $parameters['tag'];
		}
		
		// Add author filter if provided
		if ( isset( $parameters['author'] ) ) {
			if ( is_numeric( $parameters['author'] ) ) {
				$args['author'] = intval( $parameters['author'] );
			} else {
				$args['author_name'] = $parameters['author'];
			}
		}
		
		// Add search term if provided
		if ( isset( $parameters['search'] ) ) {
			$args['s'] = $parameters['search'];
		}
		
		// Add date filters if provided
		if ( isset( $parameters['date_after'] ) ) {
			$args['date_query'][] = array(
				'after' => $parameters['date_after'],
				'inclusive' => true,
			);
		}
		
		if ( isset( $parameters['date_before'] ) ) {
			$args['date_query'][] = array(
				'before' => $parameters['date_before'],
				'inclusive' => true,
			);
		}
		
		// Get posts
		$query = new WP_Query( $args );
		$posts = $query->posts;
		$result = array();
		
		foreach ( $posts as $post ) {
			$post_id = $post->ID;
			$post_data = array(
				'ID'           => $post_id,
				'title'        => $post->post_title,
				'date'         => $post->post_date,
				'status'       => $post->post_status,
				'type'         => $post->post_type,
				'excerpt'      => $post->post_excerpt,
				'author'       => get_the_author_meta( 'display_name', $post->post_author ),
				'permalink'    => get_permalink( $post_id ),
				'edit_url'     => get_edit_post_link( $post_id, 'raw' ),
			);
			
			// Get categories
			$categories = wp_get_post_categories( $post_id, array( 'fields' => 'names' ) );
			$post_data['categories'] = $categories;
			
			// Get tags
			$tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
			$post_data['tags'] = $tags;
			
			$result[] = $post_data;
		}
		
		return array(
			'success' => true,
			'count'   => count( $result ),
			'posts'   => $result,
			'total'   => $query->found_posts,
			'max_pages' => $query->max_num_pages,
		);
	}

	/**
	 * Create a user
	 *
	 * @param array $parameters Parameters for user creation
	 * @return array Created user data
	 */
	private function create_user( $parameters ) {
		if ( ! isset( $parameters['username'] ) ) {
			throw new Exception( 'Username is required' );
		}

		if ( ! isset( $parameters['email'] ) ) {
			throw new Exception( 'Email is required' );
		}

		// Create a random password if not provided
		$password = isset( $parameters['password'] ) ? $parameters['password'] : wp_generate_password( 12, true, true );

		$user_data = array(
			'user_login' => $parameters['username'],
			'user_email' => $parameters['email'],
			'user_pass'  => $password,
			'role'       => isset( $parameters['role'] ) ? $parameters['role'] : 'subscriber',
		);

		// Add first name if provided
		if ( isset( $parameters['first_name'] ) ) {
			$user_data['first_name'] = $parameters['first_name'];
		}

		// Add last name if provided
		if ( isset( $parameters['last_name'] ) ) {
			$user_data['last_name'] = $parameters['last_name'];
		}

		// Insert the user
		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			throw new Exception( 'Failed to create user: ' . $user_id->get_error_message() );
		}

		// Add user meta if provided
		if ( isset( $parameters['meta'] ) && is_array( $parameters['meta'] ) ) {
			foreach ( $parameters['meta'] as $meta_key => $meta_value ) {
				update_user_meta( $user_id, $meta_key, $meta_value );
			}
		}

		// Get the created user
		$user = get_userdata( $user_id );

		return array(
			'success'  => true,
			'user_id'  => $user_id,
			'username' => $user->user_login,
			'email'    => $user->user_email,
			'role'     => $user->roles[0],
			'message'  => "Successfully created user '{$user->user_login}' with ID {$user_id}",
		);
	}

	/**
	 * Get users
	 *
	 * @param array $parameters Parameters for retrieving users
	 * @return array Users data
	 */
	private function get_users( $parameters ) {
		$args = array(
			'number'  => isset( $parameters['limit'] ) ? intval( $parameters['limit'] ) : 10,
			'orderby' => isset( $parameters['orderby'] ) ? $parameters['orderby'] : 'ID',
			'order'   => isset( $parameters['order'] ) ? $parameters['order'] : 'ASC',
		);

		// Add role filter if provided
		if ( isset( $parameters['role'] ) ) {
			$args['role'] = $parameters['role'];
		}

		// Add meta query if provided
		if ( isset( $parameters['meta_key'] ) && isset( $parameters['meta_value'] ) ) {
			$args['meta_key']   = $parameters['meta_key'];
			$args['meta_value'] = $parameters['meta_value'];
		}
		
		// Date filtering for registration date
		if ( isset( $parameters['start_date'] ) || isset( $parameters['end_date'] ) || isset( $parameters['month'] ) ) {
			global $wpdb;
			
			// Build the date-based query for users
			$date_query = "SELECT ID FROM {$wpdb->users} WHERE 1=1";
			$query_args = array();
			
			// Handle start_date filter
			if ( isset( $parameters['start_date'] ) ) {
				$date_query .= " AND user_registered >= %s";
				$query_args[] = $parameters['start_date'];
			}
			
			// Handle end_date filter
			if ( isset( $parameters['end_date'] ) ) {
				$date_query .= " AND user_registered <= %s";
				$query_args[] = $parameters['end_date'];
			}
			
			// Handle month filter (current month)
			if ( isset( $parameters['month'] ) && $parameters['month'] === 'current' ) {
				$date_query .= " AND MONTH(user_registered) = MONTH(CURRENT_DATE()) AND YEAR(user_registered) = YEAR(CURRENT_DATE())";
			}
			
			// Handle month filter (previous month)
			if ( isset( $parameters['month'] ) && $parameters['month'] === 'previous' ) {
				$date_query .= " AND (
					(MONTH(user_registered) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) 
					AND YEAR(user_registered) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)))
					OR
					(MONTH(user_registered) = 12 
					AND MONTH(CURRENT_DATE()) = 1 
					AND YEAR(user_registered) = YEAR(CURRENT_DATE()) - 1)
				)";
			}
			
			// Add order and limit
			$date_query .= " ORDER BY user_registered DESC";
			if ( isset( $parameters['limit'] ) ) {
				$date_query .= " LIMIT %d";
				$query_args[] = intval( $parameters['limit'] );
			} else {
				$date_query .= " LIMIT 10";
			}
			
			// Get user IDs matching the date criteria
			$user_ids = $wpdb->get_col( $wpdb->prepare( $date_query, $query_args ) );
			
			if ( !empty( $user_ids ) ) {
				$args['include'] = $user_ids;
			} else {
				// No users match the date criteria
				return array(
					'success' => true,
					'count'   => 0,
					'users'   => array(),
					'message' => 'No users found for the specified date range'
				);
			}
		}

		// Get users
		$users = get_users( $args );
		$result = array();

		foreach ( $users as $user ) {
			$result[] = array(
				'ID'             => $user->ID,
				'user_login'     => $user->user_login,
				'display_name'   => $user->display_name,
				'user_email'     => $user->user_email,
				'roles'          => $user->roles,
				'user_url'       => $user->user_url,
				'user_registered' => $user->user_registered,
				'registered_date' => date( 'Y-m-d', strtotime( $user->user_registered ) ),
			);
		}

		return array(
			'success' => true,
			'count'   => count( $result ),
			'users'   => $result,
		);
	}

	/**
	 * Get plugins installed on the site with activity data from plugin logger
	 *
	 * @param array $parameters Parameters for retrieving plugins
	 * @return array List of plugins with activity data
	 */
	private function get_plugins( $parameters ) {
		mpai_log_debug('get_plugins called with parameters: ' . json_encode($parameters), 'wp-api-tool');
		$current_time = date('H:i:s');
		mpai_log_debug('Current time ' . $current_time, 'wp-api-tool');
		
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		// Get basic plugin data
		$plugins = get_plugins();
		$result = array();
		
		// Get plugin logger data if available
		$activity_data = array();
		$plugin_logger_working = false;
		
		try {
		    if (function_exists('mpai_init_plugin_logger')) {
		        mpai_log_debug('Plugin logger function exists', 'wp-api-tool');
		        
		        // Try to initialize the plugin logger
		        $plugin_logger = mpai_init_plugin_logger();
		        
		        if ($plugin_logger) {
		            mpai_log_debug('Plugin logger initialized', 'wp-api-tool');
		            
		            // Test database connection by checking if the table exists
		            global $wpdb;
		            $table_name = $wpdb->prefix . 'mpai_plugin_logs';
		            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
		            
		            if ($table_exists) {
		                mpai_log_debug('Plugin logs table exists', 'wp-api-tool');
		                
		                // Get activity summary for last 30 days
		                $activity_summary = $plugin_logger->get_activity_summary(30);
		                
		                // Create a lookup map for plugin activity data
		                if (!empty($activity_summary['most_active_plugins'])) {
		                    foreach ($activity_summary['most_active_plugins'] as $plugin_activity) {
		                        $activity_data[$plugin_activity['plugin_name']] = array(
		                            'count' => $plugin_activity['count'],
		                            'last_action' => $plugin_activity['last_action'] ?? 'unknown',
		                            'last_date' => $plugin_activity['last_date'] ?? '',
		                        );
		                    }
		                    $plugin_logger_working = true;
		                    mpai_log_debug('Retrieved activity data for ' . count($activity_data) . ' plugins', 'wp-api-tool');
		                } else {
		                    mpai_log_debug('No active plugins found in logs', 'wp-api-tool');
		                }
		            } else {
		                mpai_log_debug('Plugin logs table does not exist', 'wp-api-tool');
		                // Try to create the table
		                if (method_exists($plugin_logger, 'maybe_create_table')) {
		                    mpai_log_debug('Attempting to create plugin logs table', 'wp-api-tool');
		                    $table_created = $plugin_logger->maybe_create_table(true); // Force table creation
		                    if ($table_created) {
		                        mpai_log_debug('Successfully created plugin logs table', 'wp-api-tool');
		                    } else {
		                        mpai_log_warning('Failed to create plugin logs table', 'wp-api-tool');
		                    }
		                }
		            }
		        } else {
		            mpai_log_warning('Failed to initialize plugin logger', 'wp-api-tool');
		        }
		    } else {
		        mpai_log_debug('Plugin logger function does not exist', 'wp-api-tool');
		    }
		} catch (Exception $e) {
		    mpai_log_error('Exception in plugin logger initialization: ' . $e->getMessage(), 'wp-api-tool', array(
			    'file' => $e->getFile(),
			    'line' => $e->getLine(),
			    'trace' => $e->getTraceAsString()
			));
		}
		
		// Get last activation dates for plugins from wp_options
		$recently_activated = get_option('recently_activated', array());
		$recently_activated_time = get_option('recently_activated_time', time());
		
		// Get plugin update data
		$update_data = get_site_transient('update_plugins');
		$has_updates = array();
		if (isset($update_data->response) && is_array($update_data->response)) {
		    $has_updates = array_keys($update_data->response);
		}
		
		// Merge plugin data with activity data
		foreach ($plugins as $plugin_path => $plugin_data) {
			$is_active = is_plugin_active($plugin_path);
			$plugin_name = $plugin_data['Name'];
			$plugin_slug = dirname($plugin_path);
			$needs_update = in_array($plugin_path, $has_updates);
			
			// Determine last activity - first try plugin logger data
			$activity_info = isset($activity_data[$plugin_name]) ? $activity_data[$plugin_name] : null;
			$activity_count = $activity_info ? $activity_info['count'] : 0;
			$last_action = $activity_info ? $activity_info['last_action'] : 'unknown';
			$last_date = $activity_info ? $activity_info['last_date'] : '';
			
			// Format the last activity date if available from logger
			$activity_display = 'No recent activity';
			if (!empty($last_date)) {
				$timestamp = strtotime($last_date);
				$formatted_date = date('M j, Y', $timestamp);
				$activity_display = ucfirst($last_action) . ' on ' . $formatted_date;
			} 
			// If no logger data but plugin is in recently activated, use that
			else if (array_key_exists($plugin_path, $recently_activated)) {
			    $deactivation_time = $recently_activated[$plugin_path];
			    $formatted_date = date('M j, Y', $deactivation_time);
			    $activity_display = 'Deactivated on ' . $formatted_date;
			} 
			// Otherwise use heuristics
			else {
			    if ($is_active) {
			        if (filemtime(WP_PLUGIN_DIR . '/' . $plugin_path) > time() - 60*60*24*30) {
			            $formatted_date = date('M j, Y', filemtime(WP_PLUGIN_DIR . '/' . $plugin_path));
			            $activity_display = 'Last updated on ' . $formatted_date;
			        }
			    }
			}
			
			$result[] = array(
				'name' => $plugin_name,
				'plugin_path' => $plugin_path,
				'plugin_slug' => $plugin_slug,
				'version' => $plugin_data['Version'],
				'description' => $plugin_data['Description'],
				'author' => $plugin_data['Author'],
				'is_active' => $is_active,
				'status' => $is_active ? 'active' : 'inactive',
				'needs_update' => $needs_update,
				'activity_count' => $activity_count,
				'last_action' => $last_action,
				'last_activity' => $activity_display,
				'generated_at' => $current_time,
			);
		}
		
		// Format for tabular display if requested
		if (isset($parameters['format']) && $parameters['format'] === 'table') {
			mpai_log_debug('Formatting plugins as table', 'wp-api-tool');
			$table_output = "Name\tStatus\tVersion\tLast Activity (Generated at $current_time)\n";
			
			foreach ($result as $plugin) {
				$name = $plugin['name'];
				$status = $plugin['status'];
				$version = $plugin['version'];
				$activity = $plugin['last_activity'];
				
				$table_output .= "$name\t$status\t$version\t$activity\n";
			}
			
			return array(
				'success' => true,
				'count' => count($result),
				'format' => 'table',
				'table_data' => $table_output,
				'plugins' => $result,
				'plugin_logger_available' => $plugin_logger_working
			);
		}
		
		return array(
			'success' => true,
			'count' => count($result),
			'plugins' => $result,
			'plugin_logger_available' => $plugin_logger_working
		);
	}
	
	/**
	 * Activate a plugin
	 *
	 * @param array $parameters Parameters for activating a plugin
	 * @return array Activation result
	 */
	private function activate_plugin( $parameters ) {
		try {
			// Debug log inputs
			mpai_log_debug('activate_plugin: Starting with parameters: ' . json_encode($parameters), 'wp-api-tool');
			
			// Check user capabilities
			if ( ! current_user_can( 'activate_plugins' ) ) {
				mpai_log_error('User does not have activate_plugins capability', 'wp-api-tool');
				throw new Exception( 'You do not have sufficient permissions to activate plugins' );
			}
			
			// Load plugin functions if needed
			if ( ! function_exists( 'activate_plugin' ) || ! function_exists( 'get_plugins' ) ) {
				$plugin_php_path = ABSPATH . 'wp-admin/includes/plugin.php';
				mpai_log_debug('Loading plugin.php from: ' . $plugin_php_path, 'wp-api-tool');
				
				if (file_exists($plugin_php_path)) {
					require_once $plugin_php_path;
					mpai_log_debug('Successfully loaded plugin.php', 'wp-api-tool');
				} else {
					mpai_log_debug('plugin.php not found at expected path, trying alternative', 'wp-api-tool');
					// Try alternative method to find the file
					$alt_path = WP_PLUGIN_DIR . '/../../../wp-admin/includes/plugin.php';
					
					if (file_exists($alt_path)) {
						require_once $alt_path;
						mpai_log_debug('Successfully loaded plugin.php from alternative path', 'wp-api-tool');
					} else {
						mpai_log_error('Failed to load plugin.php', 'wp-api-tool');
						throw new Exception('Required WordPress plugin functions not available');
					}
				}
			}
			
			// Check parameters - provide more detailed error message
			if ( ! isset( $parameters['plugin'] ) || empty($parameters['plugin']) ) {
				$debug_trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
				$caller = isset($debug_trace[1]['function']) ? $debug_trace[1]['function'] : 'unknown';
				$caller_file = isset($debug_trace[1]['file']) ? basename($debug_trace[1]['file']) : 'unknown';
				$caller_line = isset($debug_trace[1]['line']) ? $debug_trace[1]['line'] : 'unknown';
				
				mpai_log_error('Missing plugin parameter. Called from ' . $caller_file . ':' . $caller_line . ' in ' . $caller);
				mpai_log_debug('Full parameters: ' . json_encode($parameters), 'wp-api-tool');
				
				throw new Exception( 'Plugin parameter is required but was missing or empty. This should be the plugin path (e.g. "memberpress-coachkit/memberpress-coachkit.php")' );
			}
			
			// Ensure proper format without escaped slashes
			$plugin = $parameters['plugin'];
			$plugin = str_replace('\\/', '/', $plugin); // Replace escaped slashes
			
			mpai_log_debug('Cleaned plugin path: ' . $plugin, 'wp-api-tool');
			
			// Get available plugins
			$all_plugins = get_plugins();
			
			// Debug
			mpai_log_debug('Attempting to activate plugin: ' . $plugin, 'wp-api-tool');
			mpai_log_debug('Available plugins: ' . implode(', ', array_keys($all_plugins)), 'wp-api-tool');
			
			// Check if plugin exists exactly as specified
			if ( ! isset( $all_plugins[ $plugin ] ) ) {
				mpai_log_debug('Plugin not found directly, trying to find match', 'wp-api-tool');
				
				// Try to find the plugin by partial matching - similar to what validation agent does
				$matching_plugin = $this->find_plugin_path($plugin, $all_plugins);
				
				if ($matching_plugin) {
					mpai_log_debug('Found matching plugin: ' . $matching_plugin, 'wp-api-tool');
					$plugin = $matching_plugin;
				} else {
					mpai_log_warning('No matching plugin found', 'wp-api-tool');
					throw new Exception( "Plugin '{$plugin}' does not exist. Available plugins include: " . 
						implode(', ', array_slice(array_keys($all_plugins), 0, 5)) );
				}
			}
			
			// Check if plugin is already active
			if ( is_plugin_active( $plugin ) ) {
				mpai_log_debug('Plugin already active: ' . $plugin, 'wp-api-tool');
				return array(
					'success' => true,
					'plugin' => $plugin,
					'message' => "Plugin '{$all_plugins[$plugin]['Name']}' is already active",
					'status' => 'active',
				);
			}
			
			// Activate the plugin
			mpai_log_debug('Activating plugin: ' . $plugin, 'wp-api-tool');
			$result = activate_plugin( $plugin );
			
			// Check for errors
			if ( is_wp_error( $result ) ) {
				mpai_log_error('Plugin activation failed: ' . $result->get_error_message(), 'wp-api-tool');
				throw new Exception( "Failed to activate plugin: " . $result->get_error_message() );
			}
			
			mpai_log_debug('Plugin activated successfully: ' . $plugin, 'wp-api-tool');
			return array(
				'success' => true,
				'plugin' => $plugin,
				'plugin_name' => $all_plugins[$plugin]['Name'],
				'message' => "Plugin '{$all_plugins[$plugin]['Name']}' has been activated successfully",
				'status' => 'active',
			);
		} catch (Exception $e) {
			mpai_log_error('activate_plugin exception: ' . $e->getMessage(), 'wp-api-tool', array(
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString()
			));
			throw $e;
		}
	}
	
	/**
	 * Find the correct plugin path based on a slug or partial path
	 *
	 * @param string $plugin_slug The plugin slug or partial path
	 * @param array $available_plugins List of available plugins
	 * @return string|false The correct plugin path or false if not found
	 */
	private function find_plugin_path($plugin_slug, $available_plugins) {
		try {
			// Bail early if plugin_slug is empty
			if (empty($plugin_slug) || empty($available_plugins)) {
				mpai_log_warning('Empty plugin slug or plugins list', 'wp-api-tool');
				return false;
			}
			
			mpai_log_debug('Finding plugin path for: ' . $plugin_slug, 'wp-api-tool');
			
			// Check for direct matches first
			if (isset($available_plugins[$plugin_slug])) {
				mpai_log_debug('Direct match found', 'wp-api-tool');
				return $plugin_slug;
			}
			
			// Clean up plugin slug for better matching
			$plugin_slug = trim($plugin_slug);
			// Remove quotes if present
			$plugin_slug = trim($plugin_slug, '"\'');
			
			// Handle special case for MemberPress plugins by name
			if (stripos($plugin_slug, 'memberpress') !== false) {
				mpai_log_debug('MemberPress plugin detected', 'wp-api-tool');
				
				// Extract specific addon name
				$memberpress_addon = '';
				if (stripos($plugin_slug, 'memberpress-') !== false) {
					$memberpress_addon = str_ireplace('memberpress-', '', $plugin_slug);
					$memberpress_addon = str_ireplace(' plugin', '', $memberpress_addon);
					$memberpress_addon = str_ireplace(' add-on', '', $memberpress_addon);
					$memberpress_addon = str_ireplace(' addon', '', $memberpress_addon);
					$memberpress_addon = trim($memberpress_addon);
				} else if (stripos($plugin_slug, 'memberpress ') !== false) {
					$memberpress_addon = str_ireplace('memberpress ', '', $plugin_slug);
					$memberpress_addon = str_ireplace(' plugin', '', $memberpress_addon);
					$memberpress_addon = str_ireplace(' add-on', '', $memberpress_addon);
					$memberpress_addon = str_ireplace(' addon', '', $memberpress_addon);
					$memberpress_addon = trim($memberpress_addon);
				}
				
				mpai_log_debug('Extracted MemberPress addon: ' . $memberpress_addon, 'wp-api-tool');
				
				// Check for exact matches first
				foreach ($available_plugins as $path => $plugin_data) {
					// Check folder name
					if (!empty($memberpress_addon) && strpos($path, 'memberpress-' . strtolower($memberpress_addon)) === 0) {
						mpai_log_debug('Found exact MemberPress addon match: ' . $path, 'wp-api-tool');
						return $path;
					}
					
					// Check plugin name
					if (!empty($memberpress_addon) && isset($plugin_data['Name']) && 
						(stripos($plugin_data['Name'], 'memberpress ' . $memberpress_addon) !== false || 
						 stripos($plugin_data['Name'], 'memberpress-' . $memberpress_addon) !== false)) {
						mpai_log_debug('Found MemberPress addon by name: ' . $path, 'wp-api-tool');
						return $path;
					}
				}
				
				// Check for partial matches
				if (!empty($memberpress_addon)) {
					foreach ($available_plugins as $path => $plugin_data) {
						// Check if the path has memberpress and the addon name
						if (strpos($path, 'memberpress-') === 0 && stripos($path, $memberpress_addon) !== false) {
							mpai_log_debug('Found partial MemberPress match in path: ' . $path, 'wp-api-tool');
							return $path;
						}
						
						// Check if the plugin name has memberpress and the addon name
						if (isset($plugin_data['Name']) && 
							stripos($plugin_data['Name'], 'memberpress') !== false && 
							stripos($plugin_data['Name'], $memberpress_addon) !== false) {
							mpai_log_debug('Found partial MemberPress match in name: ' . $path, 'wp-api-tool');
							return $path;
						}
					}
				}
				
				// Last resort - return any MemberPress plugin
				foreach ($available_plugins as $path => $plugin_data) {
					if (strpos($path, 'memberpress') === 0) {
						mpai_log_debug('Falling back to first MemberPress plugin in path: ' . $path, 'wp-api-tool');
						return $path;
					}
					
					if (isset($plugin_data['Name']) && stripos($plugin_data['Name'], 'memberpress') !== false) {
						mpai_log_debug('Falling back to first MemberPress plugin by name: ' . $path, 'wp-api-tool');
						return $path;
					}
				}
			}
			
			// Case where plugin path is partially correct (correct folder, wrong main file)
			if (strpos($plugin_slug, '/') !== false) {
				mpai_log_debug('Plugin path contains slash, checking folder', 'wp-api-tool');
				list($folder, $file) = explode('/', $plugin_slug, 2);
				
				// Check if any plugin has this folder
				foreach (array_keys($available_plugins) as $plugin_path) {
					if (strpos($plugin_path, $folder . '/') === 0) {
						mpai_log_debug('Found plugin with matching folder: ' . $plugin_path, 'wp-api-tool');
						return $plugin_path;
					}
				}
			}
			
			// Check for name-based matches
			$plugin_slug_lower = strtolower($plugin_slug);
			
			// Check exact matches
			foreach ($available_plugins as $path => $plugin_data) {
				// Check plugin name
				if (isset($plugin_data['Name']) && strtolower($plugin_data['Name']) === $plugin_slug_lower) {
					mpai_log_debug('Found exact name match: ' . $path, 'wp-api-tool');
					return $path;
				}
				
				// Check folder name
				if (strpos($path, '/') !== false) {
					list($folder, $file) = explode('/', $path, 2);
					if (strtolower($folder) === $plugin_slug_lower) {
						mpai_log_debug('Found folder match: ' . $path, 'wp-api-tool');
						return $path;
					}
				}
			}
			
			// Check partial matches
			foreach ($available_plugins as $path => $plugin_data) {
				// Check if slug is in the plugin name
				if (isset($plugin_data['Name']) && stripos($plugin_data['Name'], $plugin_slug) !== false) {
					mpai_log_debug('Found partial name match: ' . $path, 'wp-api-tool');
					return $path;
				}
				
				// Check if slug is in the path
				if (stripos($path, $plugin_slug) !== false) {
					mpai_log_debug('Found partial path match: ' . $path, 'wp-api-tool');
					return $path;
				}
			}
			
			// No matches found
			mpai_log_warning('No matching plugin found for: ' . $plugin_slug, 'wp-api-tool');
			return false;
		} catch (Exception $e) {
			mpai_log_error('find_plugin_path exception: ' . $e->getMessage(), 'wp-api-tool');
			return false;
		}
	}
	
	/**
	 * Deactivate a plugin
	 *
	 * @param array $parameters Parameters for deactivating a plugin
	 * @return array Deactivation result
	 */
	private function deactivate_plugin( $parameters ) {
		try {
			// Debug log inputs
			mpai_log_debug('deactivate_plugin: Starting with parameters: ' . json_encode($parameters), 'wp-api-tool');
			
			// Check user capabilities
			if ( ! current_user_can( 'activate_plugins' ) ) {
				mpai_log_error('User does not have activate_plugins capability', 'wp-api-tool');
				throw new Exception( 'You do not have sufficient permissions to deactivate plugins' );
			}
			
			// Load plugin functions if needed
			if ( ! function_exists( 'deactivate_plugins' ) || ! function_exists( 'get_plugins' ) ) {
				$plugin_php_path = ABSPATH . 'wp-admin/includes/plugin.php';
				mpai_log_debug('Loading plugin.php from: ' . $plugin_php_path, 'wp-api-tool');
				
				if (file_exists($plugin_php_path)) {
					require_once $plugin_php_path;
					mpai_log_debug('Successfully loaded plugin.php', 'wp-api-tool');
				} else {
					mpai_log_debug('plugin.php not found at expected path, trying alternative', 'wp-api-tool');
					// Try alternative method to find the file
					$alt_path = WP_PLUGIN_DIR . '/../../../wp-admin/includes/plugin.php';
					
					if (file_exists($alt_path)) {
						require_once $alt_path;
						mpai_log_debug('Successfully loaded plugin.php from alternative path', 'wp-api-tool');
					} else {
						mpai_log_error('Failed to load plugin.php', 'wp-api-tool');
						throw new Exception('Required WordPress plugin functions not available');
					}
				}
			}
			
			// Check parameters - provide more detailed error message
			if ( ! isset( $parameters['plugin'] ) || empty($parameters['plugin']) ) {
				$debug_trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
				$caller = isset($debug_trace[1]['function']) ? $debug_trace[1]['function'] : 'unknown';
				$caller_file = isset($debug_trace[1]['file']) ? basename($debug_trace[1]['file']) : 'unknown';
				$caller_line = isset($debug_trace[1]['line']) ? $debug_trace[1]['line'] : 'unknown';
				
				mpai_log_error('Missing plugin parameter. Called from ' . $caller_file . ':' . $caller_line . ' in ' . $caller);
				mpai_log_debug('Full parameters: ' . json_encode($parameters), 'wp-api-tool');
				
				throw new Exception( 'Plugin parameter is required but was missing or empty. This should be the plugin path (e.g. "memberpress-coachkit/memberpress-coachkit.php")' );
			}
			
			// Ensure proper format without escaped slashes
			$plugin = $parameters['plugin'];
			$plugin = str_replace('\\/', '/', $plugin); // Replace escaped slashes
			
			mpai_log_debug('Cleaned plugin path: ' . $plugin, 'wp-api-tool');
			
			// Get available plugins
			$all_plugins = get_plugins();
			
			// Debug
			mpai_log_debug('Attempting to deactivate plugin: ' . $plugin, 'wp-api-tool');
			mpai_log_debug('Available plugins: ' . implode(', ', array_keys($all_plugins)), 'wp-api-tool');
			
			// Check if plugin exists exactly as specified
			if ( ! isset( $all_plugins[ $plugin ] ) ) {
				mpai_log_debug('Plugin not found directly, trying to find match', 'wp-api-tool');
				
				// Try to find the plugin by partial matching
				$matching_plugin = $this->find_plugin_path($plugin, $all_plugins);
				
				if ($matching_plugin) {
					mpai_log_debug('Found matching plugin: ' . $matching_plugin, 'wp-api-tool');
					$plugin = $matching_plugin;
				} else {
					mpai_log_warning('No matching plugin found', 'wp-api-tool');
					throw new Exception( "Plugin '{$plugin}' does not exist. Available plugins include: " . 
						implode(', ', array_slice(array_keys($all_plugins), 0, 5)) );
				}
			}
			
			// Check if plugin is already inactive
			if ( ! is_plugin_active( $plugin ) ) {
				mpai_log_debug('Plugin already inactive: ' . $plugin, 'wp-api-tool');
				return array(
					'success' => true,
					'plugin' => $plugin,
					'message' => "Plugin '{$all_plugins[$plugin]['Name']}' is already inactive",
					'status' => 'inactive',
				);
			}
			
			// Deactivate the plugin
			mpai_log_debug('Deactivating plugin: ' . $plugin, 'wp-api-tool');
			deactivate_plugins( $plugin );
			
			mpai_log_debug('Plugin deactivated successfully: ' . $plugin, 'wp-api-tool');
			return array(
				'success' => true,
				'plugin' => $plugin,
				'plugin_name' => $all_plugins[$plugin]['Name'],
				'message' => "Plugin '{$all_plugins[$plugin]['Name']}' has been deactivated successfully",
				'status' => 'inactive',
			);
		} catch (Exception $e) {
			mpai_log_error('deactivate_plugin exception: ' . $e->getMessage(), 'wp-api-tool', array(
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString()
			));
			throw $e;
		}
	}
	
	/**
	 * Get themes installed on the site
	 *
	 * @param array $parameters Parameters for retrieving themes
	 * @return array List of themes
	 */
	private function get_themes( $parameters ) {
		mpai_log_debug('get_themes called with parameters: ' . json_encode($parameters), 'wp-api-tool');
		$current_time = date('H:i:s');
		
		// Get all themes
		$themes = wp_get_themes();
		$result = array();
		
		// Get current theme
		$current_theme = wp_get_theme();
		$current_stylesheet = $current_theme->get_stylesheet();
		
		foreach ($themes as $theme_slug => $theme) {
			$is_active = ($theme_slug === $current_stylesheet);
			
			$result[] = array(
				'name' => $theme->get('Name'),
				'slug' => $theme_slug,
				'version' => $theme->get('Version'),
				'description' => $theme->get('Description'),
				'author' => $theme->get('Author'),
				'is_active' => $is_active,
				'status' => $is_active ? 'active' : 'inactive',
				'screenshot' => $theme->get_screenshot(),
				'generated_at' => $current_time,
			);
		}
		
		// Format for tabular display if requested
		if (isset($parameters['format']) && $parameters['format'] === 'table') {
			mpai_log_debug('Formatting themes as table', 'wp-api-tool');
			$table_output = "Name\tStatus\tVersion\tAuthor (Generated at $current_time)\n";
			
			foreach ($result as $theme) {
				$name = $theme['name'];
				$status = $theme['status'];
				$version = $theme['version'];
				$author = $theme['author'];
				
				$table_output .= "$name\t$status\t$version\t$author\n";
			}
			
			return array(
				'success' => true,
				'count' => count($result),
				'format' => 'table',
				'table_data' => $table_output,
				'themes' => $result
			);
		}
		
		return array(
			'success' => true,
			'count' => count($result),
			'themes' => $result
		);
	}
	
	/**
	 * Activate a theme
	 *
	 * @param array $parameters Parameters for activating a theme
	 * @return array Activation result
	 */
	private function activate_theme( $parameters ) {
		try {
			// Debug log inputs
			mpai_log_debug('activate_theme: Starting with parameters: ' . json_encode($parameters), 'wp-api-tool');
			
			// Check user capabilities
			if ( ! current_user_can( 'switch_themes' ) ) {
				mpai_log_error('User does not have switch_themes capability', 'wp-api-tool');
				throw new Exception( 'You do not have sufficient permissions to switch themes' );
			}
			
			// Check parameters
			if ( ! isset( $parameters['theme'] ) || empty($parameters['theme']) ) {
				$debug_trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
				$caller = isset($debug_trace[1]['function']) ? $debug_trace[1]['function'] : 'unknown';
				$caller_file = isset($debug_trace[1]['file']) ? basename($debug_trace[1]['file']) : 'unknown';
				$caller_line = isset($debug_trace[1]['line']) ? $debug_trace[1]['line'] : 'unknown';
				
				mpai_log_error('Missing theme parameter. Called from ' . $caller_file . ':' . $caller_line . ' in ' . $caller);
				mpai_log_debug('Full parameters: ' . json_encode($parameters), 'wp-api-tool');
				
				throw new Exception( 'Theme parameter is required but was missing or empty. This should be the theme slug (e.g. "twentytwentythree")' );
			}
			
			// Get the theme slug
			$theme_slug = $parameters['theme'];
			
			// Get all themes
			$themes = wp_get_themes();
			
			// Debug
			mpai_log_debug('Attempting to activate theme: ' . $theme_slug, 'wp-api-tool');
			mpai_log_debug('Available themes: ' . implode(', ', array_keys($themes)), 'wp-api-tool');
			
			// Find the theme
			$theme = null;
			
			// Check for exact match
			if (isset($themes[$theme_slug])) {
				$theme = $themes[$theme_slug];
			} else {
				// Try to find a match by name
				foreach ($themes as $slug => $theme_obj) {
					if (strtolower($theme_obj->get('Name')) === strtolower($theme_slug)) {
						$theme = $theme_obj;
						$theme_slug = $slug;
						break;
					}
				}
				
				// Try to find a partial match
				if (!$theme) {
					foreach ($themes as $slug => $theme_obj) {
						if (stripos($slug, $theme_slug) !== false ||
							stripos($theme_obj->get('Name'), $theme_slug) !== false) {
							$theme = $theme_obj;
							$theme_slug = $slug;
							break;
						}
					}
				}
			}
			
			// Check if theme was found
			if (!$theme) {
				mpai_log_warning('Theme not found: ' . $theme_slug, 'wp-api-tool');
				throw new Exception( "Theme '{$theme_slug}' not found. Available themes include: " .
					implode(', ', array_slice(array_keys($themes), 0, 5)) );
			}
			
			// Get current theme
			$current_theme = wp_get_theme();
			
			// Check if theme is already active
			if ($current_theme->get_stylesheet() === $theme_slug) {
				mpai_log_debug('Theme already active: ' . $theme_slug, 'wp-api-tool');
				return array(
					'success' => true,
					'theme' => $theme_slug,
					'theme_name' => $theme->get('Name'),
					'message' => "Theme '{$theme->get('Name')}' is already active",
					'status' => 'active',
				);
			}
			
			// Activate the theme
			mpai_log_debug('Activating theme: ' . $theme_slug, 'wp-api-tool');
			switch_theme($theme_slug);
			
			// Verify activation
			$new_theme = wp_get_theme();
			if ($new_theme->get_stylesheet() === $theme_slug) {
				mpai_log_debug('Theme activated successfully: ' . $theme_slug, 'wp-api-tool');
				return array(
					'success' => true,
					'theme' => $theme_slug,
					'theme_name' => $theme->get('Name'),
					'message' => "Theme '{$theme->get('Name')}' has been activated successfully",
					'status' => 'active',
				);
			} else {
				mpai_log_error('Failed to activate theme: ' . $theme_slug, 'wp-api-tool');
				throw new Exception( "Failed to activate theme '{$theme->get('Name')}'" );
			}
		} catch (Exception $e) {
			mpai_log_error('activate_theme exception: ' . $e->getMessage(), 'wp-api-tool', array(
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString()
			));
			throw $e;
		}
	}

	/**
	 * Get parameters for the tool
	 *
	 * @return array Tool parameters
	 */
	public function get_parameters() {
		return array(
			'action' => array(
				'type' => 'string',
				'description' => 'Action to perform (create_post, update_post, get_post, create_page, etc.)',
				'enum' => array(
					'create_post',
					'update_post',
					'get_post',
					'create_page',
					'create_user',
					'get_users',
					'activate_plugin',
					'deactivate_plugin',
					'get_plugins',
					'activate_theme',
					'get_themes',
				),
			),
		);
	}
}