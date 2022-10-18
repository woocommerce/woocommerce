<?php
namespace Automattic\WooCommerce\Blocks\Shipping;

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
	}

	/**
	 * Registers the local pickup method for blocks.
	 */
	public function register_shipping_methods() {
		$pickup = new PickupLocation();
		wc()->shipping->register_shipping_method( $pickup );
	}
}
