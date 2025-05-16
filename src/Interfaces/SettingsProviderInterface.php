<?php
/**
 * Settings Provider Interface
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Interfaces;

/**
 * Interface for classes that provide settings
 */
interface SettingsProviderInterface {
    /**
     * Get the settings tabs
     *
     * @return array
     */
    public function get_tabs(): array;

    /**
     * Get the settings page slug
     *
     * @return string
     */
    public function get_page_slug(): string;
    
    /**
     * Render the settings page
     *
     * @return void
     */
    public function render_settings_page(): void;
}