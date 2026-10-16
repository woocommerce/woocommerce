# WooCommerce theme CLI

`@woocommerce/theme-cli` provides shared development tooling for WooCommerce themes in `themes/<slug>/`. Themes are versioned independently of WooCommerce Core. Each theme is its own private pnpm workspace package, with its own version, commands, changelog, and release lifecycle. Shared development tooling lives in the private `@woocommerce/theme-cli` workspace package, with shared Composer dependencies in `tools/theme-cli/`.

## Setup

Use the monorepo's Node and pnpm versions. PHP and Composer are required for PHP validation and changelog commands. Validation runs WordPress Theme Check in Docker, so Docker must be running.

Run these commands from the repository root:

```bash
pnpm install
```

The theme CLI's postinstall command installs its shared Composer dependencies, including PHPCS and WordPress Coding Standards.

## Validation

Run these commands from the repository root:

```bash
# Lint and validate one theme.
pnpm --filter=@woocommerce/theme-cli validate purple

# Fix formatting and supported lint issues, then validate.
pnpm --filter=@woocommerce/theme-cli validate purple --fix

# Lint and validate every theme under themes/.
pnpm --filter=@woocommerce/theme-cli validate --all
pnpm --filter=@woocommerce/theme-cli validate --all --fix
```


Validation runs from the theme directory and discovers its ESLint, Stylelint, and Markdownlint configuration. Each theme's lint and Prettier configurations inherit defaults directly from the repository root. ESLint uses the theme's Prettier configuration for JavaScript formatting. Keep theme-specific settings, such as the text domain and block template formatting, in the theme's configuration files so editors and validation use the same settings.

PHP validation runs the theme's `composer run-script phpcs` command, which uses its `phpcs.xml.dist` ruleset with the shared PHPCS installation. Purple's ruleset follows its [existing WordPress Coding Standards configuration](https://github.com/woocommerce/woo-themes/blob/trunk/purple/phpcs.xml.dist). With `--fix`, `composer run-script phpcbf` applies supported fixes before PHPCS checks the result.

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
pnpm --filter=@woocommerce/theme-cli build <slug>
```

The command creates the artifacts directory and moves the ZIP to `themes/<slug>/artifacts/<slug>.zip`. Builds include current working files, including uncommitted changes.

Theme content checks belong to `validate`; `build` creates the ZIP with wp-scripts. ZIP generation does not publish a release.

## Adding a theme

Add a standalone block theme under `themes/<slug>/`, preserving its licenses and using the slug as its text domain. Add `eslint.config.mjs`, `stylelint.config.mjs`, `.markdownlint.json`, and `prettier.config.mjs` that inherit the repository root configurations. Add `phpcs.xml.dist` and Composer scripts for `phpcs` and `phpcbf`, following Purple's example and setting the theme's text domain and minimum WordPress version. Run validation with that slug, and add a changelog fragment to that theme's `changelog/` directory. Register the theme explicitly in `pnpm-workspace.yaml`; a wildcard is not required.
