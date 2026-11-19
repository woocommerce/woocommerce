# WooCommerce extension changelog formatter

`woocommerce/extension-changelogger` formats the `changelog.txt` used by WooCommerce extensions. It works with Jetpack Changelogger 6 and PHP 7.4 or later. Install it from the extension root:

```sh
composer require --dev woocommerce/extension-changelogger:^1.0
```

Set `extra.changelogger.formatter.class` to `Automattic\WooCommerce\ExtensionTools\Changelogger\Changelog_Formatter`. Keep the plugin's title under `extra.changelogger.formatter.title` and its change types, entry ordering and versioning under `extra.changelogger`. Use the extension's existing Changelogger commands to validate change files and compile releases.

## Test with a local checkout

To rehearse unpublished changes, add a Composer path repository to the extension's root `composer.json`. Use a distinct development version and adjust the path to your WooCommerce checkout.

```json
{
	"repositories": [
		{
			"type": "path",
			"url": "../woocommerce/packages/php/extension-tooling",
			"options": {
				"versions": { "woocommerce/extension-changelogger": "dev-local" }
			}
		}
	],
	"require-dev": { "woocommerce/extension-changelogger": "dev-local" }
}
```

From the extension root, run `composer update woocommerce/extension-changelogger` to install the package and update the extension's lockfile for the rehearsal.

Run `composer install` and `composer test` from this directory to test the formatter itself.
