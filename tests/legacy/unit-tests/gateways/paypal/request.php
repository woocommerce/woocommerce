<?php
/**
 * Unit tests for Paypal standard gateway request.
 *
 * @package WooCommerce\Tests\Gateways\Paypal
 */

/**
 * Class WC_Tests_Paypal_Gateway_Request.
 */
class WC_Tests_Paypal_Gateway_Request extends WC_Unit_Test_Case {

	/**
	 * Products to use in order.
	 *
	 * @var array
	 */
	protected $products;

	/**
	 * Order to submit to PayPal.
	 *
	 * @var WC_Order
	 */
	protected $order;

	/**
	 * Create $product_count simple products and store them in $this->products.
	 *
	 * @param int $product_count Number of products to create.
	 */
	protected function create_products( $product_count = 30 ) {
		$this->products = array();
		for ( $i = 0; $i < $product_count; $i++ ) {
			$product = WC_Helper_Product::create_simple_product( false );
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
	 * @param WC_Order $order Order to which the products should be added.
	 * @param array    $prices Array of prices to use for created products. Leave empty for default prices.
	 */
	protected function add_products_to_order( &$order, $prices = array() ) {
		// Remove previous items.
		foreach ( $order->get_items() as $item ) {
			$order->remove_item( $item->get_id() );
		}

		// Add new products.
		$prod_count = 0;
		foreach ( $this->products as $product ) {
			$item = new WC_Order_Item_Product();
			$item->set_props(
				array(
					'product'  => $product,
					'quantity' => 3,
					'subtotal' => $prices ? $prices[ $prod_count ] : wc_get_price_excluding_tax( $product, array( 'qty' => 3 ) ),
					'total'    => $prices ? $prices[ $prod_count ] : wc_get_price_excluding_tax( $product, array( 'qty' => 3 ) ),
				)
			);

			$item->save();
			$order->add_item( $item );

			$prod_count++;
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
	 * @param int   $product_count Number of products to include in the order.
	 * @param bool  $testmode Whether to test using sandbox or not.
	 * @param array $product_prices Array of prices to use for created products. Leave empty for default prices.
	 * @param bool  $calc_order_totals Whether the WC_Order::calculate_totals() should be triggered when creating order.
	 * @return string
	 * @throws WC_Data_Exception Exception on failure.
	 */
	protected function get_request_url( $product_count, $testmode, $product_prices = array(), $calc_order_totals = true ) {
		// Create products.
		$this->create_products( $product_count );

		$this->order = WC_Helper_Order::create_order( $this->user );
		$this->add_products_to_order( $this->order, $product_prices );

		// Set payment method to Paypal.
		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$this->order->set_payment_method( $payment_gateways['paypal'] );

		// Add tax.
		if ( wc_tax_enabled() ) {
			$tax_rate = array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '11.0000',
				'tax_rate_name'     => 'TAX',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			);
			WC_Tax::_insert_tax_rate( $tax_rate );

			$tax_item = new WC_Order_Item_Tax();
			$tax_item->set_rate( 100 );
			$tax_item->set_tax_total( 100 );
			$tax_item->set_shipping_tax_total( 100 );
			$this->order->add_item( $tax_item );
			$this->order->save();
		}

		$this->order->calculate_shipping();
		if ( $calc_order_totals ) {
			$this->order->calculate_totals();
		}

		return $this->paypal_request->get_request_url( $this->order, $testmode );
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
		$this->assertTrue(
			abs( $shipping_total - floatval( $query_array['shipping_1'] ) ) < $epsilon,
			'Shipping tax mismatch: shipping total=' . $shipping_total . ' vs request shipping=' . $query_array['shipping_1']
		);

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
	 * @throws WC_Data_Exception Exception on failure.
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
		$query_array  = array();
		parse_str( $query_string, $query_array );
		foreach ( $fields_limited_to_127_chars as $field_name ) {
			$this->assertLessThanOrEqual( 127, mb_strlen( $query_array[ $field_name ] ) );
		}

		// Check that there is actually only one item for order with URL length > limit.
		$this->assertFalse( array_key_exists( 'item_name_2', $query_array ) );

		// Check that non-line item parameters are included.
		$this->assertTrue( array_key_exists( 'cmd', $query_array ) );
		$this->assertEquals( '_cart', $query_array['cmd'] );

		$this->check_shipping_tax( $query_array, $shipping_tax_included );
	}

	/**
	 * Test removing HTML tags from product title and request URL
	 *
	 * @param  bool $testmode Whether to use Paypal sandbox.
	 */
	protected function check_product_title_containing_html( $testmode ) {
		$order = WC_Helper_Order::create_order();

		foreach ( $order->get_items() as $item ) {
			$order->remove_item( $item->get_id() );
		}

		$product = new WC_Product_Simple();
		$product->set_props(
			array(
				'name'          => 'New Product <a href="#" style="color: red;">Link</a>',
				'regular_price' => 10,
				'price'         => 10,
			)
		);
		$product->save();
		$product = wc_get_product( $product->get_id() );

		$qty = 1;

		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => $qty,
				'subtotal' => wc_get_price_excluding_tax( $product, array( 'qty' => $qty ) ),
				'total'    => wc_get_price_excluding_tax( $product, array( 'qty' => $qty ) ),
			)
		);
		$item->save();

		$order->add_item( $item );
		$order->save();

		$request_url = $this->paypal_request->get_request_url( $order, $testmode );

		$query_string = wp_parse_url( $request_url, PHP_URL_QUERY )
			? wp_parse_url( $request_url, PHP_URL_QUERY )
			: '';
		$query_array  = array();
		parse_str( $query_string, $query_array );

		$this->assertEquals( $query_array['item_name_1'], 'New Product Link x ' . $qty );
	}

	/**
	 * Return true if value is < 0, false otherwise.
	 *
	 * @param int|float $value Tested value.
	 * @return bool
	 */
	protected function is_negative( $value ) {
		return $value < 0;
	}

	/**
	 * Test small order with fewer items, URL length should be < 2083 characters.
	 *
	 * @param int   $product_count Number of products to include in the order.
	 * @param bool  $shipping_tax_included Whether the shipping tax should be included or not.
	 * @param bool  $testmode Whether to use Paypal sandbox.
	 * @param array $product_prices Array of prices to use for created products. Leave empty for default prices.
	 * @param bool  $calc_order_totals Whether the WC_Order::calculate_totals() should be triggered when creating order.
	 * @throws WC_Data_Exception Exception on failure.
	 */
	protected function check_small_order( $product_count, $shipping_tax_included, $testmode, $product_prices = array(), $calc_order_totals = true ) {
		$request_url = $this->get_request_url( $product_count, $testmode, $product_prices, $calc_order_totals );
		$this->check_order_common_props( $request_url, $testmode );

		$query_string = wp_parse_url( $request_url, PHP_URL_QUERY )
			? wp_parse_url( $request_url, PHP_URL_QUERY )
			: '';
		$query_array  = array();
		parse_str( $query_string, $query_array );

		// Check that there are $product_count line items in the request URL.
		// However, if shipping tax is included, there is only one item.
		if ( $shipping_tax_included || array_filter( $product_prices, array( $this, 'is_negative' ) ) || ! $calc_order_totals ) {
			$product_count = 1;
		}

		for ( $i = 1; $i <= $product_count; $i++ ) {
			$this->assertTrue( array_key_exists( 'item_name_' . $i, $query_array ), 'Item name ' . $i . ' does not exist' );
			$this->assertTrue( array_key_exists( 'quantity_' . $i, $query_array ) );
			$this->assertTrue( array_key_exists( 'amount_' . $i, $query_array ) );
			$this->assertTrue( array_key_exists( 'item_number_' . $i, $query_array ) );

			// Check that non-line item parameters are included.
			$this->assertTrue( array_key_exists( 'cmd', $query_array ) );
			$this->assertEquals( '_cart', $query_array['cmd'] );

			$this->assertLessThanOrEqual( 127, mb_strlen( $query_array[ 'item_name_' . $i ] ) );
			$this->assertLessThanOrEqual( 127, mb_strlen( $query_array[ 'item_number_' . $i ] ) );

		}

		$this->check_shipping_tax( $query_array, $shipping_tax_included );
	}

	/**
	 * Test order with one product having negative amount.
	 * Amount < 0 forces tax inclusion and single line item, since WC_Gateway_Paypal_Request::prepare_line_items()
	 * will return false.
	 *
	 * @param bool $testmode Whether PayPal request should point to sandbox or live production.
	 * @throws WC_Data_Exception Exception on failure.
	 */
	protected function check_negative_amount( $testmode ) {
		$shipping_tax_included = true;
		$product_prices        = array( 6, 6, 6, 6, -3 );
		$this->check_small_order( count( $product_prices ), $shipping_tax_included, $testmode, $product_prices );
	}

	/**
	 * Test order with totals mismatched.
	 * This forces tax inclusion and single line item, since WC_Gateway_Paypal_Request::prepare_line_items()
	 * will return false.
	 *
	 * @param bool $testmode Whether PayPal request should point to sandbox or live production.
	 * @throws WC_Data_Exception Exception on failure.
	 */
	protected function check_totals_mismatch( $testmode ) {
		// totals mismatch forces tax inclusion and single line item.
		$shipping_tax_included = true;
		$this->check_small_order( 5, $shipping_tax_included, $testmode, array(), false );
	}

	/**
	 * Test for request_url() method.
	 *
	 * @group timeout
	 * @throws WC_Data_Exception Exception on failure.
	 */
	public function test_request_url() {
		// User set up.
		$this->login_as_administrator();

		// wc_tax_enabled(), wc_prices_include_tax() and WC_Gateway_Paypal_Request::prepare_line_items() determine if
		// shipping tax should be included, these are the correct options.
		// Note that prepare_line_items() can return false in 2 cases, tested separately below:
		// - order totals mismatch and
		// - item amount < 0.
		$correct_options = array(
			// woocommerce_calc_taxes, woocommerce_prices_include_tax, $shipping_tax_included values.
			array( 'no', 'no', false ),
			array( 'yes', 'no', false ),
			// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			// array( 'no',  'yes',  false ), // this is not a valid option due to definition of wc_prices_include_tax().
			array( 'yes', 'yes', true ),
		);

		// One test without sandbox.
		$testmode = false;
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		$shipping_tax_included = false;
		$this->check_small_order( 5, $shipping_tax_included, $testmode );

		// Other tests with sandbox active.
		$testmode = true;
		foreach ( $correct_options as $values ) {
			update_option( 'woocommerce_calc_taxes', $values[0] );
			update_option( 'woocommerce_prices_include_tax', $values[1] );
			$shipping_tax_included = $values[2];

			// Test order with < 9 items (URL shorter than limit).
			$this->check_small_order( 5, $shipping_tax_included, $testmode );

			// Test order with >9 items with URL shorter than limit.
			$this->check_small_order( 11, $shipping_tax_included, $testmode );

			// Test order with URL longer than limit.
			// Many items in order -> forced to use one line item -> shipping tax included.
			$this->check_large_order( true, $testmode );

			// Test removing tags from line item name.
			$this->check_product_title_containing_html( $testmode );

			// Test amount < 0.
			$this->check_negative_amount( $testmode );

			// Check order totals mismatch.
			$this->check_totals_mismatch( $testmode );

		}

	}

}

