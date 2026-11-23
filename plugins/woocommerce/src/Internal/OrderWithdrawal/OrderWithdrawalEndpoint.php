<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal;

/**
 * Provides configuration and URLs for the order withdrawal endpoint.
 *
 * @internal Just for internal use.
 *
 * @since 11.3.0
 */
final class OrderWithdrawalEndpoint {

	/**
	 * Internal endpoint key.
	 */
	public const ENDPOINT_KEY = 'order-withdrawal';

	/**
	 * Default endpoint slug.
	 */
	public const ENDPOINT_SLUG = 'withdraw-order';

	/**
	 * Option containing the configured endpoint slug.
	 */
	public const ENDPOINT_OPTION = 'woocommerce_myaccount_order_withdrawal_endpoint';

	/**
	 * Get the configured endpoint slug.
	 *
	 * @since 11.3.0
	 */
	public function get_slug(): string {
		return (string) get_option( self::ENDPOINT_OPTION, self::ENDPOINT_SLUG );
	}

	/**
	 * Get the configured public order withdrawal page URL.
	 *
	 * @since 11.3.0
	 */
	public function get_url(): string {
		$account_url = wc_get_page_permalink( 'myaccount' );
		$query_vars  = WC()->query->get_query_vars();
		$endpoint    = ! empty( $query_vars[ self::ENDPOINT_KEY ] ) ? self::ENDPOINT_KEY : $this->get_slug();

		return wc_get_endpoint_url( $endpoint, '', $account_url ? $account_url : home_url( '/' ) );
	}
}
