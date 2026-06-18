<?php
/**
 * WooPaymentsTransactionsListRequest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use DateTime;
use DateTimeZone;
use WP_REST_Request;

/**
 * Compatibility request object for the preserved WooPayments transactions list filter.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsTransactionsListRequest extends WooPaymentsPaginatedListRequest {

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

		if ( ! class_exists( 'WCPay\Core\Server\Request\List_Transactions', false ) ) {
			class_alias( self::class, 'WCPay\Core\Server\Request\List_Transactions' );
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
		$transactions_request = parent::from_rest_request( $request );
		$date_between         = $request->get_param( 'date_between' );
		$user_timezone        = $request->get_param( 'user_timezone' );

		if ( null !== $date_between ) {
			$date_between = array_map(
				static function ( $transaction_date ) use ( $user_timezone ): ?string {
					return self::format_transaction_date_by_timezone( is_scalar( $transaction_date ) ? (string) $transaction_date : null, is_scalar( $user_timezone ) ? (string) $user_timezone : null );
				},
				(array) $date_between
			);
		}

		$transactions_request->set_filters(
			array(
				'match'                    => $request->get_param( 'match' ),
				'date_before'              => self::format_transaction_date_by_timezone( self::get_scalar_param( $request, 'date_before' ), self::get_scalar_param( $request, 'user_timezone' ) ),
				'date_after'               => self::format_transaction_date_by_timezone( self::get_scalar_param( $request, 'date_after' ), self::get_scalar_param( $request, 'user_timezone' ) ),
				'date_between'             => $date_between,
				'type_is'                  => $request->get_param( 'type_is' ),
				'type_is_not'              => $request->get_param( 'type_is_not' ),
				'type_is_in'               => null === $request->get_param( 'type_is_in' ) ? null : (array) $request->get_param( 'type_is_in' ),
				'source_device_is'         => $request->get_param( 'source_device_is' ),
				'source_device_is_not'     => $request->get_param( 'source_device_is_not' ),
				'channel_is'               => $request->get_param( 'channel_is' ),
				'channel_is_not'           => $request->get_param( 'channel_is_not' ),
				'customer_country_is'      => $request->get_param( 'customer_country_is' ),
				'customer_country_is_not'  => $request->get_param( 'customer_country_is_not' ),
				'risk_level_is'            => $request->get_param( 'risk_level_is' ),
				'risk_level_is_not'        => $request->get_param( 'risk_level_is_not' ),
				'store_currency_is'        => $request->get_param( 'store_currency_is' ),
				'customer_currency_is'     => $request->get_param( 'customer_currency_is' ),
				'customer_currency_is_not' => $request->get_param( 'customer_currency_is_not' ),
				'source_is'                => $request->get_param( 'source_is' ),
				'source_is_not'            => $request->get_param( 'source_is_not' ),
				'loan_id_is'               => $request->get_param( 'loan_id_is' ),
				'search'                   => null === $request->get_param( 'search' ) ? null : (array) $request->get_param( 'search' ),
				'deposit_id'               => $request->get_param( 'deposit_id' ),
			)
		);

		return $transactions_request;
	}

	/**
	 * Returns the request's API.
	 *
	 * @return string
	 */
	public function get_api(): string {
		return 'transactions';
	}

	/**
	 * Set a filter.
	 *
	 * @param mixed $value Filter value.
	 */
	public function set_deposit_id( $value ): void {
		$this->set_param( 'deposit_id', $value );
	}

	/**
	 * Set a filter.
	 *
	 * @param mixed $value Filter value.
	 */
	public function set_store_currency_is( $value ): void {
		$this->set_param( 'store_currency_is', $value );
	}

	/**
	 * Set a filter.
	 *
	 * @param mixed $value Filter value.
	 */
	public function set_search( $value ): void {
		if ( ! empty( $value ) ) {
			$this->set_param( 'search', $value );
		}
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

	/**
	 * Get a scalar param from a REST request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param string          $param   Param name.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return string|null
	 */
	private static function get_scalar_param( WP_REST_Request $request, string $param ): ?string {
		$value = $request->get_param( $param );

		return is_scalar( $value ) ? (string) $value : null;
	}

	/**
	 * Format a transaction date according to the user's timezone.
	 *
	 * @param string|null $transaction_date Transaction date.
	 * @param string|null $user_timezone User timezone.
	 * @return string|null
	 */
	private static function format_transaction_date_by_timezone( ?string $transaction_date, ?string $user_timezone ): ?string {
		if ( null === $transaction_date || null === $user_timezone || '' === $transaction_date || '' === $user_timezone ) {
			return $transaction_date;
		}

		$blog_time = new DateTime( $transaction_date );
		$blog_time->setTimezone( new DateTimeZone( wp_timezone_string() ) );

		$local_time = new DateTime( $transaction_date );
		$local_time->setTimezone( new DateTimeZone( $user_timezone ) );

		$time_difference = ( strtotime( $local_time->format( 'Y-m-d H:i:s' ) ) - strtotime( $blog_time->format( 'Y-m-d H:i:s' ) ) ) / 60;
		$formatted_date  = new DateTime( $transaction_date );
		date_modify( $formatted_date, $time_difference . 'minutes' );

		return $formatted_date->format( 'Y-m-d H:i:s' );
	}
}
