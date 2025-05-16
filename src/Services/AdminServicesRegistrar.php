<?php
/**
 * Admin Services Registrar
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Services;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\Admin\MPAIAdminMenu;
use MemberpressAiAssistant\Admin\MPAISettingsController;
use MemberpressAiAssistant\Admin\MPAISettingsRenderer;
use MemberpressAiAssistant\Admin\MPAISettingsStorage;
use MemberpressAiAssistant\Admin\MPAISettingsValidator;

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
        
        // Register settings storage
        $settings_storage = new MPAISettingsStorage('settings_storage', $logger);
        $settings_storage->register($container);
        
        // Register settings validator
        $settings_validator = new MPAISettingsValidator('settings_validator', $logger);
        $settings_validator->register($container);
        
        // Register admin menu
        $admin_menu = new MPAIAdminMenu('admin_menu', $logger);
        $admin_menu->register($container);
        
        // Register settings renderer
        $settings_renderer = new MPAISettingsRenderer('settings_renderer', $logger);
        $settings_renderer->register($container);
        
        // Register settings controller using the container to resolve constructor dependencies
        $settings_controller = $container->make(MPAISettingsController::class);
        // The register method in the controller should handle registering itself if needed
        // $settings_controller->register($container); // This might be redundant if make() handles registration or if register() is called elsewhere
        
        // Dependencies are now injected via constructor, so remove the explicit call to set_dependencies
        /*
        // Set dependencies for settings renderer
        $settings_renderer->set_dependencies(
            $container->make('settings_storage'),
            $container->make('settings_controller') // This might cause circular dependency if controller needs renderer and vice-versa
        );
        
        // Set dependencies for settings controller
        $settings_controller->set_dependencies(
            $container->make('settings_storage'),
            $container->make('settings_validator'),
            $container->make('admin_menu'),
            $container->make('settings_renderer')
        );
        */
        
        // Boot services
        $settings_storage->boot();
        $settings_validator->boot();
        $admin_menu->boot();
        $settings_renderer->boot();
        $settings_controller->boot();
        
        // Log registration
        $this->log('Admin services registered', [
            'services' => [
                'settings_storage',
                'settings_validator',
                'admin_menu',
                'settings_renderer',
                'settings_controller',
            ]
        ]);
    }
}