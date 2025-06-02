<?php
/**
 * Tool Registry
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Registry;

use MemberpressAiAssistant\Interfaces\ToolInterface;

/**
 * Registry for managing and discovering tools
 */
class ToolRegistry {
    /**
     * Singleton instance
     *
     * @var ToolRegistry
     */
    private static $instance = null;

    /**
     * Registered tools
     *
     * @var array<string, ToolInterface>
     */
    private $tools = [];

    /**
     * Logger instance
     *
     * @var mixed
     */
    private $logger;

    /**
     * Private constructor for singleton pattern
     *
     * @param mixed $logger Logger instance
     */
    private function __construct($logger = null) {
        $this->logger = $logger;
    }

    /**
     * Get singleton instance
     *
     * @param mixed $logger Logger instance
     * @return ToolRegistry
     */
    public static function getInstance($logger = null): ToolRegistry {
        if (self::$instance === null) {
            self::$instance = new self($logger);
        }
        return self::$instance;
    }

    /**
     * Prevent cloning of the instance
     *
     * @return void
     */
    private function __clone() {
        // Prevent cloning of the singleton instance
    }

    /**
     * Prevent unserializing of the instance
     *
     * @return void
     */
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Register a tool
     *
     * @param ToolInterface $tool The tool to register
     * @return bool True if registration was successful, false otherwise
     */
    public function registerTool(ToolInterface $tool): bool {
        $toolId = $tool->getToolName();
        
        if ($this->hasTool($toolId)) {
            if ($this->logger) {
                $this->logger->warning("Tool with ID {$toolId} is already registered");
            }
            return false;
        }
        
        $this->tools[$toolId] = $tool;
        
        if ($this->logger) {
            $this->logger->info("Tool {$toolId} registered successfully");
        }
        
        return true;
    }

    /**
     * Unregister a tool
     *
     * @param string $toolId The ID of the tool to unregister
     * @return bool True if unregistration was successful, false otherwise
     */
    public function unregisterTool(string $toolId): bool {
        if (!$this->hasTool($toolId)) {
            if ($this->logger) {
                $this->logger->warning("Tool with ID {$toolId} is not registered");
            }
            return false;
        }
        
        unset($this->tools[$toolId]);
        
        if ($this->logger) {
            $this->logger->info("Tool {$toolId} unregistered successfully");
        }
        
        return true;
    }

    /**
     * Check if a tool is registered
     *
     * @param string $toolId The ID of the tool to check
     * @return bool True if the tool is registered, false otherwise
     */
    public function hasTool(string $toolId): bool {
        return isset($this->tools[$toolId]);
    }

    /**
     * Get a registered tool
     *
     * @param string $toolId The ID of the tool to get
     * @return ToolInterface|null The tool instance or null if not found
     */
    public function getTool(string $toolId): ?ToolInterface {
        return $this->hasTool($toolId) ? $this->tools[$toolId] : null;
    }

    /**
     * Get all registered tools
     *
     * @return array<string, ToolInterface> Array of registered tools
     */
    public function getAllTools(): array {
        return $this->tools;
    }

    /**
     * Discover and register available tools
     *
     * @param string $directory Optional directory to scan for tools
     * @return int Number of tools discovered and registered
     */
    public function discoverTools(string $directory = ''): int {
        $count = 0;
        
        // Register core tools (static registration)
        $count += $this->registerCoreTools();
        
        // Discover plugin-based tools (dynamic discovery)
        $count += $this->discoverPluginTools();
        
        // Scan directory for tool classes if provided
        if (!empty($directory) && is_dir($directory)) {
            $count += $this->scanDirectoryForTools($directory);
        }
        
        if ($this->logger) {
            $this->logger->info("Discovered and registered {$count} tools");
        }
        
        return $count;
    }

    /**
     * Register core tools
     *
     * @return int Number of core tools registered
     */
    private function registerCoreTools(): int {
        $count = 0;
        
        // Get core tool classes
        $coreToolClasses = $this->getCoreToolClasses();
        
        foreach ($coreToolClasses as $toolClass) {
            if (class_exists($toolClass)) {
                try {
                    // Special handling for MemberPressTool which requires MemberPressService
                    if ($toolClass === 'MemberpressAiAssistant\\Tools\\MemberPressTool') {
                        // Get the MemberPressService from the container
                        global $mpai_container;
                        if ($mpai_container && $mpai_container->bound('memberpress')) {
                            $memberPressService = $mpai_container->make('memberpress');
                            $tool = new $toolClass($memberPressService);
                        } else {
                            // Log error and skip if MemberPressService is not available
                            if ($this->logger) {
                                $this->logger->error("MemberPressService not available for MemberPressTool");
                            }
                            continue;
                        }
                    } else {
                        // Standard initialization for other tools
                        $tool = new $toolClass($this->logger);
                    }
                    
                    if ($this->registerTool($tool)) {
                        $count++;
                    }
                } catch (\Exception $e) {
                    if ($this->logger) {
                        $this->logger->error("Failed to instantiate core tool {$toolClass}: " . $e->getMessage());
                    }
                }
            }
        }
        
        return $count;
    }

    /**
     * Get core tool classes
     *
     * @return array List of core tool class names
     */
    private function getCoreToolClasses(): array {
        // Core tool classes
        $coreTools = [
            'MemberpressAiAssistant\\Tools\\ContentTool',
            'MemberpressAiAssistant\\Tools\\WordPressTool',
            'MemberpressAiAssistant\\Tools\\MemberPressTool',
        ];
        
        // Allow plugins to add or remove core tools
        return apply_filters('mpai_core_tool_classes', $coreTools);
    }

    /**
     * Discover plugin-based tools
     *
     * @return int Number of plugin tools registered
     */
    private function discoverPluginTools(): int {
        $count = 0;
        
        // Get plugin tool classes through WordPress filter
        $pluginToolClasses = apply_filters('mpai_plugin_tool_classes', []);
        
        foreach ($pluginToolClasses as $toolClass) {
            if (class_exists($toolClass)) {
                try {
                    $tool = new $toolClass($this->logger);
                    if ($this->registerTool($tool)) {
                        $count++;
                    }
                } catch (\Exception $e) {
                    if ($this->logger) {
                        $this->logger->error("Failed to instantiate plugin tool {$toolClass}: " . $e->getMessage());
                    }
                }
            }
        }
        
        return $count;
    }

    /**
     * Scan directory for tool classes
     *
     * @param string $directory Directory to scan
     * @return int Number of tools registered from directory scan
     */
    private function scanDirectoryForTools(string $directory): int {
        $count = 0;
        
        // Get all PHP files in the directory
        $files = glob(rtrim($directory, '/') . '/*.php');
        
        if (!$files) {
            return 0;
        }
        
        foreach ($files as $file) {
            // Get the class name from the file
            $className = $this->getClassNameFromFile($file);
            
            if ($className && class_exists($className)) {
                try {
                    $reflection = new \ReflectionClass($className);
                    
                    // Check if the class implements ToolInterface
                    if ($reflection->implementsInterface(ToolInterface::class) && !$reflection->isAbstract()) {
                        $tool = new $className($this->logger);
                        if ($this->registerTool($tool)) {
                            $count++;
                        }
                    }
                } catch (\Exception $e) {
                    if ($this->logger) {
                        $this->logger->error("Failed to load tool from file {$file}: " . $e->getMessage());
                    }
                }
            }
        }
        
        return $count;
    }

    /**
     * Get class name from file
     *
     * @param string $file File path
     * @return string|null Class name or null if not found
     */
    private function getClassNameFromFile(string $file): ?string {
        // Simple implementation - in a real-world scenario, this would be more robust
        $content = file_get_contents($file);
        if (!$content) {
            return null;
        }
        
        // Extract namespace
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/i', $content, $matches)) {
            $namespace = $matches[1];
        }
        
        // Extract class name
        $className = null;
        if (preg_match('/class\s+(\w+)\s+/i', $content, $matches)) {
            $className = $matches[1];
        }
        
        if ($className) {
            return $namespace ? $namespace . '\\' . $className : $className;
        }
        
        return null;
    }

    /**
     * Find tools by capability
     *
     * @param string $capability The capability to search for
     * @return array<string, ToolInterface> Array of tools with the specified capability
     */
    public function findToolsByCapability(string $capability): array {
        $matchingTools = [];
        
        foreach ($this->tools as $toolId => $tool) {
            $toolDefinition = $tool->getToolDefinition();
            
            // Check if the tool has the specified capability
            // This assumes that capabilities are defined in the tool definition
            if (isset($toolDefinition['capabilities']) && in_array($capability, $toolDefinition['capabilities'])) {
                $matchingTools[$toolId] = $tool;
            }
        }
        
        return $matchingTools;
    }

    /**
     * Find tools by parameter support
     *
     * @param string $parameterName The parameter name to search for
     * @return array<string, ToolInterface> Array of tools that support the specified parameter
     */
    public function findToolsByParameter(string $parameterName): array {
        $matchingTools = [];
        
        foreach ($this->tools as $toolId => $tool) {
            $toolDefinition = $tool->getToolDefinition();
            
            // Check if the tool supports the specified parameter
            if (isset($toolDefinition['parameters']) && 
                isset($toolDefinition['parameters']['properties']) && 
                isset($toolDefinition['parameters']['properties'][$parameterName])) {
                $matchingTools[$toolId] = $tool;
            }
        }
        
        return $matchingTools;
    }

    /**
     * Find the best tool for a specific task
     *
     * @param string $task The task description
     * @param array $context Additional context for the task
     * @return ToolInterface|null The best tool for the task or null if none found
     */
    public function findBestToolForTask(string $task, array $context = []): ?ToolInterface {
        // This is a placeholder implementation
        // In a real-world scenario, this would use more sophisticated matching logic
        // possibly involving AI to determine the best tool for a given task
        
        // For now, we'll just return the first tool that has a description containing keywords from the task
        $taskWords = preg_split('/\W+/', strtolower($task));
        $bestMatch = null;
        $highestScore = 0;
        
        foreach ($this->tools as $tool) {
            $description = strtolower($tool->getToolDescription());
            $score = 0;
            
            foreach ($taskWords as $word) {
                if (strlen($word) > 3 && strpos($description, $word) !== false) {
                    $score++;
                }
            }
            
            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $tool;
            }
        }
        
        if ($this->logger && $bestMatch) {
            $this->logger->info("Found best tool for task: " . $bestMatch->getToolName() . " with score {$highestScore}");
        }
        
        return $bestMatch;
    }
}