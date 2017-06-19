=== WooCommerce Beta Tester ===
Author URI: http://woothemes.com/
Plugin URI: https://github.com/woothemes/woocommerce-beta-tester
Contributors: woothemes, mikejolley, claudiosanches
Tags: woocommerce, wc, beta, beta tester, bleeding edge
Requires at least: 4.4
Tested up to: 4.8
Stable Tag: 1.0.3

Run bleeding edge versions of WooCommerce from our Github repo. This will replace your installed version of WooCommerce with the latest tagged release on Github - use with caution, and not on production sites. You have been warned.

== Description ==

**This plugin is meant for testing and development purposes only. You should under no circumstances run this on a production website.**

Easily run the latest tagged version of [WooCommerce](http://wordpress.org/plugins/woocommerce/) right from GitHub, including beta versions.

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
