<?php
/**
 * A utility class to handle WooCommerce.com connection.
 *
 * @class WC_Woo_Update_Manager_Plugin
 * @package WooCommerce\Admin\Helper
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Helper_Plugin Class
 *
 * Contains the logic to manage WooCommerce.com Helper Connection.
 */
class WC_Woo_Helper_Connection {
	/**
	 * Check if the Woo Update Manager plugin is active.
	 *
	 * @return bool
	 */
	public static function get_connection_url_notice(): string {
		$connection_data = WC_Helper::get_cached_connection_data();
		if ( false === $connection_data || false === $connection_data['alert_url_mismatch'] ) {
			return '';
		}

		$auth     = WC_Helper_Options::get( 'auth' );
		$url      = rtrim( $auth['url'], '/' );
		$home_url = rtrim( home_url(), '/' );
		if ( empty( $url ) || $home_url === $url ) {
			return '';
		}

		return sprintf(
		/* translators: 1: WooCommerce.com connection URL, 2: home URL */
			__( 'Your site is currently connected to WooCommerce.com using <b>%1$s</b>, but your actual site URL is <b>%2$s</b>. To fix this, please reconnect your site to <b>WooCommerce.com</b> to ensure everything works correctly.', 'woocommerce' ),
			$url,
			$home_url
		);
	}

	/**
	 * Check if the site has and linked host-plan orders.
	 *
	 * @return bool
	 */
	public static function has_host_plan_orders(): bool {
		$subscriptions = WC_Helper::get_subscriptions();
		foreach ( $subscriptions as $subscription ) {
			if ( isset( $subscription['included_in_host_plan'] ) && true === (bool) $subscription['included_in_host_plan'] ) {
				return true;
			}
		}

		return false;
	}
}
