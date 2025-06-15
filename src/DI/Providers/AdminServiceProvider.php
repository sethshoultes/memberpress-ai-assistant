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
use MemberpressAiAssistant\Admin\MPAIAjaxHandler;
use MemberpressAiAssistant\Admin\MPAIKeyManager;

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
        // Register admin menu
        $this->registerSingleton($locator, 'admin_menu', function() use ($locator) {
            $logger = $locator->has('logger') ? $locator->get('logger') : null;
            $adminMenu = new MPAIAdminMenu('admin_menu', $logger);
            
            $adminMenu->boot();
            
            return $adminMenu;
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
            'admin_menu',
            'ajax_handler',
            'key_manager'
        ];
    }
}