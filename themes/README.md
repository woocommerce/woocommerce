# WooCommerce themes

Themes live in `themes/<slug>/` and are versioned independently of WooCommerce Core. Shared development tooling lives in the private `@woocommerce/theme-cli` workspace package, with shared Composer dependencies in `tools/theme-cli/`. Each theme is a private workspace package with its own version, commands, Composer changelog configuration, and release lifecycle.

## Purple provenance

Purple 1.0.3 was imported from [woocommerce/woo-themes](https://github.com/woocommerce/woo-themes/tree/96ef2b9af550f11275c0febb12e9b8dada23569e/purple), commit `96ef2b9af550f11275c0febb12e9b8dada23569e`. Runtime behavior, assets, and license notices are preserved. A missing return docblock annotation was added to satisfy monorepo PHPStan checks, and inherited trailing whitespace was normalized. Development now happens in this monorepo; there is no automatic synchronization with the original repository.

## Local development

Use the monorepo's Node and pnpm versions. PHP and Composer are required for development checks. The default Playground environment does not require Docker. Docker is optional for MySQL and wp-env CLI commands.

Run these commands from the repository root:

```bash
pnpm install
composer install --working-dir=tools/theme-cli
composer install --working-dir=themes/purple
pnpm --filter=@woocommerce/theme-purple env:start
```

Open <http://localhost:8890/wp-admin/> and activate Purple under Appearance > Themes. WooCommerce is installed automatically. The default wp-env credentials are `admin` / `password`. This environment uses Playground with SQLite and installs WooCommerce from WordPress.org. It is separate from `pnpm wc:env` on port 8888. Port 8891 is reserved for the test environment, which is disabled by default.

Theme files are mounted directly, so editing Purple does not require a theme build. Local wp-env overrides can go in the ignored `themes/purple/.wp-env.override.json`. To work against the local WooCommerce checkout, run `pnpm wc:build` and override `plugins` with `[ "../../plugins/woocommerce" ]`; use `pnpm wc:watch` in another terminal for WooCommerce asset changes.

The shared command is `pnpm --filter=@woocommerce/theme-cli wp-env start purple`; the theme package supplies a shortcut. Pass `--runtime=docker` to the shared command when you need Docker, MySQL, or `wp-env run`. See [Purple's Playground instructions](purple/README.md#playground-environment) for runtime switching and preserving existing environment data.

```bash
pnpm --filter=@woocommerce/theme-purple env:stop
```

## Validation

```bash
pnpm --filter=@woocommerce/theme-purple validate
pnpm --filter=@woocommerce/theme-purple lint:php
pnpm --filter=@woocommerce/theme-purple lint
pnpm --filter=@woocommerce/theme-purple changelog add
```

Validation checks metadata, matching versions, theme JSON and style variations against the minimum supported WordPress schema, pattern references, and asset paths. Schema validation requires access to `schemas.wp.org`. PHP linting uses `tools/theme-cli/phpcs.xml.dist` and the shared Composer dependencies, selecting the theme's text domain and minimum WordPress version at runtime.

Theme changelog entries live in `themes/<slug>/changelog/`, with release configuration in that theme's `composer.json`. Shared tooling entries live separately in `tools/theme-cli/changelog/`. Purple's package, `style.css`, and `readme.txt` versions must match. See [Purple release instructions](purple/README.md).

## Adding a theme

1. Add a standalone block theme under `themes/<slug>/`, preserving its licenses and using the slug as its text domain.
2. Add a private package with its own version and commands, Composer changelog configuration, and local environment. Register its exact directory in `pnpm-workspace.yaml`; a `themes/*` wildcard is not required. Validation and PHP linting delegate to `@woocommerce/theme-cli`.
3. Run validation and PHP linting. Manually verify the shop, product, cart, checkout, and Site Editor screens, and add a changelog fragment for theme changes.

ZIP packaging, CI, automated test suites, and release publishing are deferred. Keep a theme's `style.css` version and `readme.txt` stable tag aligned. Each theme tracks its changes and releases independently of WooCommerce Core; the import does not bump Purple's version or rewrite its existing release history.
