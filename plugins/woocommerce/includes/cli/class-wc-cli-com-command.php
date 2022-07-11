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
		WP_CLI::add_command( 'wc com extension list', array( 'WC_CLI_COM_Command', 'list_extensions' ) );
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
			WP_CLI::log( WP_CLI::colorize( '%yThis site is already connected.%n' ) );
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

	/**
	 * List extensions available for the connected site.
	 *
	 * ## EXAMPLES
	 *
	 * wp wc com extension list
	 *
	 * @param  array $args WP-CLI positional arguments.
	 * @param  array $assoc_args WP-CLI associative arguments.
	 */
	public static function list_extensions( array $args, array $assoc_args ) {
		$data = WC_Helper::get_subscriptions();

		$data = array_values( $data );

		$formatter = new \WP_CLI\Formatter(
			$assoc_args,
			array(
				'product_slug',
				'product_name',
				'auto_renew',
				'expires_on',
				'expired',
				'sites_max',
				'sites_active',
				'maxed',
			)
		);

		$data = array_map(
			function( $item ) {
				$product_slug      = '';
				$product_url_parts = explode( '/', $item['product_url'] );
				if ( count( $product_url_parts ) > 2 ) {
					$product_slug = $product_url_parts[ count( $product_url_parts ) - 2 ];
				}
				return array(
					'product_slug' => $product_slug,
					'product_name' => htmlspecialchars_decode( $item['product_name'] ),
					'auto_renew'   => $item['autorenew'] ? 'On' : 'Off',
					'expires_on'   => gmdate( 'Y-m-d', $item['expires'] ),
					'expired'      => $item['expired'] ? 'Yes' : 'No',
					'sites_max'    => $item['sites_max'],
					'sites_active' => $item['sites_active'],
					'maxed'        => $item['maxed'] ? 'Yes' : 'No',
				);
			},
			$data
		);

		$formatter->display_items( $data );
	}
}
