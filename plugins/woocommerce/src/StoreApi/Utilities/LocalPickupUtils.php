<?php
namespace Automattic\WooCommerce\StoreApi\Utilities;

/**
 * Util class for local pickup related functionality, this contains methods that need to be accessed from places besides
 * the ShippingController, i.e. the OrderController.
 */
class LocalPickupUtils {

	/**
	 * Gets the local pickup location settings.
	 *
	 * @param string $context The context for the settings. Defaults to 'view'.
	 */
	public static function get_local_pickup_settings( $context = 'view' ) {
		$pickup_location_settings = get_option(
			'woocommerce_pickup_location_settings',
			[
				'enabled'    => 'no',
				'title'      => __( 'Pickup', 'woocommerce' ),
				'cost'       => '',
				'tax_status' => 'taxable',
			]
		);

		if ( empty( $pickup_location_settings['title'] ) ) {
			$pickup_location_settings['title'] = __( 'Pickup', 'woocommerce' );
		}

		if ( empty( $pickup_location_settings['enabled'] ) ) {
			$pickup_location_settings['enabled'] = 'no';
		}

		if ( ! isset( $pickup_location_settings['cost'] ) ) {
			$pickup_location_settings['cost'] = '';
		}

		// Return settings as is if we're editing them.
		if ( 'edit' === $context ) {
			return $pickup_location_settings;
		}

		// All consumers of this turn it into a bool eventually. Doing it here removes the need for that.
		$pickup_location_settings['enabled'] = wc_string_to_bool( $pickup_location_settings['enabled'] );
		$pickup_location_settings['title']   = wc_clean( $pickup_location_settings['title'] );

		return $pickup_location_settings;
	}

	/**
	 * Checks if WC Blocks local pickup is enabled.
	 *
	 * @return bool True if local pickup is enabled.
	 */
	public static function is_local_pickup_enabled() {
		// Get option directly to avoid early translation function call.
		// See https://github.com/woocommerce/woocommerce/pull/47113.
		$pickup_location_settings = get_option(
			'woocommerce_pickup_location_settings',
			[
				'enabled' => 'no',
			]
		);

		if ( empty( $pickup_location_settings['enabled'] ) ) {
			$pickup_location_settings['enabled'] = 'no';
		}

		return wc_string_to_bool( $pickup_location_settings['enabled'] );
	}
	/**
	 * Gets a list of payment method ids that support the 'local-pickup' feature.
	 *
	 * @return string[] List of payment method ids that support the 'local-pickup' feature.
	 */
	public static function get_local_pickup_method_ids() {
		$all_methods_supporting_local_pickup = array_reduce(
			WC()->shipping()->get_shipping_methods(),
			function ( $methods, $method ) {
				if ( $method->supports( 'local-pickup' ) ) {
					$methods[] = $method->id;
				}
				return $methods;
			},
			array( 'local_pickup' )
		);

		// We use array_values because this will be used in JS, so we don't need the (numerical) keys.
		return array_values(
		// This array_unique is necessary because WC()->shipping()->get_shipping_methods() can return duplicates.
			array_unique(
				$all_methods_supporting_local_pickup
			)
		);
	}

	/**
	 * Checks if a method is a local pickup method.
	 *
	 * @param string $method_id The method id to check.
	 * @return bool True if the method is a local pickup method.
	 */
	public static function is_local_pickup_method( $method_id ) {
		return in_array( $method_id, self::get_local_pickup_method_ids(), true );
	}
}
