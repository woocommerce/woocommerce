# WooCommerce themes

Themes live in `themes/<slug>/` and are versioned independently of WooCommerce Core. Shared development tooling lives in the private `@woocommerce/themes` workspace package, with one shared Composer manifest in `themes/`. Individual themes are ordinary WordPress theme directories and do not need a `package.json` or `composer.json`.

## Purple provenance

Purple 1.0.3 was imported from [woocommerce/woo-themes](https://github.com/woocommerce/woo-themes/tree/96ef2b9af550f11275c0febb12e9b8dada23569e/purple), commit `96ef2b9af550f11275c0febb12e9b8dada23569e`. Runtime behavior, assets, and license notices are preserved. A missing return docblock annotation was added to satisfy monorepo PHPStan checks, and inherited trailing whitespace was normalized. Development now happens in this monorepo; there is no automatic synchronization with the original repository.

## Local development

Use the monorepo's Node and pnpm versions. Docker, PHP, and Composer are also required.

Run these commands from the repository root:

```bash
pnpm install
composer install --working-dir=themes
pnpm --filter=@woocommerce/themes env:start
pnpm --filter=@woocommerce/themes wp-env run cli wp plugin activate woocommerce
pnpm --filter=@woocommerce/themes wp-env run cli wp theme activate purple
```

Open <http://localhost:8890>. The default wp-env credentials are `admin` / `password`. This environment installs WooCommerce from WordPress.org and has separate containers and data from `pnpm wc:env` on port 8888. Port 8891 is reserved for the test environment, which is disabled by default.

Theme files are mounted directly, so editing Purple does not require a theme build. Local wp-env overrides can go in the ignored `themes/.wp-env.override.json`. To work against the local WooCommerce checkout, run `pnpm wc:build` and override `plugins` with `[ "../plugins/woocommerce" ]`; use `pnpm wc:watch` in another terminal for WooCommerce asset changes.

```bash
pnpm --filter=@woocommerce/themes env:stop
```

## Validation

```bash
pnpm --filter=@woocommerce/themes validate purple
pnpm --filter=@woocommerce/themes lint:php purple
pnpm --filter=@woocommerce/themes lint
pnpm --filter=@woocommerce/themes changelog add
```

Validation checks metadata, matching versions, theme JSON and style variations against the minimum supported WordPress schema, pattern references, and asset paths. Schema validation requires access to `schemas.wp.org`. PHP linting uses `themes/phpcs.xml.dist` and the shared Composer dependencies, selecting the theme's text domain and minimum WordPress version at runtime.

Changelog entries for this package live in `themes/changelog/`, with shared types and versioning from `themes/composer.json`.

CI checks PHP syntax on PHP 7.4, validates source and changelogs, runs coding standards, and builds an installable ZIP. It discovers themes automatically and selects affected themes from the Git diff. Shared tooling, dependency configuration, packages, or WooCommerce changes select all themes. Automated test suites are deferred; manually verify storefront pages after installing the ZIP.

## Packaging

Supply a full commit SHA that contains the theme:

```bash
pnpm --filter=@woocommerce/themes build:zip purple "$(git rev-parse HEAD)"
```

The archive is written to `themes/artifacts/purple-<version>.zip`, with one `purple/` directory. An optional final argument selects a different output directory, relative to `themes/`.

Packaging reads committed files only; uncommitted changes are excluded. Tests, manifests, development configuration, dependencies, and changelog fragments are excluded. Asset license notices and `readme.txt` remain in the archive. Install it through Appearance → Themes → Add New Theme → Upload Theme on a separate WordPress site. To test the archive in the dedicated environment, extract it and replace the source mount in `themes/.wp-env.override.json`:

```bash
unzip themes/artifacts/purple-1.0.3.zip -d themes/artifacts
```

```json
{
    "themes": [
        "./artifacts/purple"
    ]
}
```

```bash
pnpm --filter=@woocommerce/themes env:start --update
```

Remove the override and run `env:start --update` again to return to source development. Avoid force-installing over a source-mounted theme because WordPress would replace files in the checkout.

Release preparation, version bumps, tags, and publishing automation are deferred. Keep a theme's `style.css` version and `readme.txt` stable tag aligned. Shared package changelog fragments track changes independently of WooCommerce Core; the import does not bump Purple's version or rewrite its existing release history.

## Adding a theme

1. Add a standalone block theme under `themes/<slug>/`, preserving its licenses and using the slug as its text domain.
2. Register the theme in `themes/.wp-env.json`. Dependencies, coding standards, and changelog types are shared through the parent package; no theme-specific development configuration is needed.
3. Run validation, PHP linting, and ZIP installation checks. Manually verify the shop, product, cart, checkout, and Site Editor screens, and add a changelog fragment for theme changes.

CI discovers direct child directories containing `style.css` and `theme.json`; adding another theme does not require editing a hardcoded CI theme matrix.
