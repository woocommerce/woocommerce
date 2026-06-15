<?php
/**
 * WooPaymentsOnboardingAdapter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\PaymentGateway;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Payments\NativeWooPaymentsGateway;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Throwable;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Bridges WooPayments onboarding/admin state to the native payments provider seam.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsOnboardingAdapter {

	/**
	 * Legacy proxy.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $proxy;

	/**
	 * WooPayments provider.
	 *
	 * @var WooPaymentsProvider
	 */
	private WooPaymentsProvider $provider;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param LegacyProxy         $proxy    Legacy proxy.
	 * @param WooPaymentsProvider $provider WooPayments provider.
	 */
	final public function init( LegacyProxy $proxy, WooPaymentsProvider $provider ): void {
		$this->proxy    = $proxy;
		$this->provider = $provider;
	}

	/**
	 * Tell whether the standalone WooPayments extension is active.
	 *
	 * @return bool
	 */
	public function is_extension_active(): bool {
		return (bool) $this->get_proxy()->call_function( 'class_exists', '\WC_Payments' );
	}

	/**
	 * Tell whether the native WooPayments provider can process operations.
	 *
	 * @return bool
	 */
	public function is_native_provider_available(): bool {
		try {
			return $this->get_provider()->can_process_payments();
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Tell whether any WooPayments runtime can support onboarding/admin state.
	 *
	 * @return bool
	 */
	public function is_onboarding_runtime_available(): bool {
		return $this->is_extension_active() || $this->is_native_provider_available();
	}

	/**
	 * Get the active WooPayments gateway.
	 *
	 * @return WC_Payment_Gateway
	 * @throws \RuntimeException When the WooPayments gateway is not available.
	 */
	public function get_payment_gateway(): WC_Payment_Gateway {
		if ( $this->is_extension_active() ) {
			$gateway = $this->get_proxy()->call_static( '\WC_Payments', 'get_gateway' );

			if ( $gateway instanceof WC_Payment_Gateway ) {
				return $gateway;
			}
		}

		if ( $this->is_native_provider_available() ) {
			$gateway = wc_get_container()->get( NativeWooPaymentsGateway::class );

			if ( $gateway instanceof WC_Payment_Gateway ) {
				return $gateway;
			}
		}

		throw new \RuntimeException( 'WooPayments gateway is not available.' );
	}

	/**
	 * Determine if WooPayments has an account set up.
	 *
	 * @param PaymentGateway $provider Admin payment gateway provider.
	 * @return bool
	 */
	public function has_account( PaymentGateway $provider ): bool {
		if ( ! $this->is_onboarding_runtime_available() ) {
			return false;
		}

		try {
			return $provider->is_account_connected( $this->get_payment_gateway() );
		} catch ( Throwable $e ) {
			return false;
		}
	}

	/**
	 * Determine if WooPayments has a valid, fully onboarded account set up.
	 *
	 * @param PaymentGateway $provider Admin payment gateway provider.
	 * @return bool
	 */
	public function has_valid_account( PaymentGateway $provider ): bool {
		if ( ! $this->has_account( $provider ) || ! $this->is_extension_active() ) {
			return false;
		}

		$account_service = $this->get_account_service();

		return is_object( $account_service ) &&
			is_callable( array( $account_service, 'is_stripe_account_valid' ) ) &&
			(bool) $account_service->is_stripe_account_valid();
	}

	/**
	 * Determine if WooPayments has a working account set up.
	 *
	 * @param PaymentGateway $provider Admin payment gateway provider.
	 * @return bool
	 */
	public function has_working_account( PaymentGateway $provider ): bool {
		if ( ! $this->has_account( $provider ) ) {
			return false;
		}

		$account_status = $this->get_account_status_data();

		return ! empty( $account_status['paymentsEnabled'] );
	}

	/**
	 * Determine if WooPayments has a test account set up.
	 *
	 * @param PaymentGateway $provider Admin payment gateway provider.
	 * @return bool
	 */
	public function has_test_account( PaymentGateway $provider ): bool {
		if ( ! $this->has_account( $provider ) ) {
			return false;
		}

		$account_status = $this->get_account_status_data();

		return ! empty( $account_status['testDrive'] );
	}

	/**
	 * Determine if WooPayments has a sandbox account set up.
	 *
	 * @param PaymentGateway $provider Admin payment gateway provider.
	 * @return bool
	 */
	public function has_sandbox_account( PaymentGateway $provider ): bool {
		if ( ! $this->has_account( $provider ) ) {
			return false;
		}

		$account_status = $this->get_account_status_data();

		return empty( $account_status['isLive'] ) && empty( $account_status['testDrive'] );
	}

	/**
	 * Determine if WooPayments has a live account set up.
	 *
	 * @param PaymentGateway $provider Admin payment gateway provider.
	 * @return bool
	 */
	public function has_live_account( PaymentGateway $provider ): bool {
		if ( ! $this->has_account( $provider ) ) {
			return false;
		}

		$account_status = $this->get_account_status_data();

		return ! empty( $account_status['isLive'] );
	}

	/**
	 * Get the fallback URL for the embedded KYC flow.
	 *
	 * @param PaymentGateway $provider Admin payment gateway provider.
	 * @return string
	 */
	public function get_onboarding_kyc_fallback_url( PaymentGateway $provider ): string {
		if (
			$this->is_extension_active() &&
			$this->get_proxy()->call_function( 'is_callable', '\WC_Payments_Account::get_connect_url' )
		) {
			return (string) $this->get_proxy()->call_static( '\WC_Payments_Account', 'get_connect_url', WooPaymentsService::FROM_NOX_IN_CONTEXT );
		}

		return $provider->get_onboarding_url(
			$this->get_payment_gateway(),
			Utils::wc_payments_settings_url(
				WooPaymentsService::ONBOARDING_PATH_BASE,
				array( 'from' => WooPaymentsService::FROM_KYC )
			)
		);
	}

	/**
	 * Get the WooPayments Overview page URL.
	 *
	 * @return string
	 */
	public function get_overview_page_url(): string {
		if (
			$this->is_extension_active() &&
			$this->get_proxy()->call_function( 'is_callable', '\WC_Payments_Account::get_overview_page_url' )
		) {
			return add_query_arg(
				array(
					'from' => WooPaymentsService::FROM_NOX_IN_CONTEXT,
				),
				$this->get_proxy()->call_static( '\WC_Payments_Account', 'get_overview_page_url' )
			);
		}

		return add_query_arg(
			array(
				'page' => 'wc-admin',
				'path' => '/payments/overview',
				'from' => WooPaymentsService::FROM_NOX_IN_CONTEXT,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Get the WooPayments account service.
	 *
	 * @return object|null
	 */
	private function get_account_service(): ?object {
		if ( ! $this->is_extension_active() ) {
			return null;
		}

		try {
			$account_service = $this->get_proxy()->call_static( '\WC_Payments', 'get_account_service' );

			return is_object( $account_service ) ? $account_service : null;
		} catch ( Throwable $e ) {
			return null;
		}
	}

	/**
	 * Get account status data from the active WooPayments account service.
	 *
	 * @return array<string,mixed>
	 */
	private function get_account_status_data(): array {
		$account_service = $this->get_account_service();

		if ( ! is_object( $account_service ) || ! is_callable( array( $account_service, 'get_account_status_data' ) ) ) {
			return array();
		}

		$account_status = $account_service->get_account_status_data();

		return is_array( $account_status ) ? $account_status : array();
	}

	/**
	 * Get the legacy proxy.
	 *
	 * @return LegacyProxy
	 */
	private function get_proxy(): LegacyProxy {
		if ( ! isset( $this->proxy ) ) {
			$this->proxy = wc_get_container()->get( LegacyProxy::class );
		}

		return $this->proxy;
	}

	/**
	 * Get the WooPayments provider.
	 *
	 * @return WooPaymentsProvider
	 */
	private function get_provider(): WooPaymentsProvider {
		if ( ! isset( $this->provider ) ) {
			$this->provider = wc_get_container()->get( WooPaymentsProvider::class );
		}

		return $this->provider;
	}
}
