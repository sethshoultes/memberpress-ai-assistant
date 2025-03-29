<?php
/**
 * MemberPress Agent for handling MemberPress-specific functionality
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MemberPress Agent for handling MemberPress-specific functionality
 */
class MPAI_MemberPress_Agent extends MPAI_Base_Agent {
	/**
	 * Constructor
	 *
	 * @param object $tool_registry Tool registry
	 * @param object $logger Logger
	 */
	public function __construct( $tool_registry = null, $logger = null ) {
		parent::__construct( $tool_registry, $logger );
		
		$this->id = 'memberpress';
		$this->name = 'MemberPress Agent';
		$this->description = 'Handles MemberPress-specific functionality and management tasks';
		$this->capabilities = [
			'run_mepr_command' => 'Run MemberPress CLI commands',
			'manage_memberships' => 'Create and manage membership levels',
			'process_transactions' => 'Manage and process transactions',
			'handle_subscriptions' => 'Manage memberships and subscriptions',
			'create_coupons' => 'Create and manage discount coupons',
			'export_data' => 'Export MemberPress data',
		];
	}
	
	/**
	 * Process a MemberPress request
	 *
	 * @param array $intent_data Intent data from orchestrator
	 * @param array $context User context
	 * @return array Response data
	 */
	public function process_request( $intent_data, $context = [] ) {
		$this->logger->info( 'MemberPress agent processing request', [
			'intent' => $intent_data['intent'],
			'message' => $intent_data['original_message'],
		] );
		
		// Plan the actions to take based on the intent
		$plan = $this->plan_actions( $intent_data );
		
		// Execute each action in the plan
		$results = [];
		$overall_status = true;
		
		foreach ( $plan['actions'] as $action ) {
			try {
				$tool_id = $action['tool_id'];
				$parameters = $action['parameters'];
				
				$result = $this->execute_tool( $tool_id, $parameters );
				
				$results[] = [
					'description' => $action['description'],
					'status' => 'success',
					'result' => $result,
				];
			} catch ( Exception $e ) {
				$overall_status = false;
				$results[] = [
					'description' => $action['description'],
					'status' => 'error',
					'error' => $e->getMessage(),
				];
				
				$this->logger->error( 'Action failed: ' . $e->getMessage() );
				
				// Break on critical errors
				if ( isset( $action['critical'] ) && $action['critical'] ) {
					break;
				}
			}
		}
		
		// Generate a human-readable summary of the results
		$summary = $this->generate_summary( $results, $intent_data );
		
		return [
			'success' => $overall_status,
			'message' => $summary,
			'data' => [
				'plan' => $plan,
				'results' => $results,
			],
		];
	}
	
	/**
	 * Plan actions to take based on intent
	 *
	 * @param array $intent_data Intent data
	 * @return array Action plan
	 */
	protected function plan_actions( $intent_data ) {
		// Try to use OpenAI if available
		if ( class_exists( 'MPAI_OpenAI' ) ) {
			$openai = new MPAI_OpenAI();
			
			// Build a system prompt that describes the available tools
			$system_prompt = "You are an AI assistant planning actions for the MemberPress Agent.\n\n";
			$system_prompt .= "Available tools:\n";
			$system_prompt .= "- wpcli: Execute WordPress CLI commands\n";
			$system_prompt .= "- memberpress: Access MemberPress API functions\n";
			$system_prompt .= "- database: Perform database queries\n";
			
			$system_prompt .= "\nCreate a JSON plan with a sequence of actions to accomplish the user's request.";
			$system_prompt .= "\nEach action should have: tool_id, parameters, description";
			
			$user_prompt = "User request: {$intent_data['original_message']}";
			
			// Add more context if available
			if ( ! empty( $intent_data['context'] ) ) {
				$user_prompt .= "\n\nContext: " . json_encode( $intent_data['context'] );
			}
			
			$messages = [
				['role' => 'system', 'content' => $system_prompt],
				['role' => 'user', 'content' => $user_prompt]
			];
			
			$response = $openai->generate_chat_completion( $messages );
			
			if ( ! is_wp_error( $response ) ) {
				// Extract and parse the JSON plan
				$plan = $this->extract_json_plan( $response );
				
				if ( $plan ) {
					return $plan;
				}
			}
		}
		
		// Fallback to simple plan if OpenAI not available or failed
		return $this->create_fallback_plan( $intent_data );
	}
	
	/**
	 * Extract JSON plan from text response
	 *
	 * @param string $response Text response containing JSON
	 * @return array|false Parsed plan or false if extraction failed
	 */
	private function extract_json_plan( $response ) {
		// Find JSON in the response
		preg_match( '/```(?:json)?\s*([\s\S]*?)```/', $response, $matches );
		
		if ( ! empty( $matches[1] ) ) {
			$plan = json_decode( $matches[1], true );
			if ( $plan && json_last_error() === JSON_ERROR_NONE ) {
				return $plan;
			}
		}
		
		// Try extracting without code blocks
		preg_match( '/(\{[\s\S]*\})/', $response, $matches );
		
		if ( ! empty( $matches[1] ) ) {
			$plan = json_decode( $matches[1], true );
			if ( $plan && json_last_error() === JSON_ERROR_NONE ) {
				return $plan;
			}
		}
		
		return false;
	}
	
	/**
	 * Create a fallback plan if OpenAI fails
	 *
	 * @param array $intent_data Intent data
	 * @return array Fallback plan
	 */
	private function create_fallback_plan( $intent_data ) {
		$message = strtolower( $intent_data['original_message'] );
		$actions = [];
		
		// Check for common MemberPress commands
		if ( strpos( $message, 'list' ) !== false && strpos( $message, 'membership' ) !== false ) {
			$actions[] = [
				'tool_id' => 'wpcli',
				'description' => 'List all membership levels',
				'parameters' => [
					'command' => 'wp mepr-membership list',
				],
			];
		} elseif ( strpos( $message, 'transaction' ) !== false ) {
			$actions[] = [
				'tool_id' => 'wpcli',
				'description' => 'List recent transactions',
				'parameters' => [
					'command' => 'wp mepr-transaction list --limit=10',
				],
			];
		} elseif ( strpos( $message, 'subscription' ) !== false ) {
			$actions[] = [
				'tool_id' => 'wpcli',
				'description' => 'List active subscriptions',
				'parameters' => [
					'command' => 'wp mepr-subscription list --status=active --limit=10',
				],
			];
		} else {
			// Default action
			$actions[] = [
				'tool_id' => 'wpcli',
				'description' => 'Get MemberPress status',
				'parameters' => [
					'command' => 'wp mepr-option get',
				],
			];
		}
		
		return ['actions' => $actions];
	}
	
	/**
	 * Run a MemberPress CLI command
	 *
	 * @param string $command Command to run
	 * @param array $args Additional command arguments
	 * @return string Command output
	 */
	public function run_mepr_command( $command, $args = [] ) {
		// Ensure command starts with wp mepr
		if ( strpos( $command, 'wp mepr' ) !== 0 ) {
			$command = 'wp mepr-' . $command;
		}
		
		// Execute the command using the WP-CLI tool
		return $this->execute_tool( 'wpcli', [
			'command' => $command,
			'args' => $args,
		] );
	}
	
	/**
	 * Create a new membership level
	 *
	 * @param string $name Membership level name
	 * @param float $price Membership price
	 * @param string $period Billing period (month, year, etc)
	 * @return array Membership data
	 */
	public function create_membership_level( $name, $price, $period = 'month' ) {
		// Use the MemberPress tool to create a new membership level
		$command = "wp mepr-membership create --name='{$name}' --price={$price} --period={$period} --format=json";
		
		$result = $this->execute_tool( 'wpcli', ['command' => $command] );
		
		// Process the result
		return json_decode( $result, true );
	}
	
	/**
	 * Create a new coupon
	 *
	 * @param string $code Coupon code
	 * @param string $type Discount type (percent, flat)
	 * @param float $amount Discount amount
	 * @return array Coupon data
	 */
	public function create_coupon( $code, $type = 'percent', $amount = 10 ) {
		// Use the MemberPress tool to create a new coupon
		$command = "wp mepr-coupon create --code='{$code}' --type={$type} --amount={$amount} --format=json";
		
		$result = $this->execute_tool( 'wpcli', ['command' => $command] );
		
		// Process the result
		return json_decode( $result, true );
	}
}
