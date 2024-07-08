<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Admin\Features\Blueprint\Steps\SetWCPaymentGateways;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\Step;

class ExportWCPaymentGateways implements StepExporter {
	protected array $exclude_ids = array( 'pre_install_woocommerce_payments_promotion' );

	public function export(): Step {
		$step = new SetWCPaymentGateways();
		$this->maybe_hide_wcpay_gateways();
		foreach ( WC()->payment_gateways->payment_gateways() as $id => $payment_gateway ) {
			if ( in_array( $id, $this->exclude_ids, true ) ) {
				continue;
			}

			$step->add_payment_gateway(
				$id,
				$payment_gateway->get_title(),
				$payment_gateway->get_description(),
				$payment_gateway->is_available() ? 'yes' : 'no'
			);
		}

		return $step;
	}

	public function get_step_name() {
		return SetWCPaymentGateways::get_step_name();
	}

	protected function maybe_hide_wcpay_gateways() {
		if ( class_exists( 'WC_Payments' ) ) {
			\WC_Payments::hide_gateways_on_settings_page();
		}
	}
}
