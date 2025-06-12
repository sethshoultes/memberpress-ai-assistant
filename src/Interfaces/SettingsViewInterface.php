<?php
namespace MemberpressAiAssistant\Interfaces;

/**
 * Interface SettingsViewInterface
 * 
 * Defines the contract for settings UI rendering and presentation.
 * This interface handles all view-related operations for the settings page,
 * focusing on the remaining 3 sections and 5 core fields after API/consent removal.
 * 
 * @package MemberpressAiAssistant\Interfaces
 */
interface SettingsViewInterface {
    
    /**
     * Render the complete settings page
     * 
     * @param string $current_tab Currently active tab
     * @param array $tabs Available tabs configuration
     * @param string $page_slug The page slug for form actions
     * @param SettingsModelInterface $model Settings model for data access
     * @return void
     */
    public function render_page(string $current_tab, array $tabs, string $page_slug, SettingsModelInterface $model): void;
    
    /**
     * Render the tab navigation
     * 
     * @param string $current_tab Currently active tab
     * @param array $tabs Available tabs configuration
     * @return void
     */
    public function render_tabs(string $current_tab, array $tabs): void;
    
    /**
     * Render the settings form for the current tab
     * 
     * @param string $current_tab Currently active tab
     * @param string $page_slug The page slug for form actions
     * @param SettingsModelInterface $model Settings model for data access
     * @return void
     */
    public function render_form(string $current_tab, string $page_slug, SettingsModelInterface $model): void;
    
    /**
     * Render an error message
     * 
     * @param string $message Error message to display
     * @return void
     */
    public function render_error(string $message): void;
    
    /**
     * Render the general settings section
     * 
     * @return void
     */
    public function render_general_section(): void;
    
    /**
     * Render the chat settings section
     * 
     * @return void
     */
    public function render_chat_section(): void;
    
    /**
     * Render the access control settings section
     * 
     * @return void
     */
    public function render_access_section(): void;
    
    /**
     * Render the chat enabled field
     * 
     * @param bool $value Current value of the chat enabled setting
     * @return void
     */
    public function render_chat_enabled_field(bool $value): void;
    
    /**
     * Render the log level field
     * 
     * @param string $value Current log level value
     * @return void
     */
    public function render_log_level_field(string $value): void;
    
    /**
     * Render the chat location field
     * 
     * @param string $value Current chat location value
     * @return void
     */
    public function render_chat_location_field(string $value): void;
    
    /**
     * Render the chat position field
     * 
     * @param string $value Current chat position value
     * @return void
     */
    public function render_chat_position_field(string $value): void;
    
    /**
     * Render the user roles field
     * 
     * @param array $value Current user roles array
     * @return void
     */
    public function render_user_roles_field(array $value): void;
}