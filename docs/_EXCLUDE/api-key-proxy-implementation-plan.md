# API Key Proxy Implementation Plan

This document outlines the technical implementation plan for transitioning the MemberPress AI Assistant plugin to use a proxy server for API key management, with an optional freemium hybrid model.

## Current System Overview

The current system uses:
- `LlmClientInterface` with implementations for different providers (OpenAI, Anthropic)
- `LlmProviderFactory` to create appropriate client instances
- `MPAIKeyManager` for API key management
- Dependency injection via `ServiceLocator`

## Phase 1: Basic Proxy Implementation

### 1. Server-Side Infrastructure

**1.1 Create Proxy API Endpoints:**
- `/completion` - For text completions
- `/chat` - For chat completions
- `/models` - For listing available models
- `/auth` - For site authentication

**1.2 Authentication System:**
```php
// Server-side authentication logic
function authenticate_site($site_identifier, $proxy_key) {
    // Verify the site is registered and the key is valid
    $site = get_site_by_identifier($site_identifier);
    if (!$site || $site->proxy_key !== $proxy_key) {
        return false;
    }
    return $site;
}
```

**1.3 Request Forwarding:**
```php
// Server-side request forwarding
function forward_request($provider, $endpoint, $payload, $site) {
    // Get the appropriate API key based on provider
    $api_key = get_provider_api_key($provider);
    
    // Forward the request to the actual API
    $response = make_api_request($provider, $endpoint, $payload, $api_key);
    
    // Log usage for the site
    log_api_usage($site->id, $provider, $endpoint, strlen($response));
    
    return $response;
}
```

### 2. Plugin-Side Implementation

**2.1 Create ProxyLlmClient Class:**

```php
namespace MemberpressAiAssistant\Llm\Providers;

use MemberpressAiAssistant\Llm\ValueObjects\LlmRequest;
use MemberpressAiAssistant\Llm\ValueObjects\LlmResponse;

class ProxyLlmClient extends AbstractLlmClient {
    private $site_identifier;
    private $proxy_key;
    private $proxy_url;
    private $provider_type;
    
    public function __construct($config, $provider_type = 'openai') {
        parent::__construct($config);
        $this->provider_type = $provider_type;
        $this->site_identifier = $this->generate_site_identifier();
        $this->proxy_key = $this->get_proxy_key();
        $this->proxy_url = 'https://api.memberpress.com/ai-proxy/v1/';
    }
    
    public function sendRequest(LlmRequest $request): LlmResponse {
        $endpoint = $request->isChat() ? 'chat' : 'completion';
        
        $response = wp_remote_post($this->proxy_url . $endpoint, [
            'headers' => [
                'X-Site-Identifier' => $this->site_identifier,
                'X-Proxy-Key' => $this->proxy_key,
                'X-Provider' => $this->provider_type,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($request->toArray()),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            return new LlmResponse(false, $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || isset($data['error'])) {
            return new LlmResponse(false, $data['error'] ?? 'Unknown error');
        }
        
        return new LlmResponse(true, $data['content'], $data);
    }
    
    public function listModels(): array {
        $response = wp_remote_get($this->proxy_url . 'models', [
            'headers' => [
                'X-Site-Identifier' => $this->site_identifier,
                'X-Proxy-Key' => $this->proxy_key,
                'X-Provider' => $this->provider_type
            ]
        ]);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data['models'] ?? [];
    }
    
    private function generate_site_identifier() {
        // Generate a unique identifier for this WordPress installation
        return md5(get_site_url() . AUTH_SALT);
    }
    
    private function get_proxy_key() {
        // Get or generate a proxy key for this installation
        $key = get_option('mpai_proxy_key');
        if (!$key) {
            $key = wp_generate_password(32, false);
            update_option('mpai_proxy_key', $key);
        }
        return $key;
    }
}
```

**2.2 Modify LlmProviderFactory:**

```php
namespace MemberpressAiAssistant\Llm\Services;

use MemberpressAiAssistant\Llm\Providers\OpenAiClient;
use MemberpressAiAssistant\Llm\Providers\AnthropicClient;
use MemberpressAiAssistant\Llm\Providers\ProxyLlmClient;

class LlmProviderFactory {
    private $config;
    private $key_manager;
    private $use_proxy = true; // Set to true to use proxy by default
    
    public function __construct($config, $key_manager) {
        $this->config = $config;
        $this->key_manager = $key_manager;
    }
    
    public function createClient($provider_type) {
        if ($this->use_proxy) {
            return new ProxyLlmClient($this->config, $provider_type);
        }
        
        // Fallback to direct API access if proxy is disabled
        $api_key = $this->key_manager->get_api_key($provider_type);
        
        if ($provider_type === 'openai') {
            return new OpenAiClient($this->config, $api_key);
        } elseif ($provider_type === 'anthropic') {
            return new AnthropicClient($this->config, $api_key);
        }
        
        throw new \Exception("Unsupported provider type: $provider_type");
    }
    
    public function setUseProxy($use_proxy) {
        $this->use_proxy = $use_proxy;
    }
}
```

**2.3 Register Site with Proxy Server:**

```php
function register_site_with_proxy() {
    $site_identifier = md5(get_site_url() . AUTH_SALT);
    $proxy_key = get_option('mpai_proxy_key');
    
    if (!$proxy_key) {
        $proxy_key = wp_generate_password(32, false);
        update_option('mpai_proxy_key', $proxy_key);
    }
    
    $response = wp_remote_post('https://api.memberpress.com/ai-proxy/v1/register', [
        'body' => [
            'site_identifier' => $site_identifier,
            'proxy_key' => $proxy_key,
            'site_url' => get_site_url(),
            'admin_email' => get_option('admin_email'),
            'plugin_version' => MPAI_VERSION
        ]
    ]);
    
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        update_option('mpai_proxy_registered', true);
        return true;
    }
    
    return false;
}
```

## Phase 2: Freemium Hybrid Model

### 1. Settings Model Extension

```php
namespace MemberpressAiAssistant\Admin\Settings;

class MPAISettingsModel {
    // Add to existing get_default_settings method
    public function get_default_settings() {
        return [
            // Existing settings...
            'connection_mode' => 'proxy',  // 'proxy' or 'direct'
            'proxy_tier' => 'free',        // 'free', 'basic', 'premium'
            'openai_api_key' => '',        // User's own API key if using direct mode
            'anthropic_api_key' => ''      // User's own API key if using direct mode
        ];
    }
    
    // Add new methods
    public function get_connection_mode() {
        return $this->get_setting('connection_mode', 'proxy');
    }
    
    public function get_proxy_tier() {
        return $this->get_setting('proxy_tier', 'free');
    }
}
```

### 2. Update LlmProviderFactory for Hybrid Mode

```php
public function createClient($provider_type) {
    $settings = $this->serviceLocator->get('settings');
    $mode = $settings->get_connection_mode();
    
    if ($mode === 'proxy') {
        $tier = $settings->get_proxy_tier();
        return new ProxyLlmClient($this->config, $provider_type, $tier);
    } else {
        // Direct API access with user's key
        if ($provider_type === 'openai') {
            $api_key = $settings->get_setting('openai_api_key', '');
            return new OpenAiClient($this->config, $api_key);
        } elseif ($provider_type === 'anthropic') {
            $api_key = $settings->get_setting('anthropic_api_key', '');
            return new AnthropicClient($this->config, $api_key);
        }
    }
    
    throw new \Exception("Unsupported provider type: $provider_type");
}
```

### 3. Settings UI Updates

```php
// Add to the settings view
public function render_connection_mode_field() {
    $connection_mode = $this->model->get_connection_mode();
    ?>
    <div class="mpai-setting-field">
        <label for="mpai-connection-mode">Connection Mode:</label>
        <select id="mpai-connection-mode" name="mpai_settings[connection_mode]">
            <option value="proxy" <?php selected($connection_mode, 'proxy'); ?>>MemberPress Proxy (Recommended)</option>
            <option value="direct" <?php selected($connection_mode, 'direct'); ?>>Direct API Access</option>
        </select>
        <p class="description">Choose how to connect to AI services.</p>
    </div>
    <?php
}

public function render_proxy_tier_field() {
    $proxy_tier = $this->model->get_proxy_tier();
    $connection_mode = $this->model->get_connection_mode();
    $display = $connection_mode === 'proxy' ? 'block' : 'none';
    ?>
    <div class="mpai-setting-field mpai-proxy-setting" style="display: <?php echo $display; ?>">
        <label for="mpai-proxy-tier">Proxy Tier:</label>
        <select id="mpai-proxy-tier" name="mpai_settings[proxy_tier]">
            <option value="free" <?php selected($proxy_tier, 'free'); ?>>Free</option>
            <option value="basic" <?php selected($proxy_tier, 'basic'); ?>>Basic ($9.99/month)</option>
            <option value="premium" <?php selected($proxy_tier, 'premium'); ?>>Premium ($19.99/month)</option>
        </select>
        <p class="description">Select your proxy service tier.</p>
    </div>
    <?php
}

public function render_api_key_fields() {
    $connection_mode = $this->model->get_connection_mode();
    $display = $connection_mode === 'direct' ? 'block' : 'none';
    $openai_key = $this->model->get_setting('openai_api_key', '');
    $anthropic_key = $this->model->get_setting('anthropic_api_key', '');
    ?>
    <div class="mpai-setting-field mpai-direct-setting" style="display: <?php echo $display; ?>">
        <label for="mpai-openai-key">OpenAI API Key:</label>
        <input type="password" id="mpai-openai-key" name="mpai_settings[openai_api_key]" value="<?php echo esc_attr($openai_key); ?>" class="regular-text">
        <p class="description">Enter your OpenAI API key for direct access.</p>
    </div>
    
    <div class="mpai-setting-field mpai-direct-setting" style="display: <?php echo $display; ?>">
        <label for="mpai-anthropic-key">Anthropic API Key:</label>
        <input type="password" id="mpai-anthropic-key" name="mpai_settings[anthropic_api_key]" value="<?php echo esc_attr($anthropic_key); ?>" class="regular-text">
        <p class="description">Enter your Anthropic API key for direct access.</p>
    </div>
    <?php
}
```

### 4. JavaScript for Settings UI

```javascript
jQuery(document).ready(function($) {
    // Toggle settings based on connection mode
    $('#mpai-connection-mode').on('change', function() {
        var mode = $(this).val();
        if (mode === 'proxy') {
            $('.mpai-proxy-setting').show();
            $('.mpai-direct-setting').hide();
        } else {
            $('.mpai-proxy-setting').hide();
            $('.mpai-direct-setting').show();
        }
    });
    
    // Handle tier upgrade
    $('#mpai-proxy-tier').on('change', function() {
        var tier = $(this).val();
        if (tier !== 'free') {
            // Redirect to upgrade page
            var upgradeUrl = 'https://memberpress.com/ai-assistant/upgrade?tier=' + tier;
            if (confirm('You\'ll be redirected to the upgrade page. Continue?')) {
                window.open(upgradeUrl, '_blank');
            }
        }
    });
});
```

## Phase 3: Proxy Server Rate Limiting

```php
// Server-side rate limiting
function check_rate_limit($site, $provider) {
    $tier = $site->tier ?? 'free';
    $limits = [
        'free' => [
            'daily' => 50,
            'monthly' => 1000,
            'models' => ['gpt-3.5-turbo', 'claude-instant']
        ],
        'basic' => [
            'daily' => 200,
            'monthly' => 5000,
            'models' => ['gpt-3.5-turbo', 'gpt-4', 'claude-instant', 'claude-2']
        ],
        'premium' => [
            'daily' => 1000,
            'monthly' => 20000,
            'models' => ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo', 'claude-instant', 'claude-2', 'claude-3']
        ]
    ];
    
    // Get current usage
    $daily_usage = get_daily_usage($site->id, $provider);
    $monthly_usage = get_monthly_usage($site->id, $provider);
    
    // Check against limits
    if ($daily_usage >= $limits[$tier]['daily']) {
        return [
            'allowed' => false,
            'reason' => 'Daily limit reached',
            'limit' => $limits[$tier]['daily'],
            'usage' => $daily_usage
        ];
    }
    
    if ($monthly_usage >= $limits[$tier]['monthly']) {
        return [
            'allowed' => false,
            'reason' => 'Monthly limit reached',
            'limit' => $limits[$tier]['monthly'],
            'usage' => $monthly_usage
        ];
    }
    
    return [
        'allowed' => true,
        'daily_limit' => $limits[$tier]['daily'],
        'daily_usage' => $daily_usage,
        'monthly_limit' => $limits[$tier]['monthly'],
        'monthly_usage' => $monthly_usage
    ];
}
```

## Implementation Timeline

1. **Week 1: Basic Proxy Infrastructure**
   - Set up proxy server endpoints
   - Create ProxyLlmClient class
   - Implement site registration

2. **Week 2: Integration and Testing**
   - Modify LlmProviderFactory
   - Test proxy functionality
   - Fix any issues

3. **Week 3: Freemium Model**
   - Update settings model
   - Create settings UI
   - Implement tier-based rate limiting

4. **Week 4: Finalization**
   - Add usage analytics
   - Create admin dashboard
   - Documentation and deployment

## Conclusion

This implementation plan provides a roadmap for transitioning to a proxy-based API key management system with an optional freemium model. The approach leverages our existing architecture while adding new capabilities for secure and scalable distribution of the plugin.