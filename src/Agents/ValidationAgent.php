<?php
/**
 * Validation Agent
 *
 * @package MemberpressAiAssistant
 */

namespace MemberpressAiAssistant\Agents;

use MemberpressAiAssistant\Abstracts\AbstractAgent;

/**
 * Agent specialized in security and validation
 */
class ValidationAgent extends AbstractAgent {
    /**
     * {@inheritdoc}
     */
    public function getAgentName(): string {
        return 'Validation Agent';
    }

    /**
     * {@inheritdoc}
     */
    public function getAgentDescription(): string {
        return 'Specialized agent for handling input validation, sanitization, permission verification, and security policy enforcement.';
    }

    /**
     * {@inheritdoc}
     */
    public function getSystemPrompt(): string {
        return <<<EOT
You are a specialized security and validation assistant. Your primary responsibilities include:
1. Validating and sanitizing user inputs
2. Verifying user permissions and access rights
3. Enforcing security policies and best practices
4. Identifying and mitigating potential security vulnerabilities

Focus on maintaining the highest standards of security and data integrity.
Prioritize actions that protect user data and system integrity from potential threats.
EOT;
    }

    /**
     * {@inheritdoc}
     */
    protected function registerCapabilities(): void {
        $this->addCapability('validate_input', [
            'description' => 'Validate user input',
            'parameters' => ['input', 'validation_rules'],
        ]);
        
        $this->addCapability('sanitize_input', [
            'description' => 'Sanitize user input',
            'parameters' => ['input', 'sanitization_type'],
        ]);
        
        $this->addCapability('verify_permission', [
            'description' => 'Verify user permission',
            'parameters' => ['user_id', 'permission', 'context'],
        ]);
        
        $this->addCapability('check_access', [
            'description' => 'Check user access to resource',
            'parameters' => ['user_id', 'resource_id', 'resource_type'],
        ]);
        
        $this->addCapability('enforce_policy', [
            'description' => 'Enforce security policy',
            'parameters' => ['policy_name', 'context'],
        ]);
        
        $this->addCapability('validate_form', [
            'description' => 'Validate form submission',
            'parameters' => ['form_data', 'form_type'],
        ]);
        
        $this->addCapability('check_csrf_token', [
            'description' => 'Verify CSRF token',
            'parameters' => ['token', 'action'],
        ]);
        
        $this->addCapability('validate_request', [
            'description' => 'Validate API request',
            'parameters' => ['request_data', 'endpoint'],
        ]);
        
        $this->addCapability('scan_for_vulnerabilities', [
            'description' => 'Scan for security vulnerabilities',
            'parameters' => ['target', 'scan_type'],
        ]);
        
        $this->addCapability('generate_secure_token', [
            'description' => 'Generate secure token',
            'parameters' => ['token_type', 'expiration'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function processRequest(array $request, array $context): array {
        $this->setContext($context);
        
        // Add request to short-term memory
        $this->remember('request', $request);
        
        // Log the request
        if ($this->logger) {
            $this->logger->info('Processing request with ' . $this->getAgentName(), [
                'request' => $request,
                'agent' => $this->getAgentName(),
            ]);
        }
        
        // Extract the intent from the request
        $intent = $request['intent'] ?? '';
        
        // Process based on intent
        switch ($intent) {
            case 'validate_input':
                return $this->validateInput($request);
            
            case 'sanitize_input':
                return $this->sanitizeInput($request);
            
            case 'verify_permission':
                return $this->verifyPermission($request);
            
            case 'check_access':
                return $this->checkAccess($request);
            
            case 'enforce_policy':
                return $this->enforcePolicy($request);
            
            case 'validate_form':
                return $this->validateForm($request);
            
            case 'check_csrf_token':
                return $this->checkCsrfToken($request);
            
            case 'validate_request':
                return $this->validateRequest($request);
            
            case 'scan_for_vulnerabilities':
                return $this->scanForVulnerabilities($request);
            
            case 'generate_secure_token':
                return $this->generateSecureToken($request);
            
            default:
                return [
                    'status' => 'error',
                    'message' => 'Unknown intent: ' . $intent,
                ];
        }
    }

    /**
     * Validate user input
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function validateInput(array $request): array {
        $input = $request['input'] ?? '';
        $validationRules = $request['validation_rules'] ?? [];
        
        // Implementation would validate the input against the rules
        $isValid = true;
        $errors = [];
        
        // Example validation logic
        if (isset($validationRules['required']) && $validationRules['required'] && empty($input)) {
            $isValid = false;
            $errors[] = 'Input is required';
        }
        
        if (isset($validationRules['min_length']) && strlen($input) < $validationRules['min_length']) {
            $isValid = false;
            $errors[] = 'Input must be at least ' . $validationRules['min_length'] . ' characters';
        }
        
        if (isset($validationRules['max_length']) && strlen($input) > $validationRules['max_length']) {
            $isValid = false;
            $errors[] = 'Input must be at most ' . $validationRules['max_length'] . ' characters';
        }
        
        if (isset($validationRules['pattern']) && !preg_match($validationRules['pattern'], $input)) {
            $isValid = false;
            $errors[] = 'Input does not match the required pattern';
        }
        
        return [
            'status' => $isValid ? 'success' : 'error',
            'message' => $isValid ? 'Input is valid' : 'Input validation failed',
            'data' => [
                'input' => $input,
                'is_valid' => $isValid,
                'errors' => $errors,
                'validated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Sanitize user input
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function sanitizeInput(array $request): array {
        $input = $request['input'] ?? '';
        $sanitizationType = $request['sanitization_type'] ?? 'text';
        
        // Implementation would sanitize the input based on the type
        $sanitizedInput = '';
        
        // Example sanitization logic
        switch ($sanitizationType) {
            case 'text':
                $sanitizedInput = strip_tags($input);
                break;
            
            case 'email':
                $sanitizedInput = filter_var($input, FILTER_SANITIZE_EMAIL);
                break;
            
            case 'url':
                $sanitizedInput = filter_var($input, FILTER_SANITIZE_URL);
                break;
            
            case 'integer':
                $sanitizedInput = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                break;
            
            case 'float':
                $sanitizedInput = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                break;
            
            default:
                $sanitizedInput = strip_tags($input);
                break;
        }
        
        return [
            'status' => 'success',
            'message' => 'Input sanitized successfully',
            'data' => [
                'original_input' => $input,
                'sanitized_input' => $sanitizedInput,
                'sanitization_type' => $sanitizationType,
                'sanitized_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Verify user permission
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function verifyPermission(array $request): array {
        $userId = $request['user_id'] ?? 0;
        $permission = $request['permission'] ?? '';
        $context = $request['context'] ?? [];
        
        // Implementation would verify the user's permission
        $hasPermission = true; // Example result
        
        // Example permission check logic
        if ($permission === 'manage_options' && $userId !== 1) {
            $hasPermission = false;
        }
        
        return [
            'status' => $hasPermission ? 'success' : 'error',
            'message' => $hasPermission ? 'User has the required permission' : 'User does not have the required permission',
            'data' => [
                'user_id' => $userId,
                'permission' => $permission,
                'has_permission' => $hasPermission,
                'verified_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Check user access to resource
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function checkAccess(array $request): array {
        $userId = $request['user_id'] ?? 0;
        $resourceId = $request['resource_id'] ?? 0;
        $resourceType = $request['resource_type'] ?? '';
        
        // Implementation would check the user's access to the resource
        $hasAccess = true; // Example result
        
        // Example access check logic
        if ($resourceType === 'premium_content' && $userId !== 1) {
            $hasAccess = false;
        }
        
        return [
            'status' => $hasAccess ? 'success' : 'error',
            'message' => $hasAccess ? 'User has access to the resource' : 'User does not have access to the resource',
            'data' => [
                'user_id' => $userId,
                'resource_id' => $resourceId,
                'resource_type' => $resourceType,
                'has_access' => $hasAccess,
                'checked_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Enforce security policy
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function enforcePolicy(array $request): array {
        $policyName = $request['policy_name'] ?? '';
        $context = $request['context'] ?? [];
        
        // Implementation would enforce the security policy
        $policyEnforced = true; // Example result
        $actions = [];
        
        // Example policy enforcement logic
        switch ($policyName) {
            case 'password_strength':
                $actions[] = 'Enforced minimum password length of 12 characters';
                $actions[] = 'Required at least one uppercase letter, one lowercase letter, one number, and one special character';
                break;
            
            case 'login_attempts':
                $actions[] = 'Limited login attempts to 5 per hour';
                $actions[] = 'Implemented progressive delays between attempts';
                break;
            
            case 'session_timeout':
                $actions[] = 'Set session timeout to 30 minutes of inactivity';
                $actions[] = 'Implemented secure session handling';
                break;
            
            default:
                $policyEnforced = false;
                break;
        }
        
        return [
            'status' => $policyEnforced ? 'success' : 'error',
            'message' => $policyEnforced ? 'Security policy enforced successfully' : 'Unknown security policy',
            'data' => [
                'policy_name' => $policyName,
                'actions' => $actions,
                'enforced_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Validate form submission
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function validateForm(array $request): array {
        $formData = $request['form_data'] ?? [];
        $formType = $request['form_type'] ?? '';
        
        // Implementation would validate the form submission
        $isValid = true;
        $errors = [];
        
        // Example form validation logic
        switch ($formType) {
            case 'registration':
                if (empty($formData['username'])) {
                    $isValid = false;
                    $errors['username'] = 'Username is required';
                }
                
                if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                    $isValid = false;
                    $errors['email'] = 'Valid email is required';
                }
                
                if (empty($formData['password']) || strlen($formData['password']) < 8) {
                    $isValid = false;
                    $errors['password'] = 'Password must be at least 8 characters';
                }
                break;
            
            case 'login':
                if (empty($formData['username'])) {
                    $isValid = false;
                    $errors['username'] = 'Username is required';
                }
                
                if (empty($formData['password'])) {
                    $isValid = false;
                    $errors['password'] = 'Password is required';
                }
                break;
            
            default:
                $isValid = false;
                $errors['form_type'] = 'Unknown form type';
                break;
        }
        
        return [
            'status' => $isValid ? 'success' : 'error',
            'message' => $isValid ? 'Form validation successful' : 'Form validation failed',
            'data' => [
                'form_type' => $formType,
                'is_valid' => $isValid,
                'errors' => $errors,
                'validated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Verify CSRF token
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function checkCsrfToken(array $request): array {
        $token = $request['token'] ?? '';
        $action = $request['action'] ?? '';
        
        // Implementation would verify the CSRF token
        $isValid = !empty($token) && strlen($token) >= 32; // Example validation
        
        return [
            'status' => $isValid ? 'success' : 'error',
            'message' => $isValid ? 'CSRF token is valid' : 'Invalid CSRF token',
            'data' => [
                'action' => $action,
                'is_valid' => $isValid,
                'verified_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Validate API request
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function validateRequest(array $request): array {
        $requestData = $request['request_data'] ?? [];
        $endpoint = $request['endpoint'] ?? '';
        
        // Implementation would validate the API request
        $isValid = true;
        $errors = [];
        
        // Example API request validation logic
        switch ($endpoint) {
            case '/api/v1/users':
                if (empty($requestData['name'])) {
                    $isValid = false;
                    $errors['name'] = 'Name is required';
                }
                
                if (empty($requestData['email']) || !filter_var($requestData['email'], FILTER_VALIDATE_EMAIL)) {
                    $isValid = false;
                    $errors['email'] = 'Valid email is required';
                }
                break;
            
            case '/api/v1/posts':
                if (empty($requestData['title'])) {
                    $isValid = false;
                    $errors['title'] = 'Title is required';
                }
                
                if (empty($requestData['content'])) {
                    $isValid = false;
                    $errors['content'] = 'Content is required';
                }
                break;
            
            default:
                $isValid = false;
                $errors['endpoint'] = 'Unknown endpoint';
                break;
        }
        
        return [
            'status' => $isValid ? 'success' : 'error',
            'message' => $isValid ? 'API request is valid' : 'API request validation failed',
            'data' => [
                'endpoint' => $endpoint,
                'is_valid' => $isValid,
                'errors' => $errors,
                'validated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Scan for security vulnerabilities
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function scanForVulnerabilities(array $request): array {
        $target = $request['target'] ?? '';
        $scanType = $request['scan_type'] ?? 'basic';
        
        // Implementation would scan for vulnerabilities
        $vulnerabilities = [];
        
        // Example vulnerability scanning logic
        switch ($scanType) {
            case 'basic':
                // No vulnerabilities found in this example
                break;
            
            case 'advanced':
                $vulnerabilities[] = [
                    'severity' => 'medium',
                    'type' => 'XSS',
                    'description' => 'Potential cross-site scripting vulnerability in comment form',
                    'recommendation' => 'Implement proper output escaping',
                ];
                break;
            
            case 'comprehensive':
                $vulnerabilities[] = [
                    'severity' => 'medium',
                    'type' => 'XSS',
                    'description' => 'Potential cross-site scripting vulnerability in comment form',
                    'recommendation' => 'Implement proper output escaping',
                ];
                
                $vulnerabilities[] = [
                    'severity' => 'high',
                    'type' => 'CSRF',
                    'description' => 'Missing CSRF protection on form submission',
                    'recommendation' => 'Implement CSRF tokens for all forms',
                ];
                break;
            
            default:
                // No vulnerabilities found in this example
                break;
        }
        
        return [
            'status' => 'success',
            'message' => 'Vulnerability scan completed',
            'data' => [
                'target' => $target,
                'scan_type' => $scanType,
                'vulnerabilities' => $vulnerabilities,
                'vulnerabilities_count' => count($vulnerabilities),
                'scanned_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Generate secure token
     *
     * @param array $request The request data
     * @return array The response data
     */
    protected function generateSecureToken(array $request): array {
        $tokenType = $request['token_type'] ?? 'generic';
        $expiration = $request['expiration'] ?? 3600; // Default to 1 hour
        
        // Implementation would generate a secure token
        $token = bin2hex(random_bytes(32)); // Example token generation
        $expiresAt = date('Y-m-d H:i:s', time() + $expiration);
        
        return [
            'status' => 'success',
            'message' => 'Secure token generated successfully',
            'data' => [
                'token' => $token,
                'token_type' => $tokenType,
                'expires_at' => $expiresAt,
                'generated_at' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Calculate intent match score
     *
     * @param array $request The request data
     * @return float Score between 0-30
     */
    protected function calculateIntentMatchScore(array $request): float {
        $score = 0.0;
        $message = strtolower($request['message'] ?? '');
        
        // Check for validation-related keywords
        $validationKeywords = [
            'validate', 'validation', 'sanitize', 'sanitization', 'security',
            'permission', 'access', 'policy', 'csrf', 'token', 'vulnerability',
            'secure', 'input', 'form', 'request', 'check', 'verify', 'enforce',
            'scan', 'generate', 'authentication', 'authorization', 'protection'
        ];
        
        foreach ($validationKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $score += 2.0; // Add 2 points for each keyword match
            }
        }
        
        // Cap at 30
        return min(30.0, $score);
    }

    /**
     * Calculate entity relevance score
     *
     * @param array $request The request data
     * @return float Score between 0-30
     */
    protected function calculateEntityRelevanceScore(array $request): float {
        $score = 0.0;
        $message = strtolower($request['message'] ?? '');
        
        // Check for validation-specific entities
        $entities = [
            'input' => 5.0,
            'form' => 4.0,
            'request' => 4.0,
            'token' => 4.0,
            'permission' => 5.0,
            'access' => 5.0,
            'policy' => 4.0,
            'security' => 5.0,
            'validation' => 5.0,
            'sanitization' => 4.0,
            'vulnerability' => 4.0,
            'csrf' => 3.0,
            'xss' => 3.0,
            'sql injection' => 3.0,
        ];
        
        foreach ($entities as $entity => $points) {
            if (strpos($message, $entity) !== false) {
                $score += $points;
            }
        }
        
        // Cap at 30
        return min(30.0, $score);
    }

    /**
     * Calculate capability match score
     *
     * @param array $request The request data
     * @return float Score between 0-20
     */
    protected function calculateCapabilityMatchScore(array $request): float {
        $score = 0.0;
        $message = strtolower($request['message'] ?? '');
        
        // Check if the request matches any of our capabilities
        foreach ($this->capabilities as $capability => $metadata) {
            if (strpos($message, strtolower($capability)) !== false) {
                $score += 5.0; // Add 5 points for each capability match
            }
        }
        
        // Check for action verbs related to our domain
        $actionVerbs = [
            'validate' => 3.0,
            'sanitize' => 3.0,
            'verify' => 3.0,
            'check' => 2.0,
            'enforce' => 3.0,
            'scan' => 3.0,
            'generate' => 2.0,
            'secure' => 3.0,
            'protect' => 3.0,
            'authenticate' => 3.0,
            'authorize' => 3.0,
        ];
        
        foreach ($actionVerbs as $verb => $points) {
            if (strpos($message, $verb) !== false) {
                $score += $points;
            }
        }
        
        // Cap at 20
        return min(20.0, $score);
    }

    /**
     * Calculate context continuity score
     *
     * @param array $request The request data
     * @return float Score between 0-20
     */
    protected function calculateContextContinuityScore(array $request): float {
        $score = 0.0;
        
        // Check if we have previous requests in memory
        $previousRequest = $this->recall('request');
        if ($previousRequest) {
            // If previous request was also about validation, increase score
            if (isset($previousRequest['intent']) && 
                (strpos($previousRequest['intent'], 'validate') !== false || 
                 strpos($previousRequest['intent'], 'sanitize') !== false || 
                 strpos($previousRequest['intent'], 'verify') !== false || 
                 strpos($previousRequest['intent'], 'check') !== false || 
                 strpos($previousRequest['intent'], 'enforce') !== false || 
                 strpos($previousRequest['intent'], 'scan') !== false || 
                 strpos($previousRequest['intent'], 'generate') !== false)) {
                $score += 10.0;
            }
            
            // If previous request used one of our capabilities, increase score
            foreach ($this->capabilities as $capability => $metadata) {
                if (isset($previousRequest['intent']) && 
                    $previousRequest['intent'] === $capability) {
                    $score += 10.0;
                    break;
                }
            }
        }
        
        // Cap at 20
        return min(20.0, $score);
    }

    /**
     * Apply score multipliers based on agent-specific criteria
     *
     * @param float $score The current score
     * @param array $request The request data
     * @return float The adjusted score
     */
    protected function applyScoreMultipliers(float $score, array $request): float {
        $message = strtolower($request['message'] ?? '');
        
        // Boost score if explicitly mentioning validation or security
        if (strpos($message, 'input validation') !== false || 
            strpos($message, 'security check') !== false || 
            strpos($message, 'permission verification') !== false || 
            strpos($message, 'security policy') !== false) {
            $score *= 1.5;
        }
        
        // Reduce score if request seems to be about membership operations
        if (strpos($message, 'membership') !== false || 
            strpos($message, 'subscription') !== false || 
            strpos($message, 'payment') !== false || 
            strpos($message, 'access rule') !== false) {
            $score *= 0.7;
        }
        
        // Reduce score if request seems to be about content management
        if (strpos($message, 'content') !== false || 
            strpos($message, 'post') !== false || 
            strpos($message, 'page') !== false || 
            strpos($message, 'media') !== false) {
            $score *= 0.6;
        }
        
        // Reduce score if request seems to be about system operations
        if (strpos($message, 'system config') !== false || 
            strpos($message, 'plugin management') !== false || 
            strpos($message, 'performance') !== false || 
            strpos($message, 'diagnostics') !== false) {
            $score *= 0.6;
        }
        
        return $score;
    }
}