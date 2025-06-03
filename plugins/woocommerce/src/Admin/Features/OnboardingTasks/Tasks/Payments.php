<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments as SettingsPaymentsService;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\DefaultPaymentGateways;
use WC_Gateway_BACS;
use WC_Gateway_Cheque;
use WC_Gateway_COD;

/**
 * Payments Task
 */
class Payments extends Task {

	/**
	 * Used to cache is_complete() method result.
	 *
	 * @var null
	 */
	private $is_complete_result = null;

	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'payments';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Get paid', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __(
			'Choose payment providers and enable payment methods at checkout.',
			'woocommerce'
		);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '5 minutes', 'woocommerce' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		if ( null === $this->is_complete_result ) {
			$this->is_complete_result = ! $this->has_woopayments_test_account() && self::has_online_gateways();
		}

		return $this->is_complete_result;
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		// The task is always visible.
		return true;
	}

	/**
	 * Check if the store has any enabled online gateways.
	 *
	 * @return bool
	 */
	public static function has_online_gateways() {
		$gateways         = WC()->payment_gateways()->payment_gateways;
		$enabled_gateways = array_filter(
			$gateways,
			function ( $gateway ) {
				return 'yes' === $gateway->enabled && ! in_array( $gateway->id, array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID, WC_Gateway_COD::ID ), true );
			}
		);

		return ! empty( $enabled_gateways );
	}

	/**
	 * The task action URL.
	 *
	 * Empty string means the task linking will be handled by the JS logic.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=checkout' );
	}

	/**
	 * Additional data to be passed to the front-end JS logic.
	 *
	 * Primarily used to inform the behavior of the Payments task in the LYS context.
	 *
	 * @return array
	 */
	public function get_additional_data() {
		return array(
			'wooPaymentsIsActive'                   => $this->is_woopayments_active(),
			'wooPaymentsSettingsCountryIsSupported' => $this->is_woopayments_supported_country( $this->get_payments_settings_country() ),
			'wooPaymentsIsOnboarded'                => $this->is_woopayments_onboarded(),
			'wooPaymentsHasTestAccount'             => $this->has_woopayments_test_account(),
			'wooPaymentsHasOtherProvidersEnabled'   => $this->has_providers_enabled_other_than_woopayments(),
			'wooPaymentsHasOtherProvidersNeedSetup' => $this->has_providers_needing_setup_other_than_woopayments(),
		);
	}

	/**
	 * Check if the WooPayments plugin is active.
	 *
	 * @return bool
	 */
	private function is_woopayments_active(): bool {
		return class_exists( '\WC_Payments' );
	}

	/**
	 * Check if WooPayments is completely onboarded.
	 *
	 * @return bool
	 */
	private function is_woopayments_onboarded(): bool {
		if ( ! $this->is_woopayments_active() ) {
			return false;
		}

		$woopayments_provider = $this->get_woopayments_provider();
		// We should have the WooPayments provider, but if not, return false.
		if ( ! $woopayments_provider ) {
			return false;
		}

		// Check the provider's state to determine if it is onboarded.
		if ( ! empty( $woopayments_provider['onboarding']['state']['completed'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if WooPayments is onboarded and has a test account.
	 *
	 * @return bool
	 */
	private function has_woopayments_test_account(): bool {
		if ( ! $this->is_woopayments_onboarded() ) {
			return false;
		}

		$woopayments_provider = $this->get_woopayments_provider();
		// We should have the WooPayments provider, but if not, return false.
		if ( ! $woopayments_provider ) {
			return false;
		}

		// Check the provider's state to determine if a test account is in use.
		if ( ! empty( $woopayments_provider['onboarding']['state']['test_mode'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the store is in a WooPayments-supported geography.
	 *
	 * @param string $country_code Country code to check. If not provided, uses store base country.
	 *
	 * @return bool Whether the country is supported by WooPayments.
	 */
	private function is_woopayments_supported_country( string $country_code ): bool {
		if ( class_exists( '\WC_Payments_Utils' ) && is_callable( array( '\WC_Payments_Utils', 'supported_countries' ) ) ) {
			$supported_countries = array_keys( \WC_Payments_Utils::supported_countries() );
			return in_array( $country_code, $supported_countries, true );
		}

		// WooPayments is not installed and active, use core's list of supported countries.
		$supported_countries = DefaultPaymentGateways::get_wcpay_countries();
		return in_array( $country_code, $supported_countries, true );
	}

	/**
	 * Check if the store has any enabled providers other than WooPayments.
	 *
	 * @return bool
	 */
	public function has_providers_enabled_other_than_woopayments(): bool {
		$providers = $this->get_payments_providers();

		foreach ( $providers as $provider ) {
			// Check if the provider is enabled and is not WooPayments.
			if (
				! empty( $provider['state']['enabled'] ) &&
				! empty( $provider['id'] ) &&
				'woocommerce_payments' !== $provider['id']
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if any non-WooPayments providers need setup.
	 *
	 * @return bool
	 */
	private function has_providers_needing_setup_other_than_woopayments(): bool {
		$providers = $this->get_payments_providers();

		foreach ( $providers as $provider ) {
			// Check if the provider needs setup and is not WooPayments.
			if (
				! empty( $provider['state']['needs_setup'] ) &&
				! empty( $provider['id'] ) &&
				'woocommerce_payments' !== $provider['id']
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the store's business registration country/location as it is used on the Payments Settings page.
	 *
	 * @return string The business registration country/location code.
	 */
	private function get_payments_settings_country(): string {
		try {
			$settings_payments_service = wc_get_container()->get( SettingsPaymentsService::class );

			return $settings_payments_service->get_country();
		} catch ( \Throwable $e ) {
			// In case of any error, return the WooCommerce base country.
			return WC()->countries->get_base_country();
		}
	}

	/**
	 * Get the list of payments providers as it is used on the Payments Settings page.
	 *
	 * @return array The list of payments providers.
	 */
	private function get_payments_providers(): array {
		try {
			$settings_payments_service = wc_get_container()->get( SettingsPaymentsService::class );

			return $settings_payments_service->get_payment_providers( $settings_payments_service->get_country() );
		} catch ( \Throwable $e ) {
			// In case of any error, return an empty array.
			return array();
		}
	}

	/**
	 * Get the WooPayments provider details from the list used on the Payments Settings page.
	 *
	 * @return array|null The WooPayments provider details or null if not found.
	 */
	private function get_woopayments_provider(): ?array {
		$providers = $this->get_payments_providers();
		foreach ( $providers as $provider ) {
			if ( ! empty( $provider['id'] ) && 'woocommerce_payments' === $provider['id'] ) {
				return $provider;
			}
		}

		return null;
	}
}
