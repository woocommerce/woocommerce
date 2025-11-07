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
pnpm test:php:env -- --filter TestClassName

# JavaScript Tests (run from client/admin directory)
cd client/admin && pnpm test:js -- status-badge.test.tsx

# PHP Linting (ONLY changed files or specific files)
pnpm lint:php:changes                          # Check changed files
pnpm lint:php:fix -- path/to/file.php          # Fix specific file

# JavaScript Linting (see client/admin/CLAUDE.md for details)
cd client/admin && npx eslint --fix path/to/file.tsx

# Markdown Linting (run from repo root)
markdownlint plugins/woocommerce/CLAUDE.md     # Check markdown file
markdownlint --fix plugins/woocommerce/CLAUDE.md # Auto-fix basic issues
```

## Copy Guidelines

### Sentence Case for UI Text

**Always use sentence case for UI copy, not title case.**

**Correct:**

- "Payment provider options"
- "Complete setup"
- "Add new gateway"

**Incorrect:**

- "Payment Provider Options"
- "Complete Setup"
- "Add New Gateway"

**Exceptions:**

- Proper nouns (WooPayments, WordPress)
- Acronyms (API, URL)
- Brand names

## Running Tests

### PHP Unit Tests

To run PHP unit tests in the WooCommerce plugin directory, use the following commands:

```bash
# Run all PHP unit tests
pnpm test:php:env

# Run specific test class
pnpm test:php:env -- --filter TestClassName

# Run specific test method
pnpm test:php:env -- --filter TestClassName::test_method_name

# Run tests with verbose output
pnpm test:php:env -- --verbose --filter TestClassName

# Examples:
pnpm test:php:env -- --filter PaymentsExtensionSuggestionsTest
pnpm test:php:env -- --filter PaymentsExtensionSuggestionsTest::test_something
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
pnpm test:php:env -- tests/php/src/Internal/Admin/

# Run tests matching a pattern
pnpm test:php:env -- --filter "Admin.*Test"

# Run tests and stop on first failure
pnpm test:php:env -- --stop-on-failure

# Get test coverage (if configured)
pnpm test:php:env -- --coverage-text
```

### JavaScript/Jest Tests

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

#### CRITICAL: Only lint/fix specific files or changed files - NEVER the entire codebase

```bash
# RECOMMENDED: Check only changed files in current branch
pnpm lint:php:changes

# Lint a specific file
pnpm lint:php -- path/to/file.php

# Fix a specific file
pnpm lint:php:fix -- path/to/file.php

# Example:
pnpm lint:php:fix -- src/Internal/Admin/Settings/PaymentsProviders/WooPayments.php

# ❌ NEVER run without file arguments (lints entire codebase):
pnpm lint:php           # NO
pnpm lint:php:fix       # NO
```

**Correct workflow:**

1. Make your PHP changes
2. Run `pnpm lint:php:changes` to check changed files
3. Fix specific files: `pnpm lint:php:fix -- path/to/file.php`
4. Verify: `pnpm lint:php -- path/to/file.php`
5. Commit

#### Common PHP Linting Issues & Fixes

| Issue | Wrong | Correct |
|-------|-------|---------|
| **Translators comment** | Before return | Before function call |
| **File docblock (PSR-12)** | After `declare()` | Before `declare()` |
| **Indentation** | Spaces | Tabs only |
| **Array alignment** | Inconsistent | Align `=>` with context |
| **Equals alignment** | Inconsistent | Match surrounding style |

**Translators comment patterns:**

```php
// WRONG - comment before return
/* translators: %s: Gateway name. */
return sprintf(
    esc_html__( '%s is not supported.', 'woocommerce' ),
    'Gateway'
);

// CORRECT - comment before esc_html__()
return sprintf(
    /* translators: %s: Gateway name. */
    esc_html__( '%s is not supported.', 'woocommerce' ),
    'Gateway'
);
```

**File header order (PSR-12):**

```php
// WRONG
<?php
declare( strict_types=1 );

/**
 * File docblock
 */

// CORRECT
<?php
/**
 * File docblock
 */

declare( strict_types=1 );
```

**Mock classes with intentional violations:**

When creating mock classes that must match external class names, use phpcs:disable:

```php
if ( ! class_exists( 'WC_Payments_Utils' ) ) {
    /**
     * Mock class.
     *
     * phpcs:disable Squiz.Classes.ClassFileName.NoMatch
     * phpcs:disable Suin.Classes.PSR4.IncorrectClassName
     * phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
     */
    class WC_Payments_Utils {
        // Mock implementation
    }
}
```

**Multi-line conditions alignment:**

```php
// Use tabs for continuation lines
if ( class_exists( '\WC_Payments_Utils' ) &&
    is_callable( '\WC_Payments_Utils::supported_countries' ) ) {
    // code
}
```

**Unused closure parameters:**

When creating closures with parameters required by signature but unused,
use `unset()` to avoid `Generic.CodeAnalysis.UnusedFunctionParameter` errors:

```php
// WRONG - PHPCS error for unused $return_url
'callback' => function ( string $return_url ) {
    return array( 'success' => true );
},

// CORRECT - unset unused parameters with explanatory comment
'callback' => function ( string $return_url ) {
    unset( $return_url ); // Avoid parameter not used PHPCS errors.
    return array( 'success' => true );
},

// Multiple unused parameters
'callback' => function ( $arg1, $arg2, $arg3 ) {
    unset( $arg1, $arg2 ); // Avoid parameter not used PHPCS errors.
    return $arg3;
},
```

**Common scenarios:**

- Mock method callbacks in PHPUnit tests
- Array/filter callbacks where signature is fixed
- Interface implementations with unused parameters

See: `tests/php/src/Internal/Admin/Settings/PaymentsRestControllerIntegrationTest.php:1647-1655`

### JavaScript Linting

For JavaScript/TypeScript linting, see `client/admin/CLAUDE.md`
for detailed commands and configuration.

### Markdown Linting

> **CRITICAL**: Always lint markdown files (*.md) after making changes

Markdown files, especially CLAUDE.md documentation files, must pass
markdownlint validation.

**IMPORTANT**: Always run markdownlint from the repository root so the
`.markdownlint.json` config file is loaded. Using absolute paths bypasses
the config and may show incorrect errors.

```bash
# Check markdown files (run from repo root)
markdownlint plugins/woocommerce/CLAUDE.md

# RECOMMENDED: Auto-fix issues first (handles most errors)
markdownlint --fix plugins/woocommerce/CLAUDE.md

# Check multiple files
markdownlint packages/js/CLAUDE.md plugins/woocommerce/CLAUDE.md

# Lint all CLAUDE.md files
markdownlint packages/js/CLAUDE.md plugins/woocommerce/CLAUDE.md \
  plugins/woocommerce/client/admin/CLAUDE.md
```

**Note**: `markdownlint --fix` automatically handles most issues including
blank lines around code blocks/lists, list indentation, and bare URLs.
Only a few issues require manual fixing (missing language specs, long lines).

**Installation** (if markdownlint is not available):

```bash
npm install -g markdownlint-cli
```

**Character encoding in markdown files:**

> **CRITICAL**: Always use proper UTF-8 characters, never let control
> characters or null bytes into markdown files

When creating markdown files:

- **Use UTF-8 box-drawing characters for directory trees:**
  `├──`, `│`, `└──` (NOT spaces, tabs, or ASCII art)
- **NEVER use Edit tool after markdownlint --fix** if the file contains
  directory trees - check file encoding first with `file path/to/file.md`
- **If file becomes corrupted** (shows as "data" instead of text):

  ```bash
  # Remove control characters and null bytes
  tr -d '\000-\037' < file.md > file.clean.md && mv file.clean.md file.md
  ```

- **Verify encoding after edits:** `file path/to/file.md` should show
  "UTF-8 text" or "ASCII text", never "data"

**Common markdown linting issues:**

| Issue | Description | Fix |
|-------|-------------|-----|
| **MD007** | List indentation | Use 4 spaces for nested items |
| **MD013** | Line length limit | Max 80 chars per line |
| **MD031** | Code blocks need blank lines | Add blank above/below |
| **MD032** | Lists need blank lines | Add blank before/after |
| **MD036** | Emphasis as heading | Use `###` not bold |
| **MD040** | Code needs language | Add: `\`\`\`bash` |
| **MD047** | Need trailing newline | File ends with newline |

**Workflow for CLAUDE.md changes:**

1. Make your markdown changes
2. **Run auto-fix first**: `markdownlint --fix path/to/CLAUDE.md`
   (handles blank lines, indentation, bare URLs automatically)
3. Check remaining errors: `markdownlint path/to/CLAUDE.md`
4. **Manually fix only what remains**: Usually just missing language specs
   on code blocks (add `bash`, `php`, `json`, etc.) and splitting long lines
5. Verify clean: `markdownlint path/to/CLAUDE.md` (should show no errors)
6. Commit

**Note**: CLAUDE.md files are AI assistant documentation and must be
well-formatted for optimal parsing.

## Working with Payment Extension Tests

The `PaymentsExtensionSuggestionsTest` class tests country-specific
payment extension suggestions:

- Tests are data-driven using PHPUnit data providers
- Each country has expected extension counts for online/offline merchants
- Extension counts must match implementation in
  `src/Internal/Admin/Suggestions/PaymentsExtensionSuggestions.php`
- When adding new countries, update both data providers in test file

## File Structure

Key directories for testing:

- `src/Internal/Admin/Suggestions/` - Payment extension suggestion implementation
- `tests/php/src/Internal/Admin/Suggestions/` - Corresponding unit tests

## Development Workflow

1. Make code changes
2. Run relevant tests: `pnpm test:php:env -- --filter YourTestClass`
3. Run linting/type checking if available
4. Commit changes only after tests pass

## Instructions for Claude Code

**CRITICAL: All CLAUDE.md files must be optimized for AI assistant use.**

These CLAUDE.md files are **internal working notes for Claude Code
(AI assistant)**, not user-facing documentation. They enable faster,
more accurate assistance across sessions.

### Writing AI-Optimized Documentation

**Structure for fast scanning:**

1. **Quick Reference at top** - Most frequently needed info first
2. **Tables for lookups** - Common errors, patterns, commands
3. **Concise sections** - Remove verbose explanations
4. **Code examples** - Correct vs incorrect patterns
5. **Action-oriented** - "Do this" not "This is how it works"

**Format guidelines:**

- Use tables for comparisons, lookups, and decision trees
- Keep sections under 20 lines when possible
- Use bullet points over paragraphs
- Include file paths with line numbers for references
- Provide copy/paste ready commands
- Avoid redundancy - link to other docs instead

**Example structure:**

```markdown
# Module Name - Claude Code Documentation

## Quick Reference: [Most Common Task]

**Common patterns:**
| Pattern | Code |
|---------|------|
| ... | ... |

## Critical Rules
- Rule 1
- Rule 2

## Known Issues
- Issue + fix in one line

## Related Documentation
- Link to other CLAUDE.md files
```

### Maintaining Your Documentation (CLAUDE.md files)

**After completing any task, ask the user:**

Use AskUserQuestion tool with these options:

- **Question**: "Update my CLAUDE.md documentation with what we learned?"
- **Options**:
    - "Yes, update docs" - Proceed to update the appropriate file(s)
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

- **Optimize for AI scanning** - Tables, bullet points, concise sections
- **Keep it high-level** - Patterns and principles, not specifics
- **Add to existing CLAUDE.md** if it fits the current scope
- **Create new CLAUDE.md** in a module directory only if:
    - The module has broadly applicable patterns
    - It would bloat top-level docs unnecessarily
- **Follow the pattern**: Quick Reference → Critical Rules → Patterns
  → Examples → Related Docs

**Documentation locations:**

- `CLAUDE.md` (this file) - PHP tests, plugin-level workflows
- `client/admin/CLAUDE.md` - React/Jest/Webpack development
- `client/admin/client/[module]/CLAUDE.md` - Module-specific patterns
- `src/Internal/Admin/Settings/CLAUDE.md` - Settings backend patterns
- Create new docs in other modules as needed

---

**Development Notes:**

- Always run tests after making changes to verify functionality
- Use specific test filters to run only relevant tests during development
- Test failures provide detailed output showing expected vs actual values
- The test environment handles WordPress/WooCommerce setup automatically
- Extension counts in payment tests must match the actual implementation exactly
