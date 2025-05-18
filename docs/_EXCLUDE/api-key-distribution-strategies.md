# API Key Distribution Strategies

This document outlines various approaches for handling API keys in the MemberPress AI Assistant plugin for distribution purposes. It focuses particularly on the Proxy Server and Freemium Hybrid models, which offer the most balanced solutions.

## Current Challenges

Distributing a WordPress plugin that requires third-party API keys (like OpenAI and Anthropic) presents several challenges:

1. **Security**: Embedding API keys in the plugin code is insecure, even with obfuscation
2. **Cost Management**: Direct API access from user sites makes it difficult to control costs
3. **User Experience**: Requiring users to obtain their own API keys creates friction
4. **Scalability**: Managing API usage across many installations is challenging

## Approach Options

### 1. Proxy Server Approach

**Overview:**
- Set up a middleware server that handles all API requests
- Plugin makes requests to our server, which forwards them to AI services
- API keys are stored only on our server, never in the plugin

**Architecture:**
```
WordPress Site → MemberPress AI Plugin → Our Proxy Server → AI Service API
                                      ↑
                                Authentication
```

**Implementation Requirements:**

1. **Proxy Server:**
   - REST API endpoints mirroring the AI service APIs
   - Authentication mechanism to identify legitimate plugin installations
   - Rate limiting and usage tracking
   - Error handling and logging

2. **Plugin Modifications:**
   ```php
   class ProxyLlmClient extends AbstractLlmClient {
       private $site_identifier;
       private $proxy_key;
       private $proxy_url;
       
       public function __construct($config) {
           $this->site_identifier = $this->generate_site_identifier();
           $this->proxy_key = $this->get_proxy_key();
           $this->proxy_url = 'https://api.memberpress.com/ai-proxy/v1/';
       }
       
       public function sendRequest($request) {
           // Instead of sending to OpenAI/Anthropic directly,
           // send to our proxy with site authentication
           $response = wp_remote_post($this->proxy_url . 'completion', [
               'headers' => [
                   'X-Site-Identifier' => $this->site_identifier,
                   'X-Proxy-Key' => $this->proxy_key,
                   'Content-Type' => 'application/json'
               ],
               'body' => json_encode($request->toArray())
           ]);
           
           // Process response...
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

3. **Factory Modification:**
   ```php
   public function createClient($provider_type) {
       // Always return the proxy client instead of direct API clients
       return new ProxyLlmClient($this->config);
   }
   ```

**Advantages:**
- Complete control over API key security
- Centralized usage monitoring and cost management
- Ability to implement sophisticated rate limiting
- Can cache common requests to reduce API costs

**Disadvantages:**
- Requires maintaining server infrastructure
- Single point of failure
- Potential latency increase
- All API costs fall on us

### 2. Freemium Hybrid Model

**Overview:**
- Combine the proxy approach with the option for users to use their own API keys
- Offer limited functionality through our proxy (free tier)
- Allow users to upgrade by either:
  - Subscribing to our premium proxy service with higher limits
  - Adding their own API keys for unlimited access

**Architecture:**
```
                                 ┌→ Our Proxy Server (Free/Premium) → AI Service API
                                 │
WordPress Site → MemberPress AI Plugin
                                 │
                                 └→ Direct to AI Service API (with user's key)
```

**Implementation Requirements:**

1. **Settings Model Extension:**
   ```php
   public function get_default_settings() {
       return [
           // Existing settings...
           'connection_mode' => 'proxy', // 'proxy' or 'direct'
           'proxy_tier' => 'free',       // 'free', 'basic', 'premium'
           'openai_api_key' => '',       // User's own API key if using direct mode
           'anthropic_api_key' => ''     // User's own API key if using direct mode
       ];
   }
   ```

2. **Factory Modification:**
   ```php
   public function createClient($provider_type) {
       $mode = $this->settings->get_connection_mode();
       
       if ($mode === 'proxy') {
           return new ProxyLlmClient($this->config, $this->settings->get_proxy_tier());
       } else {
           // Use direct API access with user's key
           if ($provider_type === 'openai') {
               return new OpenAiClient($this->settings->get_openai_api_key());
           } else if ($provider_type === 'anthropic') {
               return new AnthropicClient($this->settings->get_anthropic_api_key());
           }
       }
   }
   ```

3. **Settings UI Updates:**
   - Add connection mode selection (proxy vs. direct)
   - Show/hide API key fields based on selected mode
   - Add proxy tier selection and upgrade options

4. **Proxy Server Tiers:**
   - **Free**: Limited requests per day, basic models only
   - **Basic**: More requests, access to standard models
   - **Premium**: High request limit, access to all models

**Advantages:**
- Flexible solution that works for different user needs
- Creates potential for recurring revenue
- Users can start immediately (free tier) and upgrade as needed
- Distributes API costs between us and users

**Disadvantages:**
- More complex to implement and maintain
- Requires both server infrastructure and user key management
- May need to implement a licensing system for premium tiers

## Implementation Plan for Current System

Our current system is well-positioned for these changes because:

1. **Existing LLM Abstraction**: The `LlmClientInterface` and provider implementations already abstract the API calls
2. **Service Locator Pattern**: The DI container makes it easy to swap implementations
3. **Key Management**: The `MPAIKeyManager` can be adapted for proxy authentication

### Phase 1: Basic Proxy Implementation

1. Create proxy server infrastructure:
   - Set up API endpoints
   - Implement authentication
   - Add basic rate limiting

2. Create `ProxyLlmClient` class
3. Modify `LlmProviderFactory` to use proxy client
4. Update settings to store site identifier

### Phase 2: Freemium Features

1. Extend settings model with connection mode and tier options
2. Update settings UI to allow mode selection
3. Implement tier-based rate limiting on proxy server
4. Add subscription/upgrade options

### Phase 3: Analytics and Optimization

1. Implement usage analytics
2. Add request caching to reduce API costs
3. Optimize proxy performance
4. Add admin dashboard for monitoring usage

## Security Considerations

1. **Site Authentication**: Prevent unauthorized use of our proxy
2. **User API Key Storage**: Encrypt user API keys in the database
3. **Request Validation**: Validate all requests to prevent abuse
4. **Rate Limiting**: Implement per-site and per-tier rate limiting

## Conclusion

The Freemium Hybrid Model (Option 4) offers the most flexible approach for our plugin distribution. It provides immediate value to users while creating a path to monetization and sustainable operation. The implementation can be phased, starting with the basic proxy infrastructure and adding the user choice component later.

This approach aligns with our existing architecture and can be implemented with moderate effort, providing a secure and scalable solution for API key management in our distributed plugin.