<?php
/**
 * Unit tests for Paypal standard gateway request.
 *
 * @package WooCommerce\Tests\Gateways\Paypal
 */

class WC_Tests_Paypal_Gateway_Request extends WC_Unit_Test_Case {

	protected $products;

	/**
	 * Create $product_count simple products and store them in $this->products.
	 *
	 * @param int $product_count Number of products to create.
	 */
	protected function create_products( $product_count = 30 ) {
		$this->products = array();
		for ( $i = 0; $i < $product_count; $i++ ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_name( 'Dummy Product ' . $i );
			$this->products[] = $product;

		}

		// To test limit length properly, we need a product with a name that is shorter than 127 chars,
		// but longer than 127 chars when URL-encoded.
		if ( array_key_exists( 0, $this->products ) ) {
			$this->products[0]->set_name( 'Dummy Product 😎😎😎😎😎😎😎😎😎😎😎' );
		}

	}

	/**
	 * Add products from $this->products to $order as items, clearing existing order items.
	 *
	 * @param WC_Order $order  Order to which the products should be added.
	 */
	protected function add_products_to_order( $order ) {
		// Remove previous items.
		foreach ( $order->get_items() as $item ) {
			$order->remove_item( $item->get_id() );
		}

		// Add new products.
		foreach ( $this->products as $product ) {
			$item = new WC_Order_Item_Product();
			$item->set_props( array(
				'product'  => $product,
				'quantity' => 3,
				'subtotal' => wc_get_price_excluding_tax( $product, array( 'qty' => 3 ) ),
				'total'    => wc_get_price_excluding_tax( $product, array( 'qty' => 3 ) ),
			) );

			$item->save();
			$order->add_item( $item );
		}

	}

	/**
	 * Initialize the Paypal gateway and Request objects.
	 */
	public function setUp() {
		parent::setUp();

		$bootstrap = WC_Unit_Tests_Bootstrap::instance();
		include_once $bootstrap->plugin_dir . '/includes/gateways/paypal/includes/class-wc-gateway-paypal-request.php';

		$this->paypal_gateway = new WC_Gateway_Paypal();
		$this->paypal_request = new WC_Gateway_Paypal_Request( $this->paypal_gateway );
	}


	/**
	 * Create Paypal request URL for $product_count number of products.
	 *
	 * @param int  $product_count Number of products to include in the order.
	 * @param bool $testmode      Whether to test using sandbox or not.
	 *
	 * @return string
	 * @throws WC_Data_Exception
	 */
	protected function get_request_url( $product_count, $testmode ) {
		// Create products.
		$this->create_products( $product_count );

		$this->order = WC_Helper_Order::create_order( $this->user );
		$this->add_products_to_order( $this->order );

		// Set payment method to Paypal.
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$this->order->set_payment_method( $payment_gateways['paypal'] );
		$this->order->calculate_totals();
		$this->order->calculate_shipping();

		return $this->paypal_request->get_request_url( $this->order, $testmode );
	}

	/**
	 * Clean up order, deletes all products in order, too.
	 */
	protected function clean_up() {
		WC_Helper_Order::delete_order( $this->order->get_id() );
	}

	/**
	 * Check if the shipping tax is included in the total according to $shipping_tax_included.
	 *
	 * @param array $query_array           Request URL parsed into associative array.
	 * @param bool  $shipping_tax_included Whether the shipping tax should be included or not.
	 */
	protected function check_shipping_tax( $query_array, $shipping_tax_included ) {
		$shipping_total = $this->order->get_shipping_total();
		if ( $shipping_tax_included ) {
			$shipping_total += $this->order->get_shipping_tax();
		}
		$epsilon = 0.01;
		$this->assertTrue( abs( $shipping_total - floatval( $query_array['shipping_1'] ) ) < $epsilon );

	}

	/**
	 * Test common order asserts.
	 *
	 * @param string $request_url Paypal request URL.
	 * @param bool   $testmode    Whether Paypal sandbox is used or not.
	 */
	protected function check_order_common_props( $request_url, $testmode ) {
		if ( $testmode ) {
			$this->assertEquals( 'https://www.sandbox.paypal.com', substr( $request_url, 0, 30 ) );
		} else {
			$this->assertEquals( 'https://www.paypal.com', substr( $request_url, 0, 22 ) );
		}
		$this->assertLessThanOrEqual( 2083, strlen( $request_url ) );
	}

	/**
	 * Test large order with 30 items, URL length > 2083 characters.
	 *
	 * @param  bool $shipping_tax_included Whether the shipping tax should be included or not.
	 * @param  bool $testmode              Whether to use Paypal sandbox.
	 *
	 * @throws WC_Data_Exception
	 */
	protected function check_large_order( $shipping_tax_included, $testmode ) {
		$request_url = $this->get_request_url( 30, $testmode );

		$this->check_order_common_props( $request_url, $testmode );

		// Check fields limited to 127 characters for length.
		$fields_limited_to_127_chars = array(
			'invoice',
			'item_name_1',
			'item_number_1',
		);

		$query_string = wp_parse_url( $request_url, PHP_URL_QUERY )
			? wp_parse_url( $request_url, PHP_URL_QUERY )
			: '';
		$query_array = array();
		parse_str( $query_string, $query_array );
		foreach ( $fields_limited_to_127_chars as $field_name ) {
			$this->assertLessThanOrEqual( 127, strlen( $query_array[ $field_name ] ) );
		}

		// Check that there is actually only one item for order with URL length > limit.
		$this->assertFalse( array_key_exists( 'item_name_2', $query_array ) );

		$this->check_shipping_tax( $query_array, $shipping_tax_included );

		// Remove order and created products.
		$this->clean_up();

	}

	/**
	 * Test small order with fewer items, URL length should be < 2083 characters.
	 *
	 * @param int  $product_count         Number of products to include in the order.
	 * @param bool $shipping_tax_included Whether the shipping tax should be included or not.
	 * @param bool $testmode              Whether to use Paypal sandbox.
	 *
	 * @throws WC_Data_Exception
	 */
	protected function check_small_order( $product_count, $shipping_tax_included, $testmode ) {
		$request_url = $this->get_request_url( $product_count, $testmode );

		$this->check_order_common_props( $request_url, $testmode );

		$query_string = wp_parse_url( $request_url, PHP_URL_QUERY )
			? wp_parse_url( $request_url, PHP_URL_QUERY )
			: '';
		$query_array = array();
		parse_str( $query_string, $query_array );

		// Check that there are $product_count line items in the request URL.
		// However, if shipping tax is included, there is only one item.
		if ( $shipping_tax_included ) {
			$product_count = 1;
		}

		for ( $i = 1; $i <= $product_count; $i++ ) {
			$this->assertTrue( array_key_exists( 'item_name_' . $i, $query_array ), 'Item name ' . $i . ' does not exist' );
			$this->assertTrue( array_key_exists( 'quantity_' . $i, $query_array ) );
			$this->assertTrue( array_key_exists( 'amount_' . $i, $query_array ) );
			$this->assertTrue( array_key_exists( 'item_number_' . $i, $query_array ) );

			$this->assertLessThanOrEqual( 127, strlen( $query_array[ 'item_name_' . $i ] ) );
			$this->assertLessThanOrEqual( 127, strlen( $query_array[ 'item_number_' . $i ] ) );

		}

		$this->check_shipping_tax( $query_array, $shipping_tax_included );

		// Remove order and created products.
		$this->clean_up();
	}

	/**
	 * @throws WC_Data_Exception
	 */
	public function test_request_url() {
		// User set up.
		$this->user = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
		wp_set_current_user( $this->user );

		// Paths through code changed by:
		// $sandbox (true/false)
		// wc_tax_enabled() === get_option( 'woocommerce_calc_taxes' ) === 'yes'
		// wc_prices_include_tax() === wc_tax_enabled() && 'yes' === get_option( 'woocommerce_prices_include_tax' );.
		$correct_options = array(
			[ 'no', 'no', false ],
			[ 'yes', 'no', false ],
			// ['no',  'yes',  false], // this is not a valid option due to definition of wc_prices_include_tax
			[ 'yes', 'yes', true ],
		);
		foreach ( array( true, false ) as $testmode ) {
			foreach ( $correct_options as $values ) {
				update_option( 'woocommerce_calc_taxes', $values[0] );
				update_option( 'woocommerce_prices_include_tax', $values[1] );
				$shipping_tax_included = $values[2];

				// Test order with < 9 items (URL shorter than limit).
				$this->check_small_order( 5, $shipping_tax_included, $testmode );

				// Test order with >9 items with URL shorter than limit.
				$this->check_small_order( 11, $shipping_tax_included, $testmode );

				// Test order with URL longer than limit.
				$this->check_large_order( $shipping_tax_included, $testmode );

			}
		}

	}

}

