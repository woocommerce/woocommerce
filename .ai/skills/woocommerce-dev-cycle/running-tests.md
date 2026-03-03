# Running Tests

## Table of Contents

- [PHP Unit Tests](#php-unit-tests)
    - [Basic Test Commands](#basic-test-commands)
    - [Examples](#examples)
- [Common Test Commands](#common-test-commands)
- [Test Environment](#test-environment)
- [Troubleshooting Tests](#troubleshooting-tests)
- [Interpreting Test Output](#interpreting-test-output)
- [Best Practices](#best-practices)
- [Test Configuration](#test-configuration)
- [JavaScript/Jest Tests](#javascriptjest-tests)
- [Notes](#notes)

## PHP Unit Tests

To run PHP unit tests in the WooCommerce plugin directory, use the following commands:

### Basic Test Commands

```bash
# Run all PHP unit tests
pnpm run test:php:env

# Run specific test class
pnpm run test:php:env -- --filter TestClassName

# Run specific test method
pnpm run test:php:env -- --filter TestClassName::test_method_name

# Run tests with verbose output
pnpm run test:php:env -- --verbose --filter TestClassName
```

### Examples

```bash
# Run payment extension suggestions tests
pnpm run test:php:env -- --filter PaymentsExtensionSuggestionsTest

# Run specific test method
pnpm run test:php:env -- --filter PaymentsExtensionSuggestionsTest::test_get_country_extensions_count_with_merchant_selling_online
```

## Common Test Commands

### Run Tests for a Directory

```bash
# Run all tests in a directory
pnpm run test:php:env -- tests/php/src/Internal/Admin/
```

### Run Tests Matching a Pattern

```bash
# Run all Admin tests
pnpm run test:php:env -- --filter "Admin.*Test"

# Run all tests with "Payment" in the name
pnpm run test:php:env -- --filter "Payment"
```

### Stop on First Failure

```bash
# Useful during development to quickly identify issues
pnpm run test:php:env -- --stop-on-failure
```

### Get Test Coverage

```bash
# Get coverage report (if configured)
pnpm run test:php:env -- --coverage-text
```

## Test Environment

### How It Works

Tests run in Docker via `wp-env` with auto-configured WordPress/WooCommerce (PHPUnit 9.6.24, PHP 8.1).

### Environment Setup

The test environment is managed automatically, but you can control it if needed:

```bash
# Start the test environment
wp-env start

# Stop the test environment
wp-env stop

# Restart the test environment
wp-env restart

# Destroy and recreate the environment
wp-env destroy
wp-env start
```

## Troubleshooting Tests

| Problem | Solution |
|---------|----------|
| "Class not found" errors | Run `pnpm install` |
| Tests hang/fail to start | `wp-env stop && wp-env start` or `wp-env destroy && wp-env start` |
| Permission errors | Check Docker permissions |
| Xdebug warnings | Ignore (don't affect results) |

## Interpreting Test Output

### Successful Test Run

```text
PHPUnit 9.6.24

..................................................  50 / 100 ( 50%)
..................................................  100 / 100 (100%)

Time: 00:02.345, Memory: 24.00 MB

OK (100 tests, 250 assertions)
```

### Failed Test

```text
PHPUnit 9.6.24

.....F.................................................  50 / 100 ( 50%)
..................................................  100 / 100 (100%)

Time: 00:02.345, Memory: 24.00 MB

There was 1 failure:

1) PaymentsExtensionSuggestionsTest::test_get_country_extensions_count_for_online_merchants with data set "United States" ('US', 5)
Expected 5 extensions for online merchant in US
Failed asserting that 4 matches expected 5.

/path/to/test/file.php:123

FAILURES!
Tests: 100, Assertions: 250, Failures: 1.
```

### Understanding Failures

Test failures provide:

- **Which test failed:** Test class and method name
- **Test data:** Data set used (if using data providers)
- **Expected vs actual:** What was expected and what was received
- **Location:** File and line number where assertion failed

## Best Practices

### During Development

1. **Run specific tests** for the code you're changing:

   ```bash
   pnpm run test:php:env -- --filter YourTestClass
   ```

2. **Use verbose mode** when debugging:

   ```bash
   pnpm run test:php:env -- --verbose --filter YourTestClass
   ```

3. **Stop on first failure** to focus on one issue at a time:

   ```bash
   pnpm run test:php:env -- --stop-on-failure --filter YourTestClass
   ```

### Before Committing

1. **Run all affected tests:**

   ```bash
   pnpm run test:php:env -- tests/php/src/Internal/YourFeature/
   ```

2. **Ensure all tests pass** before committing

3. **Check code quality** (see code-quality.md)

## Test Configuration

Test configuration file: `plugins/woocommerce/phpunit.xml`

This file contains:

- Test suite definitions
- Bootstrap files
- Coverage settings
- Logging configuration

## JavaScript/Jest Tests

### Running JavaScript Tests

To run JavaScript tests for the admin client, navigate to the `client/admin` directory:

```bash
# Navigate to client/admin directory first
cd client/admin

# Run all JavaScript tests
pnpm test:js

# Run tests in watch mode
pnpm test:js -- --watch

# Run a specific test file
pnpm test:js -- status-badge.test.tsx

# Run tests with coverage
pnpm test:js -- --coverage
```

### Test File Locations

- JavaScript/Jest tests: `client/admin/client/**/*.test.tsx` or `*.test.ts`
- Jest configuration: `client/admin/jest.config.js`

### Troubleshooting JavaScript Tests

For detailed Jest configuration and testing patterns, see `client/admin/CLAUDE.md`.

Common issues:

- **Tests not found**: Ensure you're in the `client/admin` directory
- **Module resolution errors**: Run `pnpm install` in the `client/admin` directory
- **Cache issues**: Try `pnpm test:js -- --clearCache`

## Notes

- The test environment handles WordPress/WooCommerce setup automatically
- Extension counts in payment tests must match the actual implementation exactly
- Test data providers are useful for testing multiple scenarios
- Always check test output for helpful error messages
- For React/TypeScript testing patterns, refer to `client/admin/CLAUDE.md`
