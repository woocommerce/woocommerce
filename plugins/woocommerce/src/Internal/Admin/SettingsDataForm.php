<?php
/**
 * Core integration for the Settings DataForm package.
 *
 * @package WooCommerce
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\SettingsDataForm\ViewConfig;
use Automattic\WooCommerce\Internal\Admin\SettingsDataForm\LegacyViewConfig;
use WC_Admin_Settings;
use WC_Settings_Page;

/**
 * Loads the generated Settings DataForm page and registers its submenu.
 *
 * @internal
 */
final class SettingsDataForm {
	/**
	 * Menu links keyed by their DataForm route.
	 *
	 * @var array<string, string>
	 */
	private $menu_links = array();

	/**
	 * Register initialization hooks.
	 *
	 * @since 11.2.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'handle_init' ) );
		add_filter( 'rest_pre_dispatch', array( new LegacyViewConfig(), 'handle_rest_pre_dispatch' ), 10, 3 );
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
			// @phpstan-ignore class.notFound (wp-build copies this class into WooCommerce's generated assets.)
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
		global $submenu;

		$render_page = 'woocommerce_settings_ui_experimental_wc_settings_dataform_wp_admin_render_page';
		if ( ! function_exists( $render_page ) ) {
			return;
		}

		$menu_page = add_submenu_page(
			'woocommerce',
			__( 'Settings DataForm', 'woocommerce' ),
			__( 'Settings DataForm', 'woocommerce' ),
			'manage_woocommerce',
			'wc-settings-dataform-wp-admin',
			// @phpstan-ignore argument.type (wp-build generates this function; its existence is checked above.)
			$render_page
		);
		if ( false === $menu_page ) {
			return;
		}

		try {
			$pages = WC_Admin_Settings::get_settings_pages();
		} catch ( \Throwable $error ) {
			return;
		}
		if ( ! is_array( $pages ) ) {
			return;
		}

		$menu_items = array();
		foreach ( $pages as $page ) {
			if ( ! $page instanceof WC_Settings_Page ) {
				continue;
			}
			try {
				$id    = $page->get_id();
				$label = $page->get_label();
			} catch ( \Throwable $error ) {
				continue;
			}
			if ( ! is_string( $id ) || '' === $id || ! is_string( $label ) ) {
				continue;
			}

			$route = '/settings/' . $id;
			$url   = add_query_arg(
				'p',
				$route,
				admin_url( 'admin.php?page=wc-settings-dataform-wp-admin' )
			);
			/* translators: %s: WooCommerce settings page label. */
			$title                = sprintf( __( '%s (DataForm)', 'woocommerce' ), wp_strip_all_tags( $label ) );
			$menu_items[ $route ] = array(
				esc_html( $title ),
				'manage_woocommerce',
				esc_url( $url ),
				$title,
			);
		}
		if ( empty( $menu_items ) ) {
			return;
		}

		foreach ( $submenu['woocommerce'] as $index => $item ) {
			if ( 'wc-settings-dataform-wp-admin' === $item[2] ) {
				$submenu['woocommerce'][ $index ][4] = 'hide-if-js'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Keep the registered page in wp-admin's menu while hiding its duplicate link.
				break;
			}
		}
		foreach ( $menu_items as $route => $item ) {
			$submenu['woocommerce'][]   = $item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- wp-admin stores submenu items in this global.
			$this->menu_links[ $route ] = $item[2];
		}

		add_filter( 'submenu_file', array( $this, 'handle_submenu_file' ), 10, 2 );
	}

	/**
	 * Highlight the DataForm menu item for the selected page.
	 *
	 * @since 11.3.0
	 * @internal
	 * @param mixed $submenu_file Current submenu URL.
	 * @param mixed $parent_file Current parent menu.
	 * @return mixed
	 */
	public function handle_submenu_file( $submenu_file, $parent_file ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- These query parameters only select a menu item.
		if ( 'woocommerce' !== $parent_file || ! isset( $_GET['page'], $_GET['p'] ) || ! is_string( $_GET['page'] ) || ! is_string( $_GET['p'] ) ) {
			return $submenu_file;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- These query parameters only select a menu item.
		if ( 'wc-settings-dataform-wp-admin' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return $submenu_file;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This query parameter only selects a menu item.
		$route = sanitize_text_field( wp_unslash( $_GET['p'] ) );
		return $this->menu_links[ $route ] ?? $submenu_file;
	}
}
