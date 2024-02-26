<?php
/**
 * Woo Marketplace plugin manager.
 *
 * @class WC_Helper_Updater
 * @package WooCommerce\Admin\Helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Helper_Plugin Class
 *
 * Contains the logic to manage the Woo Marketplace plugin.
 */
class WC_Helper_Plugin {
	const WOO_UPDATE_MANAGER_PLUGIN_MAIN_FILE = 'woo-marketplace/woo-marketplace.php';
	const WOO_UPDATE_MANAGER_DOWNLOAD_URL     = 'https://woo.com/woo-update-manager/download/';

	/**
	 * Loads the class, runs on init.
	 *
	 * @return void
	 */
	public static function load(): void {
		add_action( 'admin_init', array( __CLASS__, 'configure_admin_notices' ) );
	}

	/**
	 * Check if the marketplace plugin is active.
	 *
	 * @return bool
	 */
	public static function is_plugin_active(): bool {
		return is_plugin_active_for_network( self::WOO_UPDATE_MANAGER_PLUGIN_MAIN_FILE ) || is_plugin_active( self::WOO_UPDATE_MANAGER_PLUGIN_MAIN_FILE );
	}

	/**
	 * Check if the marketplace plugin is installed.
	 *
	 * @return bool
	 */
	public static function is_plugin_installed(): bool {
		return file_exists( WP_PLUGIN_DIR . '/' . self::WOO_UPDATE_MANAGER_PLUGIN_MAIN_FILE );
	}

	/**
	 * Generate the URL to install the Woo Connect plugin.
	 *
	 * @return string
	 */
	public static function generate_install_url(): string {
		/**
		 * Filter the base URL used to install the Woo Connect plugin.
		 *
		 * @since 8.7.0
		 */
		$install_url_base = apply_filters( 'woo_com_base_url', 'https://woo.com/' );
		$install_url      = $install_url_base . 'in-app-purchase/install-woo-connect';

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

		$request_uri  = wp_parse_url( $url, PHP_URL_PATH );
		$query_string = wp_parse_url( $url, PHP_URL_QUERY );

		if ( is_string( $query_string ) ) {
			$request_uri .= '?' . $query_string;
		}

		$data = array(
			'host'        => wp_parse_url( $url, PHP_URL_HOST ),
			'request_uri' => $request_uri,
			'method'      => 'GET',
		);

		$signature = hash_hmac( 'sha256', wp_json_encode( $data ), $auth['access_token_secret'] );

		return add_query_arg(
			array(
				'token'     => $auth['access_token'],
				'signature' => $signature,
			),
			$url
		);
	}

	/**
	 * Configure admin notices.
	 *
	 * @return void
	 */
	public static function configure_admin_notices(): void {
		if ( self::is_plugin_installed() ) {
			return;
		}

		if ( ! self::is_loading_plugin_management_page() ) {
			return;
		}

		add_action( 'admin_notices', array( __CLASS__, 'show_install_notice_on_plugin_management_page' ) );
	}

	/**
	 * Show a notice on the plugins management page to install the Woo Update Manager plugin.
	 *
	 * @return void
	 */
	public static function show_install_notice_on_plugin_management_page(): void {
		printf(
			'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
			sprintf(
				wp_kses(
				/* translators: 1: Woo Update Manager plugin install URL 2: Woo Update Manager plugin download URL */
					__(
						'Please <a href="%1$s">Install the Woo Update Manager</a> plugin to keep getting updates and streamlined support for your Woo.com subscriptions. You can also <a href="%2$s">download</a> and install it manually.',
						'woocommerce'
					),
					array(
						'a' => array(
							'href' => array(),
						),
					)
				),
				esc_url( self::generate_install_url() ),
				esc_url( self::WOO_UPDATE_MANAGER_DOWNLOAD_URL )
			)
		);
	}

	/**
	 * Check if the current page is the plugin management page.
	 *
	 * @return bool
	 */
	protected static function is_loading_plugin_management_page(): bool {
		return 'plugins.php' === wp_parse_url( basename( get_self_link() ), PHP_URL_PATH );
	}
}

WC_Helper_Plugin::load();
