<?php
/**
 * Batch Processor
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Batch;

use MemberpressAiAssistant\Services\CacheService;
use MemberpressAiAssistant\Services\MemberPressService;

/**
 * Class for processing batched operations
 */
class BatchProcessor {
    /**
     * MemberPress service
     *
     * @var MemberPressService
     */
    protected $memberPressService;

    /**
     * Cache service
     *
     * @var CacheService|null
     */
    protected $cacheService;

    /**
     * Logger instance
     *
     * @var mixed
     */
    protected $logger;

    /**
     * Default batch size
     *
     * @var int
     */
    protected $defaultBatchSize = 50;

    /**
     * Batch size configuration for different operations
     *
     * @var array
     */
    protected $batchSizeConfig = [];

    /**
     * Constructor
     *
     * @param MemberPressService $memberPressService MemberPress service
     * @param CacheService|null  $cacheService       Cache service
     * @param mixed              $logger             Logger instance
     */
    public function __construct(
        MemberPressService $memberPressService,
        CacheService $cacheService = null,
        $logger = null
    ) {
        $this->memberPressService = $memberPressService;
        $this->cacheService = $cacheService;
        $this->logger = $logger;

        // Set default batch size configuration
        $this->setDefaultBatchSizeConfig();
    }

    /**
     * Set the default batch size configuration
     *
     * @return void
     */
    protected function setDefaultBatchSizeConfig(): void {
        $this->batchSizeConfig = [
            // Read operations can have larger batch sizes
            'get_membership' => 100,
            'list_memberships' => 100,
            'get_user_memberships' => 100,
            'get_user_permissions' => 100,
            
            // Write operations should have smaller batch sizes
            'create_membership' => 20,
            'update_membership' => 20,
            'delete_membership' => 20,
            'create_access_rule' => 20,
            'update_access_rule' => 20,
            'delete_access_rule' => 20,
            'associate_user_with_membership' => 30,
            'disassociate_user_from_membership' => 30,
            'update_user_role' => 30,
            
            // Default batch size for any other operation
            'default' => 50,
        ];
    }

    /**
     * Set the batch size for a specific operation
     *
     * @param string $operation  The operation
     * @param int    $batchSize  The batch size
     * @return self
     */
    public function setBatchSize(string $operation, int $batchSize): self {
        $this->batchSizeConfig[$operation] = max(1, $batchSize);
        return $this;
    }

    /**
     * Set the default batch size
     *
     * @param int $batchSize The default batch size
     * @return self
     */
    public function setDefaultBatchSize(int $batchSize): self {
        $this->defaultBatchSize = max(1, $batchSize);
        $this->batchSizeConfig['default'] = $this->defaultBatchSize;
        return $this;
    }

    /**
     * Get the batch size for an operation
     *
     * @param string $operation The operation
     * @return int The batch size
     */
    public function getBatchSize(string $operation): int {
        return $this->batchSizeConfig[$operation] ?? $this->batchSizeConfig['default'] ?? $this->defaultBatchSize;
    }

    /**
     * Process a batch of operations
     *
     * @param string $operation   The operation to perform
     * @param array  $batchParams Array of parameters for each operation in the batch
     * @return BatchResult The result of the batch operation
     */
    public function processBatch(string $operation, array $batchParams): BatchResult {
        // Validate operation
        if (empty($operation)) {
            return new BatchResult(
                'error',
                'Operation is required for batch processing',
                []
            );
        }

        // Validate batch parameters
        if (empty($batchParams)) {
            return new BatchResult(
                'error',
                'Batch parameters are required for batch processing',
                []
            );
        }

        // Get batch size for this operation
        $batchSize = $this->getBatchSize($operation);

        // Group parameters by similarity to optimize processing
        $groupedParams = $this->groupParametersByType($operation, $batchParams);

        // Process each group
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($groupedParams as $groupKey => $group) {
            $this->log("Processing batch group: $groupKey", [
                'operation' => $operation,
                'group_size' => count($group),
                'batch_size' => $batchSize
            ]);

            // Process the group in chunks of batch size
            $chunks = array_chunk($group, $batchSize);
            
            foreach ($chunks as $chunkIndex => $chunk) {
                $this->log("Processing batch chunk $chunkIndex", [
                    'operation' => $operation,
                    'chunk_size' => count($chunk)
                ]);
                
                $chunkResults = $this->processOperationChunk($operation, $chunk);
                $results = array_merge($results, $chunkResults);
                
                // Count successes and failures
                foreach ($chunkResults as $result) {
                    if (isset($result['status']) && $result['status'] === 'success') {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }
                }
            }
        }

        // Determine overall status and message
        $status = $failureCount === 0 ? 'success' : ($successCount > 0 ? 'partial' : 'error');
        $message = $this->getBatchResultMessage($status, $successCount, $failureCount);

        return new BatchResult($status, $message, $results);
    }

    /**
     * Group parameters by type to optimize processing
     *
     * @param string $operation   The operation
     * @param array  $batchParams The batch parameters
     * @return array Grouped parameters
     */
    protected function groupParametersByType(string $operation, array $batchParams): array {
        $grouped = [];

        switch ($operation) {
            case 'get_membership':
            case 'update_membership':
            case 'delete_membership':
                // Group by membership_id
                foreach ($batchParams as $params) {
                    if (isset($params['membership_id'])) {
                        $grouped['membership_' . $params['membership_id']][] = $params;
                    } else {
                        $grouped['ungrouped'][] = $params;
                    }
                }
                break;

            case 'create_access_rule':
                // Group by membership_id and content_type
                foreach ($batchParams as $params) {
                    if (isset($params['membership_id']) && isset($params['content_type'])) {
                        $grouped['membership_' . $params['membership_id'] . '_' . $params['content_type']][] = $params;
                    } else {
                        $grouped['ungrouped'][] = $params;
                    }
                }
                break;

            case 'update_access_rule':
            case 'delete_access_rule':
                // Group by rule_id
                foreach ($batchParams as $params) {
                    if (isset($params['rule_id'])) {
                        $grouped['rule_' . $params['rule_id']][] = $params;
                    } else {
                        $grouped['ungrouped'][] = $params;
                    }
                }
                break;

            case 'associate_user_with_membership':
            case 'disassociate_user_from_membership':
                // Group by user_id
                foreach ($batchParams as $params) {
                    if (isset($params['user_id'])) {
                        $grouped['user_' . $params['user_id']][] = $params;
                    } else {
                        $grouped['ungrouped'][] = $params;
                    }
                }
                break;

            case 'get_user_memberships':
            case 'get_user_permissions':
            case 'update_user_role':
                // Group by user_id
                foreach ($batchParams as $params) {
                    if (isset($params['user_id'])) {
                        $grouped['user_' . $params['user_id']][] = $params;
                    } else {
                        $grouped['ungrouped'][] = $params;
                    }
                }
                break;

            default:
                // Default grouping (no special grouping)
                $grouped['default'] = $batchParams;
                break;
        }

        return $grouped;
    }

    /**
     * Process a chunk of operations
     *
     * @param string $operation The operation
     * @param array  $chunk     The chunk of parameters
     * @return array Results for each operation in the chunk
     */
    protected function processOperationChunk(string $operation, array $chunk): array {
        $results = [];

        // Special handling for certain operations that can be optimized
        switch ($operation) {
            case 'get_membership':
                $results = $this->batchGetMemberships($chunk);
                break;

            case 'list_memberships':
                $results = $this->batchListMemberships($chunk);
                break;

            case 'get_user_memberships':
                $results = $this->batchGetUserMemberships($chunk);
                break;

            case 'get_user_permissions':
                $results = $this->batchGetUserPermissions($chunk);
                break;

            default:
                // Default processing (one by one)
                foreach ($chunk as $params) {
                    $params['operation'] = $operation;
                    $methodName = $operation;
                    
                    try {
                        if (method_exists($this->memberPressService, $methodName)) {
                            // Extract parameters based on the operation
                            $result = $this->callServiceMethod($methodName, $params);
                        } else {
                            $result = [
                                'status' => 'error',
                                'message' => "Operation method '$methodName' not found in MemberPressService"
                            ];
                        }
                    } catch (\Exception $e) {
                        $result = [
                            'status' => 'error',
                            'message' => 'Error executing operation: ' . $e->getMessage()
                        ];
                    }
                    
                    // Add the original parameters to the result for reference
                    $result['original_params'] = $params;
                    $results[] = $result;
                }
                break;
        }

        return $results;
    }

    /**
     * Call a service method with the appropriate parameters
     *
     * @param string $methodName The method name
     * @param array  $params     The parameters
     * @return array The result
     */
    protected function callServiceMethod(string $methodName, array $params): array {
        switch ($methodName) {
            case 'get_membership':
                return $this->memberPressService->getMembership($params['membership_id']);

            case 'update_membership':
                $updateData = [];
                if (isset($params['name'])) {
                    $updateData['name'] = $params['name'];
                }
                if (isset($params['price'])) {
                    $updateData['price'] = $params['price'];
                }
                if (isset($params['terms'])) {
                    // Map terms to appropriate MemberPress fields
                    switch ($params['terms']) {
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
                return $this->memberPressService->updateMembership($params['membership_id'], $updateData);

            case 'delete_membership':
                return $this->memberPressService->deleteMembership($params['membership_id']);

            case 'create_membership':
                return $this->memberPressService->createMembership($params);

            case 'create_access_rule':
                $ruleData = [
                    'product_id' => $params['membership_id'],
                    'content_type' => $params['content_type'],
                    'content_ids' => $params['content_ids'],
                    'rule_type' => $params['rule_type'],
                ];
                return $this->memberPressService->createAccessRule($ruleData);

            case 'update_access_rule':
                $updateData = [];
                if (isset($params['membership_id'])) {
                    $updateData['product_id'] = $params['membership_id'];
                }
                if (isset($params['content_type'])) {
                    $updateData['content_type'] = $params['content_type'];
                }
                if (isset($params['content_ids'])) {
                    $updateData['content_ids'] = $params['content_ids'];
                }
                if (isset($params['rule_type'])) {
                    $updateData['rule_type'] = $params['rule_type'];
                }
                return $this->memberPressService->updateAccessRule($params['rule_id'], $updateData);

            case 'delete_access_rule':
                return $this->memberPressService->deleteAccessRule($params['rule_id']);

            case 'manage_pricing':
                return $this->memberPressService->managePricing($params['membership_id'], $params);

            case 'associate_user_with_membership':
                $args = [];
                if (isset($params['transaction_data'])) {
                    $args['transaction_data'] = $params['transaction_data'];
                }
                if (isset($params['subscription_data'])) {
                    $args['subscription_data'] = $params['subscription_data'];
                }
                return $this->memberPressService->associateUserWithMembership(
                    $params['user_id'],
                    $params['membership_id'],
                    $args
                );

            case 'disassociate_user_from_membership':
                return $this->memberPressService->disassociateUserFromMembership(
                    $params['user_id'],
                    $params['membership_id']
                );

            case 'update_user_role':
                $action = isset($params['role_action']) ? $params['role_action'] : 'set';
                return $this->memberPressService->updateUserRole(
                    $params['user_id'],
                    $params['role'],
                    $action
                );

            default:
                throw new \Exception("Method '$methodName' not implemented for batch processing");
        }
    }

    /**
     * Batch get memberships
     *
     * @param array $chunk The chunk of parameters
     * @return array Results for each operation in the chunk
     */
    protected function batchGetMemberships(array $chunk): array {
        $results = [];
        $membershipIds = [];

        // Extract all membership IDs
        foreach ($chunk as $params) {
            if (isset($params['membership_id'])) {
                $membershipIds[] = $params['membership_id'];
            }
        }

        // Get unique membership IDs
        $uniqueMembershipIds = array_unique($membershipIds);

        // Get all memberships in one call if possible
        $membershipsData = [];
        try {
            $allMemberships = $this->memberPressService->getMemberships();
            if ($allMemberships['status'] === 'success' && isset($allMemberships['data'])) {
                foreach ($allMemberships['data'] as $membership) {
                    if (isset($membership['id']) && in_array($membership['id'], $uniqueMembershipIds)) {
                        $membershipsData[$membership['id']] = $membership;
                    }
                }
            }
        } catch (\Exception $e) {
            // If bulk fetch fails, we'll fall back to individual fetches
            $this->log('Bulk membership fetch failed, falling back to individual fetches', [
                'error' => $e->getMessage()
            ]);
        }

        // Process each request
        foreach ($chunk as $params) {
            if (isset($params['membership_id'])) {
                $membershipId = $params['membership_id'];
                
                if (isset($membershipsData[$membershipId])) {
                    // Use the data from the bulk fetch
                    $results[] = [
                        'status' => 'success',
                        'message' => 'Membership retrieved successfully',
                        'data' => $membershipsData[$membershipId],
                        'original_params' => $params
                    ];
                } else {
                    // Fall back to individual fetch
                    try {
                        $result = $this->memberPressService->getMembership($membershipId);
                        $result['original_params'] = $params;
                        $results[] = $result;
                    } catch (\Exception $e) {
                        $results[] = [
                            'status' => 'error',
                            'message' => 'Error retrieving membership: ' . $e->getMessage(),
                            'original_params' => $params
                        ];
                    }
                }
            } else {
                $results[] = [
                    'status' => 'error',
                    'message' => 'Membership ID is required for get_membership operation',
                    'original_params' => $params
                ];
            }
        }

        return $results;
    }

    /**
     * Batch list memberships
     *
     * @param array $chunk The chunk of parameters
     * @return array Results for each operation in the chunk
     */
    protected function batchListMemberships(array $chunk): array {
        $results = [];

        // Process each request
        foreach ($chunk as $params) {
            try {
                $args = [];
                
                if (isset($params['limit'])) {
                    $args['number'] = $params['limit'];
                }
                
                if (isset($params['offset'])) {
                    $args['offset'] = $params['offset'];
                }
                
                $result = $this->memberPressService->getMemberships($args);
                $result['original_params'] = $params;
                $results[] = $result;
            } catch (\Exception $e) {
                $results[] = [
                    'status' => 'error',
                    'message' => 'Error listing memberships: ' . $e->getMessage(),
                    'original_params' => $params
                ];
            }
        }

        return $results;
    }

    /**
     * Batch get user memberships
     *
     * @param array $chunk The chunk of parameters
     * @return array Results for each operation in the chunk
     */
    protected function batchGetUserMemberships(array $chunk): array {
        $results = [];

        // Process each request
        foreach ($chunk as $params) {
            if (isset($params['user_id'])) {
                try {
                    $result = $this->memberPressService->getUserMemberships($params['user_id']);
                    $result['original_params'] = $params;
                    $results[] = $result;
                } catch (\Exception $e) {
                    $results[] = [
                        'status' => 'error',
                        'message' => 'Error retrieving user memberships: ' . $e->getMessage(),
                        'original_params' => $params
                    ];
                }
            } else {
                $results[] = [
                    'status' => 'error',
                    'message' => 'User ID is required for get_user_memberships operation',
                    'original_params' => $params
                ];
            }
        }

        return $results;
    }

    /**
     * Batch get user permissions
     *
     * @param array $chunk The chunk of parameters
     * @return array Results for each operation in the chunk
     */
    protected function batchGetUserPermissions(array $chunk): array {
        $results = [];

        // Process each request
        foreach ($chunk as $params) {
            if (isset($params['user_id'])) {
                try {
                    $result = $this->memberPressService->getUserPermissions($params['user_id']);
                    $result['original_params'] = $params;
                    $results[] = $result;
                } catch (\Exception $e) {
                    $results[] = [
                        'status' => 'error',
                        'message' => 'Error retrieving user permissions: ' . $e->getMessage(),
                        'original_params' => $params
                    ];
                }
            } else {
                $results[] = [
                    'status' => 'error',
                    'message' => 'User ID is required for get_user_permissions operation',
                    'original_params' => $params
                ];
            }
        }

        return $results;
    }

    /**
     * Get a message for the batch result based on status and counts
     *
     * @param string $status        The status
     * @param int    $successCount  The success count
     * @param int    $failureCount  The failure count
     * @return string The message
     */
    protected function getBatchResultMessage(string $status, int $successCount, int $failureCount): string {
        $totalCount = $successCount + $failureCount;
        
        switch ($status) {
            case 'success':
                return "All $totalCount operations completed successfully";
                
            case 'partial':
                return "$successCount of $totalCount operations completed successfully, $failureCount failed";
                
            case 'error':
                return "All $totalCount operations failed";
                
            default:
                return "Batch processing completed with $successCount successes and $failureCount failures";
        }
    }

    /**
     * Log a message
     *
     * @param string $message The message
     * @param array  $context Additional context
     * @return void
     */
    protected function log(string $message, array $context = []): void {
        if ($this->logger) {
            $this->logger->info('[BatchProcessor] ' . $message, $context);
        }
    }
}