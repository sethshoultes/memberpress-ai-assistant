/**
 * MemberPress AI Assistant - Logger Module
 * 
 * A configurable console logging system for debugging and monitoring.
 */

(function($) {
    'use strict';

    // Console log immediately to verify script is loaded
    console.log('MPAI: Logger script loading...');

    // Define the Logger class
    var MpaiLogger = function() {
        console.log('MPAI: Logger constructor called');
        
        // Initialize with default settings
        this.enabled = true; // Enable by default for debugging
        this.logLevel = 'debug'; // Set to most verbose for debugging: error, warning, info, debug
        this.categories = {
            api_calls: true,
            tool_usage: true,
            agent_activity: true,
            timing: true,
            ui: true // Add UI category and enable by default
        };

        // Log level weights for comparison
        this.logLevelWeights = {
            error: 1,
            warning: 2,
            info: 3,
            debug: 4
        };

        // Timer storage for performance logging
        this.timers = {};

        // Self reference for use in callbacks
        var self = this;

        // Log that we're created
        console.log('MPAI: Logger instance created with default settings');
        console.log('MPAI: Default logLevel:', this.logLevel);
        console.log('MPAI: Default categories:', this.categories);
        
        // Initialize immediately and also when document is ready
        this.initialize();
        
        $(document).ready(function() {
            console.log('MPAI: Document ready, initializing logger again');
            self.initialize();
        });
    };

    // Initialize the logger with settings from WordPress options
    MpaiLogger.prototype.initialize = function() {
        // Less verbose logging - only log critical operations
        
        // Record initialization time for caching purposes
        this._lastInitTime = Date.now();
        
        // First try to get settings from localStorage
        var storedSettings = localStorage.getItem('mpai_logger_settings');
        
        // Adding timestamp check to avoid re-initializing too frequently
        var lastInitCheck = localStorage.getItem('mpai_logger_last_init');
        var currentTime = Date.now();
        var initNeeded = true;
        
        // If we initialized less than 5 seconds ago, don't do a full re-init
        if (lastInitCheck && (currentTime - parseInt(lastInitCheck)) < 5000) {
            initNeeded = false;
        }
        
        // Save last init time regardless
        try {
            localStorage.setItem('mpai_logger_last_init', currentTime.toString());
        } catch (e) {
            // Silent fail
        }
        
        if (storedSettings) {
            try {
                var settings = JSON.parse(storedSettings);
                this.enabled = settings.enabled;
                this.logLevel = settings.logLevel;
                this.categories = settings.categories;
                
                // Only log if we're explicitly enabled - reduces console clutter
                if (this.enabled === true) {
                    console.log('MPAI: Logger initialized from localStorage - enabled: ' + this.enabled);
                }
                
                // Update UI to match stored settings
                this.updateUIFromSettings();
                
                // If we have cached settings and don't need to re-init from server, return early
                if (!initNeeded) {
                    return;
                }
            } catch (e) {
                // Silent error - just continue with defaults
            }
        }
        
        // Helper method to update UI elements
        MpaiLogger.prototype.updateUIFromSettings = function() {
            if (typeof jQuery !== 'undefined') {
                var $ = jQuery;
                // Update the checkbox to match the enabled state
                $('#mpai_enable_console_logging').prop('checked', this.enabled === true);
                
                // Update the status indicator
                var $statusIndicator = $('#mpai-console-logging-status');
                if ($statusIndicator.length) {
                    if (this.enabled === true) {
                        $statusIndicator.removeClass('inactive').addClass('active').text('ENABLED');
                    } else {
                        $statusIndicator.removeClass('active').addClass('inactive').text('DISABLED');
                    }
                }
            }
        };
        
        // Check for logger settings in either mpai_data or mpai_chat_data
        var loggerSettings = null;
        
        // First check mpai_data - minimal logging
        if (typeof mpai_data !== 'undefined' && mpai_data.logger) {
            loggerSettings = mpai_data.logger;
        }
        // Then try mpai_chat_data if mpai_data wasn't available
        else if (typeof mpai_chat_data !== 'undefined' && mpai_chat_data.logger) {
            loggerSettings = mpai_chat_data.logger;
        }
        
        if (!loggerSettings) {
            // Enable the logger by default if no settings are available
            if (!storedSettings) {
                this.enabled = true;
                this.logLevel = 'debug'; // Force debug level
            }
        } else {
            // Load settings from found config with minimal logging
            
            // Use the settings from the config - simple check: if it's '1' or true, enable logging
            var enabledSetting = loggerSettings.enabled;
            
            // Very explicit type checking to ensure any falsey value disables logging
            if (enabledSetting === '1' || enabledSetting === true || enabledSetting === 1) {
                this.enabled = true;
            } else {
                this.enabled = false;
            }
            
            // Get log level
            this.logLevel = loggerSettings.log_level || loggerSettings.logLevel || 'debug';
            
            if (loggerSettings.categories) {
                // Convert string values ('1'/'0') to boolean if needed
                this.categories = {
                    api_calls: loggerSettings.categories.api_calls === '1' || loggerSettings.categories.api_calls === true,
                    tool_usage: loggerSettings.categories.tool_usage === '1' || loggerSettings.categories.tool_usage === true,
                    agent_activity: loggerSettings.categories.agent_activity === '1' || loggerSettings.categories.agent_activity === true,
                    timing: loggerSettings.categories.timing === '1' || loggerSettings.categories.timing === true,
                    ui: true // Always enable UI logging
                };
            }
            
            // Save settings to localStorage for next time
            try {
                localStorage.setItem('mpai_logger_settings', JSON.stringify({
                    enabled: this.enabled,
                    logLevel: this.logLevel,
                    categories: this.categories
                }));
            } catch (e) {
                // Silent fail to avoid error logs when localStorage is not available
            }
        }

        // Minimal log initialization status only if enabled
        if (this.enabled === true) {
            // Reset the disabled message flag when enabled
            this._hasShownDisabledMessage = false;
            
            // Only show one concise log message - no need for category details
            console.log('MPAI Logger: Initialized and enabled');
        }
    };

    // Check if logging is enabled for a specific level and category
    MpaiLogger.prototype.shouldLog = function(level, category) {
        // First check if logging is enabled at all - VERY STRICT enforcement
        // This is the most critical function that controls all logging
        // We use strict equality (!==) to ensure only true enables logging, nothing else
        // Note that 1, "1", "true", etc. are all NOT equal to true with !==
        
        if (this.enabled !== true) {
            // A clear comment in console to show the logger is stopping logs
            if (!this._hasShownDisabledMessage) {
                console.log('%cMPAI Logger: DISABLED - no logs will be shown', 'background: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 3px; font-weight: bold;');
                this._hasShownDisabledMessage = true;
                
                // Also update any status indicators in the UI if they exist
                if (typeof jQuery !== 'undefined') {
                    jQuery('#mpai-console-logging-status').removeClass('active').addClass('inactive').text('DISABLED');
                    jQuery('#mpai_enable_console_logging').prop('checked', false);
                }
            }
            return false;
        }

        // Check if the log level is sufficient
        if (this.logLevelWeights[level] > this.logLevelWeights[this.logLevel]) {
            return false;
        }

        // If no category specified, allow logging
        if (!category) {
            return true;
        }

        // Check if the category is enabled
        return this.categories[category] === true;
    };

    // Format a log message with timestamp and category label
    MpaiLogger.prototype.formatMessage = function(message, category) {
        var timestamp = new Date().toISOString().slice(11, 23); // HH:MM:SS.sss
        var prefix = 'MPAI';
        
        if (category) {
            prefix += ' [' + category + ']';
        }
        
        return timestamp + ' ' + prefix + ': ' + message;
    };

    // Log methods for different levels
    MpaiLogger.prototype.error = function(message, category, data) {
        if (!this.shouldLog('error', category)) {
            return;
        }

        var formattedMessage = this.formatMessage(message, category);
        if (data) {
            console.error(formattedMessage, data);
        } else {
            console.error(formattedMessage);
        }
    };

    MpaiLogger.prototype.warn = function(message, category, data) {
        if (!this.shouldLog('warning', category)) {
            return;
        }

        var formattedMessage = this.formatMessage(message, category);
        if (data) {
            console.warn(formattedMessage, data);
        } else {
            console.warn(formattedMessage);
        }
    };

    MpaiLogger.prototype.info = function(message, category, data) {
        if (!this.shouldLog('info', category)) {
            return;
        }

        var formattedMessage = this.formatMessage(message, category);
        if (data) {
            console.info(formattedMessage, data);
        } else {
            console.info(formattedMessage);
        }
    };

    MpaiLogger.prototype.debug = function(message, category, data) {
        if (!this.shouldLog('debug', category)) {
            return;
        }

        var formattedMessage = this.formatMessage(message, category);
        if (data) {
            console.log(formattedMessage, data);
        } else {
            console.log(formattedMessage);
        }
    };

    // Category-specific logging methods
    MpaiLogger.prototype.logApiCall = function(service, endpoint, params, level) {
        level = level || 'info';
        
        if (!this.shouldLog(level, 'api_calls')) {
            return;
        }

        var message = service + ' API call to ' + endpoint;
        this[level](message, 'api_calls', params);
    };

    MpaiLogger.prototype.logToolUsage = function(toolName, params, level) {
        level = level || 'info';
        
        if (!this.shouldLog(level, 'tool_usage')) {
            return;
        }

        var message = 'Tool used: ' + toolName;
        this[level](message, 'tool_usage', params);
    };

    MpaiLogger.prototype.logAgentActivity = function(agentName, action, data, level) {
        level = level || 'info';
        
        if (!this.shouldLog(level, 'agent_activity')) {
            return;
        }

        var message = 'Agent [' + agentName + ']: ' + action;
        this[level](message, 'agent_activity', data);
    };

    // Timing methods for performance logging
    MpaiLogger.prototype.startTimer = function(label) {
        if (!this.shouldLog('debug', 'timing')) {
            return;
        }

        this.timers[label] = performance.now();
        this.debug('Timer started', 'timing', { label: label });
    };

    MpaiLogger.prototype.endTimer = function(label) {
        if (!this.shouldLog('debug', 'timing') || !this.timers[label]) {
            return;
        }

        var elapsed = performance.now() - this.timers[label];
        delete this.timers[label];

        this.debug('Timer ended: ' + elapsed.toFixed(2) + 'ms', 'timing', { 
            label: label,
            duration: elapsed
        });

        return elapsed;
    };

    // Console log test method for the settings page
    MpaiLogger.prototype.testLog = function() {
        console.group('MPAI Logger Test');
        
        console.log('Logger Status: ' + (this.enabled ? 'Enabled' : 'Disabled'));
        console.log('Log Level: ' + this.logLevel);
        console.log('Categories:', this.categories);
        
        if (this.enabled) {
            this.error('This is an ERROR test message');
            this.warn('This is a WARNING test message');
            this.info('This is an INFO test message');
            this.debug('This is a DEBUG test message');
            
            if (this.categories.api_calls) {
                this.logApiCall('OpenAI', '/v1/chat/completions', { model: 'gpt-4' });
            }
            
            if (this.categories.tool_usage) {
                this.logToolUsage('wp_cli', { command: 'wp user list' });
            }
            
            if (this.categories.agent_activity) {
                this.logAgentActivity('MemberPressAgent', 'Process request', { query: 'Get user memberships' });
            }
            
            if (this.categories.timing) {
                this.startTimer('test_operation');
                setTimeout(() => {
                    this.endTimer('test_operation');
                }, 500);
            }
        }
        
        console.groupEnd();
        
        return {
            enabled: this.enabled,
            logLevel: this.logLevel,
            categories: this.categories
        };
    };

    // Create a global instance
    window.mpaiLogger = new MpaiLogger();

})(jQuery);