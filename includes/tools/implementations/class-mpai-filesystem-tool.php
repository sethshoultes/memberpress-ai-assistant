<?php
/**
 * FileSystem Tool Class
 *
 * Provides access to file system functionality with appropriate restrictions.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * FileSystem Tool for MemberPress AI Assistant
 */
class MPAI_FileSystem_Tool extends MPAI_Base_Tool {
    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'FileSystem Tool';
        $this->description = 'Provides limited access to file system functionality';
    }
    
    /**
     * Execute the tool
     *
     * @param array $parameters Tool parameters
     * @return mixed Execution result
     */
    public function execute($parameters) {
        // Check for required parameters
        if (!isset($parameters['action'])) {
            throw new Exception('The action parameter is required');
        }
        
        // Only administrators can use file system operations
        if (!current_user_can('manage_options')) {
            throw new Exception('Insufficient permissions to access file system');
        }
        
        $action = $parameters['action'];
        
        switch ($action) {
            case 'list_dir':
                return $this->list_directory($parameters);
            case 'read_file':
                return $this->read_file($parameters);
            case 'get_info':
                return $this->get_file_info($parameters);
            case 'search_files':
                return $this->search_files($parameters);
            default:
                throw new Exception("Unknown action: {$action}");
        }
    }
    
    /**
     * List directory contents
     *
     * @param array $parameters Parameters
     * @return array Directory contents
     */
    private function list_directory($parameters) {
        if (!isset($parameters['path'])) {
            throw new Exception('Path parameter is required');
        }
        
        $path = $this->sanitize_path($parameters['path']);
        
        if (!is_dir($path)) {
            throw new Exception("Directory not found: {$path}");
        }
        
        // Check if this is a WordPress directory
        if (!$this->is_within_wordpress($path)) {
            throw new Exception("Access to directory outside WordPress is not allowed: {$path}");
        }
        
        $items = scandir($path);
        $result = [
            'path' => $path,
            'items' => []
        ];
        
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            $full_path = trailingslashit($path) . $item;
            $is_dir = is_dir($full_path);
            
            $info = [
                'name' => $item,
                'type' => $is_dir ? 'dir' : 'file',
                'size' => $is_dir ? null : filesize($full_path),
                'modified' => date('Y-m-d H:i:s', filemtime($full_path))
            ];
            
            if (!$is_dir) {
                $info['extension'] = pathinfo($item, PATHINFO_EXTENSION);
            }
            
            $result['items'][] = $info;
        }
        
        return $result;
    }
    
    /**
     * Read a file
     *
     * @param array $parameters Parameters
     * @return array File contents
     */
    private function read_file($parameters) {
        if (!isset($parameters['file'])) {
            throw new Exception('File parameter is required');
        }
        
        $file = $this->sanitize_path($parameters['file']);
        
        if (!file_exists($file) || is_dir($file)) {
            throw new Exception("File not found: {$file}");
        }
        
        // Check if this is a WordPress file
        if (!$this->is_within_wordpress($file)) {
            throw new Exception("Access to files outside WordPress is not allowed: {$file}");
        }
        
        // Check file extension for security
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $allowed_extensions = ['txt', 'log', 'css', 'js', 'php', 'html', 'htm', 'xml', 'svg', 'json', 'md', 'csv'];
        
        if (!in_array($extension, $allowed_extensions)) {
            throw new Exception("Reading files of type '{$extension}' is not allowed");
        }
        
        // Limit file size
        $max_size = 1024 * 1024; // 1MB
        $file_size = filesize($file);
        
        if ($file_size > $max_size) {
            throw new Exception("File is too large to read ({$file_size} bytes). Maximum allowed size is {$max_size} bytes");
        }
        
        $content = file_get_contents($file);
        
        return [
            'file' => $file,
            'content' => $content,
            'size' => $file_size,
            'extension' => $extension,
            'modified' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
    
    /**
     * Get file or directory information
     *
     * @param array $parameters Parameters
     * @return array File or directory information
     */
    private function get_file_info($parameters) {
        if (!isset($parameters['path'])) {
            throw new Exception('Path parameter is required');
        }
        
        $path = $this->sanitize_path($parameters['path']);
        
        if (!file_exists($path)) {
            throw new Exception("Path not found: {$path}");
        }
        
        // Check if this is a WordPress path
        if (!$this->is_within_wordpress($path)) {
            throw new Exception("Access to paths outside WordPress is not allowed: {$path}");
        }
        
        $is_dir = is_dir($path);
        $info = [
            'path' => $path,
            'type' => $is_dir ? 'dir' : 'file',
            'exists' => true,
            'readable' => is_readable($path),
            'writable' => is_writable($path),
            'modified' => date('Y-m-d H:i:s', filemtime($path)),
            'permissions' => substr(sprintf('%o', fileperms($path)), -4)
        ];
        
        if (!$is_dir) {
            $info['size'] = filesize($path);
            $info['extension'] = pathinfo($path, PATHINFO_EXTENSION);
        } else {
            // Count items in directory
            $items = scandir($path);
            $info['items_count'] = count($items) - 2; // Subtract . and ..
        }
        
        return $info;
    }
    
    /**
     * Search for files with a pattern
     *
     * @param array $parameters Parameters
     * @return array Matching files
     */
    private function search_files($parameters) {
        if (!isset($parameters['pattern'])) {
            throw new Exception('Pattern parameter is required');
        }
        
        $pattern = $parameters['pattern'];
        $path = isset($parameters['path']) ? $this->sanitize_path($parameters['path']) : ABSPATH;
        
        // Check if this is a WordPress directory
        if (!$this->is_within_wordpress($path)) {
            throw new Exception("Access to directory outside WordPress is not allowed: {$path}");
        }
        
        // Limit recursion depth for performance
        $max_depth = isset($parameters['max_depth']) ? intval($parameters['max_depth']) : 3;
        
        if ($max_depth > 5) {
            $max_depth = 5; // Maximum allowed depth
        }
        
        $result = [
            'pattern' => $pattern,
            'path' => $path,
            'max_depth' => $max_depth,
            'matches' => []
        ];
        
        // Perform the search
        $this->find_files($path, $pattern, $result['matches'], 0, $max_depth);
        
        return $result;
    }
    
    /**
     * Recursively find files matching a pattern
     *
     * @param string $dir Directory to search
     * @param string $pattern Pattern to match
     * @param array &$results Results array
     * @param int $current_depth Current recursion depth
     * @param int $max_depth Maximum recursion depth
     */
    private function find_files($dir, $pattern, &$results, $current_depth, $max_depth) {
        if ($current_depth > $max_depth) {
            return;
        }
        
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            $full_path = trailingslashit($dir) . $item;
            $is_dir = is_dir($full_path);
            
            // Check if the file/dir name matches the pattern
            if (preg_match("/{$pattern}/i", $item)) {
                $results[] = [
                    'path' => $full_path,
                    'name' => $item,
                    'type' => $is_dir ? 'dir' : 'file',
                    'modified' => date('Y-m-d H:i:s', filemtime($full_path))
                ];
            }
            
            // If it's a directory, recurse into it
            if ($is_dir) {
                $this->find_files($full_path, $pattern, $results, $current_depth + 1, $max_depth);
            }
        }
    }
    
    /**
     * Sanitize and normalize a file path
     *
     * @param string $path File path
     * @return string Sanitized path
     */
    private function sanitize_path($path) {
        // Convert relative path to absolute
        if (strpos($path, '/') !== 0 && strpos($path, ':') !== 1) {
            $path = ABSPATH . ltrim($path, '/');
        }
        
        // Remove any ../
        $path = realpath($path);
        
        return $path;
    }
    
    /**
     * Check if a path is within the WordPress install
     *
     * @param string $path File path
     * @return bool Whether path is within WordPress
     */
    private function is_within_wordpress($path) {
        $abspath = realpath(ABSPATH);
        return strpos($path, $abspath) === 0;
    }
    
    /**
     * Get parameters schema
     *
     * @return array Parameters schema
     */
    public function get_parameters_schema() {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'description' => 'The file system action to perform',
                    'enum' => ['list_dir', 'read_file', 'get_info', 'search_files']
                ],
                'path' => [
                    'type' => 'string',
                    'description' => 'Directory path for list_dir, get_info, and search_files actions'
                ],
                'file' => [
                    'type' => 'string',
                    'description' => 'File path for read_file action'
                ],
                'pattern' => [
                    'type' => 'string',
                    'description' => 'Pattern to match for search_files action'
                ],
                'max_depth' => [
                    'type' => 'integer',
                    'description' => 'Maximum recursion depth for search_files action',
                    'minimum' => 1,
                    'maximum' => 5
                ]
            ],
            'required' => ['action']
        ];
    }
}