# Claude Code Documentation for WooCommerce Plugin

**Scope**: PHP backend, high-level JavaScript test commands
**For React/TypeScript development**: See `client/admin/CLAUDE.md`
**For settings-payments module**: See `client/admin/client/settings-payments/CLAUDE.md`

## Which Documentation to Use?

**Use this doc when:**
- Running PHP unit tests
- PHP linting
- Working on PHP backend code
- Running JavaScript tests from the plugin root

**Use `client/admin/CLAUDE.md` when:**
- Running Jest tests for React components
- JavaScript/TypeScript linting
- Building or watching the admin client
- Understanding admin client architecture

**Use `client/admin/client/settings-payments/CLAUDE.md` when:**
- Working on payment gateway UI components
- Adding/modifying status badges
- Understanding payment gateway patterns
- Updating payment-related TypeScript types

## Quick Reference

```bash
# PHP Tests
pnpm run test:php:env -- --filter TestClassName

# JavaScript Tests (from plugin root)
pnpm run test:client -- status-badge.test.tsx

# PHP Linting
pnpm run lint:php
pnpm run lint:php:fix

# JavaScript Linting (see client/admin/CLAUDE.md for details)
cd client/admin && pnpm run lint
```

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

### JavaScript/Jest Tests

To run JavaScript tests for the admin client:

```bash
# Run all JavaScript tests
pnpm run test:client

# Run tests in watch mode
pnpm run test:client -- --watch

# Run a specific test file
pnpm run test:client -- status-badge.test.tsx

# Run tests with coverage
pnpm run test:client -- --coverage
```

For detailed Jest configuration and testing patterns, see `client/admin/CLAUDE.md`.

### Troubleshooting Tests

**PHP Tests:**
- **Tests failing due to missing dependencies**: Ensure `pnpm install` has been run
- **Docker issues**: Try `wp-env start` to restart the test environment
- **Permission issues**: Tests run in Docker containers with proper permissions
- **Xdebug warnings**: These can be safely ignored - they don't affect test results

**JavaScript Tests:**
- See `client/admin/CLAUDE.md` for detailed troubleshooting

## Code Quality Commands

### PHP Linting

```bash
# Run PHP linting
pnpm run lint:php

# Fix PHP code style issues
pnpm run lint:php:fix
```

### JavaScript Linting

For JavaScript/TypeScript linting, see `client/admin/CLAUDE.md` for detailed commands and configuration.

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

## Instructions for Claude Code

**Maintaining Your Documentation (CLAUDE.md files):**

These CLAUDE.md files are **your working notes**, not official project documentation. They help you work more efficiently across sessions.

**After completing any task, ask the user:**

Use AskUserQuestion tool with these options:
- **Question**: "Update my CLAUDE.md documentation with what we learned?"
- **Options**:
  - "Yes, update docs" - Proceed to update the appropriate CLAUDE.md file(s)
  - "No, skip" - Dismiss without updating
- Briefly list what was learned (1-3 bullet points) before asking
- User can dismiss with Esc or select an option

**When to update your documentation:**
- **General patterns** that apply across a significant area of the codebase
- **Non-obvious architectural decisions** or conventions
- **Workflow patterns** that save time (commands, sequences, decision trees)
- **Where to find things** - File/directory organization that isn't self-evident

**What NOT to document (focus on efficiency):**
- Specific implementation details easily understood from reading files
- One-off solutions or very localized patterns
- Information that's obvious from the code structure
- Details that only apply to a single component/function

**How to update:**
- **Keep it high-level** - Patterns and principles, not specifics
- **Add to existing CLAUDE.md** if it fits the current scope
- **Create new CLAUDE.md** in a module directory only if:
  - The module has broadly applicable patterns
  - It would bloat top-level docs unnecessarily
- **Follow the established pattern**: Quick Reference → When to Use → Patterns → Examples

**Documentation locations:**
- `CLAUDE.md` (this file) - PHP tests, plugin-level workflows
- `client/admin/CLAUDE.md` - React/Jest/Webpack development
- `client/admin/client/[module]/CLAUDE.md` - Module-specific patterns
- Create new docs in other modules as needed

---

**Development Notes:**
- Always run tests after making changes to verify functionality
- Use specific test filters to run only relevant tests during development
- Test failures provide detailed output showing expected vs actual values
- The test environment handles WordPress/WooCommerce setup automatically
- Extension counts in payment tests must match the actual implementation exactly
