# WooCommerce Monorepo - AI Agents Documentation

## Project Overview

WooCommerce is a WordPress e-commerce plugin organized as a monorepo with:

- **Backend**: PHP in `plugins/woocommerce/` (PSR-4, WordPress standards)
- **Admin Client**: React/TypeScript in `plugins/woocommerce/client/admin/`
- **Payment Settings**: Specialized React/TypeScript module in `plugins/woocommerce/client/admin/client/settings-payments/`

## Navigation Guide

### Which Documentation to Use?

**Use this doc when:**

- Getting oriented in the project
- Understanding overall architecture
- Finding the right documentation or skill

## Available Skills

The `.ai/skills/` directory contains procedural HOW-TO instructions:

- **`woocommerce-backend-dev`** - Backend PHP conventions and unit tests. **Invoke before writing any PHP test files.**
- **`woocommerce-dev-cycle`** - Testing and linting workflows (PHP, JS, markdown)
- **`woocommerce-local-env`** - Local environment setup, wp-env commands, and WooCommerce build watchers
- **`woocommerce-copy-guidelines`** - UI text standards (sentence case rules)
- **`woocommerce-code-review`** - Code review standards and critical violations to flag
- **`woocommerce-markdown`** - Markdown writing and editing guidelines
- **`woocommerce-git-commit`** - Commit changes with conventional messages and smart grouping
- **`woocommerce-git-draft-pr`** - Create draft PRs with proper template, changelog, and milestone handling
- **`woocommerce-email-editor`** - Email editor development setup and Mailpit configuration
- **`woocommerce-performance`** - Performance guardrails. **Invoke when writing or reviewing PHP code.**

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

- `plugins/woocommerce/src/` - Modern PHP with dependency injection, PSR-4 autoloading
- `plugins/woocommerce/includes/` - Legacy WordPress patterns, modify only when necessary

**Namespace:**

- Root namespace: `Automattic\WooCommerce`
- Internal classes: `Automattic\WooCommerce\Internal\*`

**Dependency Injection:**

- Classes in `plugins/woocommerce/src/` use DI container (`$container->get()`)
- Dependencies injected via `init()` method

**Version Management:**

- Current version in `plugins/woocommerce/includes/class-woocommerce.php` → `$version` property
- Used for `@since` annotations (remove `-dev` suffix)
- When changing template files (PHP files used to display UI on the front-end) the version in their header should be updated to the current version, without the `-dev` suffix.

## Development Workflow

1. Make code changes
2. Run relevant tests (see `woocommerce-dev-cycle` skill)
3. Run linting (see `woocommerce-dev-cycle` skill)
4. Run PHPStan for PHP changes (see below)
5. Commit only after tests pass and all checks are clean
6. Create changelog entries for each affected package
7. Create PR only after changelog entries exist

### Pre-commit Checks

**Before committing PHP changes**, run these checks to avoid CI failures:

```sh
# Lint changed PHP files
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes

# Run PHPStan on modified files (from plugins/woocommerce directory)
composer exec -- phpstan analyse path/to/modified/File.php --memory-limit=2G
```

**PHPStan Baseline Policy:** The baseline file (`phpstan-baseline.neon`) must never be added to. It should only shrink over time as existing errors are naturally resolved by code changes. If PHPStan reports a new error, fix it in the code rather than adding it to the baseline. If your fix resolves a previously baselined error, remove the corresponding entry from the baseline.

### Pre-push Checks

**Before pushing**, run the branch-level lint to catch issues across all commits on the branch (e.g. alignment warnings that per-file linting misses):

```sh
pnpm --filter=@woocommerce/plugin-woocommerce lint:changes:branch
```

This compares the full branch diff against trunk and runs `phpcs-changed` on it. Fix any warnings before pushing.

**NEVER create a PR without changelog entries.** Each package modified in the monorepo requires its own changelog entry. Run for each affected package:

```sh
pnpm --filter=<project> changelog add
```

Example for WooCommerce Core:

```sh
pnpm --filter=@woocommerce/plugin-woocommerce changelog add
```

This command prompts for the change type and description. Run it once per affected package before creating any PR.

### Pull Request Template

When creating PRs, **always use the template** from `.github/PULL_REQUEST_TEMPLATE.md`. Key sections:

- **Submission Review Guidelines**: Checkboxes confirming adherence to contributing guidelines
- **Changes proposed in this Pull Request**: Description of changes and link to bug-introducing PR if applicable
- **Screenshots or screen recordings**: UI changes screenshots (can be removed if not applicable)
- **How to test the changes in this Pull Request**: Step-by-step testing instructions
- **Testing that has already taken place**: What testing you've done
- **Milestone**: Check the box to auto-assign milestone, or manually set to the first available milestone that is not in the past (unless otherwise specified)
- **Changelog entry**: Note if changelog was created manually or check box to auto-create

For bug fixes, always reference the PR that introduced the bug using: `Bug introduced in PR #XXXXX.`

## Testing Environment

- PHP tests run in Docker via `wp-env`
- WordPress and WooCommerce auto-installed
- Uses PHPUnit 9.6.24 with PHP 8.1

For detailed test commands, see `woocommerce-dev-cycle` skill.

## Known Constraints

- `includes/` directory changes should be minimal (legacy code)
- All new backend code goes in `plugins/woocommerce/src/Internal/` by default
- Never create standalone functions (always use class methods)
- Tests require Docker environment

## Block Development

### New Block-Library Blocks

New blocks that are built by the block-library `wp-build` package should live under:

```text
plugins/woocommerce/client/blocks/packages/block-library/src/
├── index.tsx
└── <block-name>/
    ├── block.json
    ├── index.tsx
    ├── edit.tsx
    ├── index.php
    └── test/
```

Use `plugins/woocommerce/client/blocks/packages/block-library/src/<block-name>/` as the block's source of truth. The build copies this package output to `plugins/woocommerce/assets/client/blocks/<block-name>/` and includes the block metadata in `plugins/woocommerce/assets/client/blocks/blocks-json.php`.

Server-rendered blocks in this package should register themselves from their block folder PHP file:

- Put the render callback and `register_block_type_from_metadata( __DIR__, ... )` call in `<block-name>/index.php`.
- Do not rely on `"render": "file:./index.php"` in `block.json` for blocks using this PHP self-registration pattern.
- `Automattic\WooCommerce\Internal\Blocks\BlockLibraryRegistry` loads generated block-library PHP files and registers the generated metadata collection with `wp_register_block_metadata_collection()`.
- Do not add new block-library package blocks to `BlockTypesController` block classes. That controller is for the legacy PHP block type class registration path.

Keep tests close to the block when they only exercise that block:

- JavaScript/TypeScript block unit tests can live in `<block-name>/test/`, for example `<block-name>/test/block.ts`.
- PHP unit tests for block-specific server rendering can also live in `<block-name>/test/` when the relevant PHPUnit runner discovers that path. If the current runner only covers `plugins/woocommerce/tests/php`, add or mirror integration coverage there.

Existing blocks may still live under `plugins/woocommerce/client/blocks/assets/js/blocks/` while they are migrated. Follow the local folder pattern for existing blocks, but prefer the `packages/block-library/src/` structure for new wp-build block-library work.

### Block-Library `wp-build` JavaScript Boundaries

Treat `plugins/woocommerce/client/blocks/packages/block-library/src/` as a `wp-build`-safe package boundary. It is bundled by `@wordpress/build`/esbuild, not the legacy Blocks webpack build.

When migrating blocks into this package:

- Avoid imports that pull legacy `assets/js` barrels or webpack-only files.
- Avoid broad aliases such as `@woocommerce/base-hooks`, `@woocommerce/base-components`, `@woocommerce/shared-hocs`, `@woocommerce/resource-previews`, and root `@woocommerce/atomic-utils`.
- Prefer small local helpers under `packages/block-library/src/shared/` when a legacy helper is needed.
- Enforce exceptions through `packages/block-library/.eslintrc.js`.
- Do not fix boundary failures by adding broad JSX or SCSS support to wp-build.

### `block.json` Attribute Defaults

Never include styling options such as `fontSize`, `borderColor`, `textColor`... as block attributes. They should only be listed under `supports`.

Do not add `default` values to block attributes in `block.json`.

- Default attribute values can be indistinguishable from missing attributes when parsed, especially when the default value is not serialized into saved block markup.
- Defaults can create subtle conflicts with `theme.json`, block supports, editor controls, deprecations, and migrations.
- During implementation or review, flag any newly inserted `default` in `block.json`.

## Interactivity API Stores

Most WooCommerce Interactivity API stores are **private by design**. Exception: the `woocommerce/product-filters` store is public for Product Filters inner-block extensibility.

For private stores:

- Not intended for third-party extension
- Removing or changing store state/selectors is **not a breaking change**
- No backwards compatibility is required for store internals
- If another store needs to be extensible in the future, it will be split into private (internal) and public (API) stores
- General stores (namespace `woocommerce`) may become public eventually, but currently remain private

Reference: [WordPress Interactivity API - Private Stores](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/api-reference#private-stores)

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

**Why two code styles?** The `plugins/woocommerce/includes/` directory predates modern PHP practices. New code uses PSR-4 and dependency injection in `plugins/woocommerce/src/`.

**Why DI container?** Improves testability and maintainability compared to legacy global state patterns.

## Automated Code Reviews

For code review standards and critical violations to flag, use the **`woocommerce-code-review` skill**.

## Notes for AI Agents

- This doc provides context; skills provide procedures
- When in doubt about HOW to do something, check the skills
- When in doubt about WHAT something is or WHERE it fits, check this doc
- Skills are invoked automatically when relevant to the task
