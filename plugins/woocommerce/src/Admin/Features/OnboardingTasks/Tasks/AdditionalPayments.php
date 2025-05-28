<?php
namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments as SettingsPaymentsService;

/**
 * Payments Task
 */
class AdditionalPayments extends Payments {

	/**
	 * Used to cache is_complete() method result.
	 *
	 * @var null
	 */
	private $is_complete_result = null;

	/**
	 * Used to cache can_view() method result.
	 *
	 * @var null
	 */
	private $can_view_result = null;


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
		return __(
			'Set up additional payment options',
			'woocommerce'
		);
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
		return __( '2 minutes', 'woocommerce' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		if ( null === $this->is_complete_result ) {
			$this->is_complete_result = $this->has_enabled_non_psp_payment_suggestion();
		}

		return $this->is_complete_result;
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		if ( null !== $this->can_view_result ) {
			return $this->can_view_result;
		}

		// Always show task if there are any gateways enabled (i.e. the Payments task is complete).
		if ( self::has_gateways() ) {
			$this->can_view_result = true;
		} else {
			$this->can_view_result = false;
		}

		return $this->can_view_result;
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url(): string {
		// We auto-expand the "Other" section to show the additional payment methods.
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&other_pes_section=expanded&from=' . SettingsPaymentsService::FROM_ADDITIONAL_PAYMENTS_TASK );
	}

	/**
	 * Check if there are any enabled non-PSP payment suggestions.
	 *
	 * @return bool True if there are enabled non-PSP payment suggestions, false otherwise.
	 */
	private function has_enabled_non_psp_payment_suggestion(): bool {
		$providers = $this->get_payment_providers();
		foreach ( $providers as $provider ) {
			// Check if the provider is enabled and has a suggestion category ID that matches the ones we are interested in.
			if (
				! empty( $provider['state']['enabled'] ) &&
				! empty( $provider['_suggestion_category_id'] ) &&
				in_array( $provider['_suggestion_category_id'], array( PaymentProviders::CATEGORY_BNPL, PaymentProviders::CATEGORY_EXPRESS_CHECKOUT, PaymentProviders::CATEGORY_CRYPTO ), true )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the list of payments providers as it is used on the Payments Settings page.
	 *
	 * @return array The list of payment providers.
	 */
	private function get_payment_providers(): array {
		try {
			$settings_payments_service = wc_get_container()->get( SettingsPaymentsService::class );

			$providers = $settings_payments_service->get_payment_providers( $settings_payments_service->get_country() );
		} catch ( \Exception $e ) {
			// In case of any error, return an empty array.
			$providers = array();
		}

		return $providers;
	}
}
