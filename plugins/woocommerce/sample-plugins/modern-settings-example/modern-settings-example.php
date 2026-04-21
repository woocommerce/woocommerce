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

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! class_exists( 'WC_Settings_Page' ) ) {
			return;
		}

		require_once __DIR__ . '/includes/class-modern-settings-example-tab.php';

		add_filter(
			'woocommerce_get_settings_pages',
			static function ( array $pages ): array {
				$pages[] = new \Modern_Settings_Example\Modern_Settings_Example_Tab();
				return $pages;
			}
		);
	}
);
