<?php

declare( strict_types = 1 );

/**
 * A custom order type built only on WC_Abstract_Order, so it has no get_customer_id() or get_billing_email().
 *
 * It uses the 'test-custom-order' data store, which tests must register through the 'woocommerce_data_stores' filter.
 */
class CustomerlessTestOrder extends WC_Abstract_Order {

	/**
	 * Data store registered by the test.
	 *
	 * @var string
	 */
	protected $data_store_name = 'test-custom-order';

	/**
	 * Get the order type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'test_custom_order';
	}

	/**
	 * Return an empty tax location, as this order type has no billing or shipping getters.
	 *
	 * @param array $args Override the location.
	 * @return array
	 */
	protected function get_tax_location( $args = array() ) {
		return wp_parse_args(
			$args,
			array(
				'country'  => '',
				'state'    => '',
				'postcode' => '',
				'city'     => '',
			)
		);
	}
}
