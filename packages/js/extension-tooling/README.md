# WooCommerce extension tooling

Install `@woocommerce/extension-tooling` as a development dependency in the extension.

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
