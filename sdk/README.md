# MemberPress AI Assistant SDK

This directory contains the integration with the OpenAI Agents SDK, which enhances the AI capabilities of the MemberPress AI Assistant plugin.

## Installation

1. Ensure Python 3.8+ is installed on your server.
2. Install the required Python packages:

```bash
pip install -r requirements.txt
```

3. Set up your OpenAI API key in the plugin settings.

## Directory Structure

- `config/` - Configuration files
  - `agent_definitions/` - Agent definition files
  - `tool_definitions/` - Tool definition files
- `tools/` - Tool implementations
- `agents/` - Agent implementations
- `extensions/` - SDK extensions
- `tmp/` - Temporary files directory

## Testing Installation

You can test if the SDK is properly installed by running:

```bash
python check_sdk.py
```

This should output a JSON object with `"installed": true` if everything is set up correctly.

## Developer Notes

- The SDK integration is managed by the MPAI_SDK_Integration class in PHP
- The PHP-Python bridge handles communication between WordPress and the SDK
- All agent functionality is preserved if the SDK is not available or fails

## Troubleshooting

- Check the PHP error log for messages starting with "MPAI"
- Ensure the OpenAI API key is correctly set
- Verify Python and its dependencies are properly installed