#!/bin/bash
# MemberPress AI Assistant SDK Installation Script

echo "MemberPress AI Assistant SDK Installation"
echo "========================================"
echo ""

# Check Python version
python_version=$(python3 --version 2>&1)
if [[ $? -ne 0 ]]; then
    echo "Python 3 not found. Please install Python 3.8 or higher."
    exit 1
fi

echo "Found $python_version"

# Check if version is 3.8 or higher
version=$(echo $python_version | cut -d' ' -f2)
major=$(echo $version | cut -d'.' -f1)
minor=$(echo $version | cut -d'.' -f2)

if [[ $major -lt 3 || ($major -eq 3 && $minor -lt 8) ]]; then
    echo "Python 3.8 or higher is required. Found $version"
    exit 1
fi

# Install dependencies
echo "Installing Python dependencies..."
pip3 install -r requirements.txt

if [[ $? -ne 0 ]]; then
    echo "Failed to install dependencies. Please check the error messages above."
    exit 1
fi

# Create necessary directories
echo "Creating directory structure..."
mkdir -p config/agent_definitions
mkdir -p config/tool_definitions
mkdir -p tools
mkdir -p agents
mkdir -p extensions
mkdir -p tmp

# Test SDK installation
echo "Testing SDK installation..."
python3 check_sdk.py

if [[ $? -ne 0 ]]; then
    echo "SDK test failed. Please check the error messages above."
    exit 1
fi

echo ""
echo "Installation complete! Please configure your OpenAI API key in the plugin settings."
echo "======================================"