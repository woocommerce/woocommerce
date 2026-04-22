<?php
/**
 * Plugin Name: WooCommerce Modernised Settings Example
 * Description: Demonstrates the modernised settings SDK introduced in WooCommerce 10.8. Adds a "Modern Example" tab under WooCommerce → Settings that renders via the React path when the `modern-settings` feature flag is on, and via the legacy form when it is off.
 * Version: 1.0.0
 * Author: WooCommerce
 * Requires Plugins: woocommerce
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package ModernSettingsExample
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the settings page.
 *
 * The `woocommerce_get_settings_pages` filter is fired inside
 * `WC_Admin_Settings::get_settings_pages()`, which is the same function that
 * `include_once`s `class-wc-settings-page.php`. That means `WC_Settings_Page`
 * is always loaded by the time this callback runs, so we can safely
 * `require_once` our subclass here. There is no equivalent guarantee at
 * `plugins_loaded`, so hooking later (or guarding on `class_exists` there)
 * would silently never register the tab.
 */
add_filter(
	'woocommerce_get_settings_pages',
	static function ( array $pages ): array {
		require_once __DIR__ . '/includes/class-modern-settings-example-tab.php';

		$pages[] = new \Modern_Settings_Example\Modern_Settings_Example_Tab();
		return $pages;
	}
);
