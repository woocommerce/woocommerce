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
		$checkout_fields = Package::container()->get( CheckoutFields::class );
		$result          = $checkout_fields->register_checkout_field( $options );
		if ( is_wp_error( $result ) ) {
			throw new \Exception( $result->get_error_message() );
		}
	}
}
