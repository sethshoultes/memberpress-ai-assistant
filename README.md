# MemberPress AI Assistant

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/memberpress/memberpress-ai-assistant)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![MemberPress](https://img.shields.io/badge/MemberPress-1.9.0%2B-green.svg)](https://memberpress.com/)
[![License](https://img.shields.io/badge/license-GPL%20v2%2B-red.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Tests](https://img.shields.io/badge/tests-PHPUnit%20%2B%20Jest-success.svg)](#testing)

> 🤖 **Intelligent AI-powered assistant that seamlessly integrates with MemberPress to provide natural language membership management, content generation, and comprehensive site administration.**

## 🌟 Overview

MemberPress AI Assistant revolutionizes membership site management by combining powerful AI capabilities with deep MemberPress integration. Built on a sophisticated agent-based architecture, it enables administrators to manage memberships, generate content, and analyze data through natural language conversations.

### ✨ Key Features

- **🎯 Natural Language Interface** - Manage your entire membership site through conversational AI
- **🏗️ Agent-Based Architecture** - Specialized AI agents for different domains (content, memberships, system operations)
- **🔧 Tool-Based Operations** - Modular, extensible tools with automatic caching
- **⚡ Performance Optimized** - Multi-level caching and lazy loading for enterprise-scale sites
- **🔒 Security First** - Enterprise-grade security with proper capability checks and data sanitization
- **📊 Comprehensive Analytics** - Deep insights into membership data and user behavior
- **🎨 Content Generation** - AI-powered content creation for membership sites
- **🔌 Seamless Integration** - Native WordPress and MemberPress integration

## 🚀 Quick Start

### For Site Administrators

1. **Activate the Plugin**
   ```bash
   # Navigate to your WordPress admin
   Plugins → Installed Plugins → Activate "MemberPress AI Assistant"
   ```

2. **Start Using AI Assistant**
   ```
   WordPress Admin → AI Assistant → Chat
   ```

3. **Try These Examples**
   ```
   "Show me my top-performing memberships this month"
   "Create a new VIP membership for $99/month"
   "Generate a welcome email for premium members"
   "What's my total revenue this quarter?"
   ```

### For Developers

1. **Clone & Install**
   ```bash
   git clone [repository-url]
   cd memberpress-ai-assistant
   composer install
   npm install
   ```

2. **Run Tests**
   ```bash
   composer test    # PHP tests
   npm test         # JavaScript tests
   ```

3. **Development Setup**
   ```bash
   # Enable debug mode
   define('MPAI_DEBUG_MODE', true);
   ```

## 🏛️ Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                       │
│  ┌─────────────────┐  ┌──────────────────┐  ┌─────────────┐ │
│  │   Admin UI      │  │  Chat Interface  │  │  Templates  │ │
│  └─────────────────┘  └──────────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│                   Application Layer                         │
│  ┌─────────────────┐  ┌──────────────────┐  ┌─────────────┐ │
│  │  Agent System   │  │   Tool System    │  │ Orchestrator│ │
│  └─────────────────┘  └──────────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│                  Infrastructure Layer                       │
│  ┌─────────────────┐  ┌──────────────────┐  ┌─────────────┐ │
│  │ Dependency DI   │  │     Caching      │  │   Logging   │ │
│  └─────────────────┘  └──────────────────┘  └─────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Core Components

| Component | Purpose | Location |
|-----------|---------|----------|
| **Agent System** | Specialized AI agents for different domains | `src/Agents/` |
| **Tool System** | Modular operations with standardized interfaces | `src/Tools/` |
| **Orchestration** | Request routing and agent coordination | `src/Orchestration/` |
| **Services** | Business logic and external integrations | `src/Services/` |
| **DI Container** | Dependency injection and service management | `src/DI/` |

### Agent Architecture

- **🎯 MemberPressAgent** - Membership management and operations
- **✍️ ContentAgent** - Content generation and management
- **⚙️ SystemAgent** - WordPress administration and settings
- **✅ ValidationAgent** - Data validation and verification

## 📋 Requirements

| Component | Version | Notes |
|-----------|---------|-------|
| WordPress | 5.8+ | Core WordPress installation |
| PHP | 7.4+ | 8.0+ recommended for performance |
| MemberPress | 1.9.0+ | Required for membership functionality |
| MySQL | 5.7+ | Database requirements |

## 📦 Installation

### Quick Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate through WordPress admin
3. Navigate to **AI Assistant** in admin menu
4. Start using the chat interface immediately

### Manual Installation

```bash
# Download and extract
wget [plugin-download-url]
unzip memberpress-ai-assistant.zip
mv memberpress-ai-assistant /path/to/wordpress/wp-content/plugins/

# Set permissions
chmod 755 memberpress-ai-assistant
chown -R www-data:www-data memberpress-ai-assistant

# Activate via WP-CLI
wp plugin activate memberpress-ai-assistant
```

### Development Installation

```bash
# Clone repository
git clone [repository-url] memberpress-ai-assistant
cd memberpress-ai-assistant

# Install dependencies
composer install          # PHP dependencies
npm install               # JavaScript dependencies

# Run initial setup
composer install --no-dev # Production dependencies only
npm run build             # Build assets

# Run tests
composer test             # PHPUnit tests
npm test                  # Jest tests
```

## 🔧 Configuration

### Basic Setup

The plugin works out of the box with sensible defaults. For advanced configuration:

```php
// wp-config.php
define('MPAI_DEBUG_MODE', true);           // Enable debug logging
define('MPAI_CACHE_TTL', 3600);           // Cache timeout (seconds)
define('MPAI_LOG_LEVEL', 'info');         // Logging level
```

### Advanced Configuration

```php
// Custom agent registration
add_action('mpai_register_agents', function($registry) {
    $registry->register('custom_agent', new CustomAgent());
});

// Custom tool registration  
add_action('mpai_register_tools', function($registry) {
    $registry->register('custom_tool', new CustomTool());
});
```

## 🎯 Usage Examples

### Membership Management

```
"Create a premium membership for $29.99/month with access to premium content"
"Show me all users who joined this week"
"Update the Basic Plan pricing to $19.99"
"Generate a report of expired memberships"
```

### Content Generation

```
"Write a welcome email for new premium members"
"Create a membership comparison table"
"Generate FAQ content about membership benefits"
"Draft a newsletter about new features"
```

### Analytics & Reporting

```
"What's my revenue trend over the last 6 months?"
"Show me my most popular membership plans"
"How many users upgraded this month?"
"Generate a customer retention report"
```

## 🧪 Testing

### Running Tests

```bash
# PHP Tests (PHPUnit)
composer test                    # All tests
composer test:unit              # Unit tests only
composer test:integration       # Integration tests only
./vendor/bin/phpunit --coverage-html coverage/

# JavaScript Tests (Jest)
npm test                        # All JS tests
npm run test:watch             # Watch mode
npm run test:coverage          # With coverage

# Code Quality
composer phpcs                  # Code standards
composer phpcbf                 # Auto-fix standards
```

### Test Structure

```
tests/
├── Unit/                      # PHP unit tests
│   ├── Agents/               # Agent tests
│   ├── Tools/                # Tool tests
│   └── Services/             # Service tests
├── Integration/              # Integration tests
├── js/                       # JavaScript tests
│   ├── unit/                 # JS unit tests
│   └── integration/          # JS integration tests
└── Fixtures/                 # Test data and mocks
```

## 🎨 Extending the Plugin

### Creating Custom Agents

```php
<?php
namespace YourPlugin\Agents;

use MemberpressAiAssistant\Abstracts\AbstractAgent;

class CustomAgent extends AbstractAgent {
    public function canHandle(array $context): bool {
        return strpos($context['message'], 'custom') !== false;
    }
    
    public function execute(array $context): array {
        // Your custom logic
        return ['response' => 'Custom response'];
    }
}
```

### Creating Custom Tools

```php
<?php
namespace YourPlugin\Tools;

use MemberpressAiAssistant\Abstracts\AbstractTool;

class CustomTool extends AbstractTool {
    public function execute(array $parameters): array {
        // Tool implementation
        return ['result' => 'Tool executed'];
    }
    
    public function getSchema(): array {
        return [
            'name' => 'custom_tool',
            'description' => 'Does custom operations',
            'parameters' => [
                'input' => ['type' => 'string', 'required' => true]
            ]
        ];
    }
}
```

## 📚 Documentation

### User Documentation

- [📖 Getting Started Guide](docs/getting-started.md)
- [⚙️ Installation & Configuration](docs/installation-configuration.md)
- [💬 Chat Interface Guide](docs/chat-interface.md)
- [🔧 Admin Interface Guide](docs/admin-interface.md)

### Developer Documentation

- [🏗️ System Architecture](docs/system-architecture.md)
- [🤖 Agent Architecture](docs/agent-architecture.md)
- [🔧 Available Tools](docs/available-tools.md)
- [💉 Dependency Injection](docs/dependency-injection.md)
- [👥 Membership Operations](docs/membership-operations.md)
- [🔗 User Integration](docs/user-integration.md)

### API Reference

- [🔌 REST API Endpoints](docs/api/rest-endpoints.md)
- [🎯 Agent API](docs/api/agent-api.md)
- [🛠️ Tool API](docs/api/tool-api.md)
- [⚙️ Service API](docs/api/service-api.md)

## 🏗️ Development

### Project Structure

```
memberpress-ai-assistant/
├── src/                       # PHP source code
│   ├── Abstracts/            # Abstract base classes
│   ├── Agents/               # AI agent implementations
│   ├── DI/                   # Dependency injection
│   ├── Services/             # Business logic services
│   ├── Tools/                # Operation tools
│   └── Utilities/            # Helper utilities
├── assets/                   # Frontend assets
│   ├── css/                  # Stylesheets
│   └── js/                   # JavaScript
├── templates/                # PHP templates
├── docs/                     # Documentation
└── tests/                    # Test suites
```

### Development Workflow

1. **Setup Environment**
   ```bash
   composer install
   npm install
   ```

2. **Code Standards**
   ```bash
   composer phpcs      # Check PHP standards
   composer phpcbf     # Fix PHP standards
   npm run lint        # Check JS standards
   ```

3. **Testing**
   ```bash
   composer test       # Run PHP tests
   npm test           # Run JS tests
   ```

4. **Build Assets**
   ```bash
   npm run build      # Production build
   npm run dev        # Development build
   ```

## 🚀 Performance

### Optimization Features

- **📈 Multi-Level Caching** - Tool results, agent responses, and database queries
- **⚡ Lazy Loading** - Services and components loaded on demand
- **🗜️ Asset Optimization** - Minified CSS/JS with conditional loading
- **📊 Database Optimization** - Efficient queries with proper indexing
- **🔄 Progressive Loading** - UI components load progressively

### Performance Monitoring

```php
// Enable performance monitoring
define('MPAI_PERFORMANCE_MONITORING', true);

// View performance metrics
$metrics = mpai_get_performance_metrics();
```

## 🔒 Security & Privacy

### Security Features

- ✅ **Input Validation** - All user input sanitized and validated
- ✅ **Output Escaping** - Proper output escaping for XSS prevention  
- ✅ **Capability Checks** - WordPress capability verification
- ✅ **Nonce Verification** - CSRF protection for all forms
- ✅ **Data Minimization** - Only necessary data sent to AI services
- ✅ **Secure Storage** - Encrypted storage for sensitive data

### Privacy Compliance

- **GDPR Compliant** - Full data protection compliance
- **No Tracking** - No user tracking or analytics by default
- **Data Minimization** - Minimal data processing
- **User Control** - Complete control over data sharing

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Quick Contribution Steps

1. **Fork the repository**
2. **Create a feature branch** - `git checkout -b feature/amazing-feature`
3. **Commit changes** - `git commit -m 'Add amazing feature'`
4. **Push to branch** - `git push origin feature/amazing-feature`
5. **Open a Pull Request**

### Development Guidelines

- Follow WordPress coding standards
- Write comprehensive tests
- Update documentation
- Ensure backward compatibility

## 📊 Project Status

### Current State

✅ **Phase 6A Complete** - Consent system fully removed  
✅ **Modern Architecture** - Agent-based system with DI container  
✅ **Comprehensive Testing** - PHPUnit + Jest test coverage  
✅ **Production Ready** - Enterprise-grade performance and security  

### Roadmap

- 🔄 **Enhanced AI Capabilities** - Advanced agent interactions
- 📊 **Advanced Analytics** - Deeper membership insights
- 🎨 **UI/UX Improvements** - Enhanced chat interface
- 🔌 **API Expansion** - Extended REST API endpoints

## 📞 Support

### Community Support

- **📚 Documentation** - Comprehensive guides and API reference
- **💬 Forums** - Community discussion and help
- **🐛 Issue Tracker** - Bug reports and feature requests
- **💡 Ideas Board** - Feature suggestions and voting

### Professional Support

- **📧 Email Support** - support@memberpress.com
- **💬 Priority Chat** - Available for enterprise customers
- **📞 Phone Support** - Business hours support
- **🎯 Custom Development** - Tailored solutions available

### Response Times

| Support Level | Response Time | Availability |
|---------------|---------------|--------------|
| Community | Best effort | 24/7 |
| Professional | 24 hours | Business hours |
| Enterprise | 4 hours | 24/7 |

## 📄 License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

```
MemberPress AI Assistant
Copyright (C) 2024 MemberPress

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## 🏢 About MemberPress

**MemberPress AI Assistant** is developed by [MemberPress](https://memberpress.com/), the leading WordPress membership plugin trusted by thousands of businesses worldwide.

### Connect With Us

- **🌐 Website** - [memberpress.com](https://memberpress.com/)
- **📱 Twitter** - [@memberpress](https://twitter.com/memberpress)
- **📘 Facebook** - [MemberPress](https://facebook.com/memberpress)
- **💼 LinkedIn** - [MemberPress](https://linkedin.com/company/memberpress)
- **📺 YouTube** - [MemberPress Channel](https://youtube.com/memberpress)

---

<div align="center">

**🚀 Transform your membership site with AI-powered assistance**

[Get Started](docs/getting-started.md) • [Documentation](docs/) • [Support](mailto:support@memberpress.com) • [Enterprise](https://memberpress.com/enterprise/)

</div>