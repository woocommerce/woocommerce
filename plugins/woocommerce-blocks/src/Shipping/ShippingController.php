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
		add_filter( 'woocommerce_shipping_packages', array( $this, 'filter_shipping_packages' ) );
		add_filter( 'woocommerce_local_pickup_methods', array( $this, 'filter_local_pickup_methods' ) );
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'pickup_location_customer_tax_location' ) );
	}

	/**
	 * Registers the local pickup method for blocks.
	 */
	public function register_shipping_methods() {
		$pickup = new PickupLocation();
		wc()->shipping->register_shipping_method( $pickup );
	}

	/**
	 * Disable local pickup if multiple packages are present.
	 *
	 * @param array $packages Array of shipping packages.
	 * @return array
	 */
	public function filter_shipping_packages( $packages ) {
		if ( 1 === count( $packages ) ) {
			return $packages;
		}

		foreach ( $packages as $package_id => $package ) {
			$package_rates = $package['rates'];

			foreach ( $package_rates as $rate_id => $rate ) {
				$method_id = $rate->get_method_id();

				if ( 'pickup_location' === $method_id ) {
					unset( $packages[ $package_id ]['rates'][ $rate_id ] );
				}
			}
		}

		return $packages;
	}

	/**
	 * Declares the Pickup Location shipping method as being a Local Pickup method.
	 *
	 * @param array $methods Shipping method ids.
	 * @return array
	 */
	public function filter_local_pickup_methods( $methods ) {
		$methods[] = 'pickup_location';
		return $methods;
	}

	/**
	 * Filter the location used for taxes based on the chosen pickup location.
	 *
	 * @param array $address Location args.
	 * @return array
	 */
	public function pickup_location_customer_tax_location( $address ) {
		// We only need to select from the first package, since pickup_location only supports a single package.
		$chosen_method          = current( WC()->session->get( 'chosen_shipping_methods', array() ) ) ?? '';
		$chosen_method_id       = explode( ':', $chosen_method )[0];
		$chosen_method_instance = explode( ':', $chosen_method )[1] ?? 0;

		if ( $chosen_method_id && true === apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && 'pickup_location' === $chosen_method_id ) {
			$pickup_locations = get_option( 'pickup_location_pickup_locations', [] );

			if ( ! empty( $pickup_locations[ $chosen_method_instance ] ) && ! empty( $pickup_locations[ $chosen_method_instance ]['address']['country'] ) ) {
				$address = array(
					$pickup_locations[ $chosen_method_instance ]['address']['country'],
					$pickup_locations[ $chosen_method_instance ]['address']['state'],
					$pickup_locations[ $chosen_method_instance ]['address']['postcode'],
					$pickup_locations[ $chosen_method_instance ]['address']['city'],
				);
			}
		}

		return $address;
	}
}
