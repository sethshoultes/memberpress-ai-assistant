# API Key Management

The MemberPress AI Assistant plugin requires API keys to interact with AI services like OpenAI and Anthropic. These keys are stored securely in the WordPress database using WordPress's standard encryption mechanisms.

## Setting Up API Keys

1. Obtain API keys from the respective service providers:
   - OpenAI: https://platform.openai.com/api-keys
   - Anthropic: https://console.anthropic.com/keys

2. Enter these keys in the plugin settings under "API Settings"

3. The plugin will use these keys to make requests to the AI services

## Security Considerations

- API keys are stored in the WordPress database using WordPress's standard security mechanisms
- Access to the keys is restricted to administrators
- The plugin includes security verification to ensure keys are only used in legitimate contexts
- API keys are never exposed in client-side code

## Technical Implementation

The API key management system is implemented in the following components:

1. **Settings Model (`MPAISettingsModel`)**: Stores and retrieves API keys from the WordPress database
2. **Key Manager (`MPAIKeyManager`)**: Provides a secure interface for accessing API keys with security verification
3. **Settings UI**: Allows administrators to enter and manage API keys through the WordPress admin interface

## API Key Validation

The plugin validates API keys to ensure they have the correct format:

- OpenAI API keys must start with `sk-`
- Anthropic API keys must start with `sk-ant-`

Invalid API keys will be rejected and an error message will be displayed.

## Testing API Connections

The plugin includes functionality to test API connections to ensure that the provided API keys are valid and working correctly. You can test the connections from the API Settings page.