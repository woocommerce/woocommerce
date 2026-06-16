<?php
/**
 * WooPaymentsLegacyRuntime class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Centralizes access to the transitional WooPayments plugin runtime.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsLegacyRuntime {

	/**
	 * Legacy proxy.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $legacy_proxy;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param LegacyProxy $legacy_proxy Legacy proxy.
	 */
	final public function init( LegacyProxy $legacy_proxy ): void {
		$this->legacy_proxy = $legacy_proxy;
	}

	/**
	 * Tell whether the WooPayments plugin runtime is loaded.
	 *
	 * @return bool
	 */
	public function is_loaded(): bool {
		try {
			return (bool) $this->legacy_proxy->call_function( 'class_exists', 'WC_Payments' );
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get the active WooPayments gateway.
	 *
	 * @return object|null
	 */
	public function get_gateway(): ?object {
		return $this->get_wc_payments_service( 'get_gateway' );
	}

	/**
	 * Get the WooPayments account service.
	 *
	 * @return object|null
	 */
	public function get_account_service(): ?object {
		return $this->get_wc_payments_service( 'get_account_service' );
	}

	/**
	 * Get the WooPayments API client.
	 *
	 * @return object|null
	 */
	public function get_payments_api_client(): ?object {
		return $this->get_wc_payments_service( 'get_payments_api_client' );
	}

	/**
	 * Get the WooCommerce logger.
	 *
	 * @return object|null
	 */
	public function get_logger(): ?object {
		try {
			$logger = $this->legacy_proxy->call_function( 'wc_get_logger' );
		} catch ( \Throwable $e ) {
			return null;
		}

		return is_object( $logger ) ? $logger : null;
	}

	/**
	 * Get the WooPayments account connect URL.
	 *
	 * @param string $source Source context.
	 * @return string|null
	 */
	public function get_account_connect_url( string $source ): ?string {
		return $this->get_account_static_url( 'get_connect_url', $source );
	}

	/**
	 * Get the WooPayments account overview page URL.
	 *
	 * @return string|null
	 */
	public function get_account_overview_page_url(): ?string {
		return $this->get_account_static_url( 'get_overview_page_url' );
	}

	/**
	 * Get a WooPayments service through the legacy runtime.
	 *
	 * @param string $method_name WC_Payments static accessor.
	 * @return object|null
	 */
	private function get_wc_payments_service( string $method_name ): ?object {
		if ( ! $this->is_loaded() ) {
			return null;
		}

		try {
			$service = $this->legacy_proxy->call_static( 'WC_Payments', $method_name );
		} catch ( \Throwable $e ) {
			return null;
		}

		return is_object( $service ) ? $service : null;
	}

	/**
	 * Get a WooPayments account URL through the legacy account static helper.
	 *
	 * @param string $method_name WC_Payments_Account static URL accessor.
	 * @param mixed  ...$args     Method arguments.
	 * @return string|null
	 */
	private function get_account_static_url( string $method_name, ...$args ): ?string {
		if ( ! $this->is_loaded() ) {
			return null;
		}

		try {
			if ( ! $this->legacy_proxy->call_function( 'is_callable', "\\WC_Payments_Account::{$method_name}" ) ) {
				return null;
			}

			$url = $this->legacy_proxy->call_static( 'WC_Payments_Account', $method_name, ...$args );
		} catch ( \Throwable $e ) {
			return null;
		}

		return is_scalar( $url ) ? (string) $url : null;
	}
}
