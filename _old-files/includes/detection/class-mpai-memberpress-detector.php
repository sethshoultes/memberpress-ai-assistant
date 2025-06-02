<?php
/**
 * MemberPress AI Assistant - MemberPress Detector
 *
 * Centralizes all MemberPress detection logic
 *
 * @package MemberPress AI Assistant
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class MPAI_MemberPress_Detector
 * 
 * This class centralizes all MemberPress detection logic throughout the plugin.
 */
class MPAI_MemberPress_Detector {
    /**
     * Singleton instance
     *
     * @var MPAI_MemberPress_Detector
     */
    private static $instance = null;
    
    /**
     * Whether MemberPress is available
     *
     * @var bool|null
     */
    private $has_memberpress = null;
    
    /**
     * MemberPress version (if available)
     *
     * @var string|null
     */
    private $mepr_version = null;
    
    /**
     * Detection method that determined the status
     *
     * @var string
     */
    private $detection_method = 'unknown';
    
    /**
     * Cache key for detection results
     *
     * @var string
     */
    private $cache_key = 'mpai_memberpress_detection';
    
    /**
     * Cache duration in seconds (1 hour)
     *
     * @var int
     */
    private $cache_duration = 3600;
    
    /**
     * List of core MemberPress classes to check for
     *
     * @var array
     */
    private $core_classes = [
        'MeprAppCtrl',
        'MeprOptions',
        'MeprUser',
        'MeprProduct',
        'MeprTransaction',
        'MeprSubscription'
    ];
    
    /**
     * List of core MemberPress constants to check for
     *
     * @var array
     */
    private $core_constants = [
        'MEPR_VERSION',
        'MEPR_PLUGIN_NAME',
        'MEPR_PATH',
        'MEPR_URL'
    ];
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Try to load from cache first
        $this->load_from_cache();
        
        // If still null, perform detection
        if ($this->has_memberpress === null) {
            $this->detect_memberpress();
            $this->save_to_cache();
        }
        
        // Add filter for forcing MemberPress detection when needed
        add_filter('mpai_force_memberpress_detection', [$this, 'force_memberpress_detection'], 10, 2);
        
        // Add action to clear cache on plugin activation/deactivation
        add_action('activated_plugin', [$this, 'clear_detection_cache']);
        add_action('deactivated_plugin', [$this, 'clear_detection_cache']);
    }
    
    /**
     * Get singleton instance
     *
     * @return MPAI_MemberPress_Detector
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Load detection results from cache
     */
    private function load_from_cache() {
        $cache = get_transient($this->cache_key);
        
        if ($cache !== false) {
            if (is_array($cache) && isset($cache['has_memberpress']) && isset($cache['detection_method'])) {
                $this->has_memberpress = $cache['has_memberpress'];
                $this->detection_method = $cache['detection_method'];
                $this->mepr_version = isset($cache['mepr_version']) ? $cache['mepr_version'] : null;
                
                // Log cache hit
                mpai_log_debug('Loaded MemberPress detection from cache', [
                    'has_memberpress' => $this->has_memberpress,
                    'detection_method' => $this->detection_method,
                    'mepr_version' => $this->mepr_version
                ]);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Save detection results to cache
     */
    private function save_to_cache() {
        $cache = [
            'has_memberpress' => $this->has_memberpress,
            'detection_method' => $this->detection_method,
            'mepr_version' => $this->mepr_version
        ];
        
        set_transient($this->cache_key, $cache, $this->cache_duration);
        
        // Log cache save
        mpai_log_debug('Saved MemberPress detection to cache', [
            'has_memberpress' => $this->has_memberpress,
            'detection_method' => $this->detection_method,
            'mepr_version' => $this->mepr_version,
            'cache_duration' => $this->cache_duration
        ]);
    }
    
    /**
     * Clear the detection cache
     */
    public function clear_detection_cache() {
        delete_transient($this->cache_key);
        
        // Reset internal state
        $this->has_memberpress = null;
        $this->detection_method = 'unknown';
        $this->mepr_version = null;
        
        // Re-detect MemberPress
        $this->detect_memberpress();
        $this->save_to_cache();
        
        // Log cache clear
        mpai_log_info('Cleared MemberPress detection cache', [
            'new_detection' => [
                'has_memberpress' => $this->has_memberpress,
                'detection_method' => $this->detection_method,
                'mepr_version' => $this->mepr_version
            ]
        ]);
    }
    
    /**
     * Detect if MemberPress is available
     */
    private function detect_memberpress() {
        // Start with the assumption that MemberPress is not active
        $this->has_memberpress = false;
        
        // Method 1: Check for MemberPress class definitions
        foreach ($this->core_classes as $class) {
            if (class_exists($class)) {
                $this->has_memberpress = true;
                $this->detection_method = 'class_exists';
                $this->log_detection('Found MemberPress core class: ' . $class);
                break;
            }
        }
        
        // Method 2: Check for MemberPress constants
        if (!$this->has_memberpress) {
            foreach ($this->core_constants as $constant) {
                if (defined($constant)) {
                    $this->has_memberpress = true;
                    $this->detection_method = 'constant_defined';
                    $this->log_detection('Found MemberPress constant: ' . $constant);
                    
                    // If version constant is available, capture it
                    if ($constant === 'MEPR_VERSION' && defined('MEPR_VERSION')) {
                        $this->mepr_version = MEPR_VERSION;
                    }
                    
                    break;
                }
            }
        }
        
        // Method 3: Check if the MemberPress plugin is active (requires plugin.php)
        if (!$this->has_memberpress && function_exists('is_plugin_active')) {
            if (is_plugin_active('memberpress/memberpress.php')) {
                $this->has_memberpress = true;
                $this->detection_method = 'is_plugin_active';
                $this->log_detection('Detected using is_plugin_active()');
            }
        }
        
        // Method 4: Check if MemberPress API classes exist
        if (!$this->has_memberpress) {
            if (class_exists('MeprApi') || class_exists('MeprRestApi')) {
                $this->has_memberpress = true;
                $this->detection_method = 'api_class_exists';
                $this->log_detection('Found MemberPress API class');
            }
        }
        
        // Method 5: Check if the 'memberpress' admin menu exists
        if (!$this->has_memberpress) {
            global $menu;
            if (is_array($menu)) {
                foreach ($menu as $item) {
                    if (isset($item[2]) && $item[2] === 'memberpress') {
                        $this->has_memberpress = true;
                        $this->detection_method = 'menu_exists';
                        $this->log_detection('Found MemberPress admin menu');
                        break;
                    }
                }
            }
        }
        
        // If we've detected MemberPress but don't have the version yet, try to get it
        if ($this->has_memberpress && $this->mepr_version === null) {
            $this->mepr_version = $this->get_mepr_version();
        }
    }
    
    /**
     * Try to get MemberPress version
     *
     * @return string|null
     */
    private function get_mepr_version() {
        // First try the constant
        if (defined('MEPR_VERSION')) {
            return MEPR_VERSION;
        }
        
        // Then try to get from plugin data
        if (function_exists('get_plugin_data') && file_exists(WP_PLUGIN_DIR . '/memberpress/memberpress.php')) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/memberpress/memberpress.php', false, false);
            if (isset($plugin_data['Version'])) {
                return $plugin_data['Version'];
            }
        }
        
        // Try get_plugins function
        if (function_exists('get_plugins')) {
            $plugins = get_plugins();
            if (isset($plugins['memberpress/memberpress.php']) && isset($plugins['memberpress/memberpress.php']['Version'])) {
                return $plugins['memberpress/memberpress.php']['Version'];
            }
        }
        
        return null;
    }
    
    /**
     * Log detection result
     *
     * @param string $message
     */
    private function log_detection($message) {
        mpai_log_debug('MemberPress detection: ' . $message, [
            'method' => $this->detection_method,
            'has_memberpress' => $this->has_memberpress,
            'version' => $this->mepr_version
        ]);
    }
    
    /**
     * Check if MemberPress is active
     *
     * @param bool $force Force re-detection if true
     * @return bool Whether MemberPress is active
     */
    public function is_memberpress_active($force = false) {
        if ($force) {
            $this->detect_memberpress();
            $this->save_to_cache();
        }
        
        // Check if the detection needs to be forced for certain admin pages
        $force_detection = $this->check_force_detection();
        if ($force_detection) {
            return true;
        }
        
        return $this->has_memberpress;
    }
    
    /**
     * Check if the current admin page requires forcing MemberPress detection
     *
     * @return bool Whether to force MemberPress detection
     */
    private function check_force_detection() {
        // Only check in admin
        if (!is_admin()) {
            return false;
        }
        
        global $pagenow, $plugin_page;
        
        // Special case for settings page - Always force MemberPress detection on settings page
        // This ensures the MemberPress menu is highlighted and visible
        if (($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'memberpress-ai-assistant-settings') ||
            (isset($plugin_page) && $plugin_page === 'memberpress-ai-assistant-settings')) {
            $this->log_detection('Force-enabling MemberPress detection on settings page for menu highlight');
            return true;
        }
        
        // Check if any registered forces should apply
        $forces = apply_filters('mpai_force_memberpress_detection', [], $this->has_memberpress);
        if (!empty($forces)) {
            $this->log_detection('Force-enabling MemberPress detection due to active filters');
            return true;
        }
        
        return false;
    }
    
    /**
     * Force MemberPress detection for specific contexts
     *
     * @param array $forces Current force contexts
     * @param bool  $has_memberpress Current detection status
     * @return array Modified force contexts
     */
    public function force_memberpress_detection($forces, $has_memberpress) {
        // Default implementation does nothing
        // This method is meant to be filtered by other components
        return $forces;
    }
    
    /**
     * Get MemberPress version
     *
     * @return string|null MemberPress version or null if not available
     */
    public function get_version() {
        return $this->mepr_version;
    }
    
    /**
     * Get the detection method used
     *
     * @return string Detection method
     */
    public function get_detection_method() {
        return $this->detection_method;
    }
    
    /**
     * Get all detection information
     *
     * @return array Detection information
     */
    public function get_detection_info() {
        return [
            'has_memberpress' => $this->has_memberpress,
            'detection_method' => $this->detection_method,
            'mepr_version' => $this->mepr_version,
            'forced' => $this->check_force_detection()
        ];
    }
}

/**
 * Initialize the MemberPress detector
 *
 * @return MPAI_MemberPress_Detector
 */
function mpai_memberpress_detector() {
    return MPAI_MemberPress_Detector::get_instance();
}

/**
 * Helper function to check if MemberPress is active
 *
 * @param bool $force Force re-detection if true
 * @return bool Whether MemberPress is active
 */
function mpai_is_memberpress_active($force = false) {
    return mpai_memberpress_detector()->is_memberpress_active($force);
}

/**
 * Helper function to get MemberPress version
 *
 * @return string|null MemberPress version or null if not available
 */
function mpai_get_memberpress_version() {
    return mpai_memberpress_detector()->get_version();
}

/**
 * Add installation check hook
 */
add_action('admin_init', function() {
    // Initialize the detector
    mpai_memberpress_detector();
});