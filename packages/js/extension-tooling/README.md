# WooCommerce extension tooling

Install `@woocommerce/extension-tooling` as a development dependency in the extension.

## Extension configuration

The reader uses Bash and `jq` to validate `config.woo` in the extension's `package.json` and apply defaults. Run it from the extension root.

```sh
pnpm exec woo-extension-config
pnpm exec woo-extension-config '.zip.root'
```

Use `--github-output` to write the normalized JSON to `GITHUB_OUTPUT` under `json`. Use `--phpunit-matrix` to get the selected PHPUnit legs for the current extension. Set `SUITE=integration` for integration legs (the default is `unit`), or `SCHEDULED=true` to include scheduled legs. `REQUESTED_WP`, `REQUESTED_WC` and `REQUESTED_PHP` set version overrides, and `PREVIOUS_WP` supplies the version for `previous` WordPress legs.

To run the reader before installing dependencies, get `bin/woo-config.sh` and `bin/woo-config.schema.json` from the same reviewed commit of this package. Keep the files in the same directory and run `bash /path/to/woo-config.sh` from the extension root.

## Packaging and test commands

Run `pnpm exec woo-extension-tooling <command> [args]` from the extension root. Call the same command without `pnpm exec` inside package scripts. Existing build steps, ZIP excludes, hooks, and environment scripts stay in the extension.

| Command                                                    | Use                                                                                                                                                              |
| ---------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `build-zip [commit]`                                       | Run the configured build and write `deploy/<slug>.zip`. A commit argument selects committed files when the build step is empty.                                  |
| `makepot`                                                  | Generate `languages/<slug>.pot` from the extension's translation settings with WP-CLI.                                                                           |
| `e2e <mode> [args]`                                        | Keep the existing `prepare`, `start`, `setup`, `reset`, `run`, and `with-env` modes. `prepare` extracts the ZIP and calls the extension's `bin/link_plugins.js`. |
| `phpunit [setup\|run\|help] [args]`                        | Provision the wp-env test site or run its PHPUnit suite. Use `phpunit direct <config> [args]` for an extension that already provisions the site separately.      |
| `phplint [paths]`, `phpcs [args]`, `phpcs-baseline [args]` | Check PHP syntax, report PHPCS findings outside the baseline, or generate a baseline with the extension's Composer tools.                                        |
| `phpcompat [archive]`                                      | Check the supplied production ZIP, or run the extension's `build:zip` script first.                                                                              |

Choose a single folder name for `config.woo.zip.root`. When `build-zip` receives a commit, it runs that commit's staging hook from the archived source directory. The checkout's `package.json` must match the selected commit. If it differs, check out the commit and run `build-zip` there.

`e2e setup` uses the extension's `build:zip` script unless `E2E_PLUGIN_ZIP` supplies a ZIP. It calls `bin/link_plugins.js` to write `.wp-env.e2e.json`, `wp-env:e2e` to manage the site, and `env:test:e2e:seed` to seed it. `e2e with-env` also calls `test:e2e` to run the suite.

`phpcs-baseline` uses the scan paths set by the extension's PHPCS ruleset. Declare at least one `<file>` entry in the ruleset.

For hosts with Composer and no Node.js, check out this package's `bin` directory from a reviewed WooCommerce commit and run its Bash files directly. Install the tools used by the selected command, such as `jq` for configuration, `rsync` and `zip` for packaging, `unzip` for ZIP checks, `git` for syntax checks, and WP-CLI for POT generation. Keep `woo-config.sh` and `woo-config.schema.json` alongside the commands that read extension configuration. For example, from the extension root:

```sh
bash "$EXTENSION_TOOLING_BIN/install.sh" wordpress_test root root 127.0.0.1 7.0 11.2.0
bash "$EXTENSION_TOOLING_BIN/phplint.sh" .
bash "$EXTENSION_TOOLING_BIN/phpcs.sh" .
bash "$EXTENSION_TOOLING_BIN/phpcompat.sh" deploy/example-extension.zip
```

Set `EXTENSION_TOOLING_BIN` to the absolute path of that `bin` directory. The installer uses Git, Subversion, curl or wget, and a MySQL client to reset and provision the selected test database and directories. It accepts a numeric WordPress release, `latest`, or `nightly`, and a WooCommerce tag, `latest`, or `trunk`. WordPress `nightly` tracks trunk. WordPress `latest` uses the same resolved release for the core and test library. The installer resolves relative `WP_TESTS_DIR`, `WP_CORE_DIR`, and `WC_CORE_DIR` paths from the invocation directory.

The Bash ZIP command works without Node.js when `config.woo.zip.build` is empty and the extension's staging hook and Composer scripts, if present, also work on that host.

## Webpack

Import the helpers from the extension's webpack configuration. Keep the entry paths, output paths and match patterns in that configuration.

```js
const includeScripts = require( '@woocommerce/extension-tooling/webpack-text-includes' );
const minifyBundleCopies = require( '@woocommerce/extension-tooling/webpack-dist-minify' );
```

- `includeScripts( [ [ source, destination ] ] )` expands classic scripts that use `include( 'relative/file.js' );`. It emits the regular asset and a `.min.js` copy for the caller's webpack minimizer. Limit the minimizer to `.min.js` files, for example `new TerserPlugin( { test: /\.min\.js$/ } )`, to keep the regular asset readable. Each `include()` must be on its own line; the build fails on any other `include()` line.
- `minifyBundleCopies( [ 'frontend/*.js' ] )` emits minified copies of matching bundles after dependency metadata is generated. It skips a bundle that already has a `.min.js` asset, such as an `includeScripts` destination.

## Translation checks

Call the helper from a file run by `node --test`. Pass the extension root and its text domain. The helper uses `vendor/bin/phpcs` and `tools/i18n.xml` in that root. Set `<arg name="ignore-annotations"/>` in the ruleset so annotation comments cannot bypass the translation checks.

The helper checks that the ruleset rejects wrong text domains, using generated fixtures. It does not scan the extension's own files. Run `vendor/bin/phpcs --standard=tools/i18n.xml` on the extension source to check those.

```js
const path = require( 'node:path' );
const checkI18n = require( '@woocommerce/extension-tooling/check-i18n' );

checkI18n( {
	root: path.resolve( __dirname, '..' ),
	domain: 'your-extension',
} );
```

## Test and development environments

In the extension's `package.json`, use `woo-extension-env-start` for `env:test:start` and `woo-extension-env-dev-reset` for `env:dev:reset`. The extension supplies its existing `wp-env:test` and `env:dev:seed` scripts.

Add this mapping alongside the extension's existing `wp-content/bin` mapping in `.wp-env.json`.

```json
{
	"mappings": {
		"./wp-content/woo-extension-tooling": "./node_modules/@woocommerce/extension-tooling/bin"
	}
}
```

Use `wp-env run cli bash wp-content/woo-extension-tooling/seed-dev.sh wp-content/bin/seed_dev_plugin.sh` for `env:dev:seed`. Pass the fixture hook's path inside the container. Include WooCommerce Gateway Dummy in the development site's plugin list to use the seeded dummy payment method.

## Lint and analysis presets

If your Stylelint config already disables `selector-class-pattern` for legacy SCSS, extend the shared overlay after the WordPress preset. Keep any file-specific rules in your own config.

```json
{
	"extends": [
		"@wordpress/stylelint-config/scss-stylistic",
		"@woocommerce/extension-tooling/stylelint/legacy-scss"
	]
}
```

Add `configs/phpstan.neon` from the pinned tooling source as the last entry in PHPStan's `includes` list. To use analysis level 8 and a two-process cap, remove the local `level` and `parallel.maximumNumberOfProcesses` settings, which take precedence over included values. Composer-only jobs can read the file directly from the same checkout. Keep the PHP target version, paths, bootstrap, stubs, and baseline in your extension config.

For PHPCS, extend the `WooCommerce-Core` standard from `woocommerce/woocommerce-sniffs`. Define text domains, PHP compatibility checks, and exclusions in the extension's ruleset.

## License

GPL-3.0-or-later. The helpers originated in WooCommerce Composite Products.
