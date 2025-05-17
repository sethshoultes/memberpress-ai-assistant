<?php
/**
 * Admin Service Provider
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\DI\Providers;

use MemberpressAiAssistant\DI\ServiceProvider;
use MemberpressAiAssistant\DI\ServiceLocator;
use MemberpressAiAssistant\Admin\MPAIAdminMenu;
use MemberpressAiAssistant\Admin\MPAIConsentManager;
use MemberpressAiAssistant\Admin\MPAIAjaxHandler;
use MemberpressAiAssistant\Admin\MPAIKeyManager;
use MemberpressAiAssistant\Admin\Settings\MPAISettingsController;
use MemberpressAiAssistant\Admin\Settings\MPAISettingsModel;
use MemberpressAiAssistant\Admin\Settings\MPAISettingsView;

/**
 * Service provider for admin-related services
 */
class AdminServiceProvider extends ServiceProvider {
    /**
     * Register services with the service locator
     *
     * @param ServiceLocator $locator The service locator
     * @return void
     */
    public function register(ServiceLocator $locator): void {
        // Register settings components
        $this->registerSingleton($locator, 'settings_model', function() use ($locator) {
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            return new MPAISettingsModel($logger);
        });

        $this->registerSingleton($locator, 'settings_view', function() {
            return new MPAISettingsView();
        });

        $this->registerSingleton($locator, 'settings_controller', function() use ($locator) {
            $model = $locator->get('settings_model');
            $view = $locator->get('settings_view');
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            
            $controller = new MPAISettingsController($model, $view, $logger);
            $controller->init();
            
            return $controller;
        });

        // Register admin menu
        $this->registerSingleton($locator, 'admin_menu', function() use ($locator) {
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            $adminMenu = new MPAIAdminMenu('admin_menu', $logger);
            
            // Set the settings controller in the admin menu
            if ($locator->has('settings_controller')) {
                $adminMenu->set_settings_controller($locator->get('settings_controller'));
            }
            
            $adminMenu->boot();
            
            return $adminMenu;
        });

        // Register consent manager
        $this->registerSingleton($locator, 'consent_manager', function() use ($locator) {
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            return new MPAIConsentManager('consent_manager', $logger);
        });

        // Register AJAX handler
        $this->registerSingleton($locator, 'ajax_handler', function() use ($locator) {
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            return new MPAIAjaxHandler('ajax_handler', $logger);
        });

        // Register key manager
        $this->registerSingleton($locator, 'key_manager', function() use ($locator) {
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            return new MPAIKeyManager('key_manager', $logger);
        });
    }

    /**
     * Get the services provided by the provider
     *
     * @return array Array of service names
     */
    public function provides(): array {
        return [
            'settings_model',
            'settings_view',
            'settings_controller',
            'admin_menu',
            'consent_manager',
            'ajax_handler',
            'key_manager'
        ];
    }
}