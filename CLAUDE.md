# WooCommerce Monorepo - Claude Documentation

## Project Overview

WooCommerce is a WordPress e-commerce plugin organized as a monorepo with:

- **Backend**: PHP in `plugins/woocommerce/` (PSR-4, WordPress standards)
- **Admin Client**: React/TypeScript in `client/admin/`
- **Payment Settings**: Specialized module in `client/admin/client/settings-payments/`

## Navigation Guide

### Which Documentation to Use?

**Use this doc when:**

- Getting oriented in the project
- Understanding overall architecture
- Finding the right documentation or skill

**Use other CLAUDE.md files when:**

- React/TypeScript development → `client/admin/CLAUDE.md`
- Payment gateway UI → `client/admin/client/settings-payments/CLAUDE.md`

## Available Skills

The `.ai/skills/` directory contains procedural HOW-TO instructions:

- **`woocommerce-backend-dev`** - Backend PHP conventions and unit tests. **Invoke before writing any PHP test files.**
- **`woocommerce-dev-cycle`** - Testing and linting workflows (PHP, JS, markdown)
- **`woocommerce-copy-guidelines`** - UI text standards (sentence case rules)
- **`woocommerce-code-review`** - Code review standards and critical violations to flag
- **`woocommerce-markdown`** - Markdown writing and editing guidelines

**CRITICAL:** After reading a skill, check if a personal skill override file exists at
`~/.ai/skills/{skill-name}-personal/SKILL.md` and apply it too. For example, for the
`woocommerce-backend-dev` skill, check for `~/.ai/skills/woocommerce-backend-dev-personal/SKILL.md`.
**Personal override skills take precedence over project-level skills in case of conflict**.

## Project Architecture

### Directory Structure

```text
plugins/woocommerce/
├── src/                    # Modern PHP code (PSR-4, DI container)
│   ├── Internal/           # Internal classes (default location)
│   └── [Public classes]    # Public API classes
├── includes/               # Legacy WordPress code
│   └── class-woocommerce.php  # Main plugin class
├── tests/php/              # PHPUnit tests
│   ├── includes/           # Tests for legacy code
│   └── src/                # Tests for modern code
└── client/                 # Frontend applications
    └── admin/              # Admin React app
```

### Key Architectural Concepts

**Modern vs Legacy Code:**

- `src/` - Modern PHP with dependency injection, PSR-4 autoloading
- `includes/` - Legacy WordPress patterns, modify only when necessary

**Namespace:**

- Root namespace: `Automattic\WooCommerce`
- Internal classes: `Automattic\WooCommerce\Internal\*`

**Dependency Injection:**

- Classes in `src/` use DI container (`$container->get()`)
- Dependencies injected via `init()` method

**Version Management:**

- Current version in `includes/class-woocommerce.php` → `$version` property
- Used for `@since` annotations (remove `-dev` suffix)

## Development Workflow

1. Make code changes
2. Run relevant tests (see `woocommerce-dev-cycle` skill)
3. Run linting (see `woocommerce-dev-cycle` skill)
4. Commit only after tests pass and linting is clean
5. Create changelog entries for each affected package
6. Create PR only after changelog entries exist

**NEVER create a PR without changelog entries.** Each package modified in the monorepo requires its own changelog entry. Run for each affected package:

```sh
pnpm --filter=<project> changelog add
```

Example for WooCommerce Core:

```sh
pnpm --filter=@woocommerce/plugin-woocommerce changelog add
```

This command prompts for the change type and description. Run it once per affected package before creating any PR.

## Testing Environment

- PHP tests run in Docker via `wp-env`
- WordPress and WooCommerce auto-installed
- Uses PHPUnit 9.6.24 with PHP 8.1

For detailed test commands, see `woocommerce-dev-cycle` skill.

## Known Constraints

- `includes/` directory changes should be minimal (legacy code)
- All new backend code goes in `src/Internal/` by default
- Never create standalone functions (always use class methods)
- Tests require Docker environment

## Quick Reference

### Most Common Commands

```sh
# Run specific test class
pnpm test:php:env -- --filter TestClassName

# Lint changed files
pnpm lint:php:changes

# Fix linting issues
pnpm lint:php:fix -- path/to/file.php
```

For complete command reference and workflows, see `woocommerce-dev-cycle` skill.

## Monorepo Context

This is part of the WooCommerce monorepo:

- Multiple packages managed with pnpm workspaces
- Root-level scripts coordinate across packages
- Some dependencies shared across packages

## Historical Context

**Why two code styles?** The `includes/` directory predates modern PHP practices. New code uses PSR-4 and dependency injection in `src/`.

**Why DI container?** Improves testability and maintainability compared to legacy global state patterns.

## Automated Code Reviews

For code review standards and critical violations to flag, use the **`woocommerce-code-review` skill**.

## Notes for Claude

- This doc provides context; skills provide procedures
- When in doubt about HOW to do something, check the skills
- When in doubt about WHAT something is or WHERE it fits, check this doc
- Skills are invoked automatically when relevant to the task
