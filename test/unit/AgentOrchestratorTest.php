<?php
/**
 * Test case for the Agent Orchestrator
 *
 * @package MemberPress AI Assistant
 */

class AgentOrchestratorTest extends WP_UnitTestCase {
    /**
     * Test agent discovery
     */
    public function testAgentDiscovery() {
        // Initialize orchestrator
        $orchestrator = new MPAI_Agent_Orchestrator();
        
        // Check that core agents are discovered
        $agents = $orchestrator->get_available_agents();
        
        $this->assertArrayHasKey('memberpress', $agents);
        $this->assertArrayHasKey('command_validation', $agents);
    }
    
    /**
     * Test agent security validation
     */
    public function testAgentSecurityValidation() {
        // Create a mock of the Orchestrator with the validate_agent method public
        $orchestrator = $this->getMockBuilder(MPAI_Agent_Orchestrator::class)
            ->disableOriginalConstructor()
            ->setMethods(['validate_agent_public'])
            ->getMock();
        
        // Create a reflection class to access the private validate_agent method
        $reflection = new ReflectionClass(MPAI_Agent_Orchestrator::class);
        $method = $reflection->getMethod('validate_agent');
        $method->setAccessible(true);
        
        // Create a valid mock agent
        $valid_agent = $this->getMockBuilder(MPAI_Agent::class)
            ->getMock();
        $valid_agent->method('get_capabilities')->willReturn(array('cap1' => true));
        $valid_agent->method('get_name')->willReturn('Test Agent');
        $valid_agent->method('get_description')->willReturn('Test Description');
        
        // Test a valid agent
        $result = $method->invoke($orchestrator, 'test_agent', $valid_agent);
        $this->assertTrue($result);
        
        // Create an invalid mock agent with missing methods
        $invalid_agent = $this->getMockBuilder(stdClass::class)
            ->getMock();
        
        // Test an invalid agent
        $result = $method->invoke($orchestrator, 'test_agent', $invalid_agent);
        $this->assertFalse($result);
    }
    
    /**
     * Test agent message validation
     */
    public function testAgentMessageValidation() {
        // Create a mock of the Orchestrator with the validate_agent_message method public
        $orchestrator = $this->getMockBuilder(MPAI_Agent_Orchestrator::class)
            ->disableOriginalConstructor()
            ->setMethods(['validate_agent_message_public'])
            ->getMock();
        
        // Create a reflection class to access the private validate_agent_message method
        $reflection = new ReflectionClass(MPAI_Agent_Orchestrator::class);
        $method = $reflection->getMethod('validate_agent_message');
        $method->setAccessible(true);
        
        // Create a mock Agent Message
        $message = $this->getMockBuilder(MPAI_Agent_Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Configure the mock message
        $message->method('get_sender')->willReturn('agent1');
        $message->method('get_receiver')->willReturn('agent2');
        $message->method('get_content')->willReturn('Test content');
        
        // Set up agents property to include mock agents
        $agents_property = $reflection->getProperty('agents');
        $agents_property->setAccessible(true);
        $agents_property->setValue($orchestrator, [
            'agent1' => new stdClass(),
            'agent2' => new stdClass()
        ]);
        
        // Test a valid message
        $result = $method->invoke($orchestrator, $message);
        $this->assertTrue($result);
        
        // Test a message with XSS content
        $bad_message = $this->getMockBuilder(MPAI_Agent_Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $bad_message->method('get_sender')->willReturn('agent1');
        $bad_message->method('get_receiver')->willReturn('agent2');
        $bad_message->method('get_content')->willReturn('<script>alert("XSS")</script>');
        
        $result = $method->invoke($orchestrator, $bad_message);
        $this->assertFalse($result);
    }
    
    /**
     * Test agent scoring
     */
    public function testAgentScoring() {
        // Create a mock agent
        $mock_agent = $this->getMockBuilder(MPAI_Agent::class)
            ->getMock();
        $mock_agent->method('evaluate_request')
            ->willReturnCallback(function($message, $context = []) {
                // Simple callback that returns 80 if the message contains "memberpress"
                return (stripos($message, 'memberpress') !== false) ? 80 : 30;
            });
        
        // Create a reflection class for the Orchestrator
        $reflection = new ReflectionClass(MPAI_Agent_Orchestrator::class);
        $determine_intent_method = $reflection->getMethod('determine_primary_intent');
        $determine_intent_method->setAccessible(true);
        
        // Create a mock orchestrator
        $orchestrator = $this->getMockBuilder(MPAI_Agent_Orchestrator::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set up agents property
        $agents_property = $reflection->getProperty('agents');
        $agents_property->setAccessible(true);
        $agents_property->setValue($orchestrator, [
            'memberpress' => $mock_agent,
            'wordpress' => $mock_agent
        ]);
        
        // Create a logger property with mock logger
        $logger_property = $reflection->getProperty('logger');
        $logger_property->setAccessible(true);
        $logger_property->setValue($orchestrator, (object)[
            'info' => function() {},
            'debug' => function() {},
            'warning' => function() {},
            'error' => function() {}
        ]);
        
        // Test with a message that should score high for memberpress
        $result = $determine_intent_method->invoke(
            $orchestrator, 
            'Tell me about memberpress subscriptions',
            []
        );
        $this->assertEquals('memberpress', $result);
        
        // Test with a message that should score lower
        $result = $determine_intent_method->invoke(
            $orchestrator, 
            'How do I configure settings?',
            []
        );
        // Both agents return the same score for this message, so first one wins
        $this->assertEquals('memberpress', $result);
    }
}