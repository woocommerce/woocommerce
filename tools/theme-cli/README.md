# WooCommerce theme CLI

`@woocommerce/theme-cli` provides shared development tooling for WooCommerce themes in `themes/<slug>/`. Themes are versioned independently of WooCommerce Core. Each theme is its own private pnpm workspace package, with its own version, commands, changelog, and release lifecycle. Shared development tooling lives in the private `@woocommerce/theme-cli` workspace package, with shared Composer dependencies in `tools/theme-cli/`. Individual themes keep their development manifests out of installable ZIPs.

## Setup

Use the monorepo's Node and pnpm versions. Validation runs WordPress Theme Check in Docker, so Docker must be running. PHP and Composer are only needed for changelog commands.

Run these commands from the repository root:

```bash
pnpm install

# Only needed for changelog commands.
composer install --working-dir=tools/theme-cli
```

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

The `validate` command runs `wp-scripts lint-js`, `wp-scripts lint-style`, and `wp-scripts lint-md-docs` for the selected theme, then invokes the official [WordPress Theme Check](https://github.com/WordPress/theme-check#usage-with-wp-cli) command, `wp theme-check run <slug>`. These are its only checks. The lint commands use the repository's shared ESLint, Stylelint, and markdownlint configurations. Theme JavaScript uses the theme slug as its translation text domain. Any lint or Theme Check failure makes validation fail. When checking all themes, it continues through the remaining themes if one fails. Themes can expose their own `validate` shortcut to the shared command.

The lint commands run from the selected theme's directory in `validate-theme.mjs`, using wp-scripts' default file matching and exclusions. The shared Prettier configuration remains available for editor formatting, including HTML under each theme's `templates/` and `parts/` directories. Embedded CSS stays unchanged to preserve serialized block styles. `--fix` applies supported ESLint, Stylelint, and markdownlint fixes to the selected theme.

Validation stages the current theme files using the same file exclusions as the installable ZIP, then starts a separate Docker WordPress environment derived from the shared `tools/theme-cli/.wp-env.json`. That configuration installs WooCommerce and the Theme Check plugin for both development and validation. Validation runs the official command and stops its environment afterward. Generated configuration, staged files, and cached downloads live under `tools/theme-cli/.wp-env/theme-check/`. Validation uses the shared ports (8890 by default), so stop the development environment before running it. Theme Check's exit status determines whether its check passes. The development environment keeps its existing runtime and data.

Changelog entries for shared tooling live in `tools/theme-cli/changelog/`, with shared types and versioning from `tools/theme-cli/composer.json`.

All themes use `tools/theme-cli/prettier.config.cjs` and the repository's root `stylelint.config.cjs` and `.markdownlint.json`. The shared Prettier config extends the WordPress preset and preserves embedded CSS in theme templates and parts. New themes inherit these rules automatically.

To enable validation in the monorepo's existing CI, register the theme's `validate` command in `package.json` under `config.ci.tests`, with `changes` set to `**/*` and `events` set to `[ "pull_request" ]`. CI then runs validation on pull requests when the theme or a workspace dependency such as `@woocommerce/theme-cli` changes. Lockfile changes select all packages. Markdown-only pull requests use the shared Markdown check. Root lint configuration changes do not automatically select theme validation jobs.

### Editor setup

Open the repository root and run `pnpm install`. In VS Code, install Prettier (`esbenp.prettier-vscode`), Stylelint (`stylelint.vscode-stylelint`), and markdownlint (`DavidAnson.vscode-markdownlint`). Prettier discovers `themes/.prettierrc.cjs` for files under `themes/`; that file loads the shared configuration from `tools/theme-cli/prettier.config.cjs`. Files outside `themes/` keep their existing configuration. Stylelint and markdownlint discover their root configurations; CLI scripts use the same rules.

For formatting and fixes on save, add the following to your workspace settings:

```json
{
	"[html]": {
		"editor.formatOnSave": true
	},
	"editor.codeActionsOnSave": {
		"source.fixAll.stylelint": "explicit",
		"source.fixAll.markdownlint": "explicit"
	},
	"stylelint.validate": [ "css", "scss" ]
}
```

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
