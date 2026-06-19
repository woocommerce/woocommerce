---
name: woocommerce-dev-cycle
description: Run tests, linting, and quality checks for WooCommerce development. Use when running tests, fixing code style, or following the development workflow.
---

# WooCommerce Development Cycle

This skill provides guidance for the WooCommerce development workflow, including running tests, code quality checks, and troubleshooting.

## Instructions

Follow these guidelines for WooCommerce development workflow:

1. **Running tests**: See [running-tests.md](running-tests.md) for PHP and JavaScript test commands, test environment setup, and troubleshooting
2. **Code quality**: See [code-quality.md](code-quality.md) for linting and code style fixes
3. **PHP linting patterns**: See [php-linting-patterns.md](php-linting-patterns.md) for common PHP linting issues and fixes
4. **JS/TS i18n patterns**: See [js-i18n-patterns.md](js-i18n-patterns.md) for translatable string patterns and placeholder usage
5. **Markdown linting**: See [markdown-linting.md](markdown-linting.md) for markdown file linting and formatting

## Development Workflow

The standard development workflow:

1. Make code changes
2. Run relevant tests: `pnpm run test:php:env -- --filter YourTestClass`
3. For PHP changes, run both required lint gates before the final commit:

   ```bash
   pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
   pnpm --filter=@woocommerce/plugin-woocommerce lint:changes:branch
   ```

4. Treat all `phpcs` errors and warnings as blocking. Fix every finding, or add a clear inline justification for any intentional suppression, before the final commit.
5. Run PHPStan or other type checks required by the changed area.
6. Commit changes only after tests pass and all required checks are clean.

## Key Principles

- Always run tests after making changes to verify functionality
- Use specific test filters to run relevant tests during development
- Fix linting errors solely for code in your current branch
- The PHP changed-file lint and branch-diff lint are required before the final commit or PR for PHP changes
- `phpcs` warnings block the final commit because CI treats them as failures
- Test failures provide detailed output showing expected vs actual values
- The test environment handles WordPress/WooCommerce setup automatically
