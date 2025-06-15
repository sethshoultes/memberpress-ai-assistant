<?php
namespace MemberpressAiAssistant\Interfaces;

/**
 * Interface SettingsModelInterface
 * 
 * Defines the contract for settings data operations, validation, and getters.
 * This interface represents the final state after API and consent settings removal,
 * focusing on the 5 core settings: chat_enabled, log_level, chat_location, chat_position, user_roles.
 * 
 * @package MemberpressAiAssistant\Interfaces
 */
interface SettingsModelInterface {
    
    /**
     * Get a specific setting value
     * 
     * @param string $key The setting key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The setting value or default
     */
    public function get(string $key, $default = null);
    
    /**
     * Set a specific setting value
     * 
     * @param string $key The setting key
     * @param mixed $value The value to set
     * @param bool $save Whether to save immediately
     * @return bool True on success, false on failure
     */
    public function set(string $key, $value, bool $save = true): bool;
    
    /**
     * Get all settings as an array
     * 
     * @return array All settings
     */
    public function get_all(): array;
    
    /**
     * Update multiple settings at once
     * 
     * @param array $settings Array of key-value pairs to update
     * @param bool $save Whether to save immediately
     * @return bool True on success, false on failure
     */
    public function update(array $settings, bool $save = true): bool;
    
    /**
     * Save current settings to persistent storage
     * 
     * @return bool True on success, false on failure
     */
    public function save(): bool;
    
    /**
     * Reset all settings to default values
     * 
     * @param bool $save Whether to save immediately
     * @return bool True on success, false on failure
     */
    public function reset(bool $save = true): bool;
    
    /**
     * Validate settings array
     * 
     * @param array $settings Settings to validate
     * @return array Validation results with errors if any
     */
    public function validate(array $settings): array;
    
    /**
     * Check if chat is enabled
     * 
     * @return bool True if chat is enabled
     */
    public function is_chat_enabled(): bool;
    
    /**
     * Get chat location setting
     * 
     * @return string Chat location (e.g., 'frontend', 'admin', 'both')
     */
    public function get_chat_location(): string;
    
    /**
     * Get chat position setting
     * 
     * @return string Chat position (e.g., 'bottom-right', 'bottom-left')
     */
    public function get_chat_position(): string;
    
    /**
     * Get user roles that have access to chat
     * 
     * @return array Array of user role names
     */
    public function get_user_roles(): array;
    
    /**
     * Get current log level setting
     * 
     * @return string Log level (e.g., 'error', 'warning', 'info', 'debug')
     */
    public function get_log_level(): string;
    
    /**
     * Check if a specific role can access chat
     * 
     * @param string $role The role to check
     * @return bool True if role has chat access
     */
    public function can_role_access_chat(string $role): bool;
}