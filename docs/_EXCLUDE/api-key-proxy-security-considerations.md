# API Key Proxy Security Considerations

This document outlines the security considerations for implementing a proxy server approach to API key management in the MemberPress AI Assistant plugin.

## Security Objectives

1. **Protect API Keys**: Prevent unauthorized access to AI service API keys
2. **Authenticate Plugin Installations**: Ensure only legitimate plugin installations can use the proxy
3. **Prevent Abuse**: Implement rate limiting and usage monitoring to prevent abuse
4. **Secure Data in Transit**: Protect data exchanged between the plugin and proxy server
5. **Secure Data at Rest**: Protect sensitive data stored on the proxy server
6. **Audit and Monitoring**: Track usage and detect suspicious activity

## Threat Model

### Potential Threats

1. **Unauthorized Access**: Attackers attempting to use the proxy without a legitimate plugin installation
2. **Key Extraction**: Attempts to extract API keys from the proxy server
3. **Denial of Service**: Excessive requests to exhaust resources or API quotas
4. **Man-in-the-Middle**: Interception of communications between plugin and proxy
5. **Server Compromise**: Direct compromise of the proxy server
6. **Credential Theft**: Theft of site authentication credentials

### Attack Vectors

1. **Forged Authentication**: Creating fake site identifiers or proxy keys
2. **Reverse Engineering**: Analyzing plugin code to understand authentication mechanisms
3. **Network Sniffing**: Capturing network traffic between plugin and proxy
4. **Brute Force**: Attempting to guess valid authentication credentials
5. **Server Vulnerabilities**: Exploiting vulnerabilities in the proxy server

## Security Measures

### 1. Site Authentication

**Site Identifier Generation:**
```php
/**
 * Generate a unique site identifier that cannot be easily forged
 */
function generate_site_identifier() {
    // Combine multiple unique aspects of the WordPress installation
    $components = [
        get_site_url(),                // Site URL
        defined('AUTH_SALT') ? AUTH_SALT : '',  // WordPress auth salt
        defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : '',  // WordPress secure auth key
        php_uname('n'),                // Server hostname
        get_option('admin_email')      // Admin email
    ];
    
    // Create a hash of the combined components
    return hash('sha256', implode('|', $components));
}
```

**Proxy Key Generation:**
```php
/**
 * Generate a secure proxy key for site authentication
 */
function generate_proxy_key() {
    // Generate a cryptographically secure random key
    $key = bin2hex(random_bytes(32));
    
    // Store the key securely in the WordPress database
    update_option('mpai_proxy_key', $key, true);  // true = encrypted
    
    return $key;
}
```

**Site Registration:**
```php
/**
 * Register a site with the proxy server
 */
function register_site_with_proxy() {
    $site_identifier = generate_site_identifier();
    $proxy_key = get_option('mpai_proxy_key') ?: generate_proxy_key();
    
    // Additional verification data
    $verification_data = [
        'site_url' => get_site_url(),
        'admin_email' => get_option('admin_email'),
        'plugin_version' => MPAI_VERSION,
        'registration_time' => time(),
        'ip_address' => $_SERVER['SERVER_ADDR'] ?? '',
        'verification_token' => wp_generate_password(32, false)
    ];
    
    // Store verification data for email verification step
    update_option('mpai_verification_data', $verification_data);
    
    // Send registration request to proxy server
    $response = wp_remote_post('https://api.memberpress.com/ai-proxy/v1/register', [
        'body' => [
            'site_identifier' => $site_identifier,
            'proxy_key' => $proxy_key,
            'verification_data' => $verification_data
        ],
        'timeout' => 30
    ]);
    
    if (is_wp_error($response)) {
        return [
            'success' => false,
            'message' => $response->get_error_message()
        ];
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!empty($data['success'])) {
        // Store registration status
        update_option('mpai_proxy_registered', true);
        
        // Store the verification token from the server
        if (!empty($data['verification_token'])) {
            update_option('mpai_server_verification_token', $data['verification_token']);
        }
        
        return [
            'success' => true,
            'message' => 'Site registered successfully'
        ];
    }
    
    return [
        'success' => false,
        'message' => $data['message'] ?? 'Unknown error'
    ];
}
```

**Email Verification:**
To add an additional layer of security, implement email verification:

1. After site registration, send a verification email to the admin email
2. Include a unique verification link with a token
3. Require verification before allowing full proxy access

### 2. Request Authentication

**Authentication Headers:**
```php
/**
 * Add authentication headers to proxy requests
 */
function add_auth_headers($headers) {
    $site_identifier = generate_site_identifier();
    $proxy_key = get_option('mpai_proxy_key');
    $timestamp = time();
    
    // Create a request signature using HMAC
    $signature_data = $site_identifier . '|' . $timestamp;
    $signature = hash_hmac('sha256', $signature_data, $proxy_key);
    
    $headers['X-Site-Identifier'] = $site_identifier;
    $headers['X-Timestamp'] = $timestamp;
    $headers['X-Signature'] = $signature;
    
    return $headers;
}
```

**Server-Side Verification:**
```php
/**
 * Verify request authentication on the server side
 */
function verify_request_auth($request) {
    $site_identifier = $request->get_header('X-Site-Identifier');
    $timestamp = $request->get_header('X-Timestamp');
    $signature = $request->get_header('X-Signature');
    
    // Check if all required headers are present
    if (!$site_identifier || !$timestamp || !$signature) {
        return false;
    }
    
    // Check for timestamp freshness (prevent replay attacks)
    $now = time();
    if (abs($now - $timestamp) > 300) {  // 5 minute window
        return false;
    }
    
    // Get the site from the database
    $site = get_site_by_identifier($site_identifier);
    if (!$site) {
        return false;
    }
    
    // Verify the signature
    $signature_data = $site_identifier . '|' . $timestamp;
    $expected_signature = hash_hmac('sha256', $signature_data, $site->proxy_key);
    
    return hash_equals($expected_signature, $signature);
}
```

### 3. Rate Limiting and Abuse Prevention

**Tiered Rate Limiting:**
```php
/**
 * Check rate limits for a site
 */
function check_rate_limits($site_id, $tier = 'free') {
    // Define limits for different tiers
    $limits = [
        'free' => [
            'requests_per_minute' => 10,
            'requests_per_hour' => 100,
            'requests_per_day' => 500,
            'tokens_per_day' => 50000
        ],
        'basic' => [
            'requests_per_minute' => 30,
            'requests_per_hour' => 300,
            'requests_per_day' => 2000,
            'tokens_per_day' => 200000
        ],
        'premium' => [
            'requests_per_minute' => 60,
            'requests_per_hour' => 600,
            'requests_per_day' => 5000,
            'tokens_per_day' => 500000
        ]
    ];
    
    // Get current usage
    $usage = get_site_usage($site_id);
    
    // Check against limits
    $tier_limits = $limits[$tier] ?? $limits['free'];
    
    if ($usage['requests_per_minute'] >= $tier_limits['requests_per_minute']) {
        return [
            'allowed' => false,
            'reason' => 'Rate limit exceeded: too many requests per minute'
        ];
    }
    
    if ($usage['requests_per_hour'] >= $tier_limits['requests_per_hour']) {
        return [
            'allowed' => false,
            'reason' => 'Rate limit exceeded: too many requests per hour'
        ];
    }
    
    if ($usage['requests_per_day'] >= $tier_limits['requests_per_day']) {
        return [
            'allowed' => false,
            'reason' => 'Rate limit exceeded: daily request limit reached'
        ];
    }
    
    if ($usage['tokens_per_day'] >= $tier_limits['tokens_per_day']) {
        return [
            'allowed' => false,
            'reason' => 'Rate limit exceeded: daily token limit reached'
        ];
    }
    
    return [
        'allowed' => true
    ];
}
```

**Anomaly Detection:**
Implement anomaly detection to identify suspicious usage patterns:

1. Track normal usage patterns for each site
2. Flag sudden spikes in usage
3. Detect unusual request patterns or content
4. Implement temporary blocks for suspicious activity

### 4. Secure Data in Transit

**HTTPS Enforcement:**
- Use HTTPS for all communications between the plugin and proxy server
- Implement certificate pinning to prevent MITM attacks
- Set appropriate security headers

**Request/Response Encryption:**
For additional security, implement application-level encryption:

```php
/**
 * Encrypt request data
 */
function encrypt_request_data($data, $key) {
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $encrypted = sodium_crypto_secretbox(json_encode($data), $nonce, $key);
    
    return base64_encode($nonce . $encrypted);
}

/**
 * Decrypt response data
 */
function decrypt_response_data($encrypted_data, $key) {
    $decoded = base64_decode($encrypted_data);
    $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    
    $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
    if ($decrypted === false) {
        throw new Exception('Failed to decrypt response data');
    }
    
    return json_decode($decrypted, true);
}
```

### 5. Secure Data at Rest

**API Key Storage:**
- Store API keys in a secure vault (e.g., AWS Secrets Manager, HashiCorp Vault)
- Use encryption for all sensitive data in the database
- Implement key rotation policies

**Database Security:**
- Use parameterized queries to prevent SQL injection
- Encrypt sensitive columns in the database
- Implement proper access controls for database users

### 6. Audit and Monitoring

**Comprehensive Logging:**
```php
/**
 * Log proxy request
 */
function log_proxy_request($site_id, $request_data, $response_data, $status) {
    // Log basic request information
    $log_entry = [
        'site_id' => $site_id,
        'timestamp' => time(),
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'request_type' => $request_data['type'] ?? 'unknown',
        'model' => $request_data['model'] ?? 'unknown',
        'input_tokens' => $request_data['input_tokens'] ?? 0,
        'output_tokens' => $response_data['output_tokens'] ?? 0,
        'status' => $status,
        'response_time' => $response_data['response_time'] ?? 0
    ];
    
    // Store log entry in database
    store_log_entry($log_entry);
    
    // For suspicious activity, log more details
    if ($status === 'suspicious') {
        log_suspicious_activity($site_id, $request_data, $response_data);
    }
}
```

**Real-time Monitoring:**
- Implement real-time monitoring of proxy server activity
- Set up alerts for suspicious patterns or high usage
- Create a dashboard for monitoring system health and usage

## Server Infrastructure Security

### 1. Server Hardening

- Use a minimal server image with only required components
- Implement proper firewall rules
- Keep all software updated
- Use secure configurations for web servers and databases

### 2. Access Control

- Implement strict access controls for server administration
- Use SSH keys instead of passwords
- Implement IP restrictions for administrative access
- Use multi-factor authentication for all admin accounts

### 3. Redundancy and Failover

- Implement load balancing across multiple servers
- Set up automatic failover mechanisms
- Regularly backup all data and configurations
- Test disaster recovery procedures

## Incident Response Plan

1. **Detection**: Implement monitoring to detect security incidents
2. **Containment**: Procedures to isolate affected systems
3. **Eradication**: Remove the cause of the incident
4. **Recovery**: Restore systems to normal operation
5. **Post-Incident Analysis**: Learn from incidents to improve security

## Security Testing

1. **Penetration Testing**: Regular testing of the proxy server and authentication mechanisms
2. **Code Reviews**: Security-focused code reviews for all proxy-related code
3. **Vulnerability Scanning**: Regular scanning for known vulnerabilities
4. **Dependency Audits**: Checking for vulnerabilities in dependencies

## Conclusion

Implementing a proxy server approach for API key management requires careful attention to security at multiple levels. By following these security considerations, the MemberPress AI Assistant plugin can provide a secure and reliable service while protecting sensitive API keys and preventing abuse.

The security measures outlined in this document should be implemented as part of the proxy server development process and regularly reviewed and updated as new threats emerge.