# WooCommerce extension changelog formatter

`woocommerce/extension-changelogger` formats the `changelog.txt` used by WooCommerce extensions. Add it to `require-dev` in the extension's Composer manifest. It works with Jetpack Changelogger 6 and PHP 7.4 or later.

Set `extra.changelogger.formatter.class` to `Automattic\WooCommerce\ExtensionTools\Changelogger\Changelog_Formatter`. Keep the plugin's title under `extra.changelogger.formatter.title` and its change types, entry ordering and versioning under `extra.changelogger`. Use the extension's existing Changelogger commands to validate change files and compile releases.

Run `composer install` and `composer test` from this directory to test the formatter.
