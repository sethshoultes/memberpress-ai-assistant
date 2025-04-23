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
		
		// Initialize weighted keywords for scoring
		$this->keywords = [
			// High weight for direct MemberPress mentions
			'memberpress' => 35,
			'member press' => 35,
			'mepr' => 30,
			
			// Medium weight for membership terms
			'membership' => 25,
			'memberships' => 25,
			'member' => 15,
			'members' => 15,
			
			// Medium weight for subscription terms
			'subscription' => 20,
			'subscriptions' => 20,
			'subscriber' => 15,
			'subscribers' => 15,
			
			// Medium weight for transaction terms
			'transaction' => 20,
			'transactions' => 20,
			'payment' => 15,
			'payments' => 15,
			'refund' => 15,
			'charged' => 15,
			
			// Medium weight for pricing terms
			'coupon' => 20,
			'coupons' => 20,
			'discount' => 15,
			'discounts' => 15,
			'pricing' => 15,
			'price' => 10,
			'prices' => 10,
			
			// Lower weight for related concepts
			'license' => 10,
			'licenses' => 10,
			'expire' => 10,
			'expiration' => 10,
			'renewal' => 10,
			'access' => 5,
			'customer' => 5,
			'customers' => 5,
			'user' => 3,
			'users' => 3,
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
		
		// Check for time-based queries about new members
		if ( ( strpos( $message, 'new member' ) !== false || strpos( $message, 'members joined' ) !== false )
			&& ( strpos( $message, 'this month' ) !== false || strpos( $message, 'current month' ) !== false ) ) {
			$actions[] = [
				'tool_id' => 'wp_api',
				'description' => 'Get users who registered this month',
				'parameters' => [
					'action' => 'get_users',
					'month' => 'current',
					'limit' => 100,
				],
			];
		}
		// Check for time-based queries about new members in previous month
		elseif ( ( strpos( $message, 'new member' ) !== false || strpos( $message, 'members joined' ) !== false )
			&& ( strpos( $message, 'last month' ) !== false || strpos( $message, 'previous month' ) !== false ) ) {
			$actions[] = [
				'tool_id' => 'wp_api',
				'description' => 'Get users who registered last month',
				'parameters' => [
					'action' => 'get_users',
					'month' => 'previous',
					'limit' => 100,
				],
			];
		}
		// Check for time-based queries about transactions for current month
		elseif ( strpos( $message, 'transaction' ) !== false &&
			( strpos( $message, 'this month' ) !== false || strpos( $message, 'current month' ) !== false ) ) {
			$actions[] = [
				'tool_id' => 'wp_api',
				'description' => 'Get transactions for this month',
				'parameters' => [
					'action' => 'get_transactions',
					'month' => 'current',
					'limit' => 100,
				],
			];
		}
		// Check for plugin-related queries, especially about installations
		elseif ( strpos( $message, 'plugin' ) !== false &&
			( strpos( $message, 'install' ) !== false || strpos( $message, 'recent' ) !== false ||
			  strpos( $message, 'add' ) !== false || strpos( $message, 'new' ) !== false ) ) {
			$actions[] = [
				'tool_id' => 'plugin_logs',
				'description' => 'Get recently installed plugins',
				'parameters' => [
					'action' => 'installed',
					'days' => 30,
					'limit' => 10,
				],
			];
		}
		// Check for general plugin queries
		elseif ( strpos( $message, 'plugin' ) !== false ) {
			$actions[] = [
				'tool_id' => 'plugin_logs',
				'description' => 'Get plugin activity logs',
				'parameters' => [
					'days' => 30,
					'limit' => 15,
				],
			];
		}
		// Check for common MemberPress commands
		elseif ( strpos( $message, 'list' ) !== false && strpos( $message, 'membership' ) !== false ) {
			$actions[] = [
				'tool_id' => 'wp_api',
				'description' => 'List all membership levels',
				'parameters' => [
					'action' => 'get_memberships',
					'limit' => 100,
				],
			];
		} elseif ( strpos( $message, 'transaction' ) !== false ) {
			$actions[] = [
				'tool_id' => 'wp_api',
				'description' => 'List recent transactions',
				'parameters' => [
					'action' => 'get_transactions',
					'limit' => 10,
				],
			];
		} elseif ( strpos( $message, 'subscription' ) !== false ) {
			$actions[] = [
				'tool_id' => 'wp_api',
				'description' => 'List active subscriptions',
				'parameters' => [
					'action' => 'get_subscriptions',
					'status' => 'active',
					'limit' => 10,
				],
			];
		} else {
			// Default action - try WP-CLI first, then fall back to WordPress API
			$actions[] = [
				'tool_id' => 'wpcli',
				'description' => 'Get MemberPress status using WP-CLI',
				'parameters' => [
					'command' => 'wp mepr-option get',
				],
			];
			
			// Add a fallback action using the WordPress API
			$actions[] = [
				'tool_id' => 'wp_api',
				'description' => 'Get MemberPress memberships',
				'parameters' => [
					'action' => 'get_memberships',
				],
			];
		}
		
		return ['actions' => $actions];
	}
	
	/**
	 * Get MemberPress service instance
	 * @return MPAI_MemberPress_Service
	 */
	private function get_service() {
		static $service = null;
		
		if ($service === null) {
			// Ensure the service class is loaded
			if (!class_exists('MPAI_MemberPress_Service')) {
				require_once dirname(dirname(dirname(__FILE__))) . '/class-mpai-memberpress-service.php';
			}
			$service = new MPAI_MemberPress_Service();
		}
		
		return $service;
	}
	
	/**
	 * Run a MemberPress CLI command
	 *
	 * @param string $command Command to run
	 * @param array $args Additional command arguments
	 * @return string Command output
	 */
	public function run_mepr_command( $command, $args = [] ) {
		// Check if this is a command we can handle directly with the service
		if (preg_match('/membership\s+create/i', $command) || strpos($command, 'mepr-membership create') !== false) {
			// Extract parameters from the command
			preg_match('/--name=[\'"]?([^\'"]+)[\'"]?/i', $command, $name_matches);
			preg_match('/--price=([0-9.]+)/i', $command, $price_matches);
			preg_match('/--period=([a-z]+)/i', $command, $period_matches);
			
			$name = isset($name_matches[1]) ? $name_matches[1] : 'New Membership';
			$price = isset($price_matches[1]) ? floatval($price_matches[1]) : 19.99;
			$period = isset($period_matches[1]) ? $period_matches[1] : 'month';
			
			// Log the extracted parameters
			error_log('AGENT COMMAND - Extracted parameters: name=' . $name . ', price=' . $price . ', period=' . $period);
			
			// Use our direct method
			$result = $this->create_membership_level($name, $price, ['period_type' => $period]);
			return json_encode($result);
		}
		else if (preg_match('/coupon\s+create/i', $command) || strpos($command, 'mepr-coupon create') !== false) {
			// Extract parameters from the command
			preg_match('/--code=[\'"]?([^\'"]+)[\'"]?/i', $command, $code_matches);
			preg_match('/--type=([a-z]+)/i', $command, $type_matches);
			preg_match('/--amount=([0-9.]+)/i', $command, $amount_matches);
			
			$code = isset($code_matches[1]) ? $code_matches[1] : 'COUPON' . rand(1000, 9999);
			$type = isset($type_matches[1]) ? $type_matches[1] : 'percent';
			$amount = isset($amount_matches[1]) ? floatval($amount_matches[1]) : 10;
			
			// Use our direct method
			$result = $this->create_coupon($code, $type, $amount);
			return json_encode($result);
		}
		
		// For other commands, use WP-CLI as fallback
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
	 * @param array $args Additional arguments
	 * @return array Membership data
	 */
	public function create_membership_level( $name, $price, $args = array() ) {
		$service = $this->get_service();
		
		// Ensure price is a number and greater than zero
		if (!is_numeric($price)) {
			$price = floatval($price);
		}
		
		// Set a reasonable minimum price if zero or negative
		if ($price <= 0) {
			$price = 19.99;
		}
		
		$membership_args = array_merge(array(
			'name' => $name,
			'price' => $price
		), $args);
		
		// Log the arguments being passed to create_membership
		error_log('AGENT CREATE - Membership arguments: ' . json_encode($membership_args));
		
		$result = $service->create_membership($membership_args);
		
		if (is_wp_error($result)) {
			return array(
				'success' => false, 
				'message' => $result->get_error_message()
			);
		}
		
		return array(
			'success' => true,
			'membership_id' => $result->ID,
			'name' => $result->post_title,
			'price' => $result->price,
			'period_type' => $result->period_type,
			'message' => 'Membership created successfully'
		);
	}
	
	/**
	 * Create a new coupon
	 *
	 * @param string $code Coupon code
	 * @param string $type Discount type (percent, flat)
	 * @param float $amount Discount amount
	 * @param array $args Additional arguments
	 * @return array Coupon data
	 */
	public function create_coupon( $code, $type = 'percent', $amount = 10, $args = array() ) {
		$service = $this->get_service();
		
		$coupon_args = array_merge(array(
			'code' => $code,
			'discount_type' => $type,
			'discount_amount' => $amount
		), $args);
		
		$result = $service->create_coupon($coupon_args);
		
		if (is_wp_error($result)) {
			return array(
				'success' => false, 
				'message' => $result->get_error_message()
			);
		}
		
		return array(
			'success' => true,
			'coupon_id' => $result->ID,
			'code' => $result->post_title,
			'discount_type' => $result->discount_type,
			'discount_amount' => $result->discount_amount,
			'message' => 'Coupon created successfully'
		);
	}
	
	/**
	 * Add a user to a membership
	 *
	 * @param int $user_id User ID
	 * @param int $membership_id Membership ID
	 * @param array $args Additional arguments
	 * @return array Response data
	 */
	public function add_user_to_membership($user_id, $membership_id, $args = array()) {
		$service = $this->get_service();
		
		$result = $service->add_user_to_membership($user_id, $membership_id, $args);
		
		if (is_wp_error($result)) {
			return array(
				'success' => false, 
				'message' => $result->get_error_message()
			);
		}
		
		return array(
			'success' => true,
			'subscription_id' => $result->id,
			'user_id' => $result->user_id,
			'membership_id' => $result->product_id,
			'status' => $result->status,
			'message' => 'User added to membership successfully'
		);
	}
	
	/**
	 * Create a transaction
	 *
	 * @param int $user_id User ID
	 * @param int $membership_id Membership ID
	 * @param array $args Additional arguments
	 * @return array Response data
	 */
	public function create_transaction($user_id, $membership_id, $args = array()) {
		$service = $this->get_service();
		
		$txn_args = array_merge(array(
			'user_id' => $user_id,
			'product_id' => $membership_id
		), $args);
		
		$result = $service->create_transaction($txn_args);
		
		if (is_wp_error($result)) {
			return array(
				'success' => false, 
				'message' => $result->get_error_message()
			);
		}
		
		return array(
			'success' => true,
			'transaction_id' => $result->id,
			'user_id' => $result->user_id,
			'membership_id' => $result->product_id,
			'amount' => $result->amount,
			'status' => $result->status,
			'message' => 'Transaction created successfully'
		);
	}
}
