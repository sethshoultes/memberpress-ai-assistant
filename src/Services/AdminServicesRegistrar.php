<?php
/**
 * Admin Services Registrar
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\Admin\MPAIAdminMenu;

/**
 * Service for registering Admin services
 * 
 * This service registers all Admin services with the container
 * and ensures they are properly initialized.
 */
class AdminServicesRegistrar extends AbstractService {
    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'admin_services_registrar', $logger = null) {
        parent::__construct($name, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container
        $container->singleton('admin_services_registrar', function() {
            return $this;
        });

        // Register Admin services
        $this->registerAdminServices($container);
        
        // Log registration
        $this->log('Admin services registrar registered');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Log boot
        $this->log('Admin services registrar booted');
    }

    /**
     * Register Admin services with the container
     *
     * @param \MemberpressAiAssistant\DI\Container $container The DI container
     * @return void
     */
    protected function registerAdminServices($container): void {
        // Get the logger
        $logger = $container->bound('logger') ? $container->make('logger') : null;
        
        // Register admin menu
        $admin_menu = new MPAIAdminMenu('admin_menu', $logger);
        $admin_menu->register($container);
        
        // Boot services
        $admin_menu->boot();
        
        // Log registration
        $this->log('Admin services registered', [
            'services' => [
                'admin_menu'
            ]
        ]);
    }
}