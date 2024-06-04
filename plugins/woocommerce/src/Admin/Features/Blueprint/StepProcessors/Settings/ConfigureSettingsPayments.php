<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\Settings;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;

class ConfigureSettingsPayments implements StepProcessor {
	public function process( $schema ): StepProcessorResult {
		$payment_gateways = WC()->payment_gateways->payment_gateways();

		$result = new StepProcessorResult( true ) ;
		foreach ($schema->gateways as $name => $gateway) {
			if (!isset($payment_gateways[$name])) {
				$result->add_error("{$name} is not a valid payment gateway.");
			}

			$payment_gateway = $payment_gateways[$name];

			if ($gateway->enabled) {
				$payment_gateway->update_option( 'enabled', $gateway->enabled );
			}

			if ( !empty($gateway->title)) {
				$payment_gateway->update_option('title', $gateway->title);
			}

			if ( !empty($gateway->description)) {
				$payment_gateway->update_option('description', $gateway->description);
			}

			do_action( 'woocommerce_update_options' );
		}

		return $result;
	}
}
