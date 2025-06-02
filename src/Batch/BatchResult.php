<?php
/**
 * Batch Result
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Batch;

/**
 * Class for standardized batch operation results
 */
class BatchResult {
    /**
     * The overall status of the batch operation
     *
     * @var string
     */
    protected $status;

    /**
     * The overall message for the batch operation
     *
     * @var string
     */
    protected $message;

    /**
     * The individual results for each operation in the batch
     *
     * @var array
     */
    protected $results;

    /**
     * The count of successful operations
     *
     * @var int
     */
    protected $successCount;

    /**
     * The count of failed operations
     *
     * @var int
     */
    protected $failureCount;

    /**
     * Constructor
     *
     * @param string $status  The overall status of the batch operation
     * @param string $message The overall message for the batch operation
     * @param array  $results The individual results for each operation in the batch
     */
    public function __construct(string $status, string $message, array $results = []) {
        $this->status = $status;
        $this->message = $message;
        $this->results = $results;
        $this->calculateCounts();
    }

    /**
     * Calculate success and failure counts
     *
     * @return void
     */
    protected function calculateCounts(): void {
        $this->successCount = 0;
        $this->failureCount = 0;

        foreach ($this->results as $result) {
            if (isset($result['status']) && $result['status'] === 'success') {
                $this->successCount++;
            } else {
                $this->failureCount++;
            }
        }
    }

    /**
     * Add a result to the batch
     *
     * @param array $result The result to add
     * @return self
     */
    public function addResult(array $result): self {
        $this->results[] = $result;
        $this->calculateCounts();
        return $this;
    }

    /**
     * Set the overall status
     *
     * @param string $status The status to set
     * @return self
     */
    public function setStatus(string $status): self {
        $this->status = $status;
        return $this;
    }

    /**
     * Set the overall message
     *
     * @param string $message The message to set
     * @return self
     */
    public function setMessage(string $message): self {
        $this->message = $message;
        return $this;
    }

    /**
     * Get the overall status
     *
     * @return string The overall status
     */
    public function getStatus(): string {
        return $this->status;
    }

    /**
     * Get the overall message
     *
     * @return string The overall message
     */
    public function getMessage(): string {
        return $this->message;
    }

    /**
     * Get the individual results
     *
     * @return array The individual results
     */
    public function getResults(): array {
        return $this->results;
    }

    /**
     * Get the success count
     *
     * @return int The success count
     */
    public function getSuccessCount(): int {
        return $this->successCount;
    }

    /**
     * Get the failure count
     *
     * @return int The failure count
     */
    public function getFailureCount(): int {
        return $this->failureCount;
    }

    /**
     * Get the total count of operations
     *
     * @return int The total count
     */
    public function getTotalCount(): int {
        return count($this->results);
    }

    /**
     * Check if all operations were successful
     *
     * @return bool Whether all operations were successful
     */
    public function isAllSuccess(): bool {
        return $this->failureCount === 0 && $this->successCount > 0;
    }

    /**
     * Check if any operations were successful
     *
     * @return bool Whether any operations were successful
     */
    public function hasSuccess(): bool {
        return $this->successCount > 0;
    }

    /**
     * Check if any operations failed
     *
     * @return bool Whether any operations failed
     */
    public function hasFailures(): bool {
        return $this->failureCount > 0;
    }

    /**
     * Convert the batch result to an array
     *
     * @return array The batch result as an array
     */
    public function toArray(): array {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'results' => $this->results,
            'summary' => [
                'total' => $this->getTotalCount(),
                'success' => $this->successCount,
                'failure' => $this->failureCount,
            ],
        ];
    }
}