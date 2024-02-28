<?php
/**
 * A utility class for Woo Update Manager plugin.
 *
 * @class WC_Woo_Update_Manager_Plugin
 * @package WooCommerce\Admin\Helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Helper_Plugin Class
 *
 * Contains the logic to manage the Woo Update Manager plugin.
 */
class WC_Woo_Update_Manager_Plugin {
	const WOO_UPDATE_MANAGER_PLUGIN_MAIN_FILE = 'woo-update-manager/woo-update-manager.php';
	const WOO_UPDATE_MANAGER_DOWNLOAD_URL     = 'https://woo.com/products/woo-update-manager/download/';

	/**
	 * Loads the class, runs on init.
	 *
	 * @return void
	 */
	public static function load(): void {
		add_action( 'admin_notices', array( __CLASS__, 'show_woo_update_manager_install_notice' ) );
	}

	/**
	 * Check if the Woo Update Manager plugin is active.
	 *
	 * @return bool
	 */
	public static function is_plugin_active(): bool {
		return is_plugin_active_for_network( self::WOO_UPDATE_MANAGER_PLUGIN_MAIN_FILE ) || is_plugin_active( self::WOO_UPDATE_MANAGER_PLUGIN_MAIN_FILE );
	}

	/**
	 * Check if the Woo Update Manager plugin is installed.
	 *
	 * @return bool
	 */
	public static function is_plugin_installed(): bool {
		return file_exists( WP_PLUGIN_DIR . '/' . self::WOO_UPDATE_MANAGER_PLUGIN_MAIN_FILE );
	}

	/**
	 * Generate the URL to install the Woo Update Manager plugin.
	 *
	 * @return string
	 */
	public static function generate_install_url(): string {
		/**
		 * Filter the base URL used to install the Woo Update Manager plugin.
		 *
		 * @since 8.7.0
		 */
		$install_url_base = apply_filters( 'woo_com_base_url', 'https://woo.com/' );

		/**
		 * Filter the id of the Woo Update Manager plugin.
		 *
		 * @since 8.7.0
		 */
		$woo_update_manager_plugin_id = apply_filters( 'woo_update_manager_plugin_id', 18734003334043 );

		$install_url = $install_url_base . 'auto-install/step/init/' . $woo_update_manager_plugin_id . '/';

		return self::add_auth_parameters( $install_url );
	}

	/**
	 * Add the access token and signature to the provided URL.
	 *
	 * @param string $url The URL to add the access token and signature to.
	 * @return string
	 */
	private static function add_auth_parameters( string $url ): string {
		$auth = WC_Helper_Options::get( 'auth' );

		if ( empty( $auth['access_token'] ) || empty( $auth['access_token_secret'] ) ) {
			return false;
		}

		$signature = WC_Helper_API::create_request_signature( (string) $auth['access_token_secret'], $url, 'GET' );

		return add_query_arg(
			array(
				'token'     => $auth['access_token'],
				'signature' => $signature,
			),
			$url
		);
	}

	/**
	 * Show a notice on the plugins management page to install the Woo Update Manager plugin.
	 *
	 * @return void
	 */
	public static function show_woo_update_manager_install_notice(): void {
		if ( self::is_plugin_installed() ) {
			return;
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( ! self::is_wc_admin_page() ) {
			return;
		}

		if ( self::install_admin_notice_dismissed() ) {
			return;
		}

		include dirname( __FILE__ ) . '/views/html-notice-woo-updater-not-installed.php';
	}

	/**
	 * Check if the current page is a wc-admin page.
	 *
	 * @return bool
	 */
	protected static function is_wc_admin_page(): bool {
		return isset( $_GET['page'] ) &&
			(
				'wc-admin' === $_GET['page'] ||
				'wc-status' === $_GET['page'] ||
				'wc-settings' === $_GET['page'] ||
				'wc-reports' === $_GET['page']
			)
			|| isset( $_GET['post_type'] ) && 'shop_order' === $_GET['post_type'];
	}

	/**
	 * Check if the installation notice has been dismissed.
	 *
	 * @return bool
	 */
	protected static function install_admin_notice_dismissed(): bool {
		return get_user_meta( get_current_user_id(), 'dismissed_woo_updater_not_installed_notice', true );
	}
}

WC_Woo_Update_Manager_Plugin::load();
