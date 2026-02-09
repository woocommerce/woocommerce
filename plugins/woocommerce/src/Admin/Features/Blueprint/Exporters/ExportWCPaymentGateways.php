<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use Automattic\WooCommerce\Blueprint\Steps\Step;

/**
 * ExportWCPaymentGateways class
 */
class ExportWCPaymentGateways implements StepExporter {
	/**
	 * Payment gateway IDs to exclude from export
	 *
	 * @var array|string[] Payment gateway IDs to exclude from export
	 */
	protected array $exclude_ids = array( 'pre_install_woocommerce_payments_promotion' );

	/**
	 * Export the step
	 *
	 * @return Step
	 */
	public function export(): Step {
		$options = array();
		$this->maybe_hide_wcpay_gateways();
		foreach ( $this->get_wc_payment_gateways() as $id => $payment_gateway ) {
			if ( in_array( $id, $this->exclude_ids, true ) ) {
				continue;
			}

			$options[ 'woocommerce_' . $id . '_settings' ] = $payment_gateway->settings;
		}

		return new SetSiteOptions( $options );
	}

	/**
	 * Return the payment gateways resgietered in WooCommerce
	 *
	 * @return string
	 */
	public function get_wc_payment_gateways() {
		return WC()->payment_gateways->payment_gateways();
	}

	/**
	 * Get the step name
	 *
	 * @return string
	 */
	public function get_step_name() {
		return 'wcPaymentGateways';
	}

	/**
	 * Maybe hide WooCommerce Payments gateways
	 *
	 * @return void
	 */
	protected function maybe_hide_wcpay_gateways() {
		if ( class_exists( 'WC_Payments' ) ) {
			\WC_Payments::hide_gateways_on_settings_page();
		}
	}

	/**
	 * Return label used in the frontend.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Payments', 'woocommerce' );
	}

	/**
	 * Return description used in the frontend.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Includes all settings in WooCommerce | Settings | Payments.', 'woocommerce' );
	}


	/**
	 * Check if the current user has the required capabilities for this step.
	 *
	 * @return bool True if the user has the required capabilities. False otherwise.
	 */
	public function check_step_capabilities(): bool {
		return current_user_can( 'manage_woocommerce' );
	}
}
