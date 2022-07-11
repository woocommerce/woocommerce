<?php
/**
 * WC_CLI_COM_Command class file.
 *
 * @package WooCommerce\CLI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allows to interact with extensions from WCCOM marketplace via CLI.
 *
 * @version 6.8
 * @package WooCommerce
 */
class WC_CLI_COM_Command {
	/**
	 * Registers a commands for managing WooCommerce.com extensions.
	 */
	public static function register_commands() {
		WP_CLI::add_command( 'wc com connect', array( 'WC_CLI_COM_Command', 'connect_site' ) );
	}

	/**
	 * Connect the site to WooCommerce.com.
	 *
	 * ## EXAMPLES
	 *
	 * wp wc com connect
	 *
	 * @param  array $args  WP-CLI positional arguments.
	 * @param  array $assoc_args  WP-CLI associative arguments.
	 */
	public static function connect_site( array $args, array $assoc_args ) {
		WC_Helper::_flush_authentication_cache();
		$auth = WC_Helper_Options::get( 'auth' );

		if ( ! empty( $auth['access_token'] ) && ! empty( $auth['access_token_secret'] ) ) {
			WP_CLI::log( WP_CLI::colorize( '%yThis site is already connected to WooCommerce.com.%n' ) );
			return;
		}

		WP_CLI::log( WP_CLI::colorize( '%yIn order to connect your site to WooCommerce.com, copy the following URL and paste it into a web browser.%n' ) );
		WP_CLI::log(
			WP_CLI::colorize(
				sprintf(
					'%s%sadmin.php?page=wc-addons&section=helper&wc-helper-connect=1&wc-is-cli=1%s',
					'%G',
					get_admin_url(),
					'%n'
				)
			)
		);
	}
}
