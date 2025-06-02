<?php
/**
 * Agent Scoring System
 *
 * Provides a unified approach to agent specialization scoring
 *
 * @package MemberPress AI Assistant
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Agent Scoring System
 * 
 * This class provides a unified approach to agent specialization scoring that can be
 * reused across different agents. It implements various scoring algorithms and
 * contextual modifiers to determine the most appropriate agent for a given request.
 */
class MPAI_Agent_Scoring {
    /**
     * Singleton instance
     *
     * @var MPAI_Agent_Scoring
     */
    private static $instance = null;
    
    /**
     * Logger instance
     *
     * @var object
     */
    private $logger;
    
    /**
     * Get the singleton instance
     *
     * @return MPAI_Agent_Scoring The singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->logger = $this->get_default_logger();
        mpai_log_debug('Agent Scoring System initialized', 'agent-scoring');
    }
    
    /**
     * Get a default logger if none provided
     *
     * @return object Logger instance
     */
    private function get_default_logger() {
        return (object) [
            'info'    => function( $message, $context = [] ) { mpai_log_debug( $message, 'agent-scoring', $context ); },
            'warning' => function( $message, $context = [] ) { mpai_log_warning( $message, 'agent-scoring', $context ); },
            'error'   => function( $message, $context = [] ) { mpai_log_error( $message, 'agent-scoring', $context ); },
            'debug'   => function( $message, $context = [] ) { mpai_log_debug( $message, 'agent-scoring', $context ); },
        ];
    }
    
    /**
     * Calculate confidence score for an agent based on keywords
     *
     * @param string $message User message
     * @param array $keywords Agent keywords with weights
     * @param array $context Additional context
     * @return int Confidence score (0-100)
     */
    public function calculate_keyword_score($message, $keywords, $context = []) {
        $confidence_score = 0;
        $message_lower = strtolower($message);
        
        // 1. Keyword matching (basic matching)
        foreach ($keywords as $keyword => $weight) {
            if (strpos($message_lower, $keyword) !== false) {
                $confidence_score += $weight;
            }
        }
        
        return $confidence_score;
    }
    
    /**
     * Calculate capability-based score
     *
     * @param string $message User message
     * @param array $capabilities Agent capabilities
     * @param array $context Additional context
     * @return int Capability match score (0-50)
     */
    public function calculate_capability_score($message, $capabilities, $context = []) {
        $capability_score = 0;
        $message_lower = strtolower($message);
        
        // Check each capability for relevance to the message
        foreach ($capabilities as $capability_key => $capability_description) {
            // Convert capability key and description to relevant terms for matching
            $capability_terms = array_merge(
                $this->extract_terms($capability_key),
                $this->extract_terms($capability_description)
            );
            
            // Score based on term matching
            foreach ($capability_terms as $term) {
                if (strpos($message_lower, $term) !== false) {
                    $capability_score += 10; // Significant boost for direct capability match
                    break; // Only count each capability once
                }
            }
        }
        
        return min($capability_score, 50); // Cap capability component at 50
    }
    
    /**
     * Apply contextual modifiers to the base confidence score
     *
     * @param string $agent_id Agent identifier
     * @param int $base_score Base confidence score (0-100)
     * @param string $message User message
     * @param array $context Additional context
     * @return int Modified confidence score (0-100)
     */
    public function apply_contextual_modifiers($agent_id, $base_score, $message, $context = []) {
        $modified_score = $base_score;
        
        // 1. Conversation continuity - boost score if this is a follow-up to previous interaction
        if (isset($context['memory']) && is_array($context['memory'])) {
            // Check if the last interaction was with this agent
            $last_memory = end($context['memory']);
            if ($last_memory && 
                isset($last_memory['result']['agent']) && 
                $last_memory['result']['agent'] === $agent_id) {
                // Apply a significant boost for conversation continuity
                $modified_score += 15;
                $this->logger->debug("Applied conversation continuity boost (+15) for agent: {$agent_id}");
            }
        }
        
        // 2. Agent specialization - boost score for specialized agents over general ones
        if ($agent_id !== 'memberpress') {
            // Give a slight boost to specialized agents over the default agent
            $modified_score += 5;
            $this->logger->debug("Applied specialization boost (+5) for agent: {$agent_id}");
        }
        
        // 3. Performance history - adjust based on past performance
        if (isset($context['agent_performance']) && 
            isset($context['agent_performance'][$agent_id])) {
            $performance = $context['agent_performance'][$agent_id];
            if (isset($performance['success_rate']) && $performance['success_rate'] > 0.8) {
                // Boost for agents with high success rate
                $modified_score += 10;
                $this->logger->debug("Applied high performance boost (+10) for agent: {$agent_id}");
            } elseif (isset($performance['success_rate']) && $performance['success_rate'] < 0.4) {
                // Penalty for agents with low success rate
                $modified_score -= 10;
                $this->logger->debug("Applied low performance penalty (-10) for agent: {$agent_id}");
            }
        }
        
        // 4. User preferences - boost score if user has preferred this agent
        if (isset($context['preferences']) && 
            isset($context['preferences']['preferred_agent']) && 
            $context['preferences']['preferred_agent'] === $agent_id) {
            $modified_score += 20; // Significant boost for being the preferred agent
            $this->logger->debug("Applied user preference boost (+20) for agent: {$agent_id}");
        }
        
        // 5. Message complexity - adjust based on message complexity
        $complexity = $this->calculate_message_complexity($message);
        if ($complexity > 0.7) {
            // For complex messages, boost specialized agents
            if ($agent_id !== 'memberpress') {
                $modified_score += 10;
                $this->logger->debug("Applied complexity boost (+10) for specialized agent: {$agent_id}");
            }
        } else if ($complexity < 0.3) {
            // For simple messages, boost the default agent
            if ($agent_id === 'memberpress') {
                $modified_score += 5;
                $this->logger->debug("Applied simplicity boost (+5) for default agent: {$agent_id}");
            }
        }
        
        // Cap at 0-100 range
        return max(0, min($modified_score, 100));
    }
    
    /**
     * Calculate message complexity (0.0-1.0)
     *
     * @param string $message User message
     * @return float Complexity score (0.0-1.0)
     */
    public function calculate_message_complexity($message) {
        // Simple complexity calculation based on message length, sentence count, and word count
        $length = strlen($message);
        $sentence_count = preg_match_all('/[.!?]+/', $message, $matches);
        $word_count = str_word_count($message);
        
        // Avoid division by zero
        $sentence_count = max(1, $sentence_count);
        
        // Calculate average words per sentence
        $avg_words_per_sentence = $word_count / $sentence_count;
        
        // Normalize scores
        $length_score = min(1.0, $length / 500); // Cap at 500 chars
        $sentence_complexity_score = min(1.0, $avg_words_per_sentence / 20); // Cap at 20 words per sentence
        
        // Combined score
        $complexity = ($length_score * 0.4) + ($sentence_complexity_score * 0.6);
        
        return $complexity;
    }
    
    /**
     * Select the most appropriate agent based on confidence scores
     *
     * @param array $agent_scores Associative array of agent_id => confidence_score
     * @param string $message Original user message
     * @return string Selected agent ID
     */
    public function select_agent_with_confidence($agent_scores, $message) {
        // Find highest scoring agent
        $highest_score = 0;
        $primary_agent = 'memberpress'; // Default if no high scores
        
        foreach ($agent_scores as $agent_id => $score) {
            if ($score > $highest_score) {
                $highest_score = $score;
                $primary_agent = $agent_id;
            }
        }
        
        // Apply confidence threshold - if no agent scores high enough, fall back to default
        $confidence_threshold = 30; // Minimum score to be confident in selection
        
        if ($highest_score < $confidence_threshold) {
            // No agent is confident enough, use default memberpress agent
            $this->logger->debug("No agent met confidence threshold of {$confidence_threshold}, falling back to default");
            return 'memberpress';
        }
        
        // Check for agents with similar scores and apply tiebreaker logic
        $close_scores = [];
        $similarity_threshold = 10; // Consider scores within this range as similar
        
        foreach ($agent_scores as $agent_id => $score) {
            if ($score >= ($highest_score - $similarity_threshold)) {
                $close_scores[$agent_id] = $score;
            }
        }
        
        // If multiple agents have similar high scores, apply tiebreakers
        if (count($close_scores) > 1) {
            // Log tied agents
            $this->logger->debug("Multiple agents with similar scores: " . json_encode($close_scores));
            
            // Tiebreaker 1: Prefer specialized agents over general ones
            if (isset($close_scores['memberpress']) && count($close_scores) > 1) {
                // If default agent is tied with specialized agents, prefer specialized
                unset($close_scores['memberpress']);
                $primary_agent = array_keys($close_scores)[0];
            }
            
            // Tiebreaker 2: If still tied, use content analysis (length of message, complexity)
            if (count($close_scores) > 1) {
                // More complex, longer messages might benefit from more specialized agents
                if (strlen($message) > 100) {
                    // For longer messages, prefer specialized over general agents
                    foreach (array_keys($close_scores) as $agent_id) {
                        if ($agent_id !== 'memberpress') {
                            $primary_agent = $agent_id;
                            break;
                        }
                    }
                }
            }
        }
        
        return $primary_agent;
    }
    
    /**
     * Extract searchable terms from a string
     *
     * @param string $text Text to extract terms from
     * @return array List of terms
     */
    public function extract_terms($text) {
        // Convert from camelCase or snake_case
        $text = strtolower($text);
        $text = str_replace('_', ' ', $text);
        $text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);
        
        // Split into words and filter out common words
        $words = explode(' ', $text);
        $common_words = ['and', 'or', 'the', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'with', 'by'];
        
        return array_filter($words, function($word) use ($common_words) {
            return !in_array($word, $common_words) && strlen($word) > 2;
        });
    }
    
    /**
     * Log detailed scoring information for debugging
     *
     * @param string $agent_id Agent ID
     * @param string $message Original message
     * @param int $total_score Total confidence score
     * @param int $keyword_score Keyword-based score component
     * @param int $capability_score Capability-based score component
     * @param array $context Context used for scoring
     */
    public function log_scoring_details($agent_id, $message, $total_score, $keyword_score, $capability_score, $context = []) {
        $log_data = [
            'agent' => $agent_id,
            'message' => substr($message, 0, 50) . (strlen($message) > 50 ? '...' : ''),
            'total_score' => $total_score,
            'keyword_score' => $keyword_score,
            'capability_score' => $capability_score,
            'context_modifiers' => $total_score - ($keyword_score + $capability_score),
        ];
        
        mpai_log_debug('Agent scoring details: ' . json_encode($log_data), 'agent-scoring');
    }
}

/**
 * Get the agent scoring system instance
 *
 * @return MPAI_Agent_Scoring The agent scoring system instance
 */
function mpai_agent_scoring() {
    return MPAI_Agent_Scoring::get_instance();
}