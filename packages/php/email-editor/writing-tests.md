# Writing Tests for WooCommerce Email Editor PHP Library

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Test Structure](#test-structure)
3. [Unit Tests](#unit-tests)
4. [Integration Tests](#integration-tests)
5. [Best Practices](#best-practices)
6. [Debugging Tests](#debugging-tests)

## Prerequisites

1. **Environment Setup**:

   ```bash
   # Install wp-env
   npm install
   
   # Start test environment
   composer run env:start
   ```

2. **Required Dependencies**:
   - Node
   - PNPM
   - Docker (for wp-env)
   - Composer

## Test Structure

### 1. Directory Structure

```shell
tests/
├── unit/
│   ├── Engine/
│   ├── Integrations/
│   ├── bootstrap.php
│   └── stubs.php
└── integration/
```

### 2. Test Organization

- Unit tests in `tests/unit/`
- Integration tests in `tests/integration/`
- Use descriptive file names with `_Test.php` suffix
- Group related tests using `@group` annotation

## Unit Tests

### 1. Running Unit Tests

```bash
# Run all unit tests
composer run test:unit

# Run specific test file
pnpm --filter='@woocommerce/email-editor-config' test:unit -- tests/unit/Engine/Settings_Controller_Test.php

# Run tests with specific group
composer run test:unit -- --group=settings
```

### 2. Basic Unit Test Template

```php
<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

class Settings_Controller_Test extends \Email_Editor_Unit_Test  {
    /**
     * @var Settings_Controller
     */
    private $controller;

    public function setUp(): void {
        parent::setUp();
        $this->controller = new Settings_Controller();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * @test
     * @group settings
     */
    public function test_can_register_settings() {
        // Arrange
        $expected_settings = [
            'email_subject' => 'Test Subject',
            'email_preheader' => 'Test Preheader'
        ];

        // Act
        $this->controller->register_settings($expected_settings);

        // Assert
        $this->assertEquals(
            $expected_settings,
            $this->controller->get_settings()
        );
    }
}
```

### 3. When to Write Unit Tests

- Testing individual classes and methods
- Testing business logic in isolation
- Testing edge cases and error conditions
- Testing data transformations
- Testing utility functions

## Integration Tests

### 1. Running Integration Tests

```bash
# Run all integration tests
composer run test:integration

# Run specific integration test
pnpm --filter='@woocommerce/email-editor-config' test:integration -- tests/integration/Integrations/Core/Renderer/Blocks/Social_Links_Test.php

# Run tests with specific group
composer run test:integration -- --group=email-templates
```

### 2. Basic Integration Test Template

```php
<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer;

class Renderer_Test extends \Email_Editor_Integration_Test_Case {
    /**
     * @var Renderer
     */
    private $renderer;

    public function setUp(): void {
        parent::setUp();
        $this->renderer = $this->renderer = $this->di_container->get( Renderer::class );
    }

    /**
     * @test
     * @group email-templates
     */
    public function test_can_render_email_template() {
        // Test here
    }

    private function create_mock_order() {
        // Create a mock order for testing
        return wc_create_order();
    }
}
```

### 3. When to Write Integration Tests

- Testing interactions between multiple classes
- Testing WordPress hooks and filters
- Testing database operations
- Testing email template rendering
- Testing API endpoints
- Testing complex workflows

## Best Practices

1. **Test Isolation**:
   - Each test should be independent
   - Use `setUp()` and `tearDown()` for test fixtures
   - Clean up after tests
   - Use database transactions when possible

2. **Naming Conventions**:
   - Test methods should be descriptive
   - Use `test_` prefix or `@test` annotation
   - Group related tests using `@group` annotation
   - Follow PSR-4 autoloading standards

3. **Assertions**:
   - Use specific assertions
   - Test one thing per test method
   - Include both positive and negative test cases
   - Test edge cases and error conditions

4. **Mocking**:
   - Use WordPress test utilities
   - Mock external dependencies
   - Use test doubles when appropriate
   - Keep mocks simple and focused

## Debugging Tests

1. **Running Tests in Debug Mode**:

   ```bash
   composer run test:unit -- --debug
   ```

2. **Using Xdebug**:
   - Configure Xdebug in php.ini
   - Set breakpoints in your IDE
   - Run tests with coverage report

3. **Viewing Test Coverage**:

   ```bash
   composer run test:unit -- --coverage-html coverage/
   ```

4. **Using wp-env for Testing**:

   ```bash
   # Start test environment
   composer run env:start

   # Stop test environment
   composer run env:stop

   # Reset test environment
   composer run env:clean
   ```

Guide for writing E2E tests can be found at [packages/js/email-editor/writing-e2e-tests.md](../../../packages/js/email-editor/writing-e2e-tests.md)
