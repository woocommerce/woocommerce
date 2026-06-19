<?php
/**
 * WooPaymentsReportingBalanceSummaryRequest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;

/**
 * Compatibility request object for the preserved WooPayments reporting balance summary filter.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsReportingBalanceSummaryRequest extends WooPaymentsPaginatedListRequest {

	private const CURRENCY_CODE_PATTERN = '/^[a-z]{3}$/i';

	protected const DEFAULT_PARAMS = array();

	/**
	 * Register the legacy request FQCN as an alias when the WooPayments extension is absent.
	 */
	public static function register_legacy_alias(): void {
		self::register_legacy_base_aliases();

		if ( ! class_exists( 'WCPay\Core\Server\Request\Get_Reporting_Balance_Summary', false ) ) {
			class_alias( self::class, 'WCPay\Core\Server\Request\Get_Reporting_Balance_Summary' );
		}
	}

	/**
	 * Create a request from normalized params.
	 *
	 * @param array<string,mixed> $params Request params.
	 * @return self
	 * @throws WooPaymentsApiException When params are invalid.
	 */
	public static function from_params( array $params ): self {
		$request = new self();

		if ( isset( $params['date_start'] ) && is_scalar( $params['date_start'] ) ) {
			$request->set_date_start( (string) $params['date_start'] );
		}

		if ( isset( $params['date_end'] ) && is_scalar( $params['date_end'] ) ) {
			$request->set_date_end( (string) $params['date_end'] );
		}

		if ( isset( $params['currency'] ) && is_scalar( $params['currency'] ) ) {
			$request->set_currency( (string) $params['currency'] );
		}

		return $request;
	}

	/**
	 * Returns the request's API.
	 *
	 * @return string
	 */
	public function get_api(): string {
		return 'reporting/balance_summary';
	}

	/**
	 * Set the report period start date.
	 *
	 * @param string $date_start Report period start date.
	 * @throws WooPaymentsApiException When the date is invalid.
	 */
	public function set_date_start( string $date_start ): void {
		$this->validate_rest_date_time( $date_start, 'date_start' );
		$this->set_param( 'date_start', $date_start );
	}

	/**
	 * Set the report period end date.
	 *
	 * @param string $date_end Report period end date.
	 * @throws WooPaymentsApiException When the date is invalid.
	 */
	public function set_date_end( string $date_end ): void {
		$this->validate_rest_date_time( $date_end, 'date_end' );
		$this->set_param( 'date_end', $date_end );
	}

	/**
	 * Set the report currency.
	 *
	 * @param string $currency Report currency.
	 * @throws WooPaymentsApiException When the currency is invalid.
	 */
	public function set_currency( string $currency ): void {
		$currency = strtolower( $currency );
		if ( ! self::is_valid_currency_code( $currency ) ) {
			throw new WooPaymentsApiException(
				sprintf(
					// translators: %s: currency code.
					esc_html__( '%s is not a valid currency code.', 'woocommerce' ),
					esc_html( $currency )
				),
				'wcpay_core_invalid_request_parameter_currency_code',
				400
			);
		}

		$this->set_param( 'currency', $currency );
	}

	/**
	 * Validate ISO-4217 format without checking account-supported currencies.
	 *
	 * @param mixed $currency Currency value.
	 * @return bool
	 */
	public static function is_valid_currency_code( $currency ): bool {
		return is_string( $currency ) && 1 === preg_match( self::CURRENCY_CODE_PATTERN, $currency );
	}

	/**
	 * Validate a REST date-time value.
	 *
	 * @param string $date  Date value.
	 * @param string $field Field name.
	 * @throws WooPaymentsApiException When the date is invalid.
	 */
	private function validate_rest_date_time( string $date, string $field ): void {
		if ( false !== rest_parse_date( $date, true ) ) {
			return;
		}

		throw new WooPaymentsApiException(
			sprintf(
				// translators: %s: request field name.
				esc_html__( '%s must be a valid date-time.', 'woocommerce' ),
				esc_html( $field )
			),
			'wcpay_core_invalid_request_parameter_date',
			400
		);
	}
}
