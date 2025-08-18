<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments as SettingsPaymentsService;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\DefaultPaymentGateways;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentsExtensionSuggestions;
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
		return __( 'Set up payments', 'woocommerce' );
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
			if ( $this->is_woopayments_active() ) {
				// If WooPayments is active, check if it is fully onboarded with a live account.
				$this->is_complete_result = $this->is_woopayments_onboarded() && ! $this->has_woopayments_test_account();
			} else {
				// If WooPayments is not active, check if there are any enabled gateways.
				$this->is_complete_result = self::has_gateways();
			}
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
	 * Check if the store has any enabled gateways.
	 *
	 * @return bool
	 */
	public static function has_gateways() {
		$gateways         = WC()->payment_gateways()->payment_gateways;
		$enabled_gateways = array_filter(
			$gateways,
			function ( $gateway ) {
				return 'yes' === $gateway->enabled;
			}
		);

		return ! empty( $enabled_gateways );
	}

	/**
	 * Check if the task is in progress.
	 *
	 * @return bool
	 */
	public function is_in_progress() {
		// If the task is already complete, it's not in progress.
		if ( $this->is_complete() ) {
			return false;
		}

		return ( $this->has_woopayments_live_account_in_progress() || $this->has_woopayments_test_account() );
	}

	/**
	 * The task in progress label.
	 *
	 * @return string
	 */
	public function in_progress_label() {
		// If WooPayments live account onboarding is in progress, show "Action needed" label.
		if ( $this->has_woopayments_live_account_in_progress() ) {
			return esc_html__( 'Action needed', 'woocommerce' );
		}

		return esc_html__( 'Test account', 'woocommerce' );
	}

	/**
	 * The task action URL.
	 *
	 * Empty string means the JS logic will handle the task linking.
	 *
	 * @return string
	 */
	public function get_action_url() {
		// Link to the Payments settings page.
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&from=' . SettingsPaymentsService::FROM_PAYMENTS_TASK );
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
			'wooPaymentsIsInstalled'                => $this->is_woopayments_installed(),
			'wooPaymentsSettingsCountryIsSupported' => $this->is_woopayments_supported_country( $this->get_payments_settings_country() ),
			'wooPaymentsIsOnboarded'                => $this->is_woopayments_onboarded(),
			'wooPaymentsHasTestAccount'             => $this->has_woopayments_test_account(),
			'wooPaymentsHasOtherProvidersEnabled'   => $this->has_providers_enabled_other_than_woopayments(),
			'wooPaymentsHasOtherProvidersNeedSetup' => $this->has_providers_needing_setup_other_than_woopayments(),
			'wooPaymentsHasOnlineGatewaysEnabled'   => $this->has_online_gateways(),
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
	 * Check if the WooPayments plugin is installed.
	 *
	 * @return bool
	 */
	private function is_woopayments_installed(): bool {
		if ( $this->is_woopayments_active() ) {
			// If it is active, it is also installed.
			return true;
		}

		$woopayments_suggestion = $this->get_woopayments_suggestion();
		// We should have the WooPayments suggestion, but if not, return false.
		if ( ! $woopayments_suggestion ) {
			return false;
		}

		// Check if the suggestion has its plugin installed.
		if ( ! empty( $woopayments_suggestion['plugin']['status'] ) &&
			PaymentsProviders::EXTENSION_INSTALLED === $woopayments_suggestion['plugin']['status'] ) {

			return true;
		}

		return false;
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
	 * Check if WooPayments has a live account onboarding in progress.
	 *
	 * @return bool
	 */
	private function has_woopayments_live_account_in_progress() {
		if ( $this->is_woopayments_onboarded() ) {
			return false;
		}

		$woopayments_provider = $this->get_woopayments_provider();
		// We should have the WooPayments provider, but if not, return false.
		if ( ! $woopayments_provider ) {
			return false;
		}

		// If we have a test account, we are not in live account onboarding.
		if ( $this->has_woopayments_test_account() ) {
			return false;
		}

		// Check the provider's state to determine if a live account onboarding is started.
		if ( ! empty( $woopayments_provider['onboarding']['state']['started'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if WooPayments is onboarded and has a test [drive] account.
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

		// Check the provider's state to determine if a test [drive] account is in use.
		if ( ! empty( $woopayments_provider['onboarding']['state']['test_drive_account'] ) ) {
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
	 * Check if the store has any enabled online gateways.
	 *
	 * @return bool
	 */
	private function has_online_gateways(): bool {
		$providers = $this->get_payments_providers();

		foreach ( $providers as $provider ) {
			// Check if the provider is enabled and is not WooPayments.
			if (
				! empty( $provider['state']['enabled'] ) &&
				! empty( $provider['id'] ) &&
				! in_array( $provider['id'], array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID, WC_Gateway_COD::ID ), true )
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
			/**
			 * The Payments Settings [page] service.
			 *
			 * @var SettingsPaymentsService $settings_payments_service
			 */
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
	 * The list can include payments extension suggestions, the same as on the Payments Settings page.
	 *
	 * @return array The list of payments providers.
	 */
	private function get_payments_providers(): array {
		try {
			/**
			 * The Payments Settings [page] service.
			 *
			 * @var SettingsPaymentsService $settings_payments_service
			 */
			$settings_payments_service = wc_get_container()->get( SettingsPaymentsService::class );

			return $settings_payments_service->get_payment_providers( $settings_payments_service->get_country(), false );
		} catch ( \Throwable $e ) {
			// In case of any error, return an empty array.
			return array();
		}
	}

	/**
	 * Get the list of payments extension suggestions as it is used on the Payments Settings page.
	 *
	 * @return array The list of payments extension suggestions.
	 */
	private function get_payments_extension_suggestions(): array {
		try {
			/**
			 * The Payments Settings [page] service.
			 *
			 * @var SettingsPaymentsService $settings_payments_service
			 */
			$settings_payments_service = wc_get_container()->get( SettingsPaymentsService::class );

			return $settings_payments_service->get_payment_extension_suggestions( $settings_payments_service->get_country() );
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
			if ( ! empty( $provider['id'] ) && PaymentsProviders\WooPayments\WooPaymentsService::GATEWAY_ID === $provider['id'] ) {
				return $provider;
			}
		}

		return null;
	}

	/**
	 * Get the WooPayments payments extension suggestion details from the lists used on the Payments Settings page.
	 *
	 * @return array|null The WooPayments suggestion details or null if not found.
	 */
	private function get_woopayments_suggestion(): ?array {
		// First, check the payments providers list.
		$providers = $this->get_payments_providers();
		foreach ( $providers as $provider ) {
			if ( ! empty( $provider['_type'] ) &&
				PaymentsProviders::TYPE_SUGGESTION === $provider['_type'] &&
				! empty( $provider['_suggestion_id'] ) &&
				PaymentsExtensionSuggestions::WOOPAYMENTS === $provider['_suggestion_id'] ) {

				return $provider;
			}
		}

		// If not found in the main list, check the payments extension suggestions list.
		$suggestions = $this->get_payments_extension_suggestions();
		foreach ( $suggestions as $suggestion ) {
			if ( ! empty( $suggestion['id'] ) &&
				PaymentsExtensionSuggestions::WOOPAYMENTS === $suggestion['id'] ) {

				return $suggestion;
			}
		}

		return null;
	}
}
