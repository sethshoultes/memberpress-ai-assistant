<?php
/**
 * WordPress Tool
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Tools;

use MemberpressAiAssistant\Abstracts\AbstractTool;

/**
 * Tool for handling WordPress operations
 */
class WordPressTool extends AbstractTool {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'wordpress',
            'Tool for handling WordPress operations. For create_post: MUST include post_title and post_type parameters.',
            null
        );
    }
    /**
     * Valid operations that this tool can perform
     *
     * @var array
     */
    protected $validOperations = [
        // Post and page operations
        'create_post',
        'get_post',
        'update_post',
        'delete_post',
        'list_posts',
        'list_pages',
        // User operations
        'create_user',
        'get_user',
        'update_user',
        'list_users',
        // Taxonomy operations
        'create_term',
        'get_term',
        'update_term',
        'delete_term',
        'list_terms',
        // Settings operations
        'get_option',
        'update_option',
        // Plugin operations
        'list_plugins',
        // MemberPress operations
        'memberpress_list_memberships',
        'memberpress_list_membership_levels',
        'memberpress_create_membership',
        'memberpress_get_membership',
        'memberpress_update_membership',
        'memberpress_delete_membership',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getParameters(): array {
        return [
            'type' => 'object',
            'properties' => [
                'operation' => [
                    'type' => 'string',
                    'description' => 'The operation to perform',
                    'enum' => $this->validOperations,
                ],
                // Post and page parameters
                'post_id' => [
                    'type' => 'integer',
                    'description' => 'The ID of the post or page to operate on',
                ],
                'post_type' => [
                    'type' => 'string',
                    'description' => 'The type of post (REQUIRED for create_post operation) - usually "post" for blog posts',
                    'enum' => ['post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom'],
                ],
                'post_status' => [
                    'type' => 'string',
                    'description' => 'The status of the post',
                    'enum' => ['publish', 'draft', 'pending', 'private', 'future', 'trash'],
                ],
                'post_title' => [
                    'type' => 'string',
                    'description' => 'The title of the post (REQUIRED for create_post operation)',
                ],
                'post_content' => [
                    'type' => 'string',
                    'description' => 'The content of the post',
                ],
                'post_excerpt' => [
                    'type' => 'string',
                    'description' => 'The excerpt of the post',
                ],
                'post_author' => [
                    'type' => 'integer',
                    'description' => 'The ID of the post author',
                ],
                'post_date' => [
                    'type' => 'string',
                    'description' => 'The date of the post (format: Y-m-d H:i:s)',
                ],
                'post_parent' => [
                    'type' => 'integer',
                    'description' => 'The ID of the parent post',
                ],
                'post_name' => [
                    'type' => 'string',
                    'description' => 'The slug of the post',
                ],
                'post_password' => [
                    'type' => 'string',
                    'description' => 'The password to access the post',
                ],
                'comment_status' => [
                    'type' => 'string',
                    'description' => 'Whether comments are allowed',
                    'enum' => ['open', 'closed'],
                ],
                'ping_status' => [
                    'type' => 'string',
                    'description' => 'Whether pings are allowed',
                    'enum' => ['open', 'closed'],
                ],
                'meta_input' => [
                    'type' => 'object',
                    'description' => 'Custom fields to add to the post',
                ],
                'tax_input' => [
                    'type' => 'object',
                    'description' => 'Taxonomy terms to assign to the post',
                ],
                // User parameters
                'user_id' => [
                    'type' => 'integer',
                    'description' => 'The ID of the user to operate on',
                ],
                'user_login' => [
                    'type' => 'string',
                    'description' => 'The login username of the user',
                ],
                'user_pass' => [
                    'type' => 'string',
                    'description' => 'The password of the user',
                ],
                'user_email' => [
                    'type' => 'string',
                    'description' => 'The email of the user',
                ],
                'user_url' => [
                    'type' => 'string',
                    'description' => 'The URL of the user',
                ],
                'user_nicename' => [
                    'type' => 'string',
                    'description' => 'The nice name of the user',
                ],
                'display_name' => [
                    'type' => 'string',
                    'description' => 'The display name of the user',
                ],
                'nickname' => [
                    'type' => 'string',
                    'description' => 'The nickname of the user',
                ],
                'first_name' => [
                    'type' => 'string',
                    'description' => 'The first name of the user',
                ],
                'last_name' => [
                    'type' => 'string',
                    'description' => 'The last name of the user',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'The description of the user',
                ],
                'role' => [
                    'type' => 'string',
                    'description' => 'The role of the user',
                    'enum' => ['administrator', 'editor', 'author', 'contributor', 'subscriber'],
                ],
                // Taxonomy parameters
                'taxonomy' => [
                    'type' => 'string',
                    'description' => 'The taxonomy to operate on',
                    'enum' => ['category', 'post_tag', 'nav_menu', 'link_category', 'post_format', 'custom'],
                ],
                'term_id' => [
                    'type' => 'integer',
                    'description' => 'The ID of the term to operate on',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'The name of the term',
                ],
                'slug' => [
                    'type' => 'string',
                    'description' => 'The slug of the term',
                ],
                'term_group' => [
                    'type' => 'integer',
                    'description' => 'The group of the term',
                ],
                'parent' => [
                    'type' => 'integer',
                    'description' => 'The ID of the parent term',
                ],
                // Settings parameters
                'option_name' => [
                    'type' => 'string',
                    'description' => 'The name of the option to operate on',
                ],
                'option_value' => [
                    'type' => 'string',
                    'description' => 'The value of the option',
                ],
                // Common parameters
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Limit for list operations',
                ],
                'offset' => [
                    'type' => 'integer',
                    'description' => 'Offset for list operations',
                ],
                'orderby' => [
                    'type' => 'string',
                    'description' => 'Field to order results by',
                ],
                'order' => [
                    'type' => 'string',
                    'description' => 'Order direction',
                    'enum' => ['ASC', 'DESC'],
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Search term for list operations',
                ],
            ],
            'required' => ['operation'],
        ];
    }

    /**
     * Override getToolDefinition to provide better guidance for LLMs
     */
    public function getToolDefinition(): array {
        $definition = parent::getToolDefinition();
        
        // Enhance the description to be more explicit about requirements
        $definition['description'] = 'Tool for WordPress operations. IMPORTANT: For create_post operation, you MUST provide both post_title and post_type parameters. For blog posts, use post_type="post".';
        
        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateParameters(array $parameters) {
        $errors = [];

        // Check if operation is provided and valid
        if (!isset($parameters['operation'])) {
            $errors[] = 'Operation is required';
        } elseif (!in_array($parameters['operation'], $this->validOperations)) {
            $errors[] = 'Invalid operation: ' . $parameters['operation'];
        } else {
            // Validate parameters based on operation
            switch ($parameters['operation']) {
                // Post operations
                case 'create_post':
                    if (!isset($parameters['post_title'])) {
                        $errors[] = 'Post title is required for create_post operation';
                    }
                    if (!isset($parameters['post_type'])) {
                        $errors[] = 'Post type is required for create_post operation';
                    }
                    break;

                case 'get_post':
                case 'update_post':
                case 'delete_post':
                    if (!isset($parameters['post_id'])) {
                        $errors[] = 'Post ID is required for ' . $parameters['operation'] . ' operation';
                    }
                    break;

                // User operations
                case 'create_user':
                    if (!isset($parameters['user_login'])) {
                        $errors[] = 'User login is required for create_user operation';
                    }
                    if (!isset($parameters['user_email'])) {
                        $errors[] = 'User email is required for create_user operation';
                    }
                    if (!isset($parameters['user_pass'])) {
                        $errors[] = 'User password is required for create_user operation';
                    }
                    break;

                case 'get_user':
                case 'update_user':
                    if (!isset($parameters['user_id'])) {
                        $errors[] = 'User ID is required for ' . $parameters['operation'] . ' operation';
                    }
                    break;

                // Taxonomy operations
                case 'create_term':
                    if (!isset($parameters['name'])) {
                        $errors[] = 'Term name is required for create_term operation';
                    }
                    if (!isset($parameters['taxonomy'])) {
                        $errors[] = 'Taxonomy is required for create_term operation';
                    }
                    break;

                case 'get_term':
                case 'update_term':
                case 'delete_term':
                    if (!isset($parameters['term_id'])) {
                        $errors[] = 'Term ID is required for ' . $parameters['operation'] . ' operation';
                    }
                    if (!isset($parameters['taxonomy'])) {
                        $errors[] = 'Taxonomy is required for ' . $parameters['operation'] . ' operation';
                    }
                    break;

                case 'list_terms':
                    if (!isset($parameters['taxonomy'])) {
                        $errors[] = 'Taxonomy is required for list_terms operation';
                    }
                    break;

                // Settings operations
                case 'get_option':
                case 'update_option':
                    if (!isset($parameters['option_name'])) {
                        $errors[] = 'Option name is required for ' . $parameters['operation'] . ' operation';
                    }
                    if ($parameters['operation'] === 'update_option' && !isset($parameters['option_value'])) {
                        $errors[] = 'Option value is required for update_option operation';
                    }
                    break;
            }
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Execute the tool implementation
     *
     * Implementation of the abstract method from AbstractTool
     *
     * @param array $parameters The validated parameters
     * @return array The result of the tool execution
     */
    protected function executeInternal(array $parameters): array {
        try {
            // Execute the requested operation
            $operation = $parameters['operation'];
            $result = $this->$operation($parameters);

            return $result;
        } catch (\Exception $e) {
            // Log the error
            if ($this->logger) {
                $this->logger->error('Error executing WordPressTool: ' . $e->getMessage(), [
                    'parameters' => $parameters,
                    'exception' => $e,
                ]);
            }

            return [
                'status' => 'error',
                'message' => 'Error executing operation: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create a new post
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function create_post(array $parameters): array {
        // Sanitize inputs
        $post_data = [
            'post_title' => sanitize_text_field($parameters['post_title']),
            'post_type' => sanitize_text_field($parameters['post_type']),
            'post_status' => isset($parameters['post_status']) ? sanitize_text_field($parameters['post_status']) : 'draft',
        ];

        // Optional parameters
        if (isset($parameters['post_content'])) {
            $post_data['post_content'] = wp_kses_post($parameters['post_content']);
        }
        if (isset($parameters['post_excerpt'])) {
            $post_data['post_excerpt'] = sanitize_text_field($parameters['post_excerpt']);
        }
        if (isset($parameters['post_author'])) {
            $post_data['post_author'] = intval($parameters['post_author']);
        }
        if (isset($parameters['post_date'])) {
            $post_data['post_date'] = sanitize_text_field($parameters['post_date']);
        }
        if (isset($parameters['post_parent'])) {
            $post_data['post_parent'] = intval($parameters['post_parent']);
        }
        if (isset($parameters['post_name'])) {
            $post_data['post_name'] = sanitize_title($parameters['post_name']);
        }
        if (isset($parameters['post_password'])) {
            $post_data['post_password'] = sanitize_text_field($parameters['post_password']);
        }
        if (isset($parameters['comment_status'])) {
            $post_data['comment_status'] = sanitize_text_field($parameters['comment_status']);
        }
        if (isset($parameters['ping_status'])) {
            $post_data['ping_status'] = sanitize_text_field($parameters['ping_status']);
        }
        if (isset($parameters['meta_input']) && is_array($parameters['meta_input'])) {
            $post_data['meta_input'] = $parameters['meta_input'];
        }
        if (isset($parameters['tax_input']) && is_array($parameters['tax_input'])) {
            $post_data['tax_input'] = $parameters['tax_input'];
        }

        // Insert the post
        $post_id = wp_insert_post($post_data, true);

        // Check for errors
        if (is_wp_error($post_id)) {
            return [
                'status' => 'error',
                'message' => $post_id->get_error_message(),
            ];
        }

        // Get the created post
        $post = get_post($post_id);

        return [
            'status' => 'success',
            'message' => 'Post created successfully',
            'data' => [
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'status' => $post->post_status,
                'type' => $post->post_type,
                'author' => $post->post_author,
                'date' => $post->post_date,
                'modified' => $post->post_modified,
                'permalink' => get_permalink($post->ID),
            ],
        ];
    }

    /**
     * Get a post
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function get_post(array $parameters): array {
        // Sanitize inputs
        $post_id = intval($parameters['post_id']);

        // Get the post
        $post = get_post($post_id);

        // Check if post exists
        if (!$post) {
            return [
                'status' => 'error',
                'message' => 'Post not found',
            ];
        }

        // Get post meta
        $post_meta = get_post_meta($post_id);
        $meta_data = [];
        foreach ($post_meta as $key => $values) {
            $meta_data[$key] = count($values) === 1 ? $values[0] : $values;
        }

        // Get post terms
        $taxonomies = get_object_taxonomies($post->post_type);
        $terms_data = [];
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy);
            if (!empty($terms) && !is_wp_error($terms)) {
                $terms_data[$taxonomy] = $terms;
            }
        }

        return [
            'status' => 'success',
            'message' => 'Post retrieved successfully',
            'data' => [
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'status' => $post->post_status,
                'type' => $post->post_type,
                'author' => $post->post_author,
                'date' => $post->post_date,
                'modified' => $post->post_modified,
                'permalink' => get_permalink($post->ID),
                'meta' => $meta_data,
                'terms' => $terms_data,
            ],
        ];
    }
/**
     * Update a post
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function update_post(array $parameters): array {
        // Sanitize inputs
        $post_id = intval($parameters['post_id']);

        // Check if post exists
        $post = get_post($post_id);
        if (!$post) {
            return [
                'status' => 'error',
                'message' => 'Post not found',
            ];
        }

        // Prepare post data
        $post_data = [
            'ID' => $post_id,
        ];

        // Optional parameters
        if (isset($parameters['post_title'])) {
            $post_data['post_title'] = sanitize_text_field($parameters['post_title']);
        }
        if (isset($parameters['post_content'])) {
            $post_data['post_content'] = wp_kses_post($parameters['post_content']);
        }
        if (isset($parameters['post_excerpt'])) {
            $post_data['post_excerpt'] = sanitize_text_field($parameters['post_excerpt']);
        }
        if (isset($parameters['post_status'])) {
            $post_data['post_status'] = sanitize_text_field($parameters['post_status']);
        }
        if (isset($parameters['post_author'])) {
            $post_data['post_author'] = intval($parameters['post_author']);
        }
        if (isset($parameters['post_date'])) {
            $post_data['post_date'] = sanitize_text_field($parameters['post_date']);
        }
        if (isset($parameters['post_parent'])) {
            $post_data['post_parent'] = intval($parameters['post_parent']);
        }
        if (isset($parameters['post_name'])) {
            $post_data['post_name'] = sanitize_title($parameters['post_name']);
        }
        if (isset($parameters['post_password'])) {
            $post_data['post_password'] = sanitize_text_field($parameters['post_password']);
        }
        if (isset($parameters['comment_status'])) {
            $post_data['comment_status'] = sanitize_text_field($parameters['comment_status']);
        }
        if (isset($parameters['ping_status'])) {
            $post_data['ping_status'] = sanitize_text_field($parameters['ping_status']);
        }
        if (isset($parameters['meta_input']) && is_array($parameters['meta_input'])) {
            $post_data['meta_input'] = $parameters['meta_input'];
        }
        if (isset($parameters['tax_input']) && is_array($parameters['tax_input'])) {
            $post_data['tax_input'] = $parameters['tax_input'];
        }

        // Update the post
        $result = wp_update_post($post_data, true);

        // Check for errors
        if (is_wp_error($result)) {
            return [
                'status' => 'error',
                'message' => $result->get_error_message(),
            ];
        }

        // Get the updated post
        $updated_post = get_post($post_id);

        return [
            'status' => 'success',
            'message' => 'Post updated successfully',
            'data' => [
                'id' => $updated_post->ID,
                'title' => $updated_post->post_title,
                'content' => $updated_post->post_content,
                'excerpt' => $updated_post->post_excerpt,
                'status' => $updated_post->post_status,
                'type' => $updated_post->post_type,
                'author' => $updated_post->post_author,
                'date' => $updated_post->post_date,
                'modified' => $updated_post->post_modified,
                'permalink' => get_permalink($updated_post->ID),
            ],
        ];
    }

    /**
     * Delete a post
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function delete_post(array $parameters): array {
        // Sanitize inputs
        $post_id = intval($parameters['post_id']);
        $force_delete = isset($parameters['force_delete']) ? (bool) $parameters['force_delete'] : false;

        // Check if post exists
        $post = get_post($post_id);
        if (!$post) {
            return [
                'status' => 'error',
                'message' => 'Post not found',
            ];
        }

        // Delete the post
        $result = wp_delete_post($post_id, $force_delete);

        // Check for errors
        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'Failed to delete post',
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Post deleted successfully',
            'data' => [
                'id' => $post_id,
                'force_deleted' => $force_delete,
            ],
        ];
    }

    /**
     * List posts
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function list_posts(array $parameters): array {
        // Prepare query args
        $args = [
            'post_type' => isset($parameters['post_type']) ? sanitize_text_field($parameters['post_type']) : 'post',
            'post_status' => isset($parameters['post_status']) ? sanitize_text_field($parameters['post_status']) : 'publish',
            'posts_per_page' => isset($parameters['limit']) ? intval($parameters['limit']) : 10,
            'offset' => isset($parameters['offset']) ? intval($parameters['offset']) : 0,
        ];

        // Optional parameters
        if (isset($parameters['orderby'])) {
            $args['orderby'] = sanitize_text_field($parameters['orderby']);
        }
        if (isset($parameters['order'])) {
            $args['order'] = sanitize_text_field($parameters['order']);
        }
        if (isset($parameters['search'])) {
            $args['s'] = sanitize_text_field($parameters['search']);
        }
        if (isset($parameters['author'])) {
            $args['author'] = intval($parameters['author']);
        }
        if (isset($parameters['tax_query']) && is_array($parameters['tax_query'])) {
            $args['tax_query'] = $parameters['tax_query'];
        }
        if (isset($parameters['meta_query']) && is_array($parameters['meta_query'])) {
            $args['meta_query'] = $parameters['meta_query'];
        }

        // Get posts
        $query = new \WP_Query($args);
        $posts = $query->posts;

        // Format posts data
        $posts_data = [];
        foreach ($posts as $post) {
            $posts_data[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'excerpt' => $post->post_excerpt,
                'status' => $post->post_status,
                'type' => $post->post_type,
                'author' => $post->post_author,
                'date' => $post->post_date,
                'modified' => $post->post_modified,
                'permalink' => get_permalink($post->ID),
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Posts retrieved successfully',
            'data' => [
                'posts' => $posts_data,
                'total' => $query->found_posts,
                'max_pages' => $query->max_num_pages,
                'limit' => $args['posts_per_page'],
                'offset' => $args['offset'],
            ],
        ];
    }

    /**
     * Create a new user
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function create_user(array $parameters): array {
        // Sanitize inputs
        $user_data = [
            'user_login' => sanitize_user($parameters['user_login']),
            'user_pass' => $parameters['user_pass'],
            'user_email' => sanitize_email($parameters['user_email']),
            'role' => isset($parameters['role']) ? sanitize_text_field($parameters['role']) : 'subscriber',
        ];

        // Optional parameters
        if (isset($parameters['user_url'])) {
            $user_data['user_url'] = esc_url_raw($parameters['user_url']);
        }
        if (isset($parameters['user_nicename'])) {
            $user_data['user_nicename'] = sanitize_title($parameters['user_nicename']);
        }
        if (isset($parameters['display_name'])) {
            $user_data['display_name'] = sanitize_text_field($parameters['display_name']);
        }
        if (isset($parameters['nickname'])) {
            $user_data['nickname'] = sanitize_text_field($parameters['nickname']);
        }
        if (isset($parameters['first_name'])) {
            $user_data['first_name'] = sanitize_text_field($parameters['first_name']);
        }
        if (isset($parameters['last_name'])) {
            $user_data['last_name'] = sanitize_text_field($parameters['last_name']);
        }
        if (isset($parameters['description'])) {
            $user_data['description'] = sanitize_textarea_field($parameters['description']);
        }

        // Create the user
        $user_id = wp_insert_user($user_data);

        // Check for errors
        if (is_wp_error($user_id)) {
            return [
                'status' => 'error',
                'message' => $user_id->get_error_message(),
            ];
        }

        // Get the created user
        $user = get_userdata($user_id);

        return [
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => [
                'id' => $user->ID,
                'login' => $user->user_login,
                'email' => $user->user_email,
                'url' => $user->user_url,
                'registered' => $user->user_registered,
                'display_name' => $user->display_name,
                'nickname' => $user->nickname,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'description' => $user->description,
                'roles' => $user->roles,
            ],
        ];
    }
/**
     * Get a user
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function get_user(array $parameters): array {
        // Sanitize inputs
        $user_id = intval($parameters['user_id']);

        // Get the user
        $user = get_userdata($user_id);

        // Check if user exists
        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'User not found',
            ];
        }

        // Get user meta
        $user_meta = get_user_meta($user_id);
        $meta_data = [];
        foreach ($user_meta as $key => $values) {
            // Skip sensitive data
            if (in_array($key, ['user_pass', 'session_tokens', 'wp_capabilities', 'wp_user_level'])) {
                continue;
            }
            $meta_data[$key] = count($values) === 1 ? $values[0] : $values;
        }

        return [
            'status' => 'success',
            'message' => 'User retrieved successfully',
            'data' => [
                'id' => $user->ID,
                'login' => $user->user_login,
                'email' => $user->user_email,
                'url' => $user->user_url,
                'registered' => $user->user_registered,
                'display_name' => $user->display_name,
                'nickname' => $user->nickname,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'description' => $user->description,
                'roles' => $user->roles,
                'meta' => $meta_data,
            ],
        ];
    }

    /**
     * Update a user
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function update_user(array $parameters): array {
        // Sanitize inputs
        $user_id = intval($parameters['user_id']);

        // Check if user exists
        $user = get_userdata($user_id);
        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'User not found',
            ];
        }

        // Prepare user data
        $user_data = [
            'ID' => $user_id,
        ];

        // Optional parameters
        if (isset($parameters['user_email'])) {
            $user_data['user_email'] = sanitize_email($parameters['user_email']);
        }
        if (isset($parameters['user_url'])) {
            $user_data['user_url'] = esc_url_raw($parameters['user_url']);
        }
        if (isset($parameters['user_pass'])) {
            $user_data['user_pass'] = $parameters['user_pass'];
        }
        if (isset($parameters['user_nicename'])) {
            $user_data['user_nicename'] = sanitize_title($parameters['user_nicename']);
        }
        if (isset($parameters['display_name'])) {
            $user_data['display_name'] = sanitize_text_field($parameters['display_name']);
        }
        if (isset($parameters['nickname'])) {
            $user_data['nickname'] = sanitize_text_field($parameters['nickname']);
        }
        if (isset($parameters['first_name'])) {
            $user_data['first_name'] = sanitize_text_field($parameters['first_name']);
        }
        if (isset($parameters['last_name'])) {
            $user_data['last_name'] = sanitize_text_field($parameters['last_name']);
        }
        if (isset($parameters['description'])) {
            $user_data['description'] = sanitize_textarea_field($parameters['description']);
        }
        if (isset($parameters['role'])) {
            $user_data['role'] = sanitize_text_field($parameters['role']);
        }

        // Update the user
        $result = wp_update_user($user_data);

        // Check for errors
        if (is_wp_error($result)) {
            return [
                'status' => 'error',
                'message' => $result->get_error_message(),
            ];
        }

        // Get the updated user
        $updated_user = get_userdata($user_id);

        return [
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => [
                'id' => $updated_user->ID,
                'login' => $updated_user->user_login,
                'email' => $updated_user->user_email,
                'url' => $updated_user->user_url,
                'registered' => $updated_user->user_registered,
                'display_name' => $updated_user->display_name,
                'nickname' => $updated_user->nickname,
                'first_name' => $updated_user->first_name,
                'last_name' => $updated_user->last_name,
                'description' => $updated_user->description,
                'roles' => $updated_user->roles,
            ],
        ];
    }

    /**
     * List users
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function list_users(array $parameters): array {
        // Prepare query args
        $args = [
            'number' => isset($parameters['limit']) ? intval($parameters['limit']) : 10,
            'offset' => isset($parameters['offset']) ? intval($parameters['offset']) : 0,
        ];

        // Optional parameters
        if (isset($parameters['role'])) {
            $args['role'] = sanitize_text_field($parameters['role']);
        }
        if (isset($parameters['orderby'])) {
            $args['orderby'] = sanitize_text_field($parameters['orderby']);
        }
        if (isset($parameters['order'])) {
            $args['order'] = sanitize_text_field($parameters['order']);
        }
        if (isset($parameters['search'])) {
            $args['search'] = '*' . sanitize_text_field($parameters['search']) . '*';
        }
        if (isset($parameters['meta_key'])) {
            $args['meta_key'] = sanitize_text_field($parameters['meta_key']);
        }
        if (isset($parameters['meta_value'])) {
            $args['meta_value'] = sanitize_text_field($parameters['meta_value']);
        }

        // Get users
        $user_query = new \WP_User_Query($args);
        $users = $user_query->get_results();

        // Format users data
        $users_data = [];
        foreach ($users as $user) {
            $users_data[] = [
                'id' => $user->ID,
                'login' => $user->user_login,
                'email' => $user->user_email,
                'url' => $user->user_url,
                'registered' => $user->user_registered,
                'display_name' => $user->display_name,
                'roles' => $user->roles,
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Users retrieved successfully',
            'data' => [
                'users' => $users_data,
                'total' => $user_query->get_total(),
                'limit' => $args['number'],
                'offset' => $args['offset'],
            ],
        ];
    }

    /**
     * Create a new term
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function create_term(array $parameters): array {
        // Sanitize inputs
        $name = sanitize_text_field($parameters['name']);
        $taxonomy = sanitize_text_field($parameters['taxonomy']);
        $args = [];

        // Optional parameters
        if (isset($parameters['slug'])) {
            $args['slug'] = sanitize_title($parameters['slug']);
        }
        if (isset($parameters['description'])) {
            $args['description'] = sanitize_textarea_field($parameters['description']);
        }
        if (isset($parameters['parent'])) {
            $args['parent'] = intval($parameters['parent']);
        }

        // Create the term
        $result = wp_insert_term($name, $taxonomy, $args);

        // Check for errors
        if (is_wp_error($result)) {
            return [
                'status' => 'error',
                'message' => $result->get_error_message(),
            ];
        }

        // Get the created term
        $term = get_term($result['term_id'], $taxonomy);

        return [
            'status' => 'success',
            'message' => 'Term created successfully',
            'data' => [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'taxonomy' => $term->taxonomy,
                'description' => $term->description,
                'parent' => $term->parent,
                'count' => $term->count,
            ],
        ];
    }

    /**
     * Get a term
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function get_term(array $parameters): array {
        // Sanitize inputs
        $term_id = intval($parameters['term_id']);
        $taxonomy = sanitize_text_field($parameters['taxonomy']);

        // Get the term
        $term = get_term($term_id, $taxonomy);

        // Check if term exists
        if (!$term || is_wp_error($term)) {
            return [
                'status' => 'error',
                'message' => 'Term not found',
            ];
        }

        // Get term meta
        $term_meta = get_term_meta($term_id);
        $meta_data = [];
        foreach ($term_meta as $key => $values) {
            $meta_data[$key] = count($values) === 1 ? $values[0] : $values;
        }

        return [
            'status' => 'success',
            'message' => 'Term retrieved successfully',
            'data' => [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'taxonomy' => $term->taxonomy,
                'description' => $term->description,
                'parent' => $term->parent,
                'count' => $term->count,
                'meta' => $meta_data,
            ],
        ];
    }

    /**
     * Update a term
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function update_term(array $parameters): array {
        // Sanitize inputs
        $term_id = intval($parameters['term_id']);
        $taxonomy = sanitize_text_field($parameters['taxonomy']);
        $args = [];

        // Check if term exists
        $term = get_term($term_id, $taxonomy);
        if (!$term || is_wp_error($term)) {
            return [
                'status' => 'error',
                'message' => 'Term not found',
            ];
        }

        // Optional parameters
        if (isset($parameters['name'])) {
            $args['name'] = sanitize_text_field($parameters['name']);
        }
        if (isset($parameters['slug'])) {
            $args['slug'] = sanitize_title($parameters['slug']);
        }
        if (isset($parameters['description'])) {
            $args['description'] = sanitize_textarea_field($parameters['description']);
        }
        if (isset($parameters['parent'])) {
            $args['parent'] = intval($parameters['parent']);
        }

        // Update the term
        $result = wp_update_term($term_id, $taxonomy, $args);

        // Check for errors
        if (is_wp_error($result)) {
            return [
                'status' => 'error',
                'message' => $result->get_error_message(),
            ];
        }

        // Get the updated term
        $updated_term = get_term($term_id, $taxonomy);

        return [
            'status' => 'success',
            'message' => 'Term updated successfully',
            'data' => [
                'id' => $updated_term->term_id,
                'name' => $updated_term->name,
                'slug' => $updated_term->slug,
                'taxonomy' => $updated_term->taxonomy,
                'description' => $updated_term->description,
                'parent' => $updated_term->parent,
                'count' => $updated_term->count,
            ],
        ];
    }

    /**
     * Delete a term
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function delete_term(array $parameters): array {
        // Sanitize inputs
        $term_id = intval($parameters['term_id']);
        $taxonomy = sanitize_text_field($parameters['taxonomy']);

        // Check if term exists
        $term = get_term($term_id, $taxonomy);
        if (!$term || is_wp_error($term)) {
            return [
                'status' => 'error',
                'message' => 'Term not found',
            ];
        }

        // Delete the term
        $result = wp_delete_term($term_id, $taxonomy);

        // Check for errors
        if (is_wp_error($result)) {
            return [
                'status' => 'error',
                'message' => $result->get_error_message(),
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Term deleted successfully',
            'data' => [
                'id' => $term_id,
                'taxonomy' => $taxonomy,
            ],
        ];
    }

    /**
     * List terms
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function list_terms(array $parameters): array {
        // Sanitize inputs
        $taxonomy = sanitize_text_field($parameters['taxonomy']);

        // Prepare query args
        $args = [
            'hide_empty' => isset($parameters['hide_empty']) ? (bool) $parameters['hide_empty'] : false,
            'number' => isset($parameters['limit']) ? intval($parameters['limit']) : 0,
            'offset' => isset($parameters['offset']) ? intval($parameters['offset']) : 0,
        ];

        // Optional parameters
        if (isset($parameters['orderby'])) {
            $args['orderby'] = sanitize_text_field($parameters['orderby']);
        }
        if (isset($parameters['order'])) {
            $args['order'] = sanitize_text_field($parameters['order']);
        }
        if (isset($parameters['parent'])) {
            $args['parent'] = intval($parameters['parent']);
        }
        if (isset($parameters['search'])) {
            $args['search'] = sanitize_text_field($parameters['search']);
        }
        if (isset($parameters['meta_key'])) {
            $args['meta_key'] = sanitize_text_field($parameters['meta_key']);
        }
        if (isset($parameters['meta_value'])) {
            $args['meta_value'] = sanitize_text_field($parameters['meta_value']);
        }

        // Get terms
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => $args['hide_empty'],
            'number' => $args['number'],
            'offset' => $args['offset'],
            'orderby' => isset($args['orderby']) ? $args['orderby'] : 'name',
            'order' => isset($args['order']) ? $args['order'] : 'ASC',
            'parent' => isset($args['parent']) ? $args['parent'] : '',
            'search' => isset($args['search']) ? $args['search'] : '',
            'meta_key' => isset($args['meta_key']) ? $args['meta_key'] : '',
            'meta_value' => isset($args['meta_value']) ? $args['meta_value'] : '',
        ]);

        // Check for errors
        if (is_wp_error($terms)) {
            return [
                'status' => 'error',
                'message' => $terms->get_error_message(),
            ];
        }

        // Format terms data
        $terms_data = [];
        foreach ($terms as $term) {
            $terms_data[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'taxonomy' => $term->taxonomy,
                'description' => $term->description,
                'parent' => $term->parent,
                'count' => $term->count,
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Terms retrieved successfully',
            'data' => [
                'terms' => $terms_data,
                'total' => count($terms),
                'limit' => $args['number'],
                'offset' => $args['offset'],
            ],
        ];
    }

    /**
     * Get an option
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function get_option(array $parameters): array {
        // Sanitize inputs
        $option_name = sanitize_text_field($parameters['option_name']);

        // Get the option
        $option_value = get_option($option_name);

        // Check if option exists
        if ($option_value === false) {
            return [
                'status' => 'error',
                'message' => 'Option not found',
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Option retrieved successfully',
            'data' => [
                'name' => $option_name,
                'value' => $option_value,
            ],
        ];
    }

    /**
     * Update an option
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function update_option(array $parameters): array {
        // Sanitize inputs
        $option_name = sanitize_text_field($parameters['option_name']);
        $option_value = $parameters['option_value'];

        // Update the option
        $result = update_option($option_name, $option_value);

        // Check for errors
        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'Failed to update option',
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Option updated successfully',
            'data' => [
                'name' => $option_name,
                'value' => $option_value,
            ],
        ];
    }
    
    /**
     * List installed plugins
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function list_plugins(array $parameters): array {
        // Check if get_plugins function exists
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Get all plugins
        $all_plugins = get_plugins();
        
        // Get active plugins
        $active_plugins = get_option('active_plugins', []);
        
        // Format plugins data
        $plugins_data = [];
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $plugins_data[] = [
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version'],
                'description' => $plugin_data['Description'],
                'author' => $plugin_data['Author'],
                'path' => $plugin_path,
                'active' => in_array($plugin_path, $active_plugins),
            ];
        }
        
        return [
            'status' => 'success',
            'message' => 'Plugins retrieved successfully',
            'data' => [
                'plugins' => $plugins_data,
                'total' => count($plugins_data),
            ],
        ];
    }
    
    /**
     * List pages
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function list_pages(array $parameters): array {
        // Set post_type to 'page'
        $parameters['post_type'] = 'page';
        
        // Use the list_posts method with the modified parameters
        $result = $this->list_posts($parameters);
        
        // Update the message to reflect that these are pages
        if ($result['status'] === 'success') {
            $result['message'] = 'Pages retrieved successfully';
            
            // If there's data, rename 'posts' to 'pages' for clarity
            if (isset($result['data']) && isset($result['data']['posts'])) {
                $result['data']['pages'] = $result['data']['posts'];
                unset($result['data']['posts']);
            }
        }
        
        return $result;
    }
    
    /**
     * List MemberPress memberships
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function memberpress_list_memberships(array $parameters): array {
        // Check if MemberPress is active
        if (!class_exists('MeprUser')) {
            return [
                'status' => 'error',
                'message' => 'MemberPress is not active or installed',
            ];
        }
        
        // Prepare query args
        $args = [
            'number' => isset($parameters['limit']) ? intval($parameters['limit']) : 10,
            'offset' => isset($parameters['offset']) ? intval($parameters['offset']) : 0,
        ];
        
        // Optional parameters
        if (isset($parameters['status'])) {
            $args['status'] = sanitize_text_field($parameters['status']);
        }
        if (isset($parameters['orderby'])) {
            $args['orderby'] = sanitize_text_field($parameters['orderby']);
        }
        if (isset($parameters['order'])) {
            $args['order'] = sanitize_text_field($parameters['order']);
        }
        if (isset($parameters['search'])) {
            $args['search'] = sanitize_text_field($parameters['search']);
        }
        
        // Get memberships
        $memberships = [];
        $message = 'MemberPress memberships retrieved successfully';
        
        // Check if MemberPress is properly installed and configured
        if (class_exists('MeprUser') && class_exists('MeprSubscription')) {
            try {
                // Get all users with MemberPress subscriptions
                $users = get_users();
                
                foreach ($users as $wp_user) {
                    $mepr_user = new \MeprUser($wp_user->ID);
                    
                    // Get active subscriptions for this user
                    if (method_exists($mepr_user, 'active_product_subscriptions')) {
                        $subscriptions = $mepr_user->active_product_subscriptions();
                        
                        if (!empty($subscriptions)) {
                            foreach ($subscriptions as $subscription) {
                                // Get product
                                $product = get_post($subscription->product_id);
                                
                                if ($product) {
                                    $memberships[] = [
                                        'id' => $subscription->id,
                                        'user' => $wp_user->user_login,
                                        'user_id' => $wp_user->ID,
                                        'subscription' => $product->post_title,
                                        'product_id' => $subscription->product_id,
                                        'status' => $subscription->status,
                                        'created_at' => $subscription->created_at,
                                        'expires_at' => $subscription->expires_at,
                                        'total' => $subscription->total,
                                    ];
                                }
                            }
                        }
                    }
                }
                
                // Apply limit and offset
                $memberships = array_slice($memberships, $args['offset'], $args['number']);
                
                // If no memberships found, provide sample data for demonstration
                if (empty($memberships)) {
                    $message = 'No actual memberships found. Showing sample data for demonstration purposes.';
                    
                    // Sample data
                    $memberships = [
                        [
                            'id' => 1,
                            'user' => 'john_doe',
                            'user_id' => 1,
                            'subscription' => 'Monthly Membership',
                            'product_id' => 100,
                            'status' => 'active',
                            'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                            'total' => '19.99',
                        ],
                        [
                            'id' => 2,
                            'user' => 'jane_smith',
                            'user_id' => 2,
                            'subscription' => 'Annual Membership',
                            'product_id' => 101,
                            'status' => 'active',
                            'created_at' => date('Y-m-d H:i:s', strtotime('-60 days')),
                            'expires_at' => date('Y-m-d H:i:s', strtotime('+305 days')),
                            'total' => '199.99',
                        ],
                        [
                            'id' => 3,
                            'user' => 'bob_johnson',
                            'user_id' => 3,
                            'subscription' => 'Premium Membership',
                            'product_id' => 102,
                            'status' => 'expired',
                            'created_at' => date('Y-m-d H:i:s', strtotime('-120 days')),
                            'expires_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                            'total' => '299.99',
                        ],
                    ];
                }
            } catch (\Exception $e) {
                // If there's an error, provide sample data
                $message = 'Error retrieving memberships. Showing sample data for demonstration purposes.';
                
                // Sample data (same as above)
                $memberships = [
                    [
                        'id' => 1,
                        'user' => 'john_doe',
                        'user_id' => 1,
                        'subscription' => 'Monthly Membership',
                        'product_id' => 100,
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                        'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                        'total' => '19.99',
                    ],
                    [
                        'id' => 2,
                        'user' => 'jane_smith',
                        'user_id' => 2,
                        'subscription' => 'Annual Membership',
                        'product_id' => 101,
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s', strtotime('-60 days')),
                        'expires_at' => date('Y-m-d H:i:s', strtotime('+305 days')),
                        'total' => '199.99',
                    ],
                    [
                        'id' => 3,
                        'user' => 'bob_johnson',
                        'user_id' => 3,
                        'subscription' => 'Premium Membership',
                        'product_id' => 102,
                        'status' => 'expired',
                        'created_at' => date('Y-m-d H:i:s', strtotime('-120 days')),
                        'expires_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                        'total' => '299.99',
                    ],
                ];
            }
        } else {
            // MemberPress is not installed or not properly configured
            $message = 'MemberPress is not properly configured. Showing sample data for demonstration purposes.';
            
            // Sample data (same as above)
            $memberships = [
                [
                    'id' => 1,
                    'user' => 'john_doe',
                    'user_id' => 1,
                    'subscription' => 'Monthly Membership',
                    'product_id' => 100,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'total' => '19.99',
                ],
                [
                    'id' => 2,
                    'user' => 'jane_smith',
                    'user_id' => 2,
                    'subscription' => 'Annual Membership',
                    'product_id' => 101,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-60 days')),
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+305 days')),
                    'total' => '199.99',
                ],
                [
                    'id' => 3,
                    'user' => 'bob_johnson',
                    'user_id' => 3,
                    'subscription' => 'Premium Membership',
                    'product_id' => 102,
                    'status' => 'expired',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-120 days')),
                    'expires_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                    'total' => '299.99',
                ],
            ];
        }
        
        return [
            'status' => 'success',
            'message' => $message,
            'data' => [
                'memberships' => $memberships,
                'total' => count($memberships),
                'limit' => $args['number'],
                'offset' => $args['offset'],
            ],
        ];
    }
    
    /**
     * List MemberPress membership levels
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function memberpress_list_membership_levels(array $parameters): array {
        // Check if MemberPress is active
        if (!class_exists('MeprProduct')) {
            return [
                'status' => 'error',
                'message' => 'MemberPress is not active or installed',
            ];
        }
        
        // Prepare query args
        $args = [
            'number' => isset($parameters['limit']) ? intval($parameters['limit']) : 10,
            'offset' => isset($parameters['offset']) ? intval($parameters['offset']) : 0,
        ];
        
        // Optional parameters
        if (isset($parameters['orderby'])) {
            $args['orderby'] = sanitize_text_field($parameters['orderby']);
        }
        if (isset($parameters['order'])) {
            $args['order'] = sanitize_text_field($parameters['order']);
        }
        
        // Get membership levels
        $levels = [];
        $message = 'MemberPress membership levels retrieved successfully';
        
        // Check if MemberPress is properly installed and configured
        if (class_exists('MeprProduct')) {
            try {
                // Get membership levels (products in MemberPress)
                $query_args = [
                    'post_type' => 'memberpressproduct',
                    'posts_per_page' => $args['number'],
                    'offset' => $args['offset'],
                    'post_status' => 'publish',
                ];
                
                if (isset($args['orderby'])) {
                    $query_args['orderby'] = $args['orderby'];
                }
                if (isset($args['order'])) {
                    $query_args['order'] = $args['order'];
                }
                
                $products_query = new \WP_Query($query_args);
                $products = $products_query->posts;
                
                foreach ($products as $product) {
                    if (class_exists('MeprProduct')) {
                        $mepr_product = new \MeprProduct($product->ID);
                        
                        // Get price and terms
                        $price = $mepr_product->price;
                        $period = '';
                        $period_type = '';
                        
                        if (method_exists($mepr_product, 'get_price_str')) {
                            $price_str = $mepr_product->get_price_str();
                        } else {
                            $price_str = '$' . $price;
                        }
                        
                        if (method_exists($mepr_product, 'period_type')) {
                            $period_type = $mepr_product->period_type;
                        }
                        
                        if (method_exists($mepr_product, 'period')) {
                            $period_num = $mepr_product->period;
                            
                            if ($period_type === 'months') {
                                $period = $period_num . ' ' . ($period_num == 1 ? 'month' : 'months');
                            } elseif ($period_type === 'years') {
                                $period = $period_num . ' ' . ($period_num == 1 ? 'year' : 'years');
                            } elseif ($period_type === 'weeks') {
                                $period = $period_num . ' ' . ($period_num == 1 ? 'week' : 'weeks');
                            } elseif ($period_type === 'days') {
                                $period = $period_num . ' ' . ($period_num == 1 ? 'day' : 'days');
                            } else {
                                $period = 'lifetime';
                            }
                        }
                        
                        $levels[] = [
                            'id' => $product->ID,
                            'name' => $product->post_title,
                            'description' => $product->post_excerpt,
                            'price' => $price_str,
                            'period' => $period,
                            'active' => $product->post_status === 'publish',
                        ];
                    }
                }
                
                // If no levels found, provide sample data for demonstration
                if (empty($levels)) {
                    $message = 'No actual membership levels found. Showing sample data for demonstration purposes.';
                    
                    // Sample data
                    $levels = [
                        [
                            'id' => 100,
                            'name' => 'Basic Membership',
                            'description' => 'Access to basic features',
                            'price' => '$9.99',
                            'period' => '1 month',
                            'active' => true,
                        ],
                        [
                            'id' => 101,
                            'name' => 'Premium Membership',
                            'description' => 'Access to premium features',
                            'price' => '$19.99',
                            'period' => '1 month',
                            'active' => true,
                        ],
                        [
                            'id' => 102,
                            'name' => 'Annual Membership',
                            'description' => 'Access to all features for a year',
                            'price' => '$199.99',
                            'period' => '1 year',
                            'active' => true,
                        ],
                    ];
                }
            } catch (\Exception $e) {
                // If there's an error, provide sample data
                $message = 'Error retrieving membership levels. Showing sample data for demonstration purposes.';
                
                // Sample data
                $levels = [
                    [
                        'id' => 100,
                        'name' => 'Basic Membership',
                        'description' => 'Access to basic features',
                        'price' => '$9.99',
                        'period' => '1 month',
                        'active' => true,
                    ],
                    [
                        'id' => 101,
                        'name' => 'Premium Membership',
                        'description' => 'Access to premium features',
                        'price' => '$19.99',
                        'period' => '1 month',
                        'active' => true,
                    ],
                    [
                        'id' => 102,
                        'name' => 'Annual Membership',
                        'description' => 'Access to all features for a year',
                        'price' => '$199.99',
                        'period' => '1 year',
                        'active' => true,
                    ],
                ];
            }
        } else {
            // MemberPress is not installed or not properly configured
            $message = 'MemberPress is not properly configured. Showing sample data for demonstration purposes.';
            
            // Sample data
            $levels = [
                [
                    'id' => 100,
                    'name' => 'Basic Membership',
                    'description' => 'Access to basic features',
                    'price' => '$9.99',
                    'period' => '1 month',
                    'active' => true,
                ],
                [
                    'id' => 101,
                    'name' => 'Premium Membership',
                    'description' => 'Access to premium features',
                    'price' => '$19.99',
                    'period' => '1 month',
                    'active' => true,
                ],
                [
                    'id' => 102,
                    'name' => 'Annual Membership',
                    'description' => 'Access to all features for a year',
                    'price' => '$199.99',
                    'period' => '1 year',
                    'active' => true,
                ],
            ];
        }
        
        return [
            'status' => 'success',
            'message' => $message,
            'data' => [
                'levels' => $levels,
                'total' => count($levels),
                'limit' => $args['number'],
                'offset' => $args['offset'],
            ],
        ];
    }
    
    /**
     * Create a MemberPress membership
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function memberpress_create_membership(array $parameters): array {
        // Add comprehensive debug logging for membership creation
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] WordPressTool::memberpress_create_membership - Starting membership creation');
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Raw parameters received: ' . json_encode($parameters));
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Parameter analysis: name=' . ($parameters['name'] ?? 'MISSING') .
                  ', price=' . ($parameters['price'] ?? 'MISSING') . ', terms=' . ($parameters['terms'] ?? 'MISSING'));
        
        // Check if MemberPress tool is available
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Attempting to get MemberPress tool for delegation');
        $memberPressTool = $this->getMemberPressTool();
        
        if (!$memberPressTool) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::error('[MEMBERSHIP DEBUG] MemberPress tool not available - delegation failed');
            return [
                'status' => 'error',
                'message' => 'MemberPress tool not available - delegation failed',
                'debug_info' => [
                    'delegation_failed' => true,
                    'tool_available' => false,
                    'original_parameters' => $parameters
                ]
            ];
        }
        
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] MemberPress tool obtained successfully: ' . get_class($memberPressTool));
        
        try {
            // Prepare delegation parameters with comprehensive logging
            $delegationParams = [
                'operation' => 'create_membership',
                'name' => $parameters['name'] ?? '',
                'price' => $parameters['price'] ?? '',
                'terms' => $parameters['terms'] ?? '',
                'description' => $parameters['description'] ?? '',
            ];
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Delegation parameters prepared: ' . json_encode($delegationParams));
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Parameter validation: name_empty=' . empty($delegationParams['name']) .
                      ', price_zero=' . ($delegationParams['price'] == 0) . ', terms_value=' . $delegationParams['terms']);
            
            // Delegate to MemberPress tool
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Calling MemberPressTool->execute() with delegation parameters');
            $result = $memberPressTool->execute($delegationParams);
            
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] MemberPressTool execution completed');
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Delegation result: ' . json_encode($result));
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Result status: ' . ($result['status'] ?? 'NO_STATUS'));
            
            // Validate the result to ensure delegation worked
            if (isset($result['status']) && $result['status'] === 'success') {
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Delegation successful - membership created');
                if (isset($result['data'])) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Created membership data: ' . json_encode($result['data']));
                }
            } else {
                \MemberpressAiAssistant\Utilities\LoggingUtility::warning('[MEMBERSHIP DEBUG] Delegation completed but result indicates failure');
            }
            
            return $result;
        } catch (\Exception $e) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::error('[MEMBERSHIP DEBUG] Exception during delegation: ' . $e->getMessage());
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Exception trace: ' . $e->getTraceAsString());
            
            return [
                'status' => 'error',
                'message' => 'Error creating membership: ' . $e->getMessage(),
                'debug_info' => [
                    'delegation_failed' => true,
                    'exception_occurred' => true,
                    'exception_message' => $e->getMessage(),
                    'original_parameters' => $parameters
                ]
            ];
        }
    }
    
    /**
     * Get a MemberPress membership
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function memberpress_get_membership(array $parameters): array {
        // Check if MemberPress tool is available
        $memberPressTool = $this->getMemberPressTool();
        if (!$memberPressTool) {
            return [
                'status' => 'error',
                'message' => 'MemberPress tool not available',
            ];
        }
        
        try {
            // Delegate to MemberPress tool
            return $memberPressTool->execute([
                'operation' => 'get_membership',
                'membership_id' => $parameters['membership_id'] ?? '',
            ]);
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error getting membership: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Update a MemberPress membership
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function memberpress_update_membership(array $parameters): array {
        // Check if MemberPress tool is available
        $memberPressTool = $this->getMemberPressTool();
        if (!$memberPressTool) {
            return [
                'status' => 'error',
                'message' => 'MemberPress tool not available',
            ];
        }
        
        try {
            // Delegate to MemberPress tool
            return $memberPressTool->execute([
                'operation' => 'update_membership',
                'membership_id' => $parameters['membership_id'] ?? '',
                'name' => $parameters['name'] ?? '',
                'price' => $parameters['price'] ?? '',
                'terms' => $parameters['terms'] ?? '',
                'description' => $parameters['description'] ?? '',
            ]);
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error updating membership: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Delete a MemberPress membership
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function memberpress_delete_membership(array $parameters): array {
        // Check if MemberPress tool is available
        $memberPressTool = $this->getMemberPressTool();
        if (!$memberPressTool) {
            return [
                'status' => 'error',
                'message' => 'MemberPress tool not available',
            ];
        }
        
        try {
            // Delegate to MemberPress tool
            return $memberPressTool->execute([
                'operation' => 'delete_membership',
                'membership_id' => $parameters['membership_id'] ?? '',
            ]);
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error deleting membership: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get MemberPress tool instance
     *
     * @return \MemberpressAiAssistant\Tools\MemberPressTool|null
     */
    private function getMemberPressTool() {
        // Add comprehensive debug logging for delegation diagnosis
        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] WordPressTool::getMemberPressTool - Starting tool delegation process');
        
        // Try to get MemberPress tool from global service locator
        global $mpai_service_locator;
        
        if (isset($mpai_service_locator)) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Service locator is available, attempting to get MemberPress tool');
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Service locator class: ' . get_class($mpai_service_locator));
            
            try {
                // FIXED: Check for correct tool registry key 'tool_registry' instead of 'tool.registry'
                if ($mpai_service_locator->has('tool_registry')) {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Tool registry found in service locator with key "tool_registry"');
                    
                    $toolRegistry = $mpai_service_locator->get('tool_registry');
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Tool registry retrieved: ' . get_class($toolRegistry));
                    
                    if ($toolRegistry && method_exists($toolRegistry, 'getTool')) {
                        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Tool registry has getTool method');
                        
                        // Log all available tools for debugging
                        if (method_exists($toolRegistry, 'getAllTools')) {
                            $allTools = $toolRegistry->getAllTools();
                            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Available tools in registry: ' . implode(', ', array_keys($allTools)));
                        }
                        
                        // FIXED: Use correct tool name 'memberpress' instead of 'MemberPressTool'
                        $memberPressTool = $toolRegistry->getTool('memberpress');
                        if ($memberPressTool) {
                            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Successfully retrieved MemberPressTool from registry with name "memberpress"');
                            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Retrieved tool class: ' . get_class($memberPressTool));
                            return $memberPressTool;
                        } else {
                            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] MemberPressTool not found in registry with name "memberpress", falling back to new instance');
                        }
                    } else {
                        \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Tool registry does not have getTool method');
                    }
                } else {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Tool registry not found in service locator with key "tool_registry"');
                    
                    // Check what services are actually available
                    if (method_exists($mpai_service_locator, 'getServices') || method_exists($mpai_service_locator, 'keys')) {
                        try {
                            $availableServices = method_exists($mpai_service_locator, 'getServices')
                                ? array_keys($mpai_service_locator->getServices())
                                : $mpai_service_locator->keys();
                            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Available services in locator: ' . implode(', ', $availableServices));
                        } catch (\Exception $e) {
                            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Could not list available services: ' . $e->getMessage());
                        }
                    }
                }
            } catch (\Exception $e) {
                \MemberpressAiAssistant\Utilities\LoggingUtility::error('[MEMBERSHIP DEBUG] Error accessing tool registry: ' . $e->getMessage());
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Error trace: ' . $e->getTraceAsString());
            }
        } else {
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Global service locator not available, falling back to new instance');
        }
        
        // Fall back to creating a new instance
        if (class_exists('\MemberpressAiAssistant\Tools\MemberPressTool')) {
            \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Creating new MemberPressTool instance');
            
            // Try to get MemberPressService from service locator
            $memberPressService = null;
            if (isset($mpai_service_locator) && $mpai_service_locator->has('memberpress')) {
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Getting MemberPressService from service locator');
                $memberPressService = $mpai_service_locator->get('memberpress');
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] MemberPressService retrieved: ' . get_class($memberPressService));
            } else {
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Creating new MemberPressService instance');
                // Create a new MemberPressService instance
                if (class_exists('\MemberpressAiAssistant\Services\MemberPressService')) {
                    $memberPressService = new \MemberpressAiAssistant\Services\MemberPressService();
                    \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] New MemberPressService created: ' . get_class($memberPressService));
                } else {
                    \MemberpressAiAssistant\Utilities\LoggingUtility::error('[MEMBERSHIP DEBUG] MemberPressService class not found');
                }
            }
            
            if ($memberPressService) {
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] Successfully created MemberPressTool with MemberPressService');
                $newTool = new \MemberpressAiAssistant\Tools\MemberPressTool($memberPressService);
                \MemberpressAiAssistant\Utilities\LoggingUtility::debug('[MEMBERSHIP DEBUG] New MemberPressTool instance created: ' . get_class($newTool));
                return $newTool;
            } else {
                \MemberpressAiAssistant\Utilities\LoggingUtility::error('[MEMBERSHIP DEBUG] Could not create MemberPressService for MemberPressTool');
            }
        } else {
            \MemberpressAiAssistant\Utilities\LoggingUtility::error('[MEMBERSHIP DEBUG] MemberPressTool class not found');
        }
        
        \MemberpressAiAssistant\Utilities\LoggingUtility::error('[MEMBERSHIP DEBUG] Failed to get or create MemberPressTool - delegation will fail');
        return null;
    }
}