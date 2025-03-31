/**
 * MemberPress AI Assistant - Logger Module
 * 
 * A configurable console logging system for debugging and monitoring.
 */

(function($) {
    'use strict';

    // Define the Logger class
    var MpaiLogger = function() {
        // Initialize with default settings
        this.enabled = false;
        this.logLevel = 'info'; // error, warning, info, debug
        this.categories = {
            api_calls: true,
            tool_usage: true,
            agent_activity: true,
            timing: true
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

        // Initialize when document is ready
        $(document).ready(function() {
            self.initialize();
        });
    };

    // Initialize the logger with settings from WordPress options
    MpaiLogger.prototype.initialize = function() {
        // First try to get settings from localStorage
        var storedSettings = localStorage.getItem('mpai_logger_settings');
        if (storedSettings) {
            try {
                var settings = JSON.parse(storedSettings);
                this.enabled = settings.enabled;
                this.logLevel = settings.logLevel;
                this.categories = settings.categories;
                console.log('MPAI: Logger initialized from localStorage');
            } catch (e) {
                console.error('MPAI: Error parsing logger settings from localStorage:', e);
            }
        }
        
        // Check if mpai_data exists and has logger settings (override localStorage)
        if (typeof mpai_data === 'undefined') {
            console.log('MPAI: mpai_data not available, using default/localStorage settings for logger');
            // Enable the logger by default if no settings are available
            if (!storedSettings) {
                this.enabled = true;
                console.log('MPAI: Enabling logger with default settings');
            }
        } else if (mpai_data.logger) {
            // Load settings from mpai_data
            this.enabled = mpai_data.logger.enabled === '1';
            this.logLevel = mpai_data.logger.log_level || 'info';
            
            if (mpai_data.logger.categories) {
                this.categories = {
                    api_calls: mpai_data.logger.categories.api_calls === '1',
                    tool_usage: mpai_data.logger.categories.tool_usage === '1',
                    agent_activity: mpai_data.logger.categories.agent_activity === '1',
                    timing: mpai_data.logger.categories.timing === '1'
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
                console.error('MPAI: Error saving logger settings to localStorage:', e);
            }
        }

        // Log initialization status if enabled
        if (this.enabled) {
            console.log('MPAI Logger: Initialized with level [' + this.logLevel + ']');
            
            // Log enabled categories
            var enabledCategories = [];
            for (var cat in this.categories) {
                if (this.categories[cat]) {
                    enabledCategories.push(cat);
                }
            }
            console.log('MPAI Logger: Enabled categories: ' + enabledCategories.join(', '));
        }
    };

    // Check if logging is enabled for a specific level and category
    MpaiLogger.prototype.shouldLog = function(level, category) {
        // First check if logging is enabled at all
        if (!this.enabled) {
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