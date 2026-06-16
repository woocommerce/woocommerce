<?php
/**
 * WooPaymentsLegacyRuntime class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Centralizes access to the transitional WooPayments plugin runtime.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsLegacyRuntime {

	/**
	 * Default WooPayments admin onboarding source context.
	 */
	public const ADMIN_ONBOARDING_FROM_DEFAULT = 'WCADMIN_PAYMENT_SETTINGS';

	/**
	 * Default WooPayments admin onboarding source slug.
	 */
	public const ADMIN_ONBOARDING_SOURCE_DEFAULT = 'wcadmin-settings-page';

	/**
	 * WooPayments admin onboarding source context constant.
	 */
	private const ADMIN_ONBOARDING_FROM_CONSTANT = 'WC_Payments_Onboarding_Service::FROM_WCADMIN_PAYMENTS_SETTINGS';

	/**
	 * WooPayments admin onboarding source slug constant.
	 */
	private const ADMIN_ONBOARDING_SOURCE_CONSTANT = 'WC_Payments_Onboarding_Service::SOURCE_WCADMIN_SETTINGS_PAGE';

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
	public function get_account_connect_url( string $source = '' ): ?string {
		if ( '' === $source ) {
			return $this->get_account_static_url( 'get_connect_url' );
		}

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
	 * Tell whether WooPayments is in test mode.
	 *
	 * @return bool|null
	 */
	public function is_test_mode(): ?bool {
		return $this->call_mode_boolean_method( 'is_test' );
	}

	/**
	 * Tell whether WooPayments is in development mode.
	 *
	 * @return bool|null
	 */
	public function is_dev_mode(): ?bool {
		return $this->call_mode_boolean_method( 'is_dev' );
	}

	/**
	 * Tell whether WooPayments is in test-mode onboarding.
	 *
	 * @return bool|null
	 */
	public function is_test_mode_onboarding(): ?bool {
		return $this->call_mode_boolean_method( 'is_test_mode_onboarding' );
	}

	/**
	 * Get WooPayments account status data.
	 *
	 * @return array|null
	 */
	public function get_account_status_data(): ?array {
		$account_service = $this->get_account_service();
		if ( ! is_object( $account_service ) ) {
			return null;
		}

		try {
			$account_status_data_reader = array( $account_service, 'get_account_status_data' );
			if ( ! $this->legacy_proxy->call_function( 'method_exists', $account_service, 'get_account_status_data' ) ||
				! $this->legacy_proxy->call_function( 'is_callable', $account_status_data_reader ) ||
				! is_callable( $account_status_data_reader ) ) {
				return null;
			}

			$account_status_data = $account_status_data_reader();
		} catch ( \Throwable $e ) {
			return null;
		}

		return is_array( $account_status_data ) ? $account_status_data : null;
	}

	/**
	 * Tell whether the WooPayments account cache contains account data.
	 *
	 * @return bool
	 */
	public function has_cached_account_data(): bool {
		$account_data = $this->get_cached_account_data();

		return ! empty( $account_data['account_id'] );
	}

	/**
	 * Tell whether the WooPayments account cache contains an onboarded account.
	 *
	 * @return bool
	 */
	public function is_account_onboarded_from_cache(): bool {
		$account_data = $this->get_cached_account_data();
		if ( empty( $account_data['account_id'] ) || empty( $account_data['details_submitted'] ) ) {
			return false;
		}

		return filter_var( $account_data['details_submitted'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ?? false;
	}

	/**
	 * Get WooPayments supported countries.
	 *
	 * @return array|null
	 */
	public function get_supported_countries(): ?array {
		foreach ( array( 'WC_Payments_Utils', '\WC_Payments_Utils' ) as $class_name ) {
			try {
				if ( ! $this->legacy_proxy->call_function( 'class_exists', $class_name ) ||
					! $this->legacy_proxy->call_function( 'is_callable', "{$class_name}::supported_countries" ) ) {
					continue;
				}

				$supported_countries = $this->legacy_proxy->call_static( $class_name, 'supported_countries' );
			} catch ( \Throwable $e ) {
				continue;
			}

			if ( is_array( $supported_countries ) ) {
				return $supported_countries;
			}
		}

		return null;
	}

	/**
	 * Get the WooPayments admin onboarding context.
	 *
	 * @return array{from:string,source:string}
	 */
	public function get_admin_onboarding_context(): array {
		return array(
			'from'   => $this->get_constant_value( self::ADMIN_ONBOARDING_FROM_CONSTANT, self::ADMIN_ONBOARDING_FROM_DEFAULT ),
			'source' => $this->get_constant_value( self::ADMIN_ONBOARDING_SOURCE_CONSTANT, self::ADMIN_ONBOARDING_SOURCE_DEFAULT ),
		);
	}

	/**
	 * Reset the WooPayments onboarding test-mode option when the legacy constant is available.
	 *
	 * @return void
	 */
	public function reset_onboarding_test_mode_option(): void {
		try {
			if ( ! $this->legacy_proxy->call_function( 'class_exists', 'WC_Payments_Onboarding_Service' ) ||
				! Constants::is_defined( 'WC_Payments_Onboarding_Service::TEST_MODE_OPTION' ) ) {
				return;
			}

			$this->legacy_proxy->call_function(
				'update_option',
				Constants::get_constant( 'WC_Payments_Onboarding_Service::TEST_MODE_OPTION' ),
				'no'
			);
		} catch ( \Throwable $e ) {
			return;
		}
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
	 * Get the WooPayments mode service.
	 *
	 * @return object|null
	 */
	private function get_mode_service(): ?object {
		return $this->get_wc_payments_service( 'mode' );
	}

	/**
	 * Call a boolean method on the WooPayments mode service.
	 *
	 * @param string $method_name Mode service method name.
	 * @return bool|null
	 */
	private function call_mode_boolean_method( string $method_name ): ?bool {
		$mode_service = $this->get_mode_service();
		if ( ! is_object( $mode_service ) ) {
			return null;
		}

		try {
			if ( ! $this->legacy_proxy->call_function( 'method_exists', $mode_service, $method_name ) ||
				! $this->legacy_proxy->call_function( 'is_callable', array( $mode_service, $method_name ) ) ) {
				return null;
			}

			return (bool) $mode_service->{$method_name}();
		} catch ( \Throwable $e ) {
			return null;
		}
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
			$is_callable = $this->legacy_proxy->call_function( 'is_callable', "WC_Payments_Account::{$method_name}" ) ||
				$this->legacy_proxy->call_function( 'is_callable', "\\WC_Payments_Account::{$method_name}" );
			if ( ! $is_callable ) {
				return null;
			}

			$url = $this->legacy_proxy->call_static( 'WC_Payments_Account', $method_name, ...$args );
		} catch ( \Throwable $e ) {
			return null;
		}

		return is_scalar( $url ) ? (string) $url : null;
	}

	/**
	 * Get a scalar constant value with fallback.
	 *
	 * @param string $constant_name Constant name.
	 * @param string $fallback      Fallback value.
	 * @return string
	 */
	private function get_constant_value( string $constant_name, string $fallback ): string {
		if ( ! Constants::is_defined( $constant_name ) ) {
			return $fallback;
		}

		$value = Constants::get_constant( $constant_name );

		return is_scalar( $value ) ? (string) $value : $fallback;
	}

	/**
	 * Get the normalized WooPayments account cache data.
	 *
	 * @return array<string,mixed>
	 */
	private function get_cached_account_data(): array {
		try {
			$account_data = $this->legacy_proxy->call_function( 'get_option', 'wcpay_account_data', array() );
		} catch ( \Throwable $e ) {
			return array();
		}

		if ( ! is_array( $account_data ) || empty( $account_data['data'] ) || ! is_array( $account_data['data'] ) ) {
			return array();
		}

		return $account_data['data'];
	}
}
