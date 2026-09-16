<?php
/**
 * Core integration for the Settings DataForm package.
 *
 * @package WooCommerce
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\SettingsDataForm\ViewConfig;

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
	 * Load the view configuration and the supported site admin page.
	 *
	 * @internal
	 * @return void
	 */
	public function handle_init(): void {
		$view_config_file = WC_ABSPATH . 'assets/client/settings-dataform/scripts/settings-ui/ViewConfig.php';
		if ( function_exists( 'wp_get_entity_view_config' ) && is_readable( $view_config_file ) ) {
			require_once $view_config_file;
			new ViewConfig();
		}

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
		$render_page = 'woocommerce_settings_ui_experimental_wc_settings_dataform_wp_admin_render_page';
		if ( ! function_exists( $render_page ) ) {
			return;
		}

		add_submenu_page(
			'woocommerce',
			__( 'Settings DataForm', 'woocommerce' ),
			__( 'Settings DataForm', 'woocommerce' ),
			'manage_woocommerce',
			'wc-settings-dataform-wp-admin',
			// @phpstan-ignore argument.type (wp-build generates this function; its existence is checked above.)
			$render_page
		);
	}
}
