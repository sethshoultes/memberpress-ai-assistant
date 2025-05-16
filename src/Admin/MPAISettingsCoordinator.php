<?php
/**
 * MemberPress AI Assistant Settings Coordinator
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Admin;

use MemberpressAiAssistant\Abstracts\AbstractService;
use MemberpressAiAssistant\Interfaces\SettingsCoordinatorInterface;
use MemberpressAiAssistant\Interfaces\SettingsProviderInterface;
use MemberpressAiAssistant\Interfaces\SettingsRendererInterface;

/**
 * Class for coordinating settings components and breaking circular dependencies
 * 
 * This class acts as an intermediary between the Settings Controller and Renderer
 * to ensure proper initialization and connection between components.
 */
class MPAISettingsCoordinator extends AbstractService implements SettingsCoordinatorInterface {
    /**
     * Settings storage instance
     *
     * @var MPAISettingsStorage
     */
    protected $settings_storage;
    
    /**
     * Settings controller instance
     *
     * @var MPAISettingsController
     */
    protected $settings_controller;
    
    /**
     * Settings renderer instance
     *
     * @var MPAISettingsRenderer
     */
    protected $settings_renderer;
    
    /**
     * Constructor
     *
     * @param string $name Service name
     * @param mixed $logger Logger instance
     */
    public function __construct(string $name = 'settings_coordinator', $logger = null) {
        parent::__construct($name, $logger);
    }
    
    /**
     * {@inheritdoc}
     */
    public function register($container): void {
        // Register this service with the container
        $container->singleton('settings_coordinator', function() {
            return $this;
        });
        
        // Add dependencies to the dependencies array
        $this->dependencies = [
            'settings_storage',
            'settings_controller',
            'settings_renderer',
        ];
        
        // Log registration
        $this->log('Settings coordinator service registered');
    }
    
    /**
     * Set dependencies after they've been registered
     *
     * @param MPAISettingsStorage $settings_storage The settings storage service
     * @param MPAISettingsController $settings_controller The settings controller service
     * @param MPAISettingsRenderer $settings_renderer The settings renderer service
     * @return void
     */
    public function set_dependencies(
        MPAISettingsStorage $settings_storage,
        MPAISettingsController $settings_controller,
        MPAISettingsRenderer $settings_renderer
    ): void {
        $this->settings_storage = $settings_storage;
        $this->settings_controller = $settings_controller;
        $this->settings_renderer = $settings_renderer;
        
        // Log that dependencies were set
        $this->log('Dependencies set for settings coordinator');
    }
    
    /**
     * {@inheritdoc}
     */
    public function boot(): void {
        parent::boot();
        
        // Add hooks
        $this->addHooks();
        
        // Log boot
        $this->log('Settings coordinator service booted');
    }
    
    /**
     * Add hooks and filters for this service
     *
     * @return void
     */
    protected function addHooks(): void {
        // Initialize the coordinator
        add_action('admin_init', [$this, 'initialize'], 5);
        
        // Add debug info to help diagnose issues
        if (apply_filters('mpai_debug_mode', false)) {
            add_action('admin_notices', [$this, 'display_debug_info']);
        }
    }
    
    /**
     * Initialize the coordinator and its components
     */
    public function initialize(): void {
        // Ensure all components are available
        if (!$this->settings_storage) {
            $this->logError('Error: Cannot initialize settings components - storage missing');
            return;
        }
        
        if (!$this->settings_controller) {
            $this->logError('Error: Cannot initialize settings components - controller missing');
            return;
        }
        
        if (!$this->settings_renderer) {
            $this->logError('Error: Cannot initialize settings components - renderer missing');
            return;
        }
        
        // Connect components
        $this->connect_settings_components();
        
        // Initialize components
        if ($this->settings_controller) {
            // Register settings with WordPress
            add_action('admin_init', [$this->settings_controller, 'register_settings']);
        }
        
        $this->log('Settings coordinator initialized successfully');
    }
    
    /**
     * Get the settings controller
     */
    public function getController(): SettingsProviderInterface {
        if (!$this->settings_controller) {
            $this->logWarning('Warning: Attempted to access null controller');
            throw new \RuntimeException('Controller is not initialized');
        }
        return $this->settings_controller;
    }
    
    /**
     * Get the settings renderer
     */
    public function getRenderer(): SettingsRendererInterface {
        if (!$this->settings_renderer) {
            $this->logWarning('Warning: Attempted to access null renderer');
            throw new \RuntimeException('Renderer is not initialized');
        }
        return $this->settings_renderer;
    }
    
    /**
     * Get the settings storage
     */
    public function getStorage(): MPAISettingsStorage {
        if (!$this->settings_storage) {
            $this->logWarning('Warning: Attempted to access null storage');
            throw new \RuntimeException('Storage is not initialized');
        }
        return $this->settings_storage;
    }
    
    /**
     * Connect the settings controller and renderer
     *
     * @return void
     */
    public function connect_settings_components(): void {
        $this->log('Connecting settings components');
        
        // Check if components are available
        if (!$this->settings_controller || !$this->settings_renderer || !$this->settings_storage) {
            $this->logError('Error: Cannot connect settings components - one or more components missing', [
                'controller' => isset($this->settings_controller) ? 'yes' : 'no',
                'renderer' => isset($this->settings_renderer) ? 'yes' : 'no',
                'storage' => isset($this->settings_storage) ? 'yes' : 'no'
            ]);
            return;
        }
        
        // No need to set cross-dependencies anymore
        // The coordinator will mediate all interactions
        
        $this->log('Settings components connected successfully');
    }
    
    /**
     * Display debug info
     *
     * @return void
     */
    public function display_debug_info(): void {
        // Only show on plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'mpai') === false) {
            return;
        }
        
        echo '<div class="notice notice-info">';
        echo '<h3>' . esc_html__('MemberPress AI Assistant Debug Info', 'memberpress-ai-assistant') . '</h3>';
        
        // Check for controller and renderer
        echo '<p><strong>Settings Components:</strong> ';
        echo 'Controller: ' . (isset($this->settings_controller) ? '✅' : '❌') . ', ';
        echo 'Renderer: ' . (isset($this->settings_renderer) ? '✅' : '❌') . ', ';
        echo 'Storage: ' . (isset($this->settings_storage) ? '✅' : '❌');
        echo '</p>';
        
        // Show current tab and available tabs
        if (isset($this->settings_controller)) {
            $tabs = $this->settings_controller->get_tabs();
            $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
            
            echo '<p><strong>Current tab:</strong> ' . esc_html($current_tab) . '</p>';
            echo '<p><strong>Available tabs:</strong> ' . implode(', ', array_keys($tabs)) . '</p>';
        }
        
        // Show debug info for WordPress settings API
        global $wp_settings_sections, $wp_settings_fields;
        
        if ($this->settings_controller) {
            $page = $this->settings_controller->get_page_slug();
            
            echo '<p><strong>Settings sections registered:</strong> ';
            if (isset($wp_settings_sections[$page])) {
                echo implode(', ', array_keys($wp_settings_sections[$page]));
            } else {
                echo 'None';
            }
            echo '</p>';
            
            echo '<details>';
            echo '<summary><strong>Settings fields (click to expand)</strong></summary>';
            echo '<ul>';
            
            if (isset($wp_settings_sections[$page])) {
                foreach ($wp_settings_sections[$page] as $section_id => $section) {
                    echo '<li>';
                    echo '<strong>Section:</strong> ' . esc_html($section_id);
                    
                    if (isset($wp_settings_fields[$page][$section_id])) {
                        echo '<ul>';
                        foreach ($wp_settings_fields[$page][$section_id] as $field_id => $field) {
                            echo '<li>' . esc_html($field_id) . '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo ' (no fields)';
                    }
                    
                    echo '</li>';
                }
            }
            
            echo '</ul>';
            echo '</details>';
        }
        
        echo '</div>';
    }
    
    /**
     * Log an error message
     *
     * @param string $message The error message
     * @param array $context Additional context data
     * @return void
     */
    protected function logError(string $message, array $context = []): void {
        if ($this->logger) {
            $this->logger->error($message, array_merge(['service' => $this->getServiceName()], $context));
        }
    }
    
    /**
     * Log a warning message
     *
     * @param string $message The warning message
     * @param array $context Additional context data
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void {
        if ($this->logger) {
            $this->logger->warning($message, array_merge(['service' => $this->getServiceName()], $context));
        }
    }
}