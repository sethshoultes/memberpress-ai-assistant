<?php
/**
 * Base TestCase class for MemberPress AI Assistant tests
 *
 * @package MemberpressAiAssistant\Tests
 */

namespace MemberpressAiAssistant\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use MemberpressAiAssistant\Tests\Fixtures\MockFactory;

/**
 * Base TestCase class that all test cases should extend
 */
abstract class TestCase extends PHPUnitTestCase {
    /**
     * Set up the test case
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Common setup for all tests
    }

    /**
     * Tear down the test case
     */
    protected function tearDown(): void {
        // Common teardown for all tests
        
        parent::tearDown();
    }

    /**
     * Create a mock object with expectations
     *
     * @param string $className The class name to mock
     * @param array $methods The methods to mock
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockWithExpectations(string $className, array $methods = []): \PHPUnit\Framework\MockObject\MockObject {
        return MockFactory::createMock($className, $methods);
    }

    /**
     * Create a mock object with expectations and return values
     *
     * @param string $className The class name to mock
     * @param array $methodsWithReturnValues The methods to mock with their return values
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockWithReturnValues(string $className, array $methodsWithReturnValues = []): \PHPUnit\Framework\MockObject\MockObject {
        return MockFactory::createMockWithReturnValues($className, $methodsWithReturnValues);
    }

    /**
     * Create a partial mock object
     *
     * @param string $className The class name to mock
     * @param array $methods The methods to mock
     * @param array $constructorArgs The constructor arguments
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPartialMock(string $className, array $methods = [], array $constructorArgs = []): \PHPUnit\Framework\MockObject\MockObject {
        return MockFactory::createPartialMock($className, $methods, $constructorArgs);
    }

    /**
     * Create a mock for an abstract class
     *
     * @param string $className The class name to mock
     * @param array $methods The methods to mock
     * @param array $constructorArgs The constructor arguments
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getAbstractMock(string $className, array $methods = [], array $constructorArgs = []): \PHPUnit\Framework\MockObject\MockObject {
        return MockFactory::createAbstractMock($className, $methods, $constructorArgs);
    }
}