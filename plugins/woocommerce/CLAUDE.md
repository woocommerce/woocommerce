# Claude Code Documentation for WooCommerce

This document provides Claude Code with essential information about working with the WooCommerce plugin codebase.

## Running Tests

### PHP Unit Tests

To run PHP unit tests in the WooCommerce plugin directory, use the following commands:

```bash
# Run all PHP unit tests
pnpm run test:php:env

# Run specific test class
pnpm run test:php:env -- --filter TestClassName

# Run specific test method
pnpm run test:php:env -- --filter TestClassName::test_method_name

# Run tests with verbose output
pnpm run test:php:env -- --verbose --filter TestClassName

# Examples:
pnpm run test:php:env -- --filter PaymentsExtensionSuggestionsTest
pnpm run test:php:env -- --filter PaymentsExtensionSuggestionsTest::test_get_country_extensions_count_with_merchant_selling_online
```

### Test Environment

- Tests run in a Docker-based WordPress environment using `wp-env`
- The test environment is automatically set up and configured
- Tests use PHPUnit 9.6.24 with PHP 8.0.30
- WordPress and WooCommerce are automatically installed in the test environment

### Test File Locations

- PHP unit tests: `tests/php/src/`
- Test configuration: `phpunit.xml`
- Test data and fixtures: `tests/php/`

### Common Test Commands

```bash
# Run tests for a specific directory
pnpm run test:php:env -- tests/php/src/Internal/Admin/

# Run tests matching a pattern
pnpm run test:php:env -- --filter "Admin.*Test"

# Run tests and stop on first failure
pnpm run test:php:env -- --stop-on-failure

# Get test coverage (if configured)
pnpm run test:php:env -- --coverage-text
```

### Troubleshooting Tests

- **Tests failing due to missing dependencies**: Ensure `npm install` has been run
- **Docker issues**: Try `wp-env start` to restart the test environment
- **Permission issues**: Tests run in Docker containers with proper permissions
- **Xdebug warnings**: These can be safely ignored - they don't affect test results

## Code Quality Commands

When making changes to the codebase, run these commands to ensure code quality:

```bash
# Run PHP linting
pnpm run lint:php

# Fix PHP code style issues
pnpm run lint:php:fix

# Run JS linting
pnpm run lint:lang:js
```

## Working with Payment Extension Tests

The `PaymentsExtensionSuggestionsTest` class tests country-specific payment extension suggestions:

- Tests are data-driven using PHPUnit data providers
- Each country has expected extension counts for online and offline merchants
- Extension counts must match the implementation in `src/Internal/Admin/Suggestions/PaymentsExtensionSuggestions.php`
- When adding new countries to the implementation, update both data providers in the test file

## File Structure

Key directories for testing:

- `src/Internal/Admin/Suggestions/` - Payment extension suggestion implementation
- `tests/php/src/Internal/Admin/Suggestions/` - Corresponding unit tests

## Development Workflow

1. Make code changes
2. Run relevant tests: `pnpm run test:php:env -- --filter YourTestClass`
3. Run linting/type checking if available
4. Commit changes only after tests pass

## Notes for Claude

- Always run tests after making changes to verify functionality
- Use specific test filters to run only relevant tests during development
- Test failures provide detailed output showing expected vs actual values
- The test environment handles WordPress/WooCommerce setup automatically
- Extension counts in payment tests must match the actual implementation exactly
