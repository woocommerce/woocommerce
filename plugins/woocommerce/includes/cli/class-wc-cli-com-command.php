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
		WP_CLI::add_command( 'wc com extension list', array( 'WC_CLI_COM_Command', 'list_extensions' ) );
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
