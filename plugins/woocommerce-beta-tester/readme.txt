=== WooCommerce Beta Tester ===
Contributors: automattic, bor0, claudiosanches, claudiulodro, kloon, mikejolley, peterfabian1000, rodrigosprimo, wpmuguru
Tags: woocommerce, woo commerce, beta, beta tester, bleeding edge, testing
Requires at least: 4.7
Tested up to: 5.6
Stable tag: 2.1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Easily update to prerelease versions of WooCommerce for testing and development purposes.

== Description ==

**WooCommerce Beta Tester** allows you to try out new versions of WooCommerce before they are officially released.

**Use with caution, not on production sites. Beta releases may not be stable.**

After activation, you'll be able to choose an update channel:

1. Beta - Update to beta releases, RC, or stable, depending on what is newest.
2. Release Candidate - Update to RC releases or stable, depending on what is newest.
3. Stable - No beta updates. Default WordPress behavior.

These will surface pre-releases via automatic updates in WordPress. Updates will replace your installed version of WooCommerce.

**Note**, this will not check for updates on every admin page load unless you explicitly tell it to. You can do this by clicking the "Check Again" button from the WordPress updates screen or you can set the `WC_BETA_TESTER_FORCE_UPDATE` to true in your `wp-config.php` file.

== Frequently Asked Questions ==

= Does this allow me to install multiple versions of WooCommerce at the same time?

No; updates will replace your currently installed version of WooCommerce. You can switch to any version from this plugin via the interface however.

= Where do updates come from? =

Updates are downloaded from the WordPress.org SVN repository where we tag prerelease versions specifically for this purpose.

= Does this rollback my data? =

This plugin does not rollback or update data on your store automatically.

Database updates are manually ran like after regular updates. If you downgrade, data will not be modified. We don't recommend using this in production.

= Where can I report bugs or contribute to WooCommerce Beta Tester? =

Bugs can be reported to the [WooCommerce Beta Tester GitHub issue tracker](https://github.com/woocommerce/woocommerce-beta-tester).

= Where can I report bugs or contribute to WooCommerce? =

Join in on our [GitHub repository](https://github.com/woocommerce/woocommerce/).

See our [contributing guidelines here](https://github.com/woocommerce/woocommerce/blob/master/.github/CONTRIBUTING.md).

== Changelog ==

## [2.1](https://github.com/woocommerce/woocommerce/releases/tag/2.1) - 2022-06-16 

-   Minor - Add WooCommerce Admin Helper Tester functionality to Beta Tester
-   Minor - Standardize build scripts and create a build:zip script
-   Patch - Standardize lint scripts: Add lint:fix
-   Patch - This is only updating monorepo infrastructure.
-   Minor - Updates the WC sniffs version to latest.
