<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

class ExportPaymentGateways implements ExportsStepSchema {
	protected array $exclude_ids = array('pre_install_woocommerce_payments_promotion');

	public function export() {
		$payment_gateways = array();
		$this->maybe_hide_wcpay_gateways();
		foreach (WC()->payment_gateways->payment_gateways() as $id => $payment_gateway) {
			if (in_array($id, $this->exclude_ids, true)) {
				continue;
			}
			$payment_gateways[$id] = array(
				'title'	=> $payment_gateway->get_title(),
				'description' => $payment_gateway->get_description(),
				'enabled' => $payment_gateway->is_available() ? 'yes' : 'no',
			);
		}

		return $payment_gateways;
	}

	public function export_step_schema() {
		return array(
			'step' => $this->get_step_name(),
			'payment_gateways' => $this->export()
		);
	}

	public function get_step_name() {
		return 'configurePaymentGateways';
	}

	protected function maybe_hide_wcpay_gateways() {
		if (class_exists('WC_Payments')) {
			\WC_Payments::hide_gateways_on_settings_page();
		}
	}
}
