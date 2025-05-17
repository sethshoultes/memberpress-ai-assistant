<?php
/**
 * Admin Services Registrar
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\Admin\MPAIAdminMenu;
use MemberpressAiAssistant\Admin\Settings\MPAISettingsController;
use MemberpressAiAssistant\Admin\Settings\MPAISettingsModel;
use MemberpressAiAssistant\Admin\Settings\MPAISettingsView;

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
    public function register($serviceLocator): void {
        // Store reference to this for use in closures
        $self = $this;
        
        // Register this service with the service locator
        $serviceLocator->register('admin_services_registrar', function() use ($self) {
            return $self;
        });

        // Register Admin services
        $this->registerAdminServices($serviceLocator);
        
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
     * @param \MemberpressAiAssistant\DI\ServiceLocator $serviceLocator The service locator
     * @return void
     */
    protected function registerAdminServices($serviceLocator): void {
        // Get the logger
        $logger = $serviceLocator->has('logger') ? $serviceLocator->get('logger') : null;
        
        // Register settings components
        $settings_model = new MPAISettingsModel($logger);
        $settings_view = new MPAISettingsView();
        $settings_controller = new MPAISettingsController($settings_model, $settings_view, $logger);
        
        // Register settings controller with the service locator
        $serviceLocator->register('settings_controller', function() use ($settings_controller) {
            return $settings_controller;
        });
        
        // Initialize settings controller
        $settings_controller->init();
        
        // Register admin menu
        $admin_menu = new MPAIAdminMenu('admin_menu', $logger);
        $admin_menu->register($serviceLocator);
        
        // Set the settings controller in the admin menu
        $admin_menu->set_settings_controller($settings_controller);
        
        // Boot services
        $admin_menu->boot();
        
        // Log registration
        $this->log('Admin services registered', [
            'services' => [
                'admin_menu',
                'settings_controller'
            ]
        ]);
    }
}