<?php
/**
 * MemberPress AI Assistant AJAX Handler
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin;

use MemberpressAiAssistant\Abstracts\AbstractService;

/**
 * Class for handling AJAX requests for the MemberPress AI Assistant
 */
class MPAIAjaxHandler extends AbstractService {
    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'ajax_handler', $logger = null) {
        parent::__construct($name, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function register($serviceLocator): void {
        // Register this service with the service locator
        $serviceLocator->register('ajax_handler', function() {
            return $this;
        });
        
        // Log registration
        $this->log('AJAX handler service registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks
        $this->addHooks();
        
        // Log boot
        $this->log('AJAX handler service booted');
    }

    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Add AJAX handlers
        // No AJAX handlers currently
    }
}