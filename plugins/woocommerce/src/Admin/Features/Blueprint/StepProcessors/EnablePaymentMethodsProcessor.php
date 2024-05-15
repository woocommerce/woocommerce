<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;

class EnablePaymentMethodsProcessor implements StepProcessor {
	public function process($schema) {
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		foreach ($schema->gateway_ids as $gateway_id) {
			if (!isset($payment_gateways[$gateway_id])) {
				// invalid gateway id
				// push an error message and display it later
			}

			$payment_gateway = $payment_gateways[$gateway_id];
			$payment_gateway->update_option('enabled', 'yes');
			do_action( 'woocommerce_update_options' );
		}

		return true;
	}
}
