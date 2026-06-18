<?php
/**
 * WooPaymentsDisputesListRequest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use WP_REST_Request;

/**
 * Compatibility request object for the preserved WooPayments disputes list filter.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsDisputesListRequest extends WooPaymentsPaginatedListRequest {

	/**
	 * Register the legacy request FQCN as an alias when the WooPayments extension is absent.
	 */
	public static function register_legacy_alias(): void {
		self::register_legacy_base_aliases();

		if ( ! class_exists( 'WCPay\Core\Server\Request\List_Disputes', false ) ) {
			class_alias( self::class, 'WCPay\Core\Server\Request\List_Disputes' );
		}
	}

	/**
	 * Create a request from REST request data.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return static
	 */
	public static function from_rest_request( WP_REST_Request $request ) {
		$disputes_request = parent::from_rest_request( $request );
		$date_between     = $request->get_param( 'date_between' );

		$disputes_request->set_filters(
			array(
				'match'           => $request->get_param( 'match' ),
				'currency_is'     => $request->get_param( 'store_currency_is' ),
				'created_before'  => $request->get_param( 'date_before' ),
				'created_after'   => $request->get_param( 'date_after' ),
				'created_between' => null === $date_between ? null : (array) $date_between,
				'search'          => $request->get_param( 'search' ),
				'status_is'       => $request->get_param( 'status_is' ),
				'status_is_not'   => $request->get_param( 'status_is_not' ),
			)
		);

		return $disputes_request;
	}

	/**
	 * Returns the request's API.
	 *
	 * @return string
	 */
	public function get_api(): string {
		return 'disputes';
	}

	/**
	 * Catch reference filter setters without normalizing platform-facing names.
	 *
	 * @param string           $name Method name.
	 * @param array<int,mixed> $arguments Method arguments.
	 */
	public function __call( string $name, array $arguments ): void {
		if ( 0 === strpos( $name, 'set_' ) && array_key_exists( 0, $arguments ) ) {
			$this->set_param( substr( $name, 4 ), $arguments[0] );
		}
	}
}
