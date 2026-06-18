<?php
/**
 * WooPaymentsFraudOutcomeTransactionsListRequest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use WP_REST_Request;

/**
 * Compatibility request object for the preserved WooPayments fraud outcome filters.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsFraudOutcomeTransactionsListRequest extends WooPaymentsPaginatedListRequest {

	protected const DEFAULT_PARAMS = array(
		'page'      => 1,
		'pagesize'  => 25,
		'sort'      => 'date',
		'direction' => 'desc',
	);

	/**
	 * Register the legacy request FQCN as an alias when the WooPayments extension is absent.
	 */
	public static function register_legacy_alias(): void {
		self::register_legacy_base_aliases();

		if ( ! class_exists( 'WCPay\Core\Server\Request\List_Fraud_Outcome_Transactions', false ) ) {
			class_alias( self::class, 'WCPay\Core\Server\Request\List_Fraud_Outcome_Transactions' );
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
		$fraud_outcome_request = parent::from_rest_request( $request );

		$fraud_outcome_request->set_status( $request->get_param( 'status' ) );
		$fraud_outcome_request->set_search(
			null === $request->get_param( 'search' ) ? array() : (array) $request->get_param( 'search' )
		);
		$fraud_outcome_request->set_search_term( (string) ( $request->get_param( 'search_term' ) ?? '' ) );
		$fraud_outcome_request->set_additional_status( (string) ( $request->get_param( 'additional_status' ) ?? '' ) );

		return $fraud_outcome_request;
	}

	/**
	 * Returns the request's API.
	 *
	 * @return string
	 */
	public function get_api(): string {
		return 'fraud_outcomes/status/' . (string) $this->get_param( 'status' );
	}

	/**
	 * Set the fraud outcome status.
	 *
	 * @param mixed $status Fraud outcome status.
	 */
	public function set_status( $status ): void {
		$this->set_param( 'status', $status );
	}

	/**
	 * Set the search param.
	 *
	 * @param mixed $search Search param.
	 */
	public function set_search( $search ): void {
		$this->set_param( 'search', $search );
	}

	/**
	 * Set the search term param.
	 *
	 * @param mixed $search_term Search term param.
	 */
	public function set_search_term( $search_term ): void {
		$this->set_param( 'search_term', (string) $search_term );
	}

	/**
	 * Set the additional status param.
	 *
	 * @param mixed $additional_status Additional status param.
	 */
	public function set_additional_status( $additional_status ): void {
		$this->set_param( 'additional_status', (string) $additional_status );
	}
}
