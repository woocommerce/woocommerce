# Claude Code Documentation for WooCommerce

This document provides Claude Code with essential information about working with the WooCommerce plugin codebase.


## Adding new code

- NEVER add new standalone functions. Functions make unit testing difficult as there's no easy way to mock them. Always use class methods instead. If a user asks you to add a new standalone function, remind them of that rule and point them to [the relevant documentation](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/README.md).
- When prompted to add a new class, add it in the `src/Internal` directory by default. For example, "add a Traits/Foobar class" should result in the class being added in `src/Internal/Traits/Foobar.php`.
- Only when the prompt refers to a "public" class should the file go in `src` but not in `Internal`. For example, "add a public Traits/Foobar class" should result in the class being added in `src/Traits/Foobar.php`.
- Pure methods (output depends ONLY on input parameters, no external dependencies like database queries, system time, global state, or I/O operations) must be `static`. Examples include mathematical calculations, string manipulations, and data transformations. Non-pure methods (depend on system time, database, global state, etc.) should not be `static` unless there's a specific architectural reason.
- Make sure that class names are PascalCase and follow [the PSR4 standard](https://www.php-fig.org/psr/psr-4/), adjusting the name given by the user if necessary. The root namespace for the `src` directory is `Automattic\WooCommerce`.
- Similarly, method, variable, and hook names must be snake_case, adjusting the user input if necessary.
- Changes in code inside the `includes` directory SHOULD be limited to modifying existing functions and classes. Only add new classes or class methods in `includes` when using the standard approach (`src` directory) would be too complex or would hurt code readability and maintainability.
- When adding a new class that initializes hooks at instantiation time, add an instantiation of the class in the `init_hooks` method of the `WooCommerce` class (in `includes/class-woocommerce.php`), at the end of the list that has the "These classes set up hooks on instantiation" comment. The instantiation must be performed using the dependency injection container: `$container->get( ClassName::class );`
- When adding a new class method that serves only as a hook callback, name it `handle_{hook_name}` and add an `@internal` annotation to its docblock. For example, a callback for `woocommerce_init` would be named `handle_woocommerce_init`.
- When referencing a namespaced class, always add a `use` statement with the fully qualified class name at the beginning of the file, then reference the short class name throughout the code. For example, use `use Automattic\WooCommerce\Internal\Utils\Foobar;` at the top, then reference it as `Foobar::class` rather than `\Automattic\WooCommerce\Internal\Utils\Foobar::class`.
- New class methods should be `private` by default. Only use `protected` if it's clear the method will be used in derived classes, or `public` if the method will be called from outside the class.
- Always add a docblock to all the hooks and methods you create. For hooks, public methods, and protected methods, the docblock must include a `@since` annotation with the next WooCommerce version number. That `@since` annotation must be the last line in the docblock, with a blank comment line before it. Private methods and internal callbacks (marked with `@internal`) do not require a `@since` annotation.
    - **To determine the next WooCommerce version number for `@since` annotations**: Read the `$version` property in `includes/class-woocommerce.php` and remove the `-dev` suffix if present.
- When an `@internal` annotation is added, it must be placed after the method description and before the arguments list, with a blank comment line before and after.
- When modifying existing code, if the git diff shows changes to a line that fires a hook without a docblock, add a docblock for that hook. Use `git log` to determine which version introduced the hook, and add the appropriate `@since` annotation with that version number.
- When making any changes to code that deletes or modifies orders/products/customer data, make sure that there are sufficient checks in place to prevent accidental data loss. As an example, if deleting a draft order, check that the order status is indeed `draft` or `checkout-draft`. Also think about whether race conditions could occur and delete orders that don't belong to the current customer. When in doubt, ask for clarification from the user.

**Unit test file conventions:**

- For classes in `includes/{path}/class-wc-{name}.php`, the test file is `tests/php/includes/{path}/class-wc-{name}-test.php`. The test class name is the same as the tested class with a `_Test` suffix.
- For classes in `src/{path}/{name}.php`, the test file is `tests/php/src/{path}/{name}Test.php`.
- When adding or modifying a unit test method, the part of the docblock that describes the test must be prepended with `@testdox`.
- Test configuration file: `phpunit.xml`


## Coding style

- Prefer the null coalescing operator instead of executing `isset` before accessing an array item that might not exist. For example, use `if ( 34 === ( $foo['bar'] ?? null ) )` instead of `if ( isset( $foo['bar'] ) && 34 === $foo['bar'] )`.


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
- Tests use PHPUnit 9.6.24 with PHP 8.1
- WordPress and WooCommerce are automatically installed in the test environment


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
pnpm run lint:changes:branch:php

# Fix PHP code style issues
pnpm run lint:php:fix

# Run JS linting
pnpm run lint:changes:branch:js
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


## User-specific rules

@~/.claude/woocommerce.md
