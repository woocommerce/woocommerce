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

**JavaScript:**

- Prefer vanilla JavaScript/TypeScript over jQuery for new or modified code. Keep existing jQuery when a rewrite is out of scope.

## Development Workflow

1. Make code changes
2. Run relevant tests (see `woocommerce-dev-cycle` skill)
3. Run linting and fix all errors and warnings before committing (see Pre-commit Checks)
4. Run PHPStan for PHP changes (see below)
5. Commit only after tests pass and all checks are clean
6. Create changelog entries for each affected package
7. Create PR only after changelog entries exist

### Pre-commit Checks

**Before committing PHP changes**, run both lint commands and fix what they report:

```sh
# Lint the changed PHP files
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes

# Lint the full branch diff (phpcs-changed — catches warnings the per-file pass can miss across commits)
pnpm --filter=@woocommerce/plugin-woocommerce lint:changes:branch
```

Fix every `phpcs` **error and warning** before committing — the CI **Lint** job treats warnings as failures, so an unaddressed warning turns the check red. If a finding is intentionally suppressed, add a brief inline justification. Re-run `lint:changes:branch` after any commit rewrite, since it compares the whole branch against trunk.

Also run PHPStan on modified PHP files (from the `plugins/woocommerce` directory):

```sh
composer exec -- phpstan analyse path/to/modified/File.php --memory-limit=2G
```

**PHPStan Baseline Policy:** The baseline file (`phpstan-baseline.neon`) must never be added to. It should only shrink over time as existing errors are naturally resolved by code changes. If PHPStan reports a new error, fix it in the code rather than adding it to the baseline. If your fix resolves a previously baselined error, remove the corresponding entry from the baseline.

### Changelog Entries

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

## Backward Compatibility

Any change to a **public or externally exposed** class, interface, function, or method signature is **high-risk** and **must state its backward-compatibility impact in the PR description** — regardless of whether the symbol lives in the `Internal` namespace. The `Internal` namespace is not a guarantee that a symbol is safe to change: third-party code implements and consumes some of these contracts in practice (for example, the WooCommerce Stripe Gateway implements `Internal\ProductFeed\Feed\FeedInterface`).

Treat a symbol as **externally exposed** when it is implemented or consumed outside `plugins/woocommerce/` — by extensions, other plugins, or themes — even if it lives under `Internal`. When in doubt, assume it is exposed and state the BC impact.

**Adding a method to an interface that external code can implement must be flagged explicitly.** It is a backward-incompatible change: existing implementers fatal on load because they no longer satisfy the contract. Likewise, **removing a required method from an interface is breaking** for existing implementers (they carry a now-dead method, which static analysis such as PHPStan will flag). Prefer a non-breaking alternative — add the method to the concrete class rather than the interface, introduce a separate new interface, or supply a default implementation via an abstract base class.

**Deprecate, don't rename.** For existing public symbols (classes, interfaces, methods, constants, hooks), never rename or remove them in place. Mark the old symbol `@deprecated`, introduce the replacement alongside it, and keep both working through a deprecation window so external consumers have time to migrate.

> This rule exists because WooCommerce 10.9.0 was reverted on WP Cloud: PR #64394 added a required `get_entry_count(): int` method to `FeedInterface`, fataling older WooCommerce Stripe Gateway versions that implement it. Fixed in PR #65965.

### The compatibility surface is wider than PHP signatures

WordPress exposes more contracts than class and function signatures. The following are equally binding: a change to any of them is **high-risk** and requires the same backward-compatibility impact statement in the PR description.

**Hooks and filters are public contracts.** Every `do_action` and `apply_filters` call is an interface that third-party callbacks depend on. Removing a hook, renaming it, or removing/reordering its arguments breaks every attached callback. Changing *when* or *whether* a hook fires can break consumers that depend on its timing. Additive is the safe path: append new arguments at the end, never remove or reorder existing ones. To retire a hook, fire it through `do_action_deprecated()` / `apply_filters_deprecated()` for a deprecation window instead of deleting it.

**Do not assume global state.** Code can run in admin, REST, CLI, cron, webhook, and front-end contexts, and not all of them set the globals a front-end request does (`$post`, `$wp_query`, an initialized session or cart). A newly introduced read of a global, or of `WC()->…` state, in a path reachable outside a standard request is a fatal or a silent misbehavior in the contexts that do not set it. Guard the exact dependency explicitly: use `function_exists`/`class_exists` for symbols, `isset` for variables, `did_action` for lifecycle state, and verify that `WC()` and the required component are initialized before dereferencing `WC()->…`.

**Do not assume single-site.** Multisite changes where data lives: site-scoped vs network-scoped options (`get_option` vs `get_site_option`), per-site tables, user roles and capabilities, and upload paths all differ. A change that reads or writes site state must state in its PR whether it behaves correctly under multisite — and if it was not tested there, say so explicitly.

**Do not assume install layout.** WordPress could be configured to run in a subdirectory, with relocated `wp-content`, and behind reverse proxies. Never build paths or URLs by concatenation from the domain root; derive them (`plugins_url()`, `plugin_dir_path()`, `wp_upload_dir()`, and mind the `home_url()` vs `site_url()` distinction). A path that works on a root install and breaks elsewhere is a compatibility bug, not an edge case.

### Before changing any public or externally exposed surface (agent checklist)

1. Identify the contract you are touching: signature, hook, global/scope expectation, site topology, or install layout.
2. Assume unseen consumers. You cannot enumerate third-party code; if the surface is reachable from outside this plugin, someone consumes it.
3. Prefer the additive path (new optional method, appended hook argument, new symbol + deprecation) over changing what exists.
4. State the impact in the PR description: what changed, who could consume it, and why it is safe or what the deprecation path is.
5. If you cannot establish the impact, stop and flag it to the user as needing review.

## Block Development

### `block.json` Attribute Defaults

Never include styling options such as `fontSize`, `borderColor`, `textColor`... as block attributes. They should only be listed under `supports`.

Do not add `default` values to block attributes in `block.json`.

- Default attribute values can be indistinguishable from missing attributes when parsed, especially when the default value is not serialized into saved block markup.
- Defaults can create subtle conflicts with `theme.json`, block supports, editor controls, deprecations, and migrations.
- During implementation or review, flag any newly inserted `default` in `block.json`.

### `block.json` Field Types

Consult the [Gutenberg Block Metadata reference](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/) as the source of truth for `block.json` field types. Several fields (`style`, `editorStyle`, `script`, `viewScript`) accept `string | string[]`, so code that reads them must handle both shapes — don't assume a string.

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
