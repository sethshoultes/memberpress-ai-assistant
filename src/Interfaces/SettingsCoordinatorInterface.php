<?php
/**
 * Settings Coordinator Interface
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Interfaces;

use MemberpressAiAssistant\Admin\MPAISettingsStorage;

/**
 * Interface for classes that coordinate settings components
 */
interface SettingsCoordinatorInterface {
    /**
     * Initialize the coordinator and its components
     */
    public function initialize(): void;
    
    /**
     * Get the settings controller
     */
    public function getController(): SettingsProviderInterface;
    
    /**
     * Get the settings renderer
     */
    public function getRenderer(): SettingsRendererInterface;
    
    /**
     * Get the settings storage
     */
    public function getStorage(): MPAISettingsStorage;
}