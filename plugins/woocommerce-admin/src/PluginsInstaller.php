<?php
/**
 * PluginsInstaller
 *
 * Installer to allow plugin installation via URL query.
 */

namespace Automattic\WooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Plugins;

/**
 * Class PluginsInstaller
 */
class PluginsInstaller {
	/**
	 * Message option name.
	 */
	const MESSAGE_OPTION = 'woocommerce_admin_plugin_installer_message';

	/**
	 * Constructor
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'possibly_install_activate_plugins' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'display_message' ) );
	}

	/**
	 * Check if an install or activation is being requested via URL query.
	 */
	public static function possibly_install_activate_plugins() {
		/* phpcs:disable WordPress.Security.NonceVerification.Recommended */
		if ( ! isset( $_GET['plugin_action'] ) || ! isset( $_GET['plugins'] ) || ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$plugins       = sanitize_text_field( wp_unslash( $_GET['plugins'] ) );
		$plugin_action = sanitize_text_field( wp_unslash( $_GET['plugin_action'] ) );
		/* phpcs:enable WordPress.Security.NonceVerification.Recommended */

		$plugins_api     = new Plugins();
		$install_result  = null;
		$activate_result = null;

		switch ( $plugin_action ) {
			case 'install':
				$install_result = $plugins_api->install_plugins( array( 'plugins' => $plugins ) );
				break;
			case 'activate':
				$activate_result = $plugins_api->activate_plugins( array( 'plugins' => $plugins ) );
				break;
			case 'install-activate':
				$install_result  = $plugins_api->install_plugins( array( 'plugins' => $plugins ) );
				$activate_result = $plugins_api->activate_plugins( array( 'plugins' => implode( ',', $install_result['data']['installed'] ) ) );
				break;
		}

		self::cache_results( $install_result, $activate_result );
		self::redirect_to_referer();
	}

	/**
	 * Display the results of installation and activation on the page.
	 *
	 * @param array $install_result Result of installation.
	 * @param array $activate_result Result of activation.
	 */
	public static function cache_results( $install_result, $activate_result ) {
		if ( ! $install_result && ! $activate_result ) {
			return;
		}

		$message = $activate_result ? $activate_result['message'] : $install_result['message'];

		// Show install error message if one exists.
		if ( $install_result && ! $install_result['success'] ) {
			$message = $install_result['message'];
		}

		update_option( self::MESSAGE_OPTION, $message );
	}

	/**
	 * Display the results of installation and activation on the page.
	 */
	public static function display_message() {
		$message = get_option( self::MESSAGE_OPTION );

		if ( ! $message ) {
			return;
		}

		delete_option( self::MESSAGE_OPTION );
	}

	/**
	 * Redirect back to the referring page if one exists.
	 */
	public static function redirect_to_referer() {
		$referer = wp_get_referer();
		if ( $referer && 0 !== strpos( $referer, wp_login_url() ) ) {
			wp_safe_redirect( $referer );
			exit();
		}

		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$url = remove_query_arg( 'plugin_action', wp_unslash( $_SERVER['REQUEST_URI'] ) ); // phpcs:ignore sanitization ok.
		$url = remove_query_arg( 'plugins', $url );
		wp_safe_redirect( $url );
		exit();
	}

}
