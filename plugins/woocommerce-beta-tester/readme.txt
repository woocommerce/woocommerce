=== WooCommerce Beta Tester ===
Author URI: https://woocommerce.com/
Plugin URI: https://github.com/woocommerce/woocommerce-beta-tester
Contributors: automattic, mikejolley, claudiosanches, woothemes
Tags: woocommerce, wc, beta, beta tester, bleeding edge
Requires at least: 4.4
Tested up to: 4.9
Stable Tag: 1.0.3

Run bleeding edge versions of WooCommerce. This will replace your installed version of WooCommerce with the latest tagged release - use with caution, and not on production sites. You have been warned.

== Description ==

**This plugin is meant for testing and development purposes only. You should under no circumstances run this on a production website.**

Easily run the latest tagged version of [WooCommerce](https://wordpress.org/plugins/woocommerce/), including beta versions.

Just like with any plugin, this will not check for updates on every admin page load unless you explicitly tell it to. You can do this by clicking the "Check Again" button from the WordPress updates screen or you can set the `WC_BETA_TESTER_FORCE_UPDATE` to true in your `wp-config.php` file.

Based on WP_GitHub_Updater by Joachim Kudish and code by Patrick Garman.

== Changelog ==

= 1.0.3 =
* Fix repo URLs and directory renaming.

= 1.0.2 =
* Updated API URL.

= 1.0.1 =
* Switched to releases API to get latest release, rather than tag which are not chronological.

= 1.0 =
* First release.
