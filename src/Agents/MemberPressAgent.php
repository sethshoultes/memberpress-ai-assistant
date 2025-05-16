<?php
/**
 * MemberPress Agent
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Agents;

use MemberpressAiAssistant\Abstracts\AbstractAgent;

/**
 * Agent specialized in MemberPress operations
 */
class MemberPressAgent extends AbstractAgent {
    /**
     * {@inheritdoc}
     */
    public function getAgentName(): string {
        return 'MemberPress Agent';
    }

    /**
     * {@inheritdoc}
     */
    public function getAgentDescription(): string {
        return 'Specialized agent for handling MemberPress membership operations, pricing, terms, and access rules.';
    }

    /**
     * {@inheritdoc}
     */
    public function getSystemPrompt(): string {
        return <<<EOT
You are a specialized MemberPress operations assistant. Your primary responsibilities include:
1. Helping with membership creation and management
2. Managing pricing and terms for memberships
3. Handling access rules and permissions
4. Providing guidance on MemberPress-specific features and functionality

Focus on providing accurate, helpful information about MemberPress operations.
Prioritize security and data integrity when suggesting actions related to memberships.
EOT;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerCapabilities(): void {
        $this->addCapability('create_membership', [
            'description' => 'Create a new membership',
            'parameters' => ['name', 'price', 'terms', 'access_rules'],
        ]);
        
        $this->addCapability('update_membership', [
            'description' => 'Update an existing membership',
            'parameters' => ['id', 'name', 'price', 'terms', 'access_rules'],
        ]);
        
        $this->addCapability('delete_membership', [
            'description' => 'Delete a membership',
            'parameters' => ['id'],
        ]);
        
        $this->addCapability('get_membership', [
            'description' => 'Get membership details',
            'parameters' => ['id'],
        ]);
        
        $this->addCapability('list_memberships', [
            'description' => 'List all memberships',
            'parameters' => ['limit', 'offset'],
        ]);
        
        $this->addCapability('create_access_rule', [
            'description' => 'Create a new access rule',
            'parameters' => ['membership_id', 'content_type', 'content_ids', 'rule_type'],
        ]);
        
        $this->addCapability('update_access_rule', [
            'description' => 'Update an existing access rule',
            'parameters' => ['id', 'membership_id', 'content_type', 'content_ids', 'rule_type'],
        ]);
        
        $this->addCapability('delete_access_rule', [
            'description' => 'Delete an access rule',
            'parameters' => ['id'],
        ]);
        
        $this->addCapability('manage_pricing', [
            'description' => 'Manage pricing for memberships',
            'parameters' => ['membership_id', 'price', 'billing_type', 'billing_frequency'],
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
            case 'create_membership':
                return $this->createMembership($request);
            
            case 'update_membership':
                return $this->updateMembership($request);
            
            case 'delete_membership':
                return $this->deleteMembership($request);
            
            case 'get_membership':
                return $this->getMembership($request);
            
            case 'list_memberships':
                return $this->listMemberships($request);
            
            case 'create_access_rule':
                return $this->createAccessRule($request);
            
            case 'update_access_rule':
                return $this->updateAccessRule($request);
            
            case 'delete_access_rule':
                return $this->deleteAccessRule($request);
            
            case 'manage_pricing':
                return $this->managePricing($request);
            
            default:
                return [
                    'status' => 'error',
                    'message' => 'Unknown intent: ' . $intent,
                ];
        }
    }

    /**
     * Create a new membership
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function createMembership(array $request): array {
        // Implementation would interact with MemberPress API
        return [
            'status' => 'success',
            'message' => 'Membership created successfully',
            'data' => [
                'id' => rand(1000, 9999), // Simulated ID
                'name' => $request['name'] ?? 'New Membership',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Update an existing membership
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function updateMembership(array $request): array {
        // Implementation would interact with MemberPress API
        return [
            'status' => 'success',
            'message' => 'Membership updated successfully',
            'data' => [
                'id' => $request['id'] ?? 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Delete a membership
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function deleteMembership(array $request): array {
        // Implementation would interact with MemberPress API
        return [
            'status' => 'success',
            'message' => 'Membership deleted successfully',
            'data' => [
                'id' => $request['id'] ?? 0,
            ],
        ];
    }

    /**
     * Get membership details
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function getMembership(array $request): array {
        // Implementation would interact with MemberPress API
        return [
            'status' => 'success',
            'message' => 'Membership retrieved successfully',
            'data' => [
                'id' => $request['id'] ?? 0,
                'name' => 'Sample Membership',
                'price' => 19.99,
                'terms' => 'monthly',
                'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * List all memberships
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function listMemberships(array $request): array {
        // Implementation would interact with MemberPress API
        return [
            'status' => 'success',
            'message' => 'Memberships retrieved successfully',
            'data' => [
                'memberships' => [
                    [
                        'id' => 1001,
                        'name' => 'Basic Membership',
                        'price' => 9.99,
                        'terms' => 'monthly',
                    ],
                    [
                        'id' => 1002,
                        'name' => 'Premium Membership',
                        'price' => 19.99,
                        'terms' => 'monthly',
                    ],
                    [
                        'id' => 1003,
                        'name' => 'Annual Membership',
                        'price' => 199.99,
                        'terms' => 'yearly',
                    ],
                ],
                'total' => 3,
                'limit' => $request['limit'] ?? 10,
                'offset' => $request['offset'] ?? 0,
            ],
        ];
    }

    /**
     * Create a new access rule
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function createAccessRule(array $request): array {
        // Implementation would interact with MemberPress API
        return [
            'status' => 'success',
            'message' => 'Access rule created successfully',
            'data' => [
                'id' => rand(1000, 9999), // Simulated ID
                'membership_id' => $request['membership_id'] ?? 0,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Update an existing access rule
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function updateAccessRule(array $request): array {
        // Implementation would interact with MemberPress API
        return [
            'status' => 'success',
            'message' => 'Access rule updated successfully',
            'data' => [
                'id' => $request['id'] ?? 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Delete an access rule
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function deleteAccessRule(array $request): array {
        // Implementation would interact with MemberPress API
        return [
            'status' => 'success',
            'message' => 'Access rule deleted successfully',
            'data' => [
                'id' => $request['id'] ?? 0,
            ],
        ];
    }

    /**
     * Manage pricing for memberships
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function managePricing(array $request): array {
        // Implementation would interact with MemberPress API
        return [
            'status' => 'success',
            'message' => 'Pricing updated successfully',
            'data' => [
                'membership_id' => $request['membership_id'] ?? 0,
                'price' => $request['price'] ?? 0,
                'billing_type' => $request['billing_type'] ?? 'recurring',
                'billing_frequency' => $request['billing_frequency'] ?? 'monthly',
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
        
        // Check for membership-related keywords
        $membershipKeywords = [
            'membership', 'member', 'subscription', 'access', 'rule', 'permission',
            'pricing', 'price', 'payment', 'term', 'billing', 'subscribe',
            'memberpress', 'mepr', 'mp', 'create membership', 'update membership',
            'delete membership', 'list memberships', 'access rule'
        ];
        
        foreach ($membershipKeywords as $keyword) {
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
        
        // Check for MemberPress-specific entities
        $entities = [
            'membership' => 5.0,
            'subscription' => 5.0,
            'transaction' => 4.0,
            'coupon' => 3.0,
            'access rule' => 5.0,
            'pricing' => 4.0,
            'payment method' => 3.0,
            'member' => 5.0,
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
            'manage' => 3.0,
            'configure' => 2.0,
            'setup' => 2.0,
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
            // If previous request was also about memberships, increase score
            if (isset($previousRequest['intent']) && 
                strpos($previousRequest['intent'], 'membership') !== false) {
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
        
        // Boost score if explicitly mentioning MemberPress
        if (strpos($message, 'memberpress') !== false || 
            strpos($message, 'mepr') !== false || 
            strpos($message, 'mp') !== false) {
            $score *= 1.5;
        }
        
        // Reduce score if request seems to be about content management
        if (strpos($message, 'content') !== false || 
            strpos($message, 'post') !== false || 
            strpos($message, 'page') !== false || 
            strpos($message, 'media') !== false) {
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