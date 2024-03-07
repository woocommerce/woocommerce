<?php

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

if ( ! function_exists( '__experimental_woocommerce_blocks_register_checkout_field' ) ) {
	/**
	 * Register a checkout field.
	 *
	 * @param array $options Field arguments. See CheckoutFields::register_checkout_field() for details.
	 * @throws \Exception If field registration fails.
	 */
	function __experimental_woocommerce_blocks_register_checkout_field( $options ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore

		// Check if `woocommerce_blocks_loaded` ran. If not then the CheckoutFields class will not be available yet.
		// In that case, re-hook `woocommerce_blocks_loaded` and try running this again.
		$woocommerce_blocks_loaded_ran = did_action( 'woocommerce_blocks_loaded' );
		if ( ! $woocommerce_blocks_loaded_ran ) {
			add_action(
				'woocommerce_blocks_loaded',
				function() use ( $options ) {
					__experimental_woocommerce_blocks_register_checkout_field( $options );
				}
			);
			return;
		}
		$checkout_fields = Package::container()->get( CheckoutFields::class );
		$result          = $checkout_fields->register_checkout_field( $options );
		if ( is_wp_error( $result ) ) {
			throw new \Exception( $result->get_error_message() );
		}
	}
}
