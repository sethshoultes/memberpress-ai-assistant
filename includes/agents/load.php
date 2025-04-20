<?php
/**
 * MemberPress AI Assistant - Agents System Loader
 *
 * Loads the unified agents system components
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Load the agent interface
require_once dirname( __FILE__ ) . '/interfaces/interface-mpai-agent.php';

// Load the base agent class
require_once dirname( __FILE__ ) . '/class-mpai-base-agent.php';

// Load the agent scoring system
require_once dirname( __FILE__ ) . '/class-mpai-agent-scoring.php';

// Load the agent orchestrator
require_once dirname( __FILE__ ) . '/class-mpai-agent-orchestrator.php';

// Load specialized agents
$specialized_agents_dir = dirname( __FILE__ ) . '/specialized/';
if (is_dir($specialized_agents_dir)) {
    $agent_files = glob($specialized_agents_dir . 'class-mpai-*.php');
    foreach ($agent_files as $agent_file) {
        require_once $agent_file;
    }
}

// Initialize the agents system
function mpai_init_agents_system() {
    // Get the agent scoring system instance to ensure it's initialized
    mpai_agent_scoring();
    
    // Get the agent orchestrator instance to ensure it's initialized
    $orchestrator = MPAI_Agent_Orchestrator::get_instance();
    
    // Log initialization
    mpai_log_debug('MemberPress agents system initialized', [
        'agent_scoring' => 'Initialized',
        'orchestrator' => 'Initialized'
    ]);
    
    return $orchestrator;
}

// Initialize the agents system
mpai_init_agents_system();