<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Importers;

use Automattic\WooCommerce\Admin\Features\Blueprint\Steps\SetWCPaymentGateways;
use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;

/**
 * Class ImportSetWCPaymentGateways
 *
 * This class imports WooCommerce payment gateways settings and implements the StepProcessor interface.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Importers
 */
class ImportSetWCPaymentGateways implements StepProcessor {

	/**
	 * Process the import of WooCommerce payment gateways settings.
	 *
	 * @param object $schema The schema object containing import details.
	 * @return StepProcessorResult
	 */
	public function process( $schema ): StepProcessorResult {
		$result           = StepProcessorResult::success( self::class );
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$fields           = array( 'title', 'description', 'enabled' );

		foreach ( $schema->payment_gateways as $id => $payment_gateway_data ) {
			if ( ! isset( $payment_gateways[ $id ] ) ) {
				$result->add_info( "Skipping {$id}. The payment gateway is not available" );
				continue;
			}

			$payment_gateway = $payment_gateways[ $id ];

			// Refer to https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/class-wc-ajax.php#L3564.
			foreach ( $fields as $field ) {
				if ( isset( $payment_gateway_data->{$field} ) ) {
					$payment_gateway->update_option( $field, $payment_gateway_data->{$field} );
				}
			}
			$result->add_info( "{$id} has been updated." );
			// phpcs:ignore
			do_action( 'woocommerce_update_options' );
		}

		return $result;
	}

	/**
	 * Get the class name for the step.
	 *
	 * @return string
	 */
	public function get_step_class(): string {
		return SetWCPaymentGateways::class;
	}
}
