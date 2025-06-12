<?php
namespace MemberpressAiAssistant\Interfaces;

/**
 * Interface SettingsControllerInterface
 * 
 * Defines the contract for settings coordination and form handling.
 * This interface manages the coordination between model and view components,
 * handles form submissions, and manages the settings page workflow.
 * 
 * @package MemberpressAiAssistant\Interfaces
 */
interface SettingsControllerInterface {
    
    /**
     * Render the complete settings page
     * 
     * @return void
     */
    public function render_page(): void;
    
    /**
     * Register settings with WordPress
     * 
     * @return void
     */
    public function register_settings(): void;
    
    /**
     * Handle form submission and process settings updates
     * 
     * @return void
     */
    public function handle_form_submission(): void;
    
    /**
     * Sanitize settings input from form submission
     * 
     * @param array $input Raw input from form submission
     * @return array Sanitized settings array
     */
    public function sanitize_settings(array $input): array;
    
    /**
     * Get available tabs configuration
     * 
     * @return array Array of tab configurations
     */
    public function get_tabs(): array;
    
    /**
     * Get the page slug for this settings page
     * 
     * @return string Page slug
     */
    public function get_page_slug(): string;
    
    /**
     * Render the chat enabled field
     * 
     * @return void
     */
    public function render_chat_enabled_field(): void;
    
    /**
     * Render the log level field
     * 
     * @return void
     */
    public function render_log_level_field(): void;
    
    /**
     * Render the chat location field
     * 
     * @return void
     */
    public function render_chat_location_field(): void;
    
    /**
     * Render the chat position field
     * 
     * @return void
     */
    public function render_chat_position_field(): void;
    
    /**
     * Render the user roles field
     * 
     * @return void
     */
    public function render_user_roles_field(): void;
}