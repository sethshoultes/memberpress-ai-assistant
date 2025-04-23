/**
 * MemberPress AI Assistant - Module Loader
 * 
 * This script ensures all required modules are loaded before initializing the chat interface.
 * It addresses the issue where tool calls are shown as raw JSON instead of being executed.
 */

(function($) {
    'use strict';
    
    // List of required modules
    const requiredModules = [
        'MPAI_Messages',
        'MPAI_UIUtils',
        'MPAI_Tools',
        'MPAI_Formatters',
        'MPAI_History',
        'MPAI_BlogFormatter',
        'MPAI_MessageProcessor',
        'MPAI_ToolCallDetector',
        'MPAI_ParameterValidator'
    ];
    
    // Check if all modules are loaded
    function areAllModulesLoaded() {
        for (const moduleName of requiredModules) {
            if (!window[moduleName]) {
                console.log('Module not yet loaded: ' + moduleName);
                return false;
            }
        }
        return true;
    }
    
    // Initialize the chat interface when all modules are loaded
    function initializeWhenReady() {
        if (areAllModulesLoaded()) {
            console.log('All modules loaded, initializing chat interface');
            
            // Initialize the parameter validator first
            if (window.MPAI_ParameterValidator) {
                console.log('Initializing MPAI_ParameterValidator');
            }
            
            // Initialize the tool call detector next
            if (window.MPAI_ToolCallDetector) {
                console.log('Initializing MPAI_ToolCallDetector');
                window.MPAI_ToolCallDetector.init({
                    logger: window.mpaiLogger
                });
            }
            
            // Initialize the message processor last
            if (window.MPAI_MessageProcessor) {
                console.log('Initializing MPAI_MessageProcessor');
                window.MPAI_MessageProcessor.init({
                    logger: window.mpaiLogger
                });
            }
            
            // Process any existing messages
            if (window.MPAI_MessageProcessor && typeof window.MPAI_MessageProcessor.processExistingMessages === 'function') {
                window.MPAI_MessageProcessor.processExistingMessages();
            }
            
            console.log('Module initialization complete');
        } else {
            // Try again after a short delay
            setTimeout(initializeWhenReady, 100);
        }
    }
    
    // Start checking when the document is ready
    $(document).ready(function() {
        console.log('MPAI Module Loader: Starting module initialization');
        initializeWhenReady();
    });
})(jQuery);