# Codebase Structure

**Analysis Date:** 2026-02-02

## Directory Layout

```
woocommerce/
├── .github/         # GitHub workflows, templates, and configuration
├── bin/             # Build scripts and utilities
├── docs/            # Project documentation
├── packages/        # Shared packages (monorepo)
│   ├── js/          # JavaScript/TypeScript packages
│   └── php/         # PHP packages
├── plugins/         # WordPress plugins
│   ├── woocommerce/ # Main WooCommerce plugin
│   └── woocommerce-beta-tester/
├── tools/           # Development and build tools
└── .planning/       # Planning and architecture docs
```

## Directory Purposes

**plugins/woocommerce/:**

-   Purpose: Main WooCommerce plugin
-   Contains: All plugin code, assets, tests
-   Key files: `woocommerce.php`, `composer.json`, `package.json`

**plugins/woocommerce/src/:**

-   Purpose: Modern PHP code with PSR-4 autoloading
-   Contains: Services, controllers, internal APIs
-   Key files: `Container.php`, `Autoloader.php`, `README.md`

**plugins/woocommerce/includes/:**

-   Purpose: Legacy WordPress-style code
-   Contains: Traditional WP classes, functions, hooks
-   Key files: `class-woocommerce.php`, `wc-core-functions.php`

**plugins/woocommerce/client/:**

-   Purpose: Frontend applications
-   Contains: React apps for admin, blocks, legacy scripts
-   Key files: `admin/index.tsx`, webpack configs

**packages/js/:**

-   Purpose: Shared JavaScript/TypeScript packages
-   Contains: Reusable components, utilities, tools
-   Key files: Individual package.json files

**packages/php/:**

-   Purpose: Shared PHP packages
-   Contains: Libraries used across plugins
-   Key files: Composer packages

## Key File Locations

**Entry Points:**

-   `plugins/woocommerce/woocommerce.php`: Main plugin file
-   `plugins/woocommerce/client/admin/index.tsx`: Admin UI entry
-   `plugins/woocommerce/src/Blocks/BlockTypesController.php`: Blocks registration

**Configuration:**

-   `pnpm-workspace.yaml`: Monorepo workspace config
-   `plugins/woocommerce/composer.json`: PHP dependencies
-   `plugins/woocommerce/package.json`: JS dependencies
-   `.syncpackrc`: Package version synchronization

**Core Logic:**

-   `plugins/woocommerce/includes/class-woocommerce.php`: Main plugin class
-   `plugins/woocommerce/src/Container.php`: DI container
-   `plugins/woocommerce/src/Internal/`: Internal services

**Testing:**

-   `plugins/woocommerce/tests/`: Plugin tests
-   `plugins/woocommerce/tests/php/`: PHPUnit tests
-   `plugins/woocommerce/tests/e2e-pw/`: Playwright E2E tests

## Naming Conventions

**Files:**

-   PHP classes: `class-{name}.php` (legacy), `{Name}.php` (modern)
-   React components: `{ComponentName}.tsx`
-   Test files: `{name}.test.ts`, `{Name}Test.php`

**Directories:**

-   Modern PHP: PascalCase matching namespace
-   Legacy PHP: lowercase with hyphens
-   JavaScript: kebab-case for packages, PascalCase for components

## Where to Add New Code

**New Feature:**

-   Primary code: `plugins/woocommerce/src/Internal/{Feature}/`
-   Tests: `plugins/woocommerce/tests/php/src/Internal/{Feature}/`

**New Component/Module:**

-   Implementation: `plugins/woocommerce/client/admin/client/{module}/`

**Utilities:**

-   Shared helpers: `plugins/woocommerce/src/Utilities/`

**New Package:**

-   JavaScript: `packages/js/{package-name}/`
-   PHP: `packages/php/{package-name}/`

## Special Directories

**node_modules/:**

-   Purpose: npm/pnpm dependencies
-   Generated: Yes
-   Committed: No

**vendor/:**

-   Purpose: Composer dependencies
-   Generated: Yes
-   Committed: No (except in releases)

**build/:**

-   Purpose: Compiled JavaScript/CSS
-   Generated: Yes
-   Committed: Yes (for distribution)

**changelog/:**

-   Purpose: Unreleased changelog entries
-   Generated: No
-   Committed: Yes

---

_Structure analysis: 2026-02-02_
