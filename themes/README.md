# WooCommerce themes

Themes live in `themes/<slug>/` and are versioned independently of WooCommerce Core. Each theme is its own private pnpm workspace package, with its own version, commands, changelog, and release lifecycle. Shared development tooling lives in the private `@woocommerce/theme-cli` workspace package, with shared Composer dependencies in `tools/theme-cli/`. Individual themes keep their development manifests out of installable ZIPs.

## Setup

Use the monorepo's Node and pnpm versions. PHP and Composer are also required.

Run these commands from the repository root:

```bash
pnpm install
composer install --working-dir=tools/theme-cli
```

## Validation

```bash
pnpm --filter=@woocommerce/theme-cli validate <slug>
pnpm --filter=@woocommerce/theme-cli lint:php <slug>
pnpm --filter=@woocommerce/theme-cli lint
pnpm --filter=@woocommerce/theme-cli changelog add
```

Validation checks metadata, matching versions, theme JSON and style variations against the minimum supported WordPress schema, pattern references, and asset paths. Schema validation requires access to `schemas.wp.org`. PHP linting uses `tools/theme-cli/phpcs.xml.dist` and the shared Composer dependencies, selecting the theme's text domain and minimum WordPress version at runtime.

Changelog entries for shared tooling live in `tools/theme-cli/changelog/`, with shared types and versioning from `tools/theme-cli/composer.json`.

## Local environments

The shared CLI starts an environment for the selected theme from `tools/theme-cli/`. For example, once a theme is added:

```bash
pnpm --filter=@woocommerce/theme-cli wp-env start <slug>
pnpm --filter=@woocommerce/theme-cli wp-env stop <slug>
```

The CLI owns the wp-env dependency and forwards its arguments. Start uses Playground by default; pass `--runtime=docker` to use Docker.

## Adding a theme

Add a standalone block theme under `themes/<slug>/`, preserving its licenses and using the slug as its text domain. Run validation and PHP linting with that slug, and add a changelog fragment to that theme's `changelog/` directory. Register the theme explicitly in `pnpm-workspace.yaml`; a wildcard is not required.

