# Code Quality Commands

## Table of Contents

- [Overview](#overview)
- [PHP Linting](#php-linting)
- [JavaScript Linting](#javascript-linting)
- [Markdown Linting](#markdown-linting)
- [Important Linting Guidelines](#important-linting-guidelines)
- [Example Workflow](#example-workflow)
- [Understanding Linting Output](#understanding-linting-output)
- [Pre-Commit Checklist](#pre-commit-checklist)
- [Integration with Development Cycle](#integration-with-development-cycle)
- [Additional Linting Tools](#additional-linting-tools)
- [Troubleshooting](#troubleshooting)
- [Notes](#notes)

## Overview

When making changes to the WooCommerce codebase, run these commands to ensure code quality and adherence to coding standards.

For detailed PHP linting patterns and common issues, see [php-linting-patterns.md](php-linting-patterns.md).

For markdown linting rules and workflow, see [markdown-linting.md](markdown-linting.md).

## PHP Linting

### Check for PHP Linting Issues

```bash
pnpm run lint:changes:branch:php
```

Checks changed files for WordPress Coding Standards violations (read-only).

### Fix PHP Code Style Issues

```bash
# Automatically fix PHP code style issues
pnpm run lint:php:fix
```

This command:

- Automatically fixes code style violations where possible
- Applies WordPress Coding Standards formatting
- Modifies files in place
- Should be run before committing

### Advanced PHP Linting

If you need more control, you can use phpcs and phpcbf directly:

```bash
# Check specific file or directory
vendor/bin/phpcs path/to/file.php

# Check with specific standard
vendor/bin/phpcs --standard=WordPress path/to/file.php

# Fix specific file
vendor/bin/phpcbf path/to/file.php

# Show all violations (including warnings)
vendor/bin/phpcs -s path/to/file.php
```

## JavaScript Linting

### Check for JS Linting Issues

```bash
# Run JS linting on changes in your branch
pnpm run lint:changes:branch:js
```

This command:

- Checks only JavaScript/TypeScript files changed in your current branch
- Identifies code style and potential issues
- Does not modify files

For detailed JavaScript/TypeScript linting configuration and patterns, see `client/admin/CLAUDE.md`.

## Markdown Linting

Always lint markdown files after making changes. See [markdown-linting.md](markdown-linting.md) for complete details.

**Quick commands:**

```bash
# Auto-fix most issues
markdownlint --fix path/to/file.md

# Check for remaining errors
markdownlint path/to/file.md
```

## Important Linting Guidelines

### Only Fix Code in Your Branch

**Important:** Only fix linting errors for code that has been added or modified in the branch you are working on.

Do not fix linting errors in unrelated code unless specifically asked to do so.

**Why?**

- Keeps pull requests focused on the actual changes
- Avoids merge conflicts with other branches
- Makes code review easier
- Maintains clear git history

### Example Workflow

```bash
# 1. Make your code changes
# ... edit files ...

# 2. Check what you've changed
git status
git diff

# 3. Run linting on your changes
pnpm run lint:changes:branch:php

# 4. Fix issues automatically
pnpm run lint:php:fix

# 5. Review the fixes
git diff

# 6. If needed, check JavaScript changes
pnpm run lint:changes:branch:js

# 7. Commit your changes
git add .
git commit -m "Your commit message"
```

## Understanding Linting Output

### PHP CodeSniffer Output

```text
FILE: /path/to/file.php
----------------------------------------------------------------------
FOUND 2 ERRORS AFFECTING 2 LINES
----------------------------------------------------------------------
 12 | ERROR | [x] Expected 1 space after opening parenthesis;
    |       |     0 found
 25 | ERROR | [ ] Variable "$orderID" is not in valid snake_case
    |       |     format
----------------------------------------------------------------------
```

**Legend:**

- `[x]` - Can be fixed automatically with phpcbf/lint:php:fix
- `[ ]` - Requires manual fixing

### Common PHP Issues

1. **Spacing issues** - Usually auto-fixable

   ```php
   // Wrong
   if($condition){

   // Right
   if ( $condition ) {
   ```

2. **Naming conventions** - Requires manual fix

   ```php
   // Wrong
   $orderID

   // Right
   $order_id
   ```

3. **Yoda conditions** - Requires manual fix

   ```php
   // Wrong
   if ( $value === 'active' )

   // Right
   if ( 'active' === $value )
   ```

## Pre-Commit Checklist

Before committing your changes:

- [ ] Run `pnpm run lint:changes:branch:php`
- [ ] Run `pnpm run lint:php:fix` if issues found
- [ ] Run `pnpm run lint:changes:branch:js` if you modified JS files
- [ ] Review all automatic fixes with `git diff`
- [ ] Address any remaining issues that can't be auto-fixed
- [ ] Run tests to ensure fixes didn't break functionality

## Integration with Development Cycle

Code quality checks fit into the overall development workflow:

1. Make code changes
2. Run relevant tests (see running-tests.md)
3. **Run linting checks** ← You are here
4. **Fix code quality issues** ← You are here
5. Commit changes only after tests pass and linting is clean

## Additional Linting Tools

### Running Other pnpm Scripts

WooCommerce may have additional linting scripts. Check available scripts:

```bash
# See all available scripts
pnpm run

# Common additional scripts may include:
pnpm run lint           # Lint all files
pnpm run lint:fix       # Fix all auto-fixable issues
pnpm run lint:php       # PHP linting only
pnpm run lint:js        # JavaScript linting only
```

## Troubleshooting

### Linting Command Not Found

**Problem:** Command fails with "command not found"

**Solution:** Install dependencies:

```bash
pnpm install
```

### Too Many Issues Reported

**Problem:** Linting reports issues in files you didn't change

**Solution:** Make sure you're using the branch-specific commands:

```bash
# Good - only checks your changes
pnpm run lint:changes:branch:php

# Avoid - checks entire codebase
pnpm run lint:php
```

### Conflicts After Auto-Fix

**Problem:** Git conflicts after running lint:php:fix

**Solution:**

1. Review the automatic fixes: `git diff`
2. If fixes are incorrect, revert: `git checkout -- path/to/file.php`
3. Address the issues manually instead

## Notes

- Code quality tools help maintain consistency across the codebase
- Automatic fixes save time but should always be reviewed
- Some issues require manual intervention and understanding of the context
- Linting is required before committing to ensure code quality standards
