<?php
namespace MemberpressAiAssistant\Interfaces;

interface SettingsRendererInterface {
    /**
     * Render the settings page
     *
     * @return void
     */
    public function render_settings_page(): void;
    
    /**
     * Render the settings tabs
     *
     * @param string $current_tab Current tab
     * @param array $tabs Available tabs
     * @return void
     */
    public function render_settings_tabs(string $current_tab, array $tabs): void;
    
    /**
     * Render the settings fields for the current tab
     *
     * @param string $current_tab Current tab
     * @return void
     */
    public function render_settings_fields(string $current_tab): void;
    
    /**
     * Render the form submit button
     *
     * @return void
     */
    public function render_submit_button(): void;
}