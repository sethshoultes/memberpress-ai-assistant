<?php
/**
 * Test script for Agent Specialization Scoring system
 * 
 * This script tests the improved agent specialization scoring system.
 * 
 * Usage: Include this file in the System Diagnostics tab
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Test function for agent specialization scoring system
 * 
 * @return array Test results
 */
function mpai_test_agent_specialization_scoring() {
    $results = [
        'success' => false,
        'message' => '',
        'data' => [
            'tests' => [],
            'scores' => []
        ]
    ];
    
    try {
        // Initialize the Agent Orchestrator
        $orchestrator = new MPAI_Agent_Orchestrator();
        
        // Get available agents
        $available_agents = $orchestrator->get_available_agents();
        
        if (empty($available_agents)) {
            throw new Exception('No agents available for testing');
        }
        
        $results['data']['available_agents'] = array_keys($available_agents);
        
        // Define test cases for different agent types
        $test_messages = [
            // MemberPress agent test cases
            'memberpress' => [
                'Show me all MemberPress memberships',
                'How many active subscriptions do we have?',
                'List all transactions this month',
                'I need to create a new coupon code',
                'What is the most popular membership level?'
            ],
            
            // Command validation agent test cases
            'command_validation_agent' => [
                'Validate this WP-CLI command: wp plugin activate memberpress',
                'Is this command secure to run: wp theme activate twentytwenty',
                'Check if this command will work: wp block unregister core/paragraph',
                'Verify that this API request is valid',
                'Help me fix this command error'
            ],
            
            // Generic/ambiguous test cases
            'generic' => [
                'What plugins are installed?',
                'Show me the WordPress version',
                'How can I optimize my site?',
                'What is the status of my website?',
                'Help me with my website'
            ]
        ];
        
        // Test each message against each agent type
        foreach ($test_messages as $expected_agent_type => $messages) {
            foreach ($messages as $index => $message) {
                // Call private method via reflection to test scoring
                $method = new ReflectionMethod('MPAI_Agent_Orchestrator', 'determine_primary_intent');
                $method->setAccessible(true);
                
                // Get primary intent for the message
                $primary_agent = $method->invoke($orchestrator, $message, []);
                
                // Get agent scores via reflection
                $get_scores_method = new ReflectionMethod('MPAI_Agent_Orchestrator', 'get_agent_confidence_scores');
                $get_scores_method->setAccessible(true);
                $scores = $get_scores_method->invoke($orchestrator, $message, []);
                
                // Store test result
                $results['data']['tests'][] = [
                    'message' => $message,
                    'expected_agent_type' => $expected_agent_type === 'generic' ? 'any' : $expected_agent_type,
                    'actual_agent' => $primary_agent,
                    'pass' => $expected_agent_type === 'generic' || $primary_agent === $expected_agent_type,
                    'scores' => $scores
                ];
                
                // Store scores for analysis
                foreach ($scores as $agent_id => $score) {
                    if (!isset($results['data']['scores'][$agent_id])) {
                        $results['data']['scores'][$agent_id] = [
                            'total' => 0,
                            'count' => 0,
                            'avg' => 0,
                            'max' => 0
                        ];
                    }
                    
                    $results['data']['scores'][$agent_id]['total'] += $score;
                    $results['data']['scores'][$agent_id]['count']++;
                    $results['data']['scores'][$agent_id]['avg'] = 
                        $results['data']['scores'][$agent_id]['total'] / $results['data']['scores'][$agent_id]['count'];
                    $results['data']['scores'][$agent_id]['max'] = 
                        max($results['data']['scores'][$agent_id]['max'], $score);
                }
            }
        }
        
        // Calculate test pass rate
        $total_tests = count($results['data']['tests']);
        $passed_tests = 0;
        
        foreach ($results['data']['tests'] as $test) {
            if ($test['pass']) {
                $passed_tests++;
            }
        }
        
        $pass_rate = $total_tests > 0 ? ($passed_tests / $total_tests) * 100 : 0;
        
        $results['success'] = $pass_rate >= 80; // Require at least 80% pass rate for success
        $results['message'] = "Agent specialization scoring test complete. " .
                            "Pass rate: {$pass_rate}% ({$passed_tests}/{$total_tests})";
        
    } catch (Exception $e) {
        $results['success'] = false;
        $results['message'] = 'Error testing agent specialization scoring: ' . $e->getMessage();
    }
    
    return $results;
}

/**
 * Format the agent specialization scoring test results for display
 * 
 * @param array $results Test results
 * @return string Formatted HTML
 */
function mpai_format_agent_specialization_results($results) {
    $html = '<div class="mpai-test-results">';
    
    // Add status header
    if ($results['success']) {
        $html .= '<div class="mpai-test-success">✅ ' . esc_html($results['message']) . '</div>';
    } else {
        $html .= '<div class="mpai-test-error">❌ ' . esc_html($results['message']) . '</div>';
    }
    
    // Add available agents section
    $html .= '<h3>Available Agents</h3>';
    $html .= '<ul>';
    foreach ($results['data']['available_agents'] as $agent_id) {
        $html .= '<li>' . esc_html($agent_id) . '</li>';
    }
    $html .= '</ul>';
    
    // Add agent performance metrics
    $html .= '<h3>Agent Scoring Performance</h3>';
    $html .= '<table class="mpai-table mpai-agent-performance-table">';
    $html .= '<tr><th>Agent</th><th>Average Score</th><th>Max Score</th><th>Test Count</th></tr>';
    
    foreach ($results['data']['scores'] as $agent_id => $metrics) {
        $html .= '<tr>';
        $html .= '<td>' . esc_html($agent_id) . '</td>';
        $html .= '<td>' . number_format($metrics['avg'], 1) . '</td>';
        $html .= '<td>' . number_format($metrics['max'], 1) . '</td>';
        $html .= '<td>' . esc_html($metrics['count']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    // Add individual test results
    $html .= '<h3>Individual Test Results</h3>';
    $html .= '<div class="mpai-test-results-table-wrapper">';
    $html .= '<table class="mpai-table mpai-test-results-table">';
    $html .= '<tr><th>Message</th><th>Expected Agent</th><th>Actual Agent</th><th>Result</th><th>Agent Scores</th></tr>';
    
    foreach ($results['data']['tests'] as $test) {
        $html .= '<tr class="' . ($test['pass'] ? 'mpai-test-pass' : 'mpai-test-fail') . '">';
        $html .= '<td>' . esc_html($test['message']) . '</td>';
        $html .= '<td>' . esc_html($test['expected_agent_type']) . '</td>';
        $html .= '<td>' . esc_html($test['actual_agent']) . '</td>';
        $html .= '<td>' . ($test['pass'] ? '✅ Pass' : '❌ Fail') . '</td>';
        
        // Format scores
        $scores_html = '<ul class="mpai-score-list">';
        arsort($test['scores']); // Sort scores in descending order
        foreach ($test['scores'] as $agent_id => $score) {
            $is_highest = $agent_id === $test['actual_agent'];
            $scores_html .= '<li class="' . ($is_highest ? 'mpai-highest-score' : '') . '">';
            $scores_html .= esc_html($agent_id) . ': ' . number_format($score, 1);
            $scores_html .= $is_highest ? ' 🏆' : '';
            $scores_html .= '</li>';
        }
        $scores_html .= '</ul>';
        
        $html .= '<td>' . $scores_html . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    // Add CSS for formatting
    $html .= '
    <style>
        .mpai-test-results {
            margin: 20px 0;
        }
        .mpai-test-success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .mpai-test-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .mpai-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .mpai-table th, .mpai-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .mpai-table th {
            background-color: #f5f5f5;
        }
        .mpai-test-results-table-wrapper {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        .mpai-test-pass {
            background-color: #f0fff0;
        }
        .mpai-test-fail {
            background-color: #fff0f0;
        }
        .mpai-score-list {
            margin: 0;
            padding: 0;
            list-style-type: none;
        }
        .mpai-highest-score {
            font-weight: bold;
        }
        .mpai-agent-performance-table {
            max-width: 600px;
        }
    </style>
    ';
    
    return $html;
}