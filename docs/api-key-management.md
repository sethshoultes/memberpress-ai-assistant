# API Key Management

The MemberPress AI Assistant plugin requires API keys to interact with AI services like OpenAI and Anthropic. These keys are stored in the WordPress database using the standard WordPress options API.

## Setting Up API Keys

1. Obtain API keys from the respective service providers:
   - OpenAI: https://platform.openai.com/api-keys
   - Anthropic: https://console.anthropic.com/keys

2. Enter these keys in the plugin settings under "API Settings"

3. The plugin will use these keys to make requests to the AI services

## Security Considerations

- API keys are stored in the WordPress database using the `mpai_settings` option
- Access to the keys is restricted to administrators through multiple security layers:
  - Admin context verification (ensures keys are only accessible in WordPress admin)
  - User capability verification (requires 'manage_options' capability)
  - Request origin verification (ensures requests come from the same server)
  - Plugin integrity verification (checks plugin file existence and readability)
  - Rate limiting (prevents excessive key access)
- API keys are never exposed in client-side code
- Special security bypass for legitimate chat interface requests

## Technical Implementation

The API key management system is implemented in the following components:

1. **Settings Model (`MPAISettingsModel`)**: Stores and retrieves API keys from the WordPress database using the `mpai_settings` option
2. **Key Manager (`MPAIKeyManager`)**: Provides a secure interface for accessing API keys with comprehensive security verification
3. **Settings UI**: Allows administrators to enter and manage API keys through the WordPress admin interface

## API Key Validation

The plugin validates API keys to ensure they have the correct format:

- OpenAI API keys must start with `sk-`
- Anthropic API keys must start with `sk-ant-`

Invalid API keys will be rejected and an error message will be displayed. The validation is performed in the `validate_key_format()` method of the Key Manager.

## Testing API Connections

The plugin includes functionality to test API connections to ensure that the provided API keys are valid and working correctly. You can test the connections from the API Settings page.

- For OpenAI, the plugin tests the connection by making a request to the models list endpoint
- For Anthropic, the plugin tests the connection by making a simple completion request

The test results include success status, error messages if applicable, and additional information about the response.

## Component Interactions

- The Settings Model (`MPAISettingsModel`) provides methods to get and set API keys in the WordPress database
- The Key Manager (`MPAIKeyManager`) uses the Settings Model to retrieve API keys and applies security verification before returning them
- If the Settings Model is unavailable, the Key Manager falls back to directly accessing the WordPress options
- The Key Manager also supports API key overrides through WordPress filters, allowing addons to provide their own keys