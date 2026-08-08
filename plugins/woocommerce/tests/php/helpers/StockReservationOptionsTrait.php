<?php

namespace Automattic\WooCommerce\RestApi\UnitTests;

/**
 * Trait StockReservationOptionsTrait.
 *
 * Runs a callback with the options stock reservation depends on, and restores them afterwards.
 */
trait StockReservationOptionsTrait {

	/**
	 * Run a callback with stock reservation enabled, then restore the options it touched.
	 *
	 * Options that did not exist beforehand are deleted rather than written back, so a test does not
	 * leave an empty value behind for the rest of the run.
	 *
	 * @param callable        $callback Callback to run.
	 * @param string|int|null $hold_stock_minutes Value for `woocommerce_hold_stock_minutes`, or null to leave it alone.
	 * @return mixed Whatever the callback returns.
	 */
	protected function with_stock_reservation_options( callable $callback, $hold_stock_minutes = null ) {
		$option_values = array(
			'woocommerce_manage_stock'   => 'yes',
			// Stock reservation needs the table added in WooCommerce 4.3.
			'woocommerce_schema_version' => 430,
		);

		if ( ! is_null( $hold_stock_minutes ) ) {
			$option_values['woocommerce_hold_stock_minutes'] = $hold_stock_minutes;
		}

		$missing_option_value = new \stdClass();
		$original_options     = array();

		foreach ( array_keys( $option_values ) as $option_name ) {
			$option_value                     = get_option( $option_name, $missing_option_value );
			$original_options[ $option_name ] = array(
				'exists' => $missing_option_value !== $option_value,
				'value'  => $option_value,
			);
		}

		foreach ( $option_values as $option_name => $option_value ) {
			update_option( $option_name, $option_value );
		}

		try {
			return $callback();
		} finally {
			foreach ( $original_options as $option_name => $option_data ) {
				if ( $option_data['exists'] ) {
					update_option( $option_name, $option_data['value'] );
				} else {
					delete_option( $option_name );
				}
			}
		}
	}
}
