<?php
/**
 * WooPaymentsAuthorizationsListRequest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use WP_REST_Request;

/**
 * Compatibility request object for the preserved WooPayments authorizations list filter.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsAuthorizationsListRequest extends WooPaymentsPaginatedListRequest {

	protected const DEFAULT_PARAMS = array(
		'page'      => 0,
		'pagesize'  => 25,
		'sort'      => 'created',
		'direction' => 'desc',
		'limit'     => 100,
	);

	/**
	 * Register the legacy request FQCN as an alias when the WooPayments extension is absent.
	 */
	public static function register_legacy_alias(): void {
		self::register_legacy_base_aliases();

		if ( ! class_exists( 'WCPay\Core\Server\Request\List_Authorizations', false ) ) {
			class_alias( self::class, 'WCPay\Core\Server\Request\List_Authorizations' );
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
		$authorizations_request = parent::from_rest_request( $request );
		$authorizations_request->set_page_size( (int) ( $request->get_param( 'pagesize' ) ?? $request->get_param( 'per_page' ) ?? 25 ) );
		$authorizations_request->set_filters(
			array(
				'match'               => $request->get_param( 'match' ),
				'order_id_is'         => $request->get_param( 'order_id_is' ) ?? $request->get_param( 'order_id' ),
				'customer_email_is'   => $request->get_param( 'customer_email_is' ) ?? $request->get_param( 'customer_email' ),
				'customer_country_is' => $request->get_param( 'customer_country_is' ),
				'risk_level_is'       => $request->get_param( 'risk_level_is' ),
				'source_is'           => $request->get_param( 'source_is' ) ?? $request->get_param( 'payment_method_type' ),
				'date_before'         => $request->get_param( 'date_before' ),
				'date_after'          => $request->get_param( 'date_after' ),
				'date_between'        => $request->get_param( 'date_between' ),
				'search'              => $request->get_param( 'search' ),
			)
		);

		return $authorizations_request;
	}

	/**
	 * Create a request from normalized params.
	 *
	 * @param array<string,mixed> $params Request params.
	 * @return self
	 */
	public static function from_params( array $params ): self {
		$request = new self();

		foreach ( $params as $key => $value ) {
			$request->set_param( (string) $key, $value );
		}

		return $request;
	}

	/**
	 * Returns the request's API.
	 *
	 * @return string
	 */
	public function get_api(): string {
		return 'authorizations';
	}

	/**
	 * Catch reference filter setters without normalizing platform-facing names.
	 *
	 * @param string           $name      Method name.
	 * @param array<int,mixed> $arguments Method arguments.
	 */
	public function __call( string $name, array $arguments ): void {
		if ( 0 === strpos( $name, 'set_' ) && array_key_exists( 0, $arguments ) ) {
			$this->set_param( substr( $name, 4 ), $arguments[0] );
		}
	}
}
