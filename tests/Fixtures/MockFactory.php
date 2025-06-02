<?php
/**
 * Mock Factory for creating test fixtures
 *
 * @package MemberpressAiAssistant\Tests\Fixtures
 */

namespace MemberpressAiAssistant\Tests\Fixtures;

use PHPUnit\Framework\TestCase;
use MemberpressAiAssistant\Interfaces\AgentInterface;

/**
 * Factory class for creating mock objects
 */
class MockFactory {
    /**
     * Create a mock object
     *
     * @param string $className The class name to mock
     * @param array $methods The methods to mock
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public static function createMock(string $className, array $methods = []): \PHPUnit\Framework\MockObject\MockObject {
        $mockBuilder = TestCase::getMockBuilder($className);
        
        if (!empty($methods)) {
            $mockBuilder->onlyMethods($methods);
        }
        
        return $mockBuilder->disableOriginalConstructor()->getMock();
    }

    /**
     * Create a mock object with return values
     *
     * @param string $className The class name to mock
     * @param array $methodsWithReturnValues The methods to mock with their return values
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public static function createMockWithReturnValues(string $className, array $methodsWithReturnValues = []): \PHPUnit\Framework\MockObject\MockObject {
        $methods = array_keys($methodsWithReturnValues);
        $mock = self::createMock($className, $methods);
        
        foreach ($methodsWithReturnValues as $method => $returnValue) {
            $mock->method($method)->willReturn($returnValue);
        }
        
        return $mock;
    }

    /**
     * Create a partial mock object
     *
     * @param string $className The class name to mock
     * @param array $methods The methods to mock
     * @param array $constructorArgs The constructor arguments
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public static function createPartialMock(string $className, array $methods = [], array $constructorArgs = []): \PHPUnit\Framework\MockObject\MockObject {
        $mockBuilder = TestCase::getMockBuilder($className);
        
        if (!empty($methods)) {
            $mockBuilder->onlyMethods($methods);
        }
        
        if (!empty($constructorArgs)) {
            $mockBuilder->setConstructorArgs($constructorArgs);
        } else {
            $mockBuilder->disableOriginalConstructor();
        }
        
        return $mockBuilder->getMock();
    }

    /**
     * Create a mock for an abstract class
     *
     * @param string $className The class name to mock
     * @param array $methods The methods to mock
     * @param array $constructorArgs The constructor arguments
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public static function createAbstractMock(string $className, array $methods = [], array $constructorArgs = []): \PHPUnit\Framework\MockObject\MockObject {
        $mockBuilder = TestCase::getMockBuilder($className);
        
        if (!empty($methods)) {
            $mockBuilder->onlyMethods($methods);
        }
        
        if (!empty($constructorArgs)) {
            $mockBuilder->setConstructorArgs($constructorArgs);
        } else {
            $mockBuilder->disableOriginalConstructor();
        }
        
        return $mockBuilder->getMockForAbstractClass();
    }

    /**
     * Create a mock for a trait
     *
     * @param string $traitName The trait name to mock
     * @param array $methods The methods to mock
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public static function createTraitMock(string $traitName, array $methods = []): \PHPUnit\Framework\MockObject\MockObject {
        $mockBuilder = TestCase::getMockBuilder($traitName);
        
        if (!empty($methods)) {
            $mockBuilder->onlyMethods($methods);
        }
        
        return $mockBuilder->getMockForTrait();
    }

    /**
     * Create a stub object
     *
     * @param string $className The class name to stub
     * @param array $methodsWithReturnValues The methods to stub with their return values
     * @return \PHPUnit\Framework\MockObject\Stub
     */
    public static function createStub(string $className, array $methodsWithReturnValues = []): \PHPUnit\Framework\MockObject\Stub {
        $stub = TestCase::createStub($className);
        
        foreach ($methodsWithReturnValues as $method => $returnValue) {
            $stub->method($method)->willReturn($returnValue);
        }
        
        return $stub;
    }

    /**
     * Create a mock agent
     *
     * @param string $agentName The agent name
     * @param float $specializationScore The specialization score
     * @return \PHPUnit\Framework\MockObject\MockObject|AgentInterface
     */
    public static function createMockAgent(string $agentName, float $specializationScore = 50.0): \PHPUnit\Framework\MockObject\MockObject {
        $agent = self::createMock(AgentInterface::class);
        
        // Set up common agent methods
        $agent->method('getAgentName')->willReturn($agentName);
        $agent->method('getSpecializationScore')->willReturn($specializationScore);
        $agent->method('getCapabilities')->willReturn([]);
        
        return $agent;
    }
}