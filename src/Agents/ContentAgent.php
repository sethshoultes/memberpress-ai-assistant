<?php
/**
 * Content Agent
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Agents;

use MemberpressAiAssistant\Abstracts\AbstractAgent;

/**
 * Agent specialized in content management
 */
class ContentAgent extends AbstractAgent {
    /**
     * {@inheritdoc}
     */
    public function getAgentName(): string {
        return 'Content Agent';
    }

    /**
     * {@inheritdoc}
     */
    public function getAgentDescription(): string {
        return 'Specialized agent for handling content creation, formatting, media management, and content organization.';
    }

    /**
     * {@inheritdoc}
     */
    public function getSystemPrompt(): string {
        return <<<EOT
You are a specialized content management assistant. Your primary responsibilities include:
1. Helping with blog post creation and formatting
2. Managing media assets (images, videos, documents)
3. Organizing content effectively
4. Providing guidance on content strategy and optimization

Focus on creating high-quality, engaging content that follows best practices.
Prioritize content that is well-structured, properly formatted, and optimized for both users and search engines.
EOT;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerCapabilities(): void {
        $this->addCapability('create_post', [
            'description' => 'Create a new blog post',
            'parameters' => ['title', 'content', 'categories', 'tags', 'status'],
        ]);
        
        $this->addCapability('update_post', [
            'description' => 'Update an existing blog post',
            'parameters' => ['id', 'title', 'content', 'categories', 'tags', 'status'],
        ]);
        
        $this->addCapability('delete_post', [
            'description' => 'Delete a blog post',
            'parameters' => ['id'],
        ]);
        
        $this->addCapability('get_post', [
            'description' => 'Get blog post details',
            'parameters' => ['id'],
        ]);
        
        $this->addCapability('list_posts', [
            'description' => 'List all blog posts',
            'parameters' => ['limit', 'offset', 'status'],
        ]);
        
        $this->addCapability('upload_media', [
            'description' => 'Upload a media file',
            'parameters' => ['file', 'title', 'description', 'alt_text'],
        ]);
        
        $this->addCapability('get_media', [
            'description' => 'Get media details',
            'parameters' => ['id'],
        ]);
        
        $this->addCapability('delete_media', [
            'description' => 'Delete a media file',
            'parameters' => ['id'],
        ]);
        
        $this->addCapability('list_media', [
            'description' => 'List all media files',
            'parameters' => ['limit', 'offset', 'type'],
        ]);
        
        $this->addCapability('organize_content', [
            'description' => 'Organize content by categories and tags',
            'parameters' => ['content_ids', 'categories', 'tags'],
        ]);
        
        $this->addCapability('format_content', [
            'description' => 'Format content for better readability',
            'parameters' => ['content', 'format_type'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function processRequest(array $request, array $context): array {
        $this->setContext($context);
        
        // Add request to short-term memory
        $this->remember('request', $request);
        
        // Log the request
        if ($this->logger) {
            $this->logger->info('Processing request with ' . $this->getAgentName(), [
                'request' => $request,
                'agent' => $this->getAgentName(),
            ]);
        }
        
        // Extract the intent from the request
        $intent = $request['intent'] ?? '';
        
        // Process based on intent
        switch ($intent) {
            case 'create_post':
                return $this->createPost($request);
            
            case 'update_post':
                return $this->updatePost($request);
            
            case 'delete_post':
                return $this->deletePost($request);
            
            case 'get_post':
                return $this->getPost($request);
            
            case 'list_posts':
                return $this->listPosts($request);
            
            case 'upload_media':
                return $this->uploadMedia($request);
            
            case 'get_media':
                return $this->getMedia($request);
            
            case 'delete_media':
                return $this->deleteMedia($request);
            
            case 'list_media':
                return $this->listMedia($request);
            
            case 'organize_content':
                return $this->organizeContent($request);
            
            case 'format_content':
                return $this->formatContent($request);
            
            default:
                return [
                    'status' => 'error',
                    'message' => 'Unknown intent: ' . $intent,
                ];
        }
    }

    /**
     * Create a new blog post
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function createPost(array $request): array {
        // Implementation would interact with WordPress API
        return [
            'status' => 'success',
            'message' => 'Blog post created successfully',
            'data' => [
                'id' => rand(1000, 9999), // Simulated ID
                'title' => $request['title'] ?? 'New Blog Post',
                'status' => $request['status'] ?? 'draft',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Update an existing blog post
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function updatePost(array $request): array {
        // Implementation would interact with WordPress API
        return [
            'status' => 'success',
            'message' => 'Blog post updated successfully',
            'data' => [
                'id' => $request['id'] ?? 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Delete a blog post
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function deletePost(array $request): array {
        // Implementation would interact with WordPress API
        return [
            'status' => 'success',
            'message' => 'Blog post deleted successfully',
            'data' => [
                'id' => $request['id'] ?? 0,
            ],
        ];
    }

    /**
     * Get blog post details
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function getPost(array $request): array {
        // Implementation would interact with WordPress API
        return [
            'status' => 'success',
            'message' => 'Blog post retrieved successfully',
            'data' => [
                'id' => $request['id'] ?? 0,
                'title' => 'Sample Blog Post',
                'content' => 'This is a sample blog post content.',
                'status' => 'published',
                'categories' => ['Category 1', 'Category 2'],
                'tags' => ['Tag 1', 'Tag 2'],
                'created_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * List all blog posts
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function listPosts(array $request): array {
        // Implementation would interact with WordPress API
        return [
            'status' => 'success',
            'message' => 'Blog posts retrieved successfully',
            'data' => [
                'posts' => [
                    [
                        'id' => 1001,
                        'title' => 'Getting Started with MemberPress',
                        'status' => 'published',
                        'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                    ],
                    [
                        'id' => 1002,
                        'title' => 'Advanced MemberPress Techniques',
                        'status' => 'published',
                        'created_at' => date('Y-m-d H:i:s', strtotime('-15 days')),
                    ],
                    [
                        'id' => 1003,
                        'title' => 'Upcoming Features',
                        'status' => 'draft',
                        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                    ],
                ],
                'total' => 3,
                'limit' => $request['limit'] ?? 10,
                'offset' => $request['offset'] ?? 0,
            ],
        ];
    }

    /**
     * Upload a media file
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function uploadMedia(array $request): array {
        // Implementation would interact with WordPress API
        return [
            'status' => 'success',
            'message' => 'Media uploaded successfully',
            'data' => [
                'id' => rand(1000, 9999), // Simulated ID
                'title' => $request['title'] ?? 'New Media',
                'url' => 'https://example.com/wp-content/uploads/2023/01/sample-image.jpg',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Get media details
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function getMedia(array $request): array {
        // Implementation would interact with WordPress API
        return [
            'status' => 'success',
            'message' => 'Media retrieved successfully',
            'data' => [
                'id' => $request['id'] ?? 0,
                'title' => 'Sample Media',
                'description' => 'This is a sample media description.',
                'alt_text' => 'Sample alt text',
                'url' => 'https://example.com/wp-content/uploads/2023/01/sample-image.jpg',
                'type' => 'image/jpeg',
                'created_at' => date('Y-m-d H:i:s', strtotime('-14 days')),
            ],
        ];
    }

    /**
     * Delete a media file
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function deleteMedia(array $request): array {
        // Implementation would interact with WordPress API
        return [
            'status' => 'success',
            'message' => 'Media deleted successfully',
            'data' => [
                'id' => $request['id'] ?? 0,
            ],
        ];
    }

    /**
     * List all media files
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function listMedia(array $request): array {
        // Implementation would interact with WordPress API
        return [
            'status' => 'success',
            'message' => 'Media files retrieved successfully',
            'data' => [
                'media' => [
                    [
                        'id' => 2001,
                        'title' => 'Featured Image 1',
                        'type' => 'image/jpeg',
                        'url' => 'https://example.com/wp-content/uploads/2023/01/featured-1.jpg',
                    ],
                    [
                        'id' => 2002,
                        'title' => 'Product Demo Video',
                        'type' => 'video/mp4',
                        'url' => 'https://example.com/wp-content/uploads/2023/01/demo.mp4',
                    ],
                    [
                        'id' => 2003,
                        'title' => 'User Manual',
                        'type' => 'application/pdf',
                        'url' => 'https://example.com/wp-content/uploads/2023/01/manual.pdf',
                    ],
                ],
                'total' => 3,
                'limit' => $request['limit'] ?? 10,
                'offset' => $request['offset'] ?? 0,
            ],
        ];
    }

    /**
     * Organize content by categories and tags
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function organizeContent(array $request): array {
        // Implementation would interact with WordPress API
        return [
            'status' => 'success',
            'message' => 'Content organized successfully',
            'data' => [
                'content_ids' => $request['content_ids'] ?? [],
                'categories' => $request['categories'] ?? [],
                'tags' => $request['tags'] ?? [],
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Format content for better readability
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function formatContent(array $request): array {
        // Implementation would process the content and format it
        return [
            'status' => 'success',
            'message' => 'Content formatted successfully',
            'data' => [
                'original_length' => strlen($request['content'] ?? ''),
                'formatted_length' => strlen($request['content'] ?? '') + 20, // Simulated change
                'format_type' => $request['format_type'] ?? 'standard',
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Calculate intent match score
     *
     * @param array $request The request data
     * @return float Score between 0-30
     */
    protected function calculateIntentMatchScore(array $request): float {
        $score = 0.0;
        $message = strtolower($request['message'] ?? '');
        
        // Check for content-related keywords
        $contentKeywords = [
            'content', 'blog', 'post', 'article', 'media', 'image', 'video',
            'document', 'category', 'tag', 'format', 'organize', 'upload',
            'create post', 'update post', 'delete post', 'get post', 'list posts',
            'upload media', 'get media', 'delete media', 'list media'
        ];
        
        foreach ($contentKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $score += 2.0; // Add 2 points for each keyword match
            }
        }
        
        // Cap at 30
        return min(30.0, $score);
    }

    /**
     * Calculate entity relevance score
     *
     * @param array $request The request data
     * @return float Score between 0-30
     */
    protected function calculateEntityRelevanceScore(array $request): float {
        $score = 0.0;
        $message = strtolower($request['message'] ?? '');
        
        // Check for content-specific entities
        $entities = [
            'blog post' => 5.0,
            'article' => 4.0,
            'media' => 5.0,
            'image' => 4.0,
            'video' => 4.0,
            'document' => 3.0,
            'category' => 3.0,
            'tag' => 3.0,
            'content' => 5.0,
            'format' => 3.0,
            'seo' => 3.0,
            'readability' => 3.0,
        ];
        
        foreach ($entities as $entity => $points) {
            if (strpos($message, $entity) !== false) {
                $score += $points;
            }
        }
        
        // Cap at 30
        return min(30.0, $score);
    }

    /**
     * Calculate capability match score
     *
     * @param array $request The request data
     * @return float Score between 0-20
     */
    protected function calculateCapabilityMatchScore(array $request): float {
        $score = 0.0;
        $message = strtolower($request['message'] ?? '');
        
        // Check if the request matches any of our capabilities
        foreach ($this->capabilities as $capability => $metadata) {
            if (strpos($message, strtolower($capability)) !== false) {
                $score += 5.0; // Add 5 points for each capability match
            }
        }
        
        // Check for action verbs related to our domain
        $actionVerbs = [
            'create' => 3.0,
            'update' => 3.0,
            'delete' => 3.0,
            'get' => 2.0,
            'list' => 2.0,
            'upload' => 3.0,
            'format' => 3.0,
            'organize' => 3.0,
            'write' => 3.0,
            'edit' => 3.0,
        ];
        
        foreach ($actionVerbs as $verb => $points) {
            if (strpos($message, $verb) !== false) {
                $score += $points;
            }
        }
        
        // Cap at 20
        return min(20.0, $score);
    }

    /**
     * Calculate context continuity score
     *
     * @param array $request The request data
     * @return float Score between 0-20
     */
    protected function calculateContextContinuityScore(array $request): float {
        $score = 0.0;
        
        // Check if we have previous requests in memory
        $previousRequest = $this->recall('request');
        if ($previousRequest) {
            // If previous request was also about content, increase score
            if (isset($previousRequest['intent']) && 
                (strpos($previousRequest['intent'], 'post') !== false || 
                 strpos($previousRequest['intent'], 'media') !== false || 
                 strpos($previousRequest['intent'], 'content') !== false)) {
                $score += 10.0;
            }
            
            // If previous request used one of our capabilities, increase score
            foreach ($this->capabilities as $capability => $metadata) {
                if (isset($previousRequest['intent']) && 
                    $previousRequest['intent'] === $capability) {
                    $score += 10.0;
                    break;
                }
            }
        }
        
        // Cap at 20
        return min(20.0, $score);
    }

    /**
     * Apply score multipliers based on agent-specific criteria
     *
     * @param float $score The current score
     * @param array $request The request data
     * @return float The adjusted score
     */
    protected function applyScoreMultipliers(float $score, array $request): float {
        $message = strtolower($request['message'] ?? '');
        
        // Boost score if explicitly mentioning content management
        if (strpos($message, 'content management') !== false || 
            strpos($message, 'blog post') !== false || 
            strpos($message, 'media library') !== false) {
            $score *= 1.5;
        }
        
        // Reduce score if request seems to be about membership operations
        if (strpos($message, 'membership') !== false || 
            strpos($message, 'subscription') !== false || 
            strpos($message, 'payment') !== false || 
            strpos($message, 'access rule') !== false) {
            $score *= 0.7;
        }
        
        // Reduce score if request seems to be about system operations
        if (strpos($message, 'system') !== false || 
            strpos($message, 'plugin') !== false || 
            strpos($message, 'performance') !== false || 
            strpos($message, 'config') !== false) {
            $score *= 0.6;
        }
        
        return $score;
    }
}