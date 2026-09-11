<?php
/**
 * Core integration for the Settings DataForm package.
 *
 * @package WooCommerce
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin;

/**
 * Loads the generated Settings DataForm page and registers its submenu.
 *
 * @internal
 */
final class SettingsDataForm {

	/**
	 * Register initialization hooks.
	 *
	 * @since 11.2.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'handle_init' ) );
	}

	/**
	 * Load the package only in supported site admin requests.
	 *
	 * @internal
	 * @return void
	 */
	public function handle_init(): void {
		if ( ! is_admin() || is_network_admin() || wp_doing_ajax() || version_compare( get_bloginfo( 'version' ), '7.0', '<' ) ) {
			return;
		}

		$build_file = WC_ABSPATH . 'assets/client/settings-dataform/build.php';
		if ( ! is_readable( $build_file ) ) {
			return;
		}

		require_once $build_file;
		add_action( 'admin_menu', array( $this, 'handle_admin_menu' ), 99 );
	}

	/**
	 * Register the submenu after WooCommerce registers its menu.
	 *
	 * @internal
	 * @return void
	 */
	public function handle_admin_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Settings DataForm', 'woocommerce' ),
			__( 'Settings DataForm', 'woocommerce' ),
			'manage_woocommerce',
			'wc-settings-dataform-wp-admin',
			'woocommerce_settings_ui_experimental_wc_settings_dataform_wp_admin_render_page'
		);
	}
}
