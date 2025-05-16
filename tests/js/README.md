# MemberPress AI Assistant Frontend Tests

This directory contains the frontend test suite for the MemberPress AI Assistant. The tests are written using Jest and focus on ensuring the JavaScript components work correctly.

## Test Structure

The test suite is organized as follows:

```
tests/js/
├── unit/                 # Unit tests for individual components
│   ├── chat.test.js
│   ├── button-renderer.test.js
│   ├── content-preview.test.js
│   ├── form-generator.test.js
│   ├── text-formatter.test.js
│   └── xml-processor.test.js
│
├── integration/          # Integration tests for component interactions
│   └── component-interactions.test.js
│
├── mocks/                # Mock objects and utilities
│   ├── api-mocks.js
│   ├── dom-mocks.js
│   └── event-mocks.js
│
└── utils/                # Test utilities
    └── test-utils.js
```

## Running Tests

To run the tests, you need to have Node.js and npm installed. Then, you can use the following commands:

```bash
# Install dependencies
npm install

# Run all tests
npm test

# Run tests in watch mode (for development)
npm run test:watch

# Run tests with coverage report
npm run test:coverage
```

## Test Coverage

The test suite aims to provide comprehensive coverage of the JavaScript components:

- **Unit Tests**: Test individual functions and methods in isolation
- **Integration Tests**: Test interactions between components
- **Mock Objects**: Provide controlled test environments

## Components Tested

The following JavaScript components are tested:

1. **chat.js**: The main chat interface component
   - User interactions
   - Message sending/receiving
   - UI updates

2. **button-renderer.js**: Dynamic button generation
   - Button creation with various options
   - Button group creation
   - Button updates

3. **content-preview.js**: Content preview components
   - Text previews
   - Image previews
   - Table previews
   - Code previews

4. **form-generator.js**: Dynamic form generation
   - Form creation
   - Field validation
   - Form submission

5. **text-formatter.js**: Text formatting utilities
   - Markdown formatting
   - Auto-linking
   - Text truncation

6. **xml-processor.js**: XML processing utilities
   - XML parsing
   - Tag extraction
   - Content processing

## Mock Utilities

The test suite includes several mock utilities to facilitate testing:

- **API Mocks**: Mock API responses for testing asynchronous operations
- **DOM Mocks**: Mock DOM elements for testing UI components
- **Event Mocks**: Mock event handling for testing user interactions

## Adding New Tests

When adding new tests, follow these guidelines:

1. Place unit tests in the `unit/` directory
2. Place integration tests in the `integration/` directory
3. Use the provided mock utilities for consistent testing
4. Follow the existing test patterns for consistency
5. Ensure tests are isolated and don't depend on external state

## Test Best Practices

- Write descriptive test names that explain what is being tested
- Use `describe` blocks to group related tests
- Use `beforeEach` and `afterEach` for setup and cleanup
- Mock external dependencies to isolate tests
- Test both success and failure cases
- Test edge cases and boundary conditions

## Troubleshooting

If you encounter issues with the tests:

1. Ensure all dependencies are installed (`npm install`)
2. Check for syntax errors in test files
3. Verify that the component files are in the correct location
4. Check for conflicts with global variables or mocks
5. Try running tests in isolation (`npm test -- -t "test name"`)