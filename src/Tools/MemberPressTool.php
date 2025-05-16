<?php
/**
 * MemberPress Tool
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Tools;

use MemberpressAiAssistant\Abstracts\AbstractTool;
use MemberpressAiAssistant\Services\MemberPressService;

/**
 * Tool for handling MemberPress membership operations
 */
class MemberPressTool extends AbstractTool {
    /**
     * MemberPress service
     *
     * @var MemberPressService
     */
    protected $memberPressService;

    /**
     * Batch processor
     *
     * @var \MemberpressAiAssistant\Batch\BatchProcessor
     */
    protected $batchProcessor;

    /**
     * Constructor
     *
     * @param MemberPressService $memberPressService The MemberPress service
     * @param \MemberpressAiAssistant\Batch\BatchProcessor|null $batchProcessor The batch processor
     */
    public function __construct(
        MemberPressService $memberPressService,
        \MemberpressAiAssistant\Batch\BatchProcessor $batchProcessor = null
    ) {
        parent::__construct(
            'memberpress',
            'Tool for handling MemberPress membership operations',
            null
        );
        $this->memberPressService = $memberPressService;
        $this->batchProcessor = $batchProcessor;
    }

    /**
     * Set the batch processor
     *
     * @param \MemberpressAiAssistant\Batch\BatchProcessor $batchProcessor The batch processor
     * @return self
     */
    public function setBatchProcessor(\MemberpressAiAssistant\Batch\BatchProcessor $batchProcessor): self {
        $this->batchProcessor = $batchProcessor;
        return $this;
    }
    /**
     * Valid operations that this tool can perform
     *
     * @var array
     */
    protected $validOperations = [
        'create_membership',
        'get_membership',
        'update_membership',
        'delete_membership',
        'list_memberships',
        'create_access_rule',
        'update_access_rule',
        'delete_access_rule',
        'manage_pricing',
        'associate_user_with_membership',
        'disassociate_user_from_membership',
        'get_user_memberships',
        'update_user_role',
        'get_user_permissions',
        // Batch operations
        'batch_get_memberships',
        'batch_update_memberships',
        'batch_delete_memberships',
        'batch_create_memberships',
        'batch_create_access_rules',
        'batch_update_access_rules',
        'batch_delete_access_rules',
        'batch_associate_users',
        'batch_disassociate_users',
        'batch_get_user_memberships',
        'batch_update_user_roles',
        'batch_get_user_permissions',
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
                    'description' => 'The operation to perform (create_membership, get_membership, update_membership, delete_membership, list_memberships, create_access_rule, update_access_rule, delete_access_rule, manage_pricing, associate_user_with_membership, disassociate_user_from_membership, get_user_memberships, update_user_role, get_user_permissions)',
                    'enum' => $this->validOperations,
                ],
                'membership_id' => [
                    'type' => 'integer',
                    'description' => 'The ID of the membership to operate on',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'The name of the membership',
                ],
                'price' => [
                    'type' => 'number',
                    'description' => 'The price of the membership',
                ],
                'terms' => [
                    'type' => 'string',
                    'description' => 'The billing terms (monthly, yearly, lifetime, etc.)',
                    'enum' => ['monthly', 'yearly', 'quarterly', 'lifetime', 'one-time'],
                ],
                'billing_type' => [
                    'type' => 'string',
                    'description' => 'The type of billing (recurring or one-time)',
                    'enum' => ['recurring', 'one-time'],
                ],
                'billing_frequency' => [
                    'type' => 'string',
                    'description' => 'The frequency of billing (monthly, yearly, etc.)',
                    'enum' => ['monthly', 'yearly', 'quarterly'],
                ],
                'access_rules' => [
                    'type' => 'array',
                    'description' => 'Access rules for the membership',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'content_type' => [
                                'type' => 'string',
                                'description' => 'The type of content to protect',
                                'enum' => ['post', 'page', 'category', 'tag', 'custom_post_type'],
                            ],
                            'content_ids' => [
                                'type' => 'array',
                                'description' => 'IDs of content to protect',
                                'items' => [
                                    'type' => 'integer',
                                ],
                            ],
                            'rule_type' => [
                                'type' => 'string',
                                'description' => 'Type of access rule',
                                'enum' => ['include', 'exclude'],
                            ],
                        ],
                    ],
                ],
                'rule_id' => [
                    'type' => 'integer',
                    'description' => 'The ID of the access rule to operate on',
                ],
                'content_type' => [
                    'type' => 'string',
                    'description' => 'The type of content to protect',
                    'enum' => ['post', 'page', 'category', 'tag', 'custom_post_type'],
                ],
                'content_ids' => [
                    'type' => 'array',
                    'description' => 'IDs of content to protect',
                    'items' => [
                        'type' => 'integer',
                    ],
                ],
                'rule_type' => [
                    'type' => 'string',
                    'description' => 'Type of access rule',
                    'enum' => ['include', 'exclude'],
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Limit for list operations',
                ],
                'offset' => [
                    'type' => 'integer',
                    'description' => 'Offset for list operations',
                ],
                'user_id' => [
                    'type' => 'integer',
                    'description' => 'The ID of the user to operate on',
                ],
                'role' => [
                    'type' => 'string',
                    'description' => 'The WordPress role to assign to the user',
                ],
                'role_action' => [
                    'type' => 'string',
                    'description' => 'The action to perform with the role (set, add, remove)',
                    'enum' => ['set', 'add', 'remove'],
                ],
                'transaction_data' => [
                    'type' => 'object',
                    'description' => 'Optional transaction data for membership association',
                ],
                'subscription_data' => [
                    'type' => 'object',
                    'description' => 'Optional subscription data for membership association',
                ],
                'batch_params' => [
                    'type' => 'array',
                    'description' => 'Parameters for batch operations',
                    'items' => [
                        'type' => 'object'
                    ]
                ],
            ],
            'required' => ['operation'],
            'allOf' => [
                // Batch operations require batch_params
                [
                    'if' => [
                        'properties' => [
                            'operation' => [
                                'enum' => [
                                    'batch_get_memberships',
                                    'batch_update_memberships',
                                    'batch_delete_memberships',
                                    'batch_create_memberships',
                                    'batch_create_access_rules',
                                    'batch_update_access_rules',
                                    'batch_delete_access_rules',
                                    'batch_associate_users',
                                    'batch_disassociate_users',
                                    'batch_get_user_memberships',
                                    'batch_update_user_roles',
                                    'batch_get_user_permissions',
                                ]
                            ]
                        ]
                    ],
                    'then' => [
                        'required' => ['batch_params']
                    ]
                ]
            ]
        ];
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
            // Check if this is a batch operation
            if (strpos($parameters['operation'], 'batch_') === 0) {
                // Validate batch parameters
                if (!isset($parameters['batch_params']) || !is_array($parameters['batch_params'])) {
                    $errors[] = 'Batch parameters array is required for ' . $parameters['operation'] . ' operation';
                } elseif (empty($parameters['batch_params'])) {
                    $errors[] = 'Batch parameters array cannot be empty for ' . $parameters['operation'] . ' operation';
                } else {
                    // Validate each set of parameters in the batch
                    $baseOperation = str_replace('batch_', '', $parameters['operation']);
                    $baseOperation = preg_replace('/s$/', '', $baseOperation); // Remove trailing 's'
                    
                    // Map batch operations to their base operations
                    $operationMap = [
                        'get_membership' => 'get_membership',
                        'update_membership' => 'update_membership',
                        'delete_membership' => 'delete_membership',
                        'create_membership' => 'create_membership',
                        'create_access_rule' => 'create_access_rule',
                        'update_access_rule' => 'update_access_rule',
                        'delete_access_rule' => 'delete_access_rule',
                        'associate_user' => 'associate_user_with_membership',
                        'disassociate_user' => 'disassociate_user_from_membership',
                        'get_user_membership' => 'get_user_memberships',
                        'update_user_role' => 'update_user_role',
                        'get_user_permission' => 'get_user_permissions',
                    ];
                    
                    // Get the corresponding base operation
                    $baseOperation = $operationMap[$baseOperation] ?? $baseOperation;
                    
                    // Validate each item in the batch
                    foreach ($parameters['batch_params'] as $index => $params) {
                        // Create a temporary parameters array with the base operation
                        $tempParams = $params;
                        $tempParams['operation'] = $baseOperation;
                        
                        // Validate using the base operation validation
                        $itemErrors = $this->validateSingleOperation($tempParams);
                        if (is_array($itemErrors)) {
                            foreach ($itemErrors as $error) {
                                $errors[] = "Batch item $index: $error";
                            }
                        }
                    }
                }
                
                // Additional validation for specific batch operations
                switch ($parameters['operation']) {
                    case 'batch_update_memberships':
                    case 'batch_delete_memberships':
                    case 'batch_get_memberships':
                        // No additional validation needed
                        break;
                        
                    case 'batch_create_memberships':
                        // No additional validation needed
                        break;
                        
                    case 'batch_create_access_rules':
                    case 'batch_update_access_rules':
                    case 'batch_delete_access_rules':
                        // No additional validation needed
                        break;
                        
                    case 'batch_associate_users':
                    case 'batch_disassociate_users':
                        // No additional validation needed
                        break;
                        
                    case 'batch_get_user_memberships':
                    case 'batch_update_user_roles':
                    case 'batch_get_user_permissions':
                        // No additional validation needed
                        break;
                }
            } else {
                // Validate parameters for non-batch operations
                $errors = array_merge($errors, $this->validateSingleOperation($parameters) ?: []);
            }
        }

        return empty($errors) ? true : $errors;
    }
    
    /**
     * Validate parameters for a single operation
     *
     * @param array $parameters The parameters to validate
     * @return bool|array True if valid, array of errors otherwise
     */
    protected function validateSingleOperation(array $parameters) {
        $errors = [];
        
        // Validate parameters based on operation
        switch ($parameters['operation']) {
                case 'create_membership':
                    if (!isset($parameters['name'])) {
                        $errors[] = 'Name is required for create_membership operation';
                    }
                    if (!isset($parameters['price'])) {
                        $errors[] = 'Price is required for create_membership operation';
                    }
                    if (!isset($parameters['terms'])) {
                        $errors[] = 'Terms is required for create_membership operation';
                    }
                    break;

                case 'update_membership':
                case 'get_membership':
                case 'delete_membership':
                    if (!isset($parameters['membership_id'])) {
                        $errors[] = 'Membership ID is required for ' . $parameters['operation'] . ' operation';
                    }
                    break;

                case 'create_access_rule':
                    if (!isset($parameters['membership_id'])) {
                        $errors[] = 'Membership ID is required for create_access_rule operation';
                    }
                    if (!isset($parameters['content_type'])) {
                        $errors[] = 'Content type is required for create_access_rule operation';
                    }
                    if (!isset($parameters['content_ids']) || !is_array($parameters['content_ids'])) {
                        $errors[] = 'Content IDs array is required for create_access_rule operation';
                    }
                    if (!isset($parameters['rule_type'])) {
                        $errors[] = 'Rule type is required for create_access_rule operation';
                    }
                    break;

                case 'update_access_rule':
                case 'delete_access_rule':
                    if (!isset($parameters['rule_id'])) {
                        $errors[] = 'Rule ID is required for ' . $parameters['operation'] . ' operation';
                    }
                    break;

                case 'manage_pricing':
                    if (!isset($parameters['membership_id'])) {
                        $errors[] = 'Membership ID is required for manage_pricing operation';
                    }
                    if (!isset($parameters['price'])) {
                        $errors[] = 'Price is required for manage_pricing operation';
                    }
                    if (!isset($parameters['billing_type'])) {
                        $errors[] = 'Billing type is required for manage_pricing operation';
                    }
                    if ($parameters['billing_type'] === 'recurring' && !isset($parameters['billing_frequency'])) {
                        $errors[] = 'Billing frequency is required for recurring billing type';
                    }
                    break;
                    
                case 'associate_user_with_membership':
                case 'disassociate_user_from_membership':
                    if (!isset($parameters['user_id'])) {
                        $errors[] = 'User ID is required for ' . $parameters['operation'] . ' operation';
                    }
                    if (!isset($parameters['membership_id'])) {
                        $errors[] = 'Membership ID is required for ' . $parameters['operation'] . ' operation';
                    }
                    break;
                    
                case 'get_user_memberships':
                case 'get_user_permissions':
                    if (!isset($parameters['user_id'])) {
                        $errors[] = 'User ID is required for ' . $parameters['operation'] . ' operation';
                    }
                    break;
                    
                case 'update_user_role':
                    if (!isset($parameters['user_id'])) {
                        $errors[] = 'User ID is required for update_user_role operation';
                    }
                    if (!isset($parameters['role'])) {
                        $errors[] = 'Role is required for update_user_role operation';
                    }
                    break;
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
            
            // Check if this is a batch operation
            if (strpos($operation, 'batch_') === 0 && $this->batchProcessor) {
                $result = $this->$operation($parameters);
            } else {
                $result = $this->$operation($parameters);
            }

            return $result;
        } catch (\Exception $e) {
            // Log the error
            if ($this->logger) {
                $this->logger->error('Error executing MemberPressTool: ' . $e->getMessage(), [
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
     * Create a new membership
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function create_membership(array $parameters): array {
        // Sanitize inputs
        $name = sanitize_text_field($parameters['name']);
        $price = floatval($parameters['price']);
        $terms = sanitize_text_field($parameters['terms']);
        
        // Prepare data for the service
        $membershipData = [
            'name' => $name,
            'price' => $price,
        ];
        
        // Map terms to appropriate MemberPress fields
        switch ($terms) {
            case 'monthly':
                $membershipData['period'] = 1;
                $membershipData['period_type'] = 'months';
                break;
            case 'yearly':
                $membershipData['period'] = 1;
                $membershipData['period_type'] = 'years';
                break;
            case 'quarterly':
                $membershipData['period'] = 3;
                $membershipData['period_type'] = 'months';
                break;
            case 'lifetime':
            case 'one-time':
                $membershipData['period'] = 0;
                $membershipData['period_type'] = 'lifetime';
                break;
        }
        
        // Use the service to create the membership
        return $this->memberPressService->createMembership($membershipData);
    }

    /**
     * Get membership details
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function get_membership(array $parameters): array {
        // Sanitize inputs
        $membership_id = intval($parameters['membership_id']);
        
        // Use the service to get the membership
        $result = $this->memberPressService->getMembership($membership_id);
        
        // Add a message if not present and status is success
        if ($result['status'] === 'success' && !isset($result['message'])) {
            $result['message'] = 'Membership retrieved successfully';
        }
        
        return $result;
    }

    /**
     * Update an existing membership
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function update_membership(array $parameters): array {
        // Sanitize inputs
        $membership_id = intval($parameters['membership_id']);
        $updateData = [];
        
        // Only include parameters that are set
        if (isset($parameters['name'])) {
            $updateData['name'] = sanitize_text_field($parameters['name']);
        }
        
        if (isset($parameters['price'])) {
            $updateData['price'] = floatval($parameters['price']);
        }
        
        if (isset($parameters['terms'])) {
            $terms = sanitize_text_field($parameters['terms']);
            
            // Map terms to appropriate MemberPress fields
            switch ($terms) {
                case 'monthly':
                    $updateData['period'] = 1;
                    $updateData['period_type'] = 'months';
                    break;
                case 'yearly':
                    $updateData['period'] = 1;
                    $updateData['period_type'] = 'years';
                    break;
                case 'quarterly':
                    $updateData['period'] = 3;
                    $updateData['period_type'] = 'months';
                    break;
                case 'lifetime':
                case 'one-time':
                    $updateData['period'] = 0;
                    $updateData['period_type'] = 'lifetime';
                    break;
            }
        }
        
        // Use the service to update the membership
        return $this->memberPressService->updateMembership($membership_id, $updateData);
    }

    /**
     * Delete a membership
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function delete_membership(array $parameters): array {
        // Sanitize inputs
        $membership_id = intval($parameters['membership_id']);
        
        // Use the service to delete the membership
        return $this->memberPressService->deleteMembership($membership_id);
    }

    /**
     * List all memberships
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function list_memberships(array $parameters): array {
        // Prepare arguments for the service
        $args = [];
        
        if (isset($parameters['limit'])) {
            $args['number'] = intval($parameters['limit']);
        }
        
        if (isset($parameters['offset'])) {
            $args['offset'] = intval($parameters['offset']);
        }
        
        // Use the service to get all memberships
        $result = $this->memberPressService->getMemberships($args);
        
        // Add a message if not present and status is success
        if ($result['status'] === 'success' && !isset($result['message'])) {
            $result['message'] = 'Memberships retrieved successfully';
        }
        
        // Format the response to match the expected structure
        if ($result['status'] === 'success') {
            $memberships = $result['data'];
            $result['data'] = [
                'memberships' => $memberships,
                'total' => count($memberships),
                'limit' => $args['number'] ?? 10,
                'offset' => $args['offset'] ?? 0,
            ];
        }
        
        return $result;
    }

    /**
     * Create a new access rule
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function create_access_rule(array $parameters): array {
        // Sanitize inputs
        $membership_id = intval($parameters['membership_id']);
        $content_type = sanitize_text_field($parameters['content_type']);
        $content_ids = array_map('intval', $parameters['content_ids']);
        $rule_type = sanitize_text_field($parameters['rule_type']);
        
        // Prepare data for the service
        $ruleData = [
            'product_id' => $membership_id,
            'content_type' => $content_type,
            'content_ids' => $content_ids,
            'rule_type' => $rule_type,
        ];
        
        // Use the service to create the access rule
        return $this->memberPressService->createAccessRule($ruleData);
    }

    /**
     * Update an existing access rule
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function update_access_rule(array $parameters): array {
        // Sanitize inputs
        $rule_id = intval($parameters['rule_id']);
        $updateData = [];
        
        // Only include parameters that are set
        if (isset($parameters['membership_id'])) {
            $updateData['product_id'] = intval($parameters['membership_id']);
        }
        
        if (isset($parameters['content_type'])) {
            $updateData['content_type'] = sanitize_text_field($parameters['content_type']);
        }
        
        if (isset($parameters['content_ids'])) {
            $updateData['content_ids'] = array_map('intval', $parameters['content_ids']);
        }
        
        if (isset($parameters['rule_type'])) {
            $updateData['rule_type'] = sanitize_text_field($parameters['rule_type']);
        }
        
        // Use the service to update the access rule
        return $this->memberPressService->updateAccessRule($rule_id, $updateData);
    }

    /**
     * Delete an access rule
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function delete_access_rule(array $parameters): array {
        // Sanitize inputs
        $rule_id = intval($parameters['rule_id']);
        
        // Use the service to delete the access rule
        return $this->memberPressService->deleteAccessRule($rule_id);
    }

    /**
     * Manage pricing for memberships
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function manage_pricing(array $parameters): array {
        // Sanitize inputs
        $membership_id = intval($parameters['membership_id']);
        $price = floatval($parameters['price']);
        $billing_type = sanitize_text_field($parameters['billing_type']);
        $billing_frequency = isset($parameters['billing_frequency']) ? sanitize_text_field($parameters['billing_frequency']) : null;
        
        // Prepare pricing data for the service
        $pricingData = [
            'price' => $price,
        ];
        
        // Set period and period_type based on billing type and frequency
        if ($billing_type === 'recurring' && $billing_frequency) {
            switch ($billing_frequency) {
                case 'monthly':
                    $pricingData['period'] = 1;
                    $pricingData['period_type'] = 'months';
                    break;
                case 'yearly':
                    $pricingData['period'] = 1;
                    $pricingData['period_type'] = 'years';
                    break;
                case 'quarterly':
                    $pricingData['period'] = 3;
                    $pricingData['period_type'] = 'months';
                    break;
            }
        } else if ($billing_type === 'one-time') {
            $pricingData['period'] = 0;
            $pricingData['period_type'] = 'lifetime';
        }
        
        // Use the service to manage pricing
        return $this->memberPressService->managePricing($membership_id, $pricingData);
    }
    
    /**
     * Associate a user with a membership
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function associate_user_with_membership(array $parameters): array {
        // Sanitize inputs
        $user_id = intval($parameters['user_id']);
        $membership_id = intval($parameters['membership_id']);
        
        // Prepare optional arguments
        $args = [];
        
        if (isset($parameters['transaction_data']) && is_array($parameters['transaction_data'])) {
            $args['transaction_data'] = $parameters['transaction_data'];
        }
        
        if (isset($parameters['subscription_data']) && is_array($parameters['subscription_data'])) {
            $args['subscription_data'] = $parameters['subscription_data'];
        }
        
        // Use the service to associate the user with the membership
        return $this->memberPressService->associateUserWithMembership($user_id, $membership_id, $args);
    }
    
    /**
     * Disassociate a user from a membership
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function disassociate_user_from_membership(array $parameters): array {
        // Sanitize inputs
        $user_id = intval($parameters['user_id']);
        $membership_id = intval($parameters['membership_id']);
        
        // Use the service to disassociate the user from the membership
        return $this->memberPressService->disassociateUserFromMembership($user_id, $membership_id);
    }
    
    /**
     * Get a user's memberships
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function get_user_memberships(array $parameters): array {
        // Sanitize inputs
        $user_id = intval($parameters['user_id']);
        
        // Use the service to get the user's memberships
        return $this->memberPressService->getUserMemberships($user_id);
    }
    
    /**
     * Update a user's role
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function update_user_role(array $parameters): array {
        // Sanitize inputs
        $user_id = intval($parameters['user_id']);
        $role = sanitize_text_field($parameters['role']);
        $action = isset($parameters['role_action']) ? sanitize_text_field($parameters['role_action']) : 'set';
        
        // Use the service to update the user's role
        return $this->memberPressService->updateUserRole($user_id, $role, $action);
    }
    
    /**
     * Get a user's permissions
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     */
    protected function get_user_permissions(array $parameters): array {
        // Sanitize inputs
        $user_id = intval($parameters['user_id']);
        
        // Use the service to get the user's permissions
        return $this->memberPressService->getUserPermissions($user_id);
    }

    /**
     * Batch get memberships
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     * @throws \Exception If batch processor is not set
     */
    protected function batch_get_memberships(array $parameters): array {
        if (!$this->batchProcessor) {
            throw new \Exception('Batch processor is not set');
        }

        $batchResult = $this->batchProcessor->processBatch('get_membership', $parameters['batch_params']);
        return $batchResult->toArray();
    }

    /**
     * Batch update memberships
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     * @throws \Exception If batch processor is not set
     */
    protected function batch_update_memberships(array $parameters): array {
        if (!$this->batchProcessor) {
            throw new \Exception('Batch processor is not set');
        }

        $batchResult = $this->batchProcessor->processBatch('update_membership', $parameters['batch_params']);
        return $batchResult->toArray();
    }

    /**
     * Batch delete memberships
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     * @throws \Exception If batch processor is not set
     */
    protected function batch_delete_memberships(array $parameters): array {
        if (!$this->batchProcessor) {
            throw new \Exception('Batch processor is not set');
        }

        $batchResult = $this->batchProcessor->processBatch('delete_membership', $parameters['batch_params']);
        return $batchResult->toArray();
    }

    /**
     * Batch create memberships
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     * @throws \Exception If batch processor is not set
     */
    protected function batch_create_memberships(array $parameters): array {
        if (!$this->batchProcessor) {
            throw new \Exception('Batch processor is not set');
        }

        $batchResult = $this->batchProcessor->processBatch('create_membership', $parameters['batch_params']);
        return $batchResult->toArray();
    }

    /**
     * Batch create access rules
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     * @throws \Exception If batch processor is not set
     */
    protected function batch_create_access_rules(array $parameters): array {
        if (!$this->batchProcessor) {
            throw new \Exception('Batch processor is not set');
        }

        $batchResult = $this->batchProcessor->processBatch('create_access_rule', $parameters['batch_params']);
        return $batchResult->toArray();
    }

    /**
     * Batch update access rules
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     * @throws \Exception If batch processor is not set
     */
    protected function batch_update_access_rules(array $parameters): array {
        if (!$this->batchProcessor) {
            throw new \Exception('Batch processor is not set');
        }

        $batchResult = $this->batchProcessor->processBatch('update_access_rule', $parameters['batch_params']);
        return $batchResult->toArray();
    }

    /**
     * Batch delete access rules
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     * @throws \Exception If batch processor is not set
     */
    protected function batch_delete_access_rules(array $parameters): array {
        if (!$this->batchProcessor) {
            throw new \Exception('Batch processor is not set');
        }

        $batchResult = $this->batchProcessor->processBatch('delete_access_rule', $parameters['batch_params']);
        return $batchResult->toArray();
    }

    /**
     * Batch associate users with memberships
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     * @throws \Exception If batch processor is not set
     */
    protected function batch_associate_users(array $parameters): array {
        if (!$this->batchProcessor) {
            throw new \Exception('Batch processor is not set');
        }

        $batchResult = $this->batchProcessor->processBatch('associate_user_with_membership', $parameters['batch_params']);
        return $batchResult->toArray();
    }

    /**
     * Batch disassociate users from memberships
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     * @throws \Exception If batch processor is not set
     */
    protected function batch_disassociate_users(array $parameters): array {
        if (!$this->batchProcessor) {
            throw new \Exception('Batch processor is not set');
        }

        $batchResult = $this->batchProcessor->processBatch('disassociate_user_from_membership', $parameters['batch_params']);
        return $batchResult->toArray();
    }

    /**
     * Batch get user memberships
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     * @throws \Exception If batch processor is not set
     */
    protected function batch_get_user_memberships(array $parameters): array {
        if (!$this->batchProcessor) {
            throw new \Exception('Batch processor is not set');
        }

        $batchResult = $this->batchProcessor->processBatch('get_user_memberships', $parameters['batch_params']);
        return $batchResult->toArray();
    }

    /**
     * Batch update user roles
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     * @throws \Exception If batch processor is not set
     */
    protected function batch_update_user_roles(array $parameters): array {
        if (!$this->batchProcessor) {
            throw new \Exception('Batch processor is not set');
        }

        $batchResult = $this->batchProcessor->processBatch('update_user_role', $parameters['batch_params']);
        return $batchResult->toArray();
    }

    /**
     * Batch get user permissions
     *
     * @param array $parameters The parameters for the operation
     * @return array The result of the operation
     * @throws \Exception If batch processor is not set
     */
    protected function batch_get_user_permissions(array $parameters): array {
        if (!$this->batchProcessor) {
            throw new \Exception('Batch processor is not set');
        }

        $batchResult = $this->batchProcessor->processBatch('get_user_permissions', $parameters['batch_params']);
        return $batchResult->toArray();
    }
}