# WooCommerce theme CLI

`@woocommerce/theme-cli` provides shared development tooling for WooCommerce themes in `themes/<slug>/`. Themes are versioned independently of WooCommerce Core. Each theme is its own private pnpm workspace package, with its own version, commands, changelog, and release lifecycle. Shared development tooling lives in the private `@woocommerce/theme-cli` workspace package, with shared Composer dependencies in `tools/theme-cli/`. Individual themes keep their development manifests out of installable ZIPs.

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
pnpm --filter=@woocommerce/theme-cli lint
pnpm --filter=@woocommerce/theme-cli changelog add
```

Validation checks metadata, matching versions, theme JSON and style variations against the minimum supported WordPress schema, pattern references, asset paths, and PHP coding standards. The command fails if either validation or PHP linting fails. Schema validation requires access to `schemas.wp.org`. PHP linting uses `tools/theme-cli/phpcs.xml.dist` and the shared Composer dependencies, selecting the theme's text domain and minimum WordPress version at runtime.

Changelog entries for shared tooling live in `tools/theme-cli/changelog/`, with shared types and versioning from `tools/theme-cli/composer.json`.

## Local environments

The shared CLI starts an environment for the selected theme. For example, once a theme is added:

```bash
pnpm --filter=@woocommerce/theme-cli wp-env start <slug>
pnpm --filter=@woocommerce/theme-cli wp-env stop <slug>
```

`@woocommerce/theme-cli` provides the wp-env command, dependency, and shared configuration in `tools/theme-cli/.wp-env.json`. The slug selects the theme to mount. The CLI runs wp-env from its own package, generating an ignored configuration in `tools/theme-cli/.wp-env/<slug>/.wp-env.json` to keep each theme's environment separate. Local overrides belong in `tools/theme-cli/.wp-env/<slug>/.wp-env.override.json`; relative paths are resolved from `tools/theme-cli/`. Start uses Playground by default; pass `--runtime=docker` to use Docker. Theme packages can expose shortcuts to these shared commands.

## Build a theme ZIP

Validate the theme first, then build its ZIP from the repository root. Replace `<slug>` with the theme directory name:

```bash
pnpm --filter=@woocommerce/theme-cli validate <slug>
pnpm --filter=@woocommerce/theme-cli build <slug>
```

This packages the current commit's theme files into `themes/<slug>/artifacts/<slug>-<version>.zip`, with a single `<slug>/` directory. The version comes from the committed `style.css`. Assets, licenses, and `readme.txt` are included; development manifests, dependencies, local configuration, changelog fragments, and development documentation are excluded. Uncommitted edits are excluded, so commit theme changes before building their ZIP.

Theme content checks belong to `validate`; `build` packages the committed files. ZIP generation does not publish a release.

## Adding a theme

Add a standalone block theme under `themes/<slug>/`, preserving its licenses and using the slug as its text domain. Run validation with that slug, and add a changelog fragment to that theme's `changelog/` directory. Register the theme explicitly in `pnpm-workspace.yaml`; a wildcard is not required.

