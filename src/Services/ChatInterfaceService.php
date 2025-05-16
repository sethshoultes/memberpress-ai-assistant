<?php
/**
 * Chat Interface Service
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services;

use MemberpressAiAssistant\ChatInterface;
use MemberpressAiAssistant\DI\Container;
use MemberpressAiAssistant\Interfaces\ServiceInterface;

/**
 * Class ChatInterfaceService
 *
 * Service for initializing and managing the chat interface.
 */
class ChatInterfaceService implements ServiceInterface {
    /**
     * Service name
     *
     * @var string
     */
    protected $name;

    /**
     * Logger instance
     *
     * @var mixed
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name, $logger = null) {
        $this->name = $name;
        $this->logger = $logger;
    }

    /**
     * Register the service with the container
     *
     * @param mixed $container The DI container
     * @return void
     */
    public function register($container): void {
        // Register the chat interface as a singleton
        $container->singleton('chat_interface', function() {
            return ChatInterface::getInstance();
        });

        // Log registration
        if ($this->logger) {
            $this->logger->info('ChatInterfaceService registered');
        }
    }

    /**
     * Boot the service
     *
     * @return void
     */
    public function boot(): void {
        // Initialize the chat interface
        ChatInterface::getInstance();

        // Log boot
        if ($this->logger) {
            $this->logger->info('ChatInterfaceService booted');
        }
    }

    /**
     * Get the service name
     *
     * @return string
     */
    public function getServiceName(): string {
        return $this->name;
    }

    /**
     * Get the service dependencies
     *
     * @return array
     */
    public function getDependencies(): array {
        return [];
    }
}