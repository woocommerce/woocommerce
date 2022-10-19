<?php
namespace Automattic\WooCommerce\Blocks\Shipping;

use Automattic\WooCommerce\StoreApi\Utilities\CartController;

/**
 * ShippingController class.
 *
 * @internal
 */
class ShippingController {
	/**
	 * Initialization method.
	 */
	public function init() {
		add_action( 'woocommerce_load_shipping_methods', array( $this, 'register_shipping_methods' ) );
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'handle_customer_taxable_address' ) );
	}

	/**
	 * Registers the local pickup method for blocks.
	 */
	public function register_shipping_methods() {
		$pickup = new PickupLocation();
		wc()->shipping->register_shipping_method( $pickup );
	}

	/**
	 * Filter the location used for taxes based on the chosen pickup location.
	 *
	 * @param array $address Location args.
	 * @return array
	 */
	public function handle_customer_taxable_address( $address ) {
		$controller       = new CartController();
		$packages         = $controller->get_shipping_packages( false );
		$selected_rates   = wc()->session->get( 'chosen_shipping_methods', array() );
		$pickup_locations = get_option( 'pickup_location_pickup_locations', [] );

		if ( empty( $packages ) || empty( $selected_rates ) || empty( $pickup_locations ) ) {
			return $address;
		}

		$location_ids = [];

		// Require pickup for all packages.
		foreach ( $packages as $package_id => $package ) {
			$selected_rate = $selected_rates[ $package_id ] ?? '';
			$method_id     = explode( ':', $selected_rate )[0];

			if ( 'pickup_location' !== $method_id ) {
				return $address;
			}

			$location_ids[] = explode( ':', $selected_rate )[1] ?? null;
		}

		$location_ids = array_unique( $location_ids );

		if ( count( $location_ids ) > 1 ) {
			return $address;
		}

		$location_id = $location_ids[0];

		if ( ! empty( $pickup_locations[ $location_id ] ) && ! empty( $pickup_locations[ $location_id ]['address']['country'] ) ) {
			return array(
				$pickup_locations[ $location_id ]['address']['country'],
				$pickup_locations[ $location_id ]['address']['state'],
				$pickup_locations[ $location_id ]['address']['postcode'],
				$pickup_locations[ $location_id ]['address']['city'],
			);
		}

		return $address;
	}
}
