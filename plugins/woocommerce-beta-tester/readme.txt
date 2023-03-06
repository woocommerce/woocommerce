=== WooCommerce Beta Tester ===
Contributors: automattic, bor0, claudiosanches, claudiulodro, kloon, mikejolley, peterfabian1000, rodrigosprimo, wpmuguru
Tags: woocommerce, woo commerce, beta, beta tester, bleeding edge, testing
Requires at least: 4.7
Tested up to: 6.0
Stable tag: 2.2.0
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

You can get to the settings and features from your top admin bar under the name WC Beta Tester.

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

= 2.1.0 2022-10-11 =

* Dev - Add WooCommerce Admin Helper Tester functionality to Beta Tester

= 2.0.5 - 2021-12-17 =
* Fix: make WC version comparison case insensitive

= 2.0.4 - 2021-09-29 =
* Dev: Bump tested to version
* Fix: enqueue logic for css/js assets

= 2.0.3 - 2021-09-22 =
* Fix: Bump version to release version including admin.css.

= 2.0.2 =

* Fix notice for undefined `item`
* Fix auto_update_plugin filter reference
* Fix including SSR in bug report
* Fix style in version modal header
* Add check for WooCommerce installed in default location

= 2.0.1 =
* Changes to make this plugin compatible with the upcoming WooCommerce 3.6

= 2.0.0 =
* Enhancement - Re-built to pull updates from the WordPress.org repository rather than GitHub.
* Enhancement - Channel selection; choose to receive RC or beta versions.
* Enhancement - Admin bar item shows version information, and offers shortcuts to functionality.
* Enhancement - Shortcut to log GitHub issues.
* Enhancement - Version switcher; choose which release or prerelease to switch to.
* Enhancement - Setting to enable auto-updates.

= 1.0.3 =
* Fix repo URLs and directory renaming.

= 1.0.2 =
* Updated API URL.

= 1.0.1 =
* Switched to releases API to get latest release, rather than tag which are not chronological.

= 1.0 =
* First release.
