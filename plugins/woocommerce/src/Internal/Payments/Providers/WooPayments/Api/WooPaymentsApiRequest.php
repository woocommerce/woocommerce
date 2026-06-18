<?php
/**
 * WooPaymentsApiRequest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPaginatedListRequest;

/**
 * Mutable WooPayments request object exposed to legacy compatibility filters.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsApiRequest extends WooPaymentsPaginatedListRequest {

	protected const DEFAULT_PARAMS = array();

	/**
	 * WooPayments API path.
	 *
	 * @var string
	 */
	private string $api = '';

	/**
	 * HTTP method.
	 *
	 * @var string
	 */
	private string $method = 'GET';

	/**
	 * Create a generic WooPayments API request object.
	 *
	 * @param array<int|string,mixed> $params Request params.
	 * @param string                  $api    WooPayments API path.
	 * @param string                  $method HTTP method.
	 * @return self
	 */
	public static function create( array $params, string $api, string $method ): self {
		$request = new self();

		foreach ( $params as $key => $value ) {
			$request->set_param( (string) $key, $value );
		}

		$request->set_api( $api );
		$request->set_method( $method );

		return $request;
	}

	/**
	 * Register legacy request aliases when the WooPayments extension is absent.
	 */
	public static function register_legacy_aliases(): void {
		self::register_legacy_base_aliases();

		if ( ! class_exists( 'WCPay\Core\Server\Request\Get_Request', false ) ) {
			class_alias( self::class, 'WCPay\Core\Server\Request\Get_Request' );
		}
	}

	/**
	 * Get the WooPayments API path.
	 *
	 * @return string
	 */
	public function get_api(): string {
		return $this->api;
	}

	/**
	 * Set the WooPayments API path.
	 *
	 * @param string $api WooPayments API path.
	 */
	public function set_api( string $api ): void {
		$this->api = ltrim( $api, '/' );
	}

	/**
	 * Get the HTTP method.
	 *
	 * @return string
	 */
	public function get_method(): string {
		return $this->method;
	}

	/**
	 * Set the HTTP method.
	 *
	 * @param string $method HTTP method.
	 */
	public function set_method( string $method ): void {
		$this->method = strtoupper( $method );
	}
}
