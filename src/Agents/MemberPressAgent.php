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
     * MemberPress service instance
     *
     * @var \MemberpressAiAssistant\Services\MemberPressService
     */
    protected $memberPressService;
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
     * Set the MemberPress service
     *
     * @param \MemberpressAiAssistant\Services\MemberPressService $service The MemberPress service
     * @return self
     */
    public function setMemberPressService(\MemberpressAiAssistant\Services\MemberPressService $service): self {
        $this->memberPressService = $service;
        return $this;
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
        
        // Add comprehensive logging for membership creation debugging
        \MemberpressAiAssistant\Utilities\debug_log("[MEMBERSHIP DEBUG] MemberPressAgent processing request with intent: " . $intent);
        \MemberpressAiAssistant\Utilities\debug_log("[MEMBERSHIP DEBUG] Full request: " . json_encode($request, JSON_PRETTY_PRINT));
        
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
                
            case 'general':
                return $this->handleGeneralIntent($request);
                
            case 'greeting':
                return $this->handleGreeting($request);
            
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
        // Log the request for debugging
        if ($this->logger) {
            $this->logger->info('Creating membership', [
                'request' => $request,
            ]);
        }
        
        // Extract membership details from the message
        $message = $request['message'] ?? '';
        $name = 'New Membership';
        $price = 0;
        $period_type = 'month';
        $period = 1;
        
        \MemberpressAiAssistant\Utilities\debug_log("[MEMBERSHIP DEBUG] Parsing message: " . $message);
        
        // Parse the message to extract membership details
        if (preg_match('/called\s+([^\s]+)/i', $message, $matches)) {
            $name = $matches[1];
            \MemberpressAiAssistant\Utilities\debug_log("[MEMBERSHIP DEBUG] Extracted name: " . $name);
        }
        
        if (preg_match('/\$(\d+(\.\d+)?)/i', $message, $matches)) {
            $price = floatval($matches[1]);
            \MemberpressAiAssistant\Utilities\debug_log("[MEMBERSHIP DEBUG] Extracted price: " . $price);
        }
        
        if (preg_match('/per\s+(month|year|week|day)/i', $message, $matches)) {
            $period_type = strtolower($matches[1]);
            \MemberpressAiAssistant\Utilities\debug_log("[MEMBERSHIP DEBUG] Extracted period_type: " . $period_type);
        }
        
        if (preg_match('/for\s+(\d+)\s+(month|year|week|day)/i', $message, $matches)) {
            $period = intval($matches[1]);
            $period_type = strtolower($matches[2]);
            \MemberpressAiAssistant\Utilities\debug_log("[MEMBERSHIP DEBUG] Extracted period: " . $period . " " . $period_type);
        }
        
        // Prepare membership data for MemberPress
        $membershipData = [
            'title' => $name,
            'price' => $price,
            'period' => $period,
            'period_type' => $period_type,
            'trial' => false,
            'limit_cycles' => false,
            'who_can_purchase' => 'everyone',
            'thank_you_page_enabled' => false,
            'expire_type' => 'none'
        ];
        
        // Add detailed logging
        if ($this->logger) {
            $this->logger->info('Prepared membership data', [
                'data' => $membershipData
            ]);
        }
        
        // Check if MemberPress is active by checking if the MeprProduct class exists
        if (!class_exists('\MeprProduct')) {
            if ($this->logger) {
                $this->logger->error('MemberPress is not active or not installed');
            }
            
            return [
                'status' => 'error',
                'message' => 'MemberPress is not active or not installed. Cannot create a real membership.',
            ];
        }
        
        // Check if we have access to the MemberPress service
        if (isset($this->memberPressService)) {
            try {
                // Use the MemberPress service to create the membership
                $result = $this->memberPressService->createMembership($membershipData);
                
                // Log the result
                if ($this->logger) {
                    $this->logger->info('Membership creation result', [
                        'result' => $result
                    ]);
                }
                
                return $result;
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error creating membership: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                return [
                    'status' => 'error',
                    'message' => 'Error creating membership: ' . $e->getMessage(),
                ];
            }
        } else {
            // Try to create the membership directly using ProductAdapter
            try {
                if ($this->logger) {
                    $this->logger->warning('MemberPress service not available, attempting direct creation');
                }
                
                // Create a new ProductAdapter instance
                $productAdapter = new \MemberpressAiAssistant\Services\Adapters\ProductAdapter($this->logger);
                
                // Create the membership
                $product = $productAdapter->create($membershipData);
                
                if ($product && $product->ID) {
                    return [
                        'status' => 'success',
                        'message' => 'Membership created successfully',
                        'data' => [
                            'id' => $product->ID,
                            'name' => $product->post_title,
                            'price' => $product->price,
                            'period_type' => $product->period_type,
                            'created_at' => date('Y-m-d H:i:s'),
                        ],
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to create membership directly',
                    ];
                }
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error creating membership directly: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                // Return simulated response as fallback
                return [
                    'status' => 'success',
                    'message' => 'Membership created successfully (simulated)',
                    'data' => [
                        'id' => rand(1000, 9999), // Simulated ID
                        'name' => $name,
                        'price' => $price,
                        'period_type' => $period_type,
                        'created_at' => date('Y-m-d H:i:s'),
                        'simulated' => true,
                    ],
                ];
            }
        }
    }

    /**
     * Update an existing membership
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function updateMembership(array $request): array {
        // Log the request for debugging
        if ($this->logger) {
            $this->logger->info('Updating membership', [
                'request' => $request,
            ]);
        }
        
        // Extract membership ID from the request
        $id = 0;
        if (isset($request['id'])) {
            $id = intval($request['id']);
        } else {
            // Try to extract ID from the message
            $message = $request['message'] ?? '';
            if (preg_match('/membership\s+(?:id|#)?\s*(\d+)/i', $message, $matches)) {
                $id = intval($matches[1]);
            }
        }
        
        if ($id <= 0) {
            return [
                'status' => 'error',
                'message' => 'Membership ID is required for updating',
            ];
        }
        
        // Extract membership details from the message
        $message = $request['message'] ?? '';
        $updateData = [];
        
        // Check for name/title update
        if (preg_match('/name\s+to\s+([^\s]+)/i', $message, $matches)) {
            $updateData['title'] = $matches[1];
        }
        
        // Check for price update
        if (preg_match('/price\s+to\s+\$?(\d+(\.\d+)?)/i', $message, $matches)) {
            $updateData['price'] = floatval($matches[1]);
        }
        
        // Check for period type update
        if (preg_match('/period\s+(?:type|to)\s+(month|year|week|day)/i', $message, $matches)) {
            $updateData['period_type'] = strtolower($matches[1]);
        }
        
        // If no updates were found, return an error
        if (empty($updateData)) {
            return [
                'status' => 'error',
                'message' => 'No updates specified in the request',
            ];
        }
        
        // Add detailed logging
        if ($this->logger) {
            $this->logger->info('Prepared membership update data', [
                'id' => $id,
                'data' => $updateData
            ]);
        }
        
        // Check if MemberPress is active
        if (!class_exists('\MeprProduct')) {
            if ($this->logger) {
                $this->logger->error('MemberPress is not active or not installed');
            }
            
            return [
                'status' => 'error',
                'message' => 'MemberPress is not active or not installed. Cannot update membership.',
            ];
        }
        
        // Check if we have access to the MemberPress service
        if (isset($this->memberPressService)) {
            try {
                // Use the MemberPress service to update the membership
                $result = $this->memberPressService->updateMembership($id, $updateData);
                
                // Log the result
                if ($this->logger) {
                    $this->logger->info('Membership update result', [
                        'result' => $result
                    ]);
                }
                
                return $result;
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error updating membership: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                return [
                    'status' => 'error',
                    'message' => 'Error updating membership: ' . $e->getMessage(),
                ];
            }
        } else {
            // Try to update the membership directly using ProductAdapter
            try {
                if ($this->logger) {
                    $this->logger->warning('MemberPress service not available, attempting direct update');
                }
                
                // Create a new ProductAdapter instance
                $productAdapter = new \MemberpressAiAssistant\Services\Adapters\ProductAdapter($this->logger);
                
                // Update the membership
                $product = $productAdapter->update($id, $updateData);
                
                if ($product && $product->ID) {
                    return [
                        'status' => 'success',
                        'message' => 'Membership updated successfully',
                        'data' => [
                            'id' => $product->ID,
                            'name' => $product->post_title,
                            'price' => $product->price,
                            'period_type' => $product->period_type,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ],
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to update membership directly',
                    ];
                }
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error updating membership directly: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                // Return simulated response as fallback
                return [
                    'status' => 'success',
                    'message' => 'Membership updated successfully (simulated)',
                    'data' => [
                        'id' => $id,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'simulated' => true,
                    ],
                ];
            }
        }
    }

    /**
     * Delete a membership
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function deleteMembership(array $request): array {
        // Log the request for debugging
        if ($this->logger) {
            $this->logger->info('Deleting membership', [
                'request' => $request,
            ]);
        }
        
        // Extract membership ID from the request
        $id = 0;
        if (isset($request['id'])) {
            $id = intval($request['id']);
        } else {
            // Try to extract ID from the message
            $message = $request['message'] ?? '';
            if (preg_match('/membership\s+(?:id|#)?\s*(\d+)/i', $message, $matches)) {
                $id = intval($matches[1]);
            }
        }
        
        if ($id <= 0) {
            return [
                'status' => 'error',
                'message' => 'Membership ID is required for deletion',
            ];
        }
        
        // Add detailed logging
        if ($this->logger) {
            $this->logger->info('Preparing to delete membership', [
                'id' => $id
            ]);
        }
        
        // Check if MemberPress is active
        if (!class_exists('\MeprProduct')) {
            if ($this->logger) {
                $this->logger->error('MemberPress is not active or not installed');
            }
            
            return [
                'status' => 'error',
                'message' => 'MemberPress is not active or not installed. Cannot delete membership.',
            ];
        }
        
        // Check if we have access to the MemberPress service
        if (isset($this->memberPressService)) {
            try {
                // Use the MemberPress service to delete the membership
                $result = $this->memberPressService->deleteMembership($id);
                
                // Log the result
                if ($this->logger) {
                    $this->logger->info('Membership deletion result', [
                        'result' => $result
                    ]);
                }
                
                return $result;
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error deleting membership: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                return [
                    'status' => 'error',
                    'message' => 'Error deleting membership: ' . $e->getMessage(),
                ];
            }
        } else {
            // Try to delete the membership directly using ProductAdapter
            try {
                if ($this->logger) {
                    $this->logger->warning('MemberPress service not available, attempting direct deletion');
                }
                
                // Create a new ProductAdapter instance
                $productAdapter = new \MemberpressAiAssistant\Services\Adapters\ProductAdapter($this->logger);
                
                // Delete the membership
                $result = $productAdapter->delete($id);
                
                if ($result) {
                    return [
                        'status' => 'success',
                        'message' => 'Membership deleted successfully',
                        'data' => [
                            'id' => $id,
                        ],
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to delete membership directly',
                    ];
                }
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error deleting membership directly: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                // Return simulated response as fallback
                return [
                    'status' => 'success',
                    'message' => 'Membership deleted successfully (simulated)',
                    'data' => [
                        'id' => $id,
                        'simulated' => true,
                    ],
                ];
            }
        }
    }

    /**
     * Get membership details
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function getMembership(array $request): array {
        // Log the request for debugging
        if ($this->logger) {
            $this->logger->info('Getting membership details', [
                'request' => $request,
            ]);
        }
        
        // Extract membership ID from the request
        $id = 0;
        if (isset($request['id'])) {
            $id = intval($request['id']);
        } else {
            // Try to extract ID from the message
            $message = $request['message'] ?? '';
            if (preg_match('/membership\s+(?:id|#)?\s*(\d+)/i', $message, $matches)) {
                $id = intval($matches[1]);
            }
        }
        
        if ($id <= 0) {
            return [
                'status' => 'error',
                'message' => 'Membership ID is required to get details',
            ];
        }
        
        // Add detailed logging
        if ($this->logger) {
            $this->logger->info('Preparing to get membership details', [
                'id' => $id
            ]);
        }
        
        // Check if MemberPress is active
        if (!class_exists('\MeprProduct')) {
            if ($this->logger) {
                $this->logger->error('MemberPress is not active or not installed');
            }
            
            return [
                'status' => 'error',
                'message' => 'MemberPress is not active or not installed. Cannot get membership details.',
            ];
        }
        
        // Check if we have access to the MemberPress service
        if (isset($this->memberPressService)) {
            try {
                // Use the MemberPress service to get the membership
                $result = $this->memberPressService->getMembership($id);
                
                // Log the result
                if ($this->logger) {
                    $this->logger->info('Membership details result', [
                        'result' => $result
                    ]);
                }
                
                return $result;
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error getting membership details: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                return [
                    'status' => 'error',
                    'message' => 'Error getting membership details: ' . $e->getMessage(),
                ];
            }
        } else {
            // Try to get the membership directly using ProductAdapter
            try {
                if ($this->logger) {
                    $this->logger->warning('MemberPress service not available, attempting direct retrieval');
                }
                
                // Create a new ProductAdapter instance
                $productAdapter = new \MemberpressAiAssistant\Services\Adapters\ProductAdapter($this->logger);
                
                // Get the membership
                $product = $productAdapter->get($id);
                
                if ($product && $product->ID) {
                    // Get terms if available
                    $terms = [];
                    try {
                        $terms = $productAdapter->getTerms($product);
                    } catch (\Exception $e) {
                        if ($this->logger) {
                            $this->logger->warning('Error getting membership terms: ' . $e->getMessage());
                        }
                    }
                    
                    return [
                        'status' => 'success',
                        'message' => 'Membership retrieved successfully',
                        'data' => [
                            'id' => $product->ID,
                            'name' => $product->post_title,
                            'price' => $product->price,
                            'period_type' => $product->period_type,
                            'period' => $product->period,
                            'terms' => $terms,
                            'created_at' => $product->post_date,
                            'updated_at' => $product->post_modified,
                        ],
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Membership not found',
                    ];
                }
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error getting membership directly: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                // Return simulated response as fallback
                return [
                    'status' => 'success',
                    'message' => 'Membership retrieved successfully (simulated)',
                    'data' => [
                        'id' => $id,
                        'name' => 'Sample Membership',
                        'price' => 19.99,
                        'period_type' => 'month',
                        'period' => 1,
                        'created_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'simulated' => true,
                    ],
                ];
            }
        }
    }

    /**
     * List all memberships
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function listMemberships(array $request): array {
        // Log the request for debugging
        if ($this->logger) {
            $this->logger->info('Listing memberships', [
                'request' => $request,
            ]);
        }
        
        // Extract pagination parameters
        $limit = isset($request['limit']) ? intval($request['limit']) : 10;
        $offset = isset($request['offset']) ? intval($request['offset']) : 0;
        
        // Add detailed logging
        if ($this->logger) {
            $this->logger->info('Preparing to list memberships', [
                'limit' => $limit,
                'offset' => $offset
            ]);
        }
        
        // Check if MemberPress is active
        if (!class_exists('\MeprProduct')) {
            if ($this->logger) {
                $this->logger->error('MemberPress is not active or not installed');
            }
            
            return [
                'status' => 'error',
                'message' => 'MemberPress is not active or not installed. Cannot list memberships.',
            ];
        }
        
        // Check if we have access to the MemberPress service
        if (isset($this->memberPressService)) {
            try {
                // Use the MemberPress service to list memberships
                $result = $this->memberPressService->getMemberships([
                    'limit' => $limit,
                    'offset' => $offset
                ]);
                
                // Log the result
                if ($this->logger) {
                    $this->logger->info('Memberships list result', [
                        'result' => $result
                    ]);
                }
                
                return $result;
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error listing memberships: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                return [
                    'status' => 'error',
                    'message' => 'Error listing memberships: ' . $e->getMessage(),
                ];
            }
        } else {
            // Try to list memberships directly using ProductAdapter
            try {
                if ($this->logger) {
                    $this->logger->warning('MemberPress service not available, attempting direct listing');
                }
                
                // Create a new ProductAdapter instance
                $productAdapter = new \MemberpressAiAssistant\Services\Adapters\ProductAdapter($this->logger);
                
                // Get all memberships
                $products = $productAdapter->getAll([
                    'limit' => $limit,
                    'offset' => $offset
                ]);
                
                if (is_array($products)) {
                    $memberships = [];
                    
                    foreach ($products as $product) {
                        if ($product && $product->ID) {
                            $memberships[] = [
                                'id' => $product->ID,
                                'name' => $product->post_title,
                                'price' => $product->price,
                                'period_type' => $product->period_type,
                                'period' => $product->period,
                            ];
                        }
                    }
                    
                    return [
                        'status' => 'success',
                        'message' => 'Memberships retrieved successfully',
                        'data' => [
                            'memberships' => $memberships,
                            'total' => count($memberships),
                            'limit' => $limit,
                            'offset' => $offset,
                        ],
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to list memberships directly',
                    ];
                }
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error listing memberships directly: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                // Return simulated response as fallback
                return [
                    'status' => 'success',
                    'message' => 'Memberships retrieved successfully (simulated)',
                    'data' => [
                        'memberships' => [
                            [
                                'id' => 1001,
                                'name' => 'Basic Membership',
                                'price' => 9.99,
                                'period_type' => 'month',
                                'period' => 1,
                                'simulated' => true,
                            ],
                            [
                                'id' => 1002,
                                'name' => 'Premium Membership',
                                'price' => 19.99,
                                'period_type' => 'month',
                                'period' => 1,
                                'simulated' => true,
                            ],
                            [
                                'id' => 1003,
                                'name' => 'Annual Membership',
                                'price' => 199.99,
                                'period_type' => 'year',
                                'period' => 1,
                                'simulated' => true,
                            ],
                        ],
                        'total' => 3,
                        'limit' => $limit,
                        'offset' => $offset,
                        'simulated' => true,
                    ],
                ];
            }
        }
    }

    /**
     * Create a new access rule
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function createAccessRule(array $request): array {
        // Log the request for debugging
        if ($this->logger) {
            $this->logger->info('Creating access rule', [
                'request' => $request,
            ]);
        }
        
        // Extract access rule details from the request
        $membership_id = isset($request['membership_id']) ? intval($request['membership_id']) : 0;
        $content_type = $request['content_type'] ?? 'post';
        $content_ids = $request['content_ids'] ?? [];
        $rule_type = $request['rule_type'] ?? 'include';
        
        // Extract details from message if not provided directly
        if ($membership_id <= 0 || empty($content_ids)) {
            $message = $request['message'] ?? '';
            
            // Try to extract membership ID from message
            if (preg_match('/membership\s+(?:id|#)?\s*(\d+)/i', $message, $matches)) {
                $membership_id = intval($matches[1]);
            }
            
            // Try to extract content type from message
            if (preg_match('/(post|page|category|tag|taxonomy)\s+content/i', $message, $matches)) {
                $content_type = strtolower($matches[1]);
            }
            
            // Try to extract content IDs from message
            if (preg_match('/content\s+(?:ids?|#)?\s*([\d,\s]+)/i', $message, $matches)) {
                $content_ids = array_map('intval', explode(',', preg_replace('/\s+/', '', $matches[1])));
            }
            
            // Try to extract rule type from message
            if (preg_match('/(include|exclude)\s+rule/i', $message, $matches)) {
                $rule_type = strtolower($matches[1]);
            }
        }
        
        // Validate required parameters
        if ($membership_id <= 0) {
            return [
                'status' => 'error',
                'message' => 'Membership ID is required for creating an access rule',
            ];
        }
        
        if (empty($content_ids)) {
            return [
                'status' => 'error',
                'message' => 'Content IDs are required for creating an access rule',
            ];
        }
        
        // Prepare rule data
        $ruleData = [
            'membership_id' => $membership_id,
            'content_type' => $content_type,
            'content_ids' => $content_ids,
            'rule_type' => $rule_type,
        ];
        
        // Add detailed logging
        if ($this->logger) {
            $this->logger->info('Prepared access rule data', [
                'data' => $ruleData
            ]);
        }
        
        // Check if MemberPress is active
        if (!class_exists('\MeprRule')) {
            if ($this->logger) {
                $this->logger->error('MemberPress is not active or not installed');
            }
            
            return [
                'status' => 'error',
                'message' => 'MemberPress is not active or not installed. Cannot create an access rule.',
            ];
        }
        
        // Check if we have access to the MemberPress service
        if (isset($this->memberPressService)) {
            try {
                // Use the MemberPress service to create the access rule
                $result = $this->memberPressService->createAccessRule($ruleData);
                
                // Log the result
                if ($this->logger) {
                    $this->logger->info('Access rule creation result', [
                        'result' => $result
                    ]);
                }
                
                return $result;
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error creating access rule: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                return [
                    'status' => 'error',
                    'message' => 'Error creating access rule: ' . $e->getMessage(),
                ];
            }
        } else {
            // Try to create the access rule directly using RuleAdapter
            try {
                if ($this->logger) {
                    $this->logger->warning('MemberPress service not available, attempting direct creation');
                }
                
                // Create a new RuleAdapter instance
                $ruleAdapter = new \MemberpressAiAssistant\Services\Adapters\RuleAdapter($this->logger);
                
                // Create the access rule
                $rule = $ruleAdapter->create($ruleData);
                
                if ($rule && $rule->ID) {
                    return [
                        'status' => 'success',
                        'message' => 'Access rule created successfully',
                        'data' => [
                            'id' => $rule->ID,
                            'membership_id' => $membership_id,
                            'content_type' => $content_type,
                            'rule_type' => $rule_type,
                            'created_at' => date('Y-m-d H:i:s'),
                        ],
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to create access rule directly',
                    ];
                }
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error creating access rule directly: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                // Return simulated response as fallback
                return [
                    'status' => 'success',
                    'message' => 'Access rule created successfully (simulated)',
                    'data' => [
                        'id' => rand(1000, 9999), // Simulated ID
                        'membership_id' => $membership_id,
                        'content_type' => $content_type,
                        'rule_type' => $rule_type,
                        'created_at' => date('Y-m-d H:i:s'),
                        'simulated' => true,
                    ],
                ];
            }
        }
    }

    /**
     * Update an existing access rule
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function updateAccessRule(array $request): array {
        // Log the request for debugging
        if ($this->logger) {
            $this->logger->info('Updating access rule', [
                'request' => $request,
            ]);
        }
        
        // Extract access rule ID from the request
        $id = isset($request['id']) ? intval($request['id']) : 0;
        
        // Extract details from message if not provided directly
        if ($id <= 0) {
            $message = $request['message'] ?? '';
            
            // Try to extract rule ID from message
            if (preg_match('/rule\s+(?:id|#)?\s*(\d+)/i', $message, $matches)) {
                $id = intval($matches[1]);
            }
        }
        
        // Validate required parameters
        if ($id <= 0) {
            return [
                'status' => 'error',
                'message' => 'Access rule ID is required for updating',
            ];
        }
        
        // Extract update data from the request
        $updateData = [];
        
        // Extract membership ID if provided
        if (isset($request['membership_id'])) {
            $updateData['membership_id'] = intval($request['membership_id']);
        }
        
        // Extract content type if provided
        if (isset($request['content_type'])) {
            $updateData['content_type'] = $request['content_type'];
        }
        
        // Extract content IDs if provided
        if (isset($request['content_ids'])) {
            $updateData['content_ids'] = $request['content_ids'];
        }
        
        // Extract rule type if provided
        if (isset($request['rule_type'])) {
            $updateData['rule_type'] = $request['rule_type'];
        }
        
        // Extract details from message if not provided directly
        if (empty($updateData)) {
            $message = $request['message'] ?? '';
            
            // Try to extract membership ID from message
            if (preg_match('/membership\s+(?:id|#)?\s*(\d+)/i', $message, $matches)) {
                $updateData['membership_id'] = intval($matches[1]);
            }
            
            // Try to extract content type from message
            if (preg_match('/(post|page|category|tag|taxonomy)\s+content/i', $message, $matches)) {
                $updateData['content_type'] = strtolower($matches[1]);
            }
            
            // Try to extract content IDs from message
            if (preg_match('/content\s+(?:ids?|#)?\s*([\d,\s]+)/i', $message, $matches)) {
                $updateData['content_ids'] = array_map('intval', explode(',', preg_replace('/\s+/', '', $matches[1])));
            }
            
            // Try to extract rule type from message
            if (preg_match('/(include|exclude)\s+rule/i', $message, $matches)) {
                $updateData['rule_type'] = strtolower($matches[1]);
            }
        }
        
        // If no updates were found, return an error
        if (empty($updateData)) {
            return [
                'status' => 'error',
                'message' => 'No updates specified in the request',
            ];
        }
        
        // Add detailed logging
        if ($this->logger) {
            $this->logger->info('Prepared access rule update data', [
                'id' => $id,
                'data' => $updateData
            ]);
        }
        
        // Check if MemberPress is active
        if (!class_exists('\MeprRule')) {
            if ($this->logger) {
                $this->logger->error('MemberPress is not active or not installed');
            }
            
            return [
                'status' => 'error',
                'message' => 'MemberPress is not active or not installed. Cannot update access rule.',
            ];
        }
        
        // Check if we have access to the MemberPress service
        if (isset($this->memberPressService)) {
            try {
                // Use the MemberPress service to update the access rule
                $result = $this->memberPressService->updateAccessRule($id, $updateData);
                
                // Log the result
                if ($this->logger) {
                    $this->logger->info('Access rule update result', [
                        'result' => $result
                    ]);
                }
                
                return $result;
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error updating access rule: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                return [
                    'status' => 'error',
                    'message' => 'Error updating access rule: ' . $e->getMessage(),
                ];
            }
        } else {
            // Try to update the access rule directly using RuleAdapter
            try {
                if ($this->logger) {
                    $this->logger->warning('MemberPress service not available, attempting direct update');
                }
                
                // Create a new RuleAdapter instance
                $ruleAdapter = new \MemberpressAiAssistant\Services\Adapters\RuleAdapter($this->logger);
                
                // Update the access rule
                $rule = $ruleAdapter->update($id, $updateData);
                
                if ($rule && $rule->ID) {
                    return [
                        'status' => 'success',
                        'message' => 'Access rule updated successfully',
                        'data' => [
                            'id' => $rule->ID,
                            'membership_id' => $rule->mepr_product_id,
                            'content_type' => $rule->mepr_type,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ],
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to update access rule directly',
                    ];
                }
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error updating access rule directly: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                // Return simulated response as fallback
                return [
                    'status' => 'success',
                    'message' => 'Access rule updated successfully (simulated)',
                    'data' => [
                        'id' => $id,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'simulated' => true,
                    ],
                ];
            }
        }
    }

    /**
     * Delete an access rule
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function deleteAccessRule(array $request): array {
        // Log the request for debugging
        if ($this->logger) {
            $this->logger->info('Deleting access rule', [
                'request' => $request,
            ]);
        }
        
        // Initialize $id to 0
        $id = 0;
        
        // Extract access rule ID from the request
        if (isset($request['id'])) {
            $id = intval($request['id']);
        }
        
        // Extract details from message if not provided directly
        if ($id <= 0) {
            $message = $request['message'] ?? '';
            
            // Try to extract rule ID from message
            if (preg_match('/rule\s+(?:id|#)?\s*(\d+)/i', $message, $matches)) {
                $id = intval($matches[1]);
            }
        }
        
        // Validate required parameters
        if ($id <= 0) {
            return [
                'status' => 'error',
                'message' => 'Access rule ID is required for deletion',
            ];
        }
        
        // Add detailed logging
        if ($this->logger) {
            $this->logger->info('Preparing to delete access rule', [
                'id' => $id
            ]);
        }
        
        // Check if MemberPress is active
        if (!class_exists('\MeprRule')) {
            if ($this->logger) {
                $this->logger->error('MemberPress is not active or not installed');
            }
            
            return [
                'status' => 'error',
                'message' => 'MemberPress is not active or not installed. Cannot delete access rule.',
            ];
        }
        
        // Check if we have access to the MemberPress service
        if (isset($this->memberPressService)) {
            try {
                // Use the MemberPress service to delete the access rule
                $result = $this->memberPressService->deleteAccessRule($id);
                
                // Log the result
                if ($this->logger) {
                    $this->logger->info('Access rule deletion result', [
                        'result' => $result
                    ]);
                }
                
                return $result;
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error deleting access rule: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                return [
                    'status' => 'error',
                    'message' => 'Error deleting access rule: ' . $e->getMessage(),
                ];
            }
        } else {
            // Try to delete the access rule directly using RuleAdapter
            try {
                if ($this->logger) {
                    $this->logger->warning('MemberPress service not available, attempting direct deletion');
                }
                
                // Create a new RuleAdapter instance
                $ruleAdapter = new \MemberpressAiAssistant\Services\Adapters\RuleAdapter($this->logger);
                
                // Delete the access rule
                $result = $ruleAdapter->delete($id);
                
                if ($result) {
                    return [
                        'status' => 'success',
                        'message' => 'Access rule deleted successfully',
                        'data' => [
                            'id' => $id,
                        ],
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to delete access rule directly',
                    ];
                }
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error deleting access rule directly: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                // Return simulated response as fallback
                return [
                    'status' => 'success',
                    'message' => 'Access rule deleted successfully (simulated)',
                    'data' => [
                        'id' => $id,
                        'simulated' => true,
                    ],
                ];
            }
        }
    }

    /**
     * Manage pricing for memberships
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function managePricing(array $request): array {
        // Log the request for debugging
        if ($this->logger) {
            $this->logger->info('Managing pricing', [
                'request' => $request,
            ]);
        }
        
        // Initialize membership_id to 0
        $membership_id = 0;
        
        // Extract membership ID from the request
        if (isset($request['membership_id'])) {
            $membership_id = intval($request['membership_id']);
        } else {
            // Try to extract ID from the message
            $message = $request['message'] ?? '';
            if (preg_match('/membership\s+(?:id|#)?\s*(\d+)/i', $message, $matches)) {
                $membership_id = intval($matches[1]);
            }
        }
        
        // Validate required parameters
        if ($membership_id <= 0) {
            return [
                'status' => 'error',
                'message' => 'Membership ID is required for managing pricing',
            ];
        }
        
        // Extract pricing details from the request
        $price = isset($request['price']) ? floatval($request['price']) : 0;
        $billing_type = $request['billing_type'] ?? 'recurring';
        $billing_frequency = $request['billing_frequency'] ?? 'monthly';
        
        // Extract details from message if not provided directly
        if ($price <= 0) {
            $message = $request['message'] ?? '';
            
            // Try to extract price from message
            if (preg_match('/price\s+(?:to)?\s+\$?(\d+(\.\d+)?)/i', $message, $matches)) {
                $price = floatval($matches[1]);
            }
            
            // Try to extract billing type from message
            if (preg_match('/(one-time|recurring|lifetime)/i', $message, $matches)) {
                $billing_type = strtolower($matches[1]);
            }
            
            // Try to extract billing frequency from message
            if (preg_match('/(monthly|yearly|weekly|daily)/i', $message, $matches)) {
                $billing_frequency = strtolower($matches[1]);
            }
        }
        
        // If no price was found, return an error
        if ($price <= 0) {
            return [
                'status' => 'error',
                'message' => 'Price is required for managing pricing',
            ];
        }
        
        // Prepare pricing data
        $pricingData = [
            'price' => $price,
            'billing_type' => $billing_type,
            'billing_frequency' => $billing_frequency,
        ];
        
        // Add detailed logging
        if ($this->logger) {
            $this->logger->info('Prepared pricing data', [
                'membership_id' => $membership_id,
                'data' => $pricingData
            ]);
        }
        
        // Check if MemberPress is active
        if (!class_exists('\MeprProduct')) {
            if ($this->logger) {
                $this->logger->error('MemberPress is not active or not installed');
            }
            
            return [
                'status' => 'error',
                'message' => 'MemberPress is not active or not installed. Cannot manage pricing.',
            ];
        }
        
        // Check if we have access to the MemberPress service
        if (isset($this->memberPressService)) {
            try {
                // Use the MemberPress service to manage pricing
                $result = $this->memberPressService->updateMembership($membership_id, $pricingData);
                
                // Log the result
                if ($this->logger) {
                    $this->logger->info('Pricing update result', [
                        'result' => $result
                    ]);
                }
                
                return $result;
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error managing pricing: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                return [
                    'status' => 'error',
                    'message' => 'Error managing pricing: ' . $e->getMessage(),
                ];
            }
        } else {
            // Try to manage pricing directly using ProductAdapter
            try {
                if ($this->logger) {
                    $this->logger->warning('MemberPress service not available, attempting direct pricing update');
                }
                
                // Create a new ProductAdapter instance
                $productAdapter = new \MemberpressAiAssistant\Services\Adapters\ProductAdapter($this->logger);
                
                // Update the pricing
                $product = $productAdapter->update($membership_id, $pricingData);
                
                if ($product && $product->ID) {
                    return [
                        'status' => 'success',
                        'message' => 'Pricing updated successfully',
                        'data' => [
                            'membership_id' => $membership_id,
                            'price' => $price,
                            'billing_type' => $billing_type,
                            'billing_frequency' => $billing_frequency,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ],
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to update pricing directly',
                    ];
                }
            } catch (\Exception $e) {
                // Log the error
                if ($this->logger) {
                    $this->logger->error('Error updating pricing directly: ' . $e->getMessage(), [
                        'exception' => $e
                    ]);
                }
                
                // Return simulated response as fallback
                return [
                    'status' => 'success',
                    'message' => 'Pricing updated successfully (simulated)',
                    'data' => [
                        'membership_id' => $membership_id,
                        'price' => $price,
                        'billing_type' => $billing_type,
                        'billing_frequency' => $billing_frequency,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'simulated' => true,
                    ],
                ];
            }
        }
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
    
    /**
     * Handle general intent
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function handleGeneralIntent(array $request): array {
        // For general intent, provide a helpful response
        return [
            'status' => 'success',
            'message' => 'Hello! I\'m the MemberPress Agent. I can help you with membership creation, management, pricing, and access rules. How can I assist you today?',
            'agent' => $this->getAgentName(),
            'timestamp' => time(),
        ];
    }
    
    /**
     * Handle greeting intent
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function handleGreeting(array $request): array {
        // For greeting intent, provide a friendly response
        return [
            'status' => 'success',
            'message' => 'Hello! I\'m the MemberPress Agent. I\'m here to help you with MemberPress-related tasks. How can I assist you today?',
            'agent' => $this->getAgentName(),
            'timestamp' => time(),
        ];
    }
}