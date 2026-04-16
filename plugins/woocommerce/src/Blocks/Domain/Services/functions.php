<?php

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
	/**
	 * Register a checkout field.
	 *
	 * @param array $options Field arguments. See CheckoutFields::register_checkout_field() for details.
	 * @throws \Exception If field registration fails.
	 */
	function woocommerce_register_additional_checkout_field( $options ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore

		// Check if `woocommerce_blocks_loaded` ran. If not then the CheckoutFields class will not be available yet.
		// In that case, re-hook `woocommerce_blocks_loaded` and try running this again.
		$woocommerce_blocks_loaded_ran = did_action( 'woocommerce_blocks_loaded' );
		if ( ! $woocommerce_blocks_loaded_ran ) {
			add_action(
				'woocommerce_blocks_loaded',
				function () use ( $options ) {
					woocommerce_register_additional_checkout_field( $options );
				}
			);
			return;
		}
		$checkout_fields = Package::container()->get( CheckoutFields::class );
		$result          = $checkout_fields->register_checkout_field( $options );
		if ( is_wp_error( $result ) ) {
			throw new \Exception( esc_attr( $result->get_error_message() ) );
		}
	}
}

if ( ! function_exists( '__experimental_woocommerce_blocks_register_checkout_field' ) ) {

	/**
	 * Register a checkout field.
	 *
	 * @param array $options Field arguments. See CheckoutFields::register_checkout_field() for details.
	 * @throws \Exception If field registration fails.
	 * @deprecated 5.6.0 Use woocommerce_register_additional_checkout_field() instead.
	 */
	function __experimental_woocommerce_blocks_register_checkout_field( $options ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
		wc_deprecated_function( __FUNCTION__, '8.9.0', 'woocommerce_register_additional_checkout_field' );
		woocommerce_register_additional_checkout_field( $options );
	}
}

if ( ! function_exists( 'woocommerce_register_checkout_core_field_rules' ) ) {
	/**
	 * Register conditional rules for a core checkout field.
	 *
	 * Attaches JSON-Schema-based `required`, `hidden` and `validation` rules to a built-in
	 * checkout field such as `first_name`, `last_name`, `country`, `postcode`, etc. Rules use
	 * the same shape and semantics as those for additional checkout fields and are evaluated
	 * on both the client and the server.
	 *
	 * @since 10.8.0
	 *
	 * @param string $field_id The core field id (e.g. 'first_name', 'postcode', 'email').
	 * @param array  $rules    The rules. May contain any subset of 'required', 'hidden', 'validation'.
	 * @throws \Exception If registration fails.
	 */
	function woocommerce_register_checkout_core_field_rules( $field_id, $rules ) {
		// Mirror the deferred registration behaviour of additional fields.
		if ( ! did_action( 'woocommerce_blocks_loaded' ) ) {
			add_action(
				'woocommerce_blocks_loaded',
				function () use ( $field_id, $rules ) {
					woocommerce_register_checkout_core_field_rules( $field_id, $rules );
				}
			);
			return;
		}
		$checkout_fields = Package::container()->get( CheckoutFields::class );
		$result          = $checkout_fields->register_core_field_rules( $field_id, $rules );
		if ( is_wp_error( $result ) ) {
			throw new \Exception( esc_attr( $result->get_error_message() ) );
		}
	}
}

if ( ! function_exists( '__internal_woocommerce_blocks_deregister_core_field_rules' ) ) {
	/**
	 * Deregister conditional rules previously registered for a core checkout field.
	 *
	 * @since 10.8.0
	 *
	 * @param string $field_id The core field id.
	 * @internal
	 */
	function __internal_woocommerce_blocks_deregister_core_field_rules( $field_id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
		$checkout_fields = Package::container()->get( CheckoutFields::class );
		$checkout_fields->deregister_core_field_rules( $field_id );
	}
}

if ( ! function_exists( '__internal_woocommerce_blocks_deregister_checkout_field' ) ) {
	/**
	 * Deregister a checkout field.
	 *
	 * @param string $field_id Field ID.
	 * @throws \Exception If field deregistration fails.
	 * @internal
	 */
	function __internal_woocommerce_blocks_deregister_checkout_field( $field_id ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
		$checkout_fields = Package::container()->get( CheckoutFields::class );
		$result          = $checkout_fields->deregister_checkout_field( $field_id );
		if ( is_wp_error( $result ) ) {
			throw new \Exception( esc_attr( $result->get_error_message() ) );
		}
	}
}
