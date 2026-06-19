<?php
/**
 * WooPaymentsDocumentsListRequest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use WP_REST_Request;

/**
 * Compatibility request object for the preserved WooPayments documents list filter.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsDocumentsListRequest extends WooPaymentsPaginatedListRequest {

	protected const DEFAULT_PARAMS = array(
		'page'      => 0,
		'pagesize'  => 25,
		'sort'      => 'date',
		'direction' => 'desc',
		'limit'     => 100,
	);

	/**
	 * Register the legacy request FQCN as an alias when the WooPayments extension is absent.
	 */
	public static function register_legacy_alias(): void {
		self::register_legacy_base_aliases();

		if ( ! class_exists( 'WCPay\Core\Server\Request\List_Documents', false ) ) {
			class_alias( self::class, 'WCPay\Core\Server\Request\List_Documents' );
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
		$documents_request = parent::from_rest_request( $request );
		$documents_request->set_filters(
			array(
				'match'        => $request->get_param( 'match' ),
				'date_before'  => $request->get_param( 'date_before' ),
				'date_after'   => $request->get_param( 'date_after' ),
				'date_between' => (array) $request->get_param( 'date_between' ),
				'type_is'      => $request->get_param( 'type_is' ),
				'type_is_not'  => $request->get_param( 'type_is_not' ),
			)
		);

		return $documents_request;
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
		return 'documents';
	}

	/**
	 * Set document type filter.
	 *
	 * @param string $type_is Document type.
	 */
	public function set_type_is( string $type_is ): void {
		$this->set_param( 'type_is', $type_is );
	}

	/**
	 * Set excluded document type filter.
	 *
	 * @param string $type_is_not Excluded document type.
	 */
	public function set_type_is_not( string $type_is_not ): void {
		$this->set_param( 'type_is_not', $type_is_not );
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
