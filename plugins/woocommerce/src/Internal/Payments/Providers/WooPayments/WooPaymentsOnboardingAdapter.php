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
	 * WooPayments legacy runtime.
	 *
	 * @var WooPaymentsLegacyRuntime
	 */
	private WooPaymentsLegacyRuntime $legacy_runtime;

	/**
	 * WooPayments provider.
	 *
	 * @var WooPaymentsProvider
	 */
	private WooPaymentsProvider $provider;

	/**
	 * Native WooPayments gateway.
	 *
	 * @var NativeWooPaymentsGateway
	 */
	private NativeWooPaymentsGateway $native_gateway;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsLegacyRuntime $legacy_runtime WooPayments legacy runtime.
	 * @param WooPaymentsProvider      $provider       WooPayments provider.
	 * @param NativeWooPaymentsGateway $native_gateway Native WooPayments gateway.
	 */
	final public function init( WooPaymentsLegacyRuntime $legacy_runtime, WooPaymentsProvider $provider, NativeWooPaymentsGateway $native_gateway ): void {
		$this->legacy_runtime = $legacy_runtime;
		$this->provider       = $provider;
		$this->native_gateway = $native_gateway;
	}

	/**
	 * Tell whether the standalone WooPayments extension is active.
	 *
	 * @return bool
	 */
	public function is_extension_active(): bool {
		return $this->get_legacy_runtime()->is_loaded();
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
			$gateway = $this->get_legacy_runtime()->get_gateway();

			if ( $gateway instanceof WC_Payment_Gateway ) {
				return $gateway;
			}
		}

		if ( $this->is_native_provider_available() ) {
			return $this->native_gateway;
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
		$connect_url = $this->get_legacy_runtime()->get_account_connect_url( WooPaymentsService::FROM_NOX_IN_CONTEXT );
		if ( null !== $connect_url ) {
			return $connect_url;
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
		$overview_url = $this->get_legacy_runtime()->get_account_overview_page_url();
		if ( null !== $overview_url ) {
			return add_query_arg(
				array(
					'from' => WooPaymentsService::FROM_NOX_IN_CONTEXT,
				),
				$overview_url
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
		return $this->get_legacy_runtime()->get_account_service();
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
	 * Get the WooPayments legacy runtime.
	 *
	 * @return WooPaymentsLegacyRuntime
	 */
	private function get_legacy_runtime(): WooPaymentsLegacyRuntime {
		return $this->legacy_runtime;
	}

	/**
	 * Get the WooPayments provider.
	 *
	 * @return WooPaymentsProvider
	 */
	private function get_provider(): WooPaymentsProvider {
		return $this->provider;
	}
}
