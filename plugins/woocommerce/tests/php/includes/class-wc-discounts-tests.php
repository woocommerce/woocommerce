<?php
/**
 * Unit tests for WC_Discounts class.
 *
 * @package WooCommerce\Tests.
 */

use Automattic\WooCommerce\Enums\OrderStatus;

/**
  * Class WC_Discounts_Tests.
  */
class WC_Discounts_Tests extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 *
	 * The cart and current user are in-memory globals that the per-test DB transaction
	 * does not roll back, so reset them explicitly to avoid leaking state into other tests.
	 */
	public function tearDown(): void {
		WC()->cart->empty_cart();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Helper method to create limited coupon.
	 */
	private function create_limited_coupon() {
		update_option( 'woocommerce_hold_stock_minutes', 60 );
		return WC_Helper_Coupon::create_coupon(
			'coupon4one' . microtime( true ) . wp_generate_password( 6, false, false ),
			array(
				'usage_limit'          => 1,
				'usage_limit_per_user' => 1,
			)
		);
	}

	/**
	 * Helper method to create customer.
	 */
	public function create_customer() {
		$username = sanitize_title( 'testusername-' . microtime( true ) . wp_generate_password( 6, false, false ) );
		$customer = new WC_Customer();
		$customer->set_username( $username );
		$customer->set_password( 'test123' );
		$customer->set_email( "$username@woo.local" );
		$customer->save();
		return $customer;
	}

	/**
	 * Test if coupon is valid when usage limit is reached for guest
	 */
	public function test_is_coupon_valid_when_limit_reached_for_guest() {
		$coupon     = $this->create_limited_coupon();
		$data_store = WC_Data_Store::load( 'coupon' );

		$result = $data_store->check_and_hold_coupon( $coupon );
		$this->assertNotNull( $result );

		wp_set_current_user( 0 );
		$valid = ( new WC_Discounts() )->is_coupon_valid( $coupon );
		$this->assertWPError( $valid );
		$this->assertEquals( $coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_USAGE_LIMIT_COUPON_STUCK_GUEST ), $valid->get_error_message() );
	}

	/**
	 * Test if coupon is valid when usage limit is reached for logged in user.
	 */
	public function test_is_coupon_valid_when_limit_reached_for_user() {
		$coupon     = $this->create_limited_coupon();
		$customer   = $this->create_customer();
		$data_store = WC_Data_Store::load( 'coupon' );
		$order      = wc_create_order(
			array(
				'status'      => OrderStatus::PENDING,
				'customer_id' => $customer->get_id(),
			)
		);
		$order->save();

		$result = $data_store->check_and_hold_coupon( $coupon );
		$this->assertNotNull( $result );

		wp_set_current_user( $customer->get_id() );
		$valid = ( new WC_Discounts() )->is_coupon_valid( $coupon );
		$this->assertWPError( $valid );
		$this->assertEquals( $coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_USAGE_LIMIT_COUPON_STUCK ), $valid->get_error_message() );
	}

	/**
	 * Test if coupon is valid when usage limit per user is reached for logged in user.
	 */
	public function test_is_coupon_valid_per_user_when_limit_reached_for_user() {
		$coupon     = $this->create_limited_coupon();
		$data_store = WC_Data_Store::load( 'coupon' );
		$customer   = $this->create_customer();

		$result = $data_store->check_and_hold_coupon_for_user( $coupon, array( $customer->get_id() ), $customer->get_id() );
		$this->assertNotNull( $result );

		wp_set_current_user( $customer->get_id() );
		$valid = ( new WC_Discounts() )->is_coupon_valid( $coupon );
		$this->assertWPError( $valid );
		$this->assertEquals( $coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_USAGE_LIMIT_COUPON_STUCK ), $valid->get_error_message() );
	}

	/**
	 * Test if coupon is valid (it shouldn't be) if it has been placed in the trash.
	 */
	public function test_is_trashed_coupon_valid() {
		$coupon = new WC_Coupon( uniqid() );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( 10 );
		$coupon->save();

		$discounts = new WC_Discounts();
		$this->assertTrue( $discounts->is_coupon_valid( $coupon ), 'Newly created coupon is initially valid.' );

		wp_trash_post( $coupon->get_id() );
		$coupon = new WC_Coupon( $coupon );
		$result = $discounts->is_coupon_valid( $coupon );
		$this->assertInstanceOf( WP_Error::class, $result, 'Once trashed, the coupon is no longer valid.' );
		$this->assertEquals( 'invalid_coupon', $result->get_error_code(), 'We receive an appropriate WP_Error.' );
	}

	/**
	 * @testdox is_coupon_valid rejects a coupon when the cart subtotal is below its minimum spend.
	 */
	public function test_is_coupon_valid_rejects_below_minimum_spend() {
		update_option( 'woocommerce_calc_taxes', 'no' );
		WC()->cart->empty_cart();

		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 20 ) );
		$coupon  = new WC_Coupon();
		$coupon->set_props(
			array(
				'discount_type'  => 'fixed_cart',
				'amount'         => 10,
				'minimum_amount' => 50,
			)
		);
		$coupon->save();

		// $20 < $50 minimum.
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$discounts = new WC_Discounts( WC()->cart );

		$result = $discounts->is_coupon_valid( $coupon );
		$this->assertWPError( $result, 'coupon below minimum spend should be invalid' );
		$this->assertEquals( $coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_MIN_SPEND_LIMIT_NOT_MET ), $result->get_error_message() );
	}

	/**
	 * @testdox is_coupon_valid rejects a product/category-restricted coupon when the cart has none of its products.
	 */
	public function test_is_coupon_valid_rejects_non_included_product() {
		update_option( 'woocommerce_calc_taxes', 'no' );
		WC()->cart->empty_cart();

		$included = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 20 ) );
		$other    = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 20 ) );
		$coupon   = new WC_Coupon();
		$coupon->set_props(
			array(
				'code'          => 'included-only',
				'discount_type' => 'fixed_cart',
				'amount'        => 10,
				'product_ids'   => array( $included->get_id() ),
			)
		);
		$coupon->save();

		// Not the included product.
		WC()->cart->add_to_cart( $other->get_id(), 1 );
		$discounts = new WC_Discounts( WC()->cart );

		$result = $discounts->is_coupon_valid( $coupon );
		$this->assertWPError( $result, 'coupon should not apply to non-included products' );
		// The product_ids rule throws its own inline message (class-wc-discounts.php) rather than
		// routing through WC_Coupon::get_coupon_error(), so assert that stable phrase directly.
		$this->assertStringContainsString( 'is not applicable to selected products.', $result->get_error_message() );
	}

	/**
	 * @testdox is_coupon_valid rejects a coupon when the cart contains one of its excluded products.
	 *
	 * Closes the gap left by the e2e "excluded product/category" test, which never applied the excluded coupon.
	 */
	public function test_is_coupon_valid_rejects_excluded_product() {
		update_option( 'woocommerce_calc_taxes', 'no' );
		WC()->cart->empty_cart();

		$excluded = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 20 ) );
		$coupon   = new WC_Coupon();
		$coupon->set_props(
			array(
				'discount_type'        => 'fixed_cart',
				'amount'               => 20,
				'excluded_product_ids' => array( $excluded->get_id() ),
			)
		);
		$coupon->save();

		WC()->cart->add_to_cart( $excluded->get_id(), 1 );
		$discounts = new WC_Discounts( WC()->cart );

		$result = $discounts->is_coupon_valid( $coupon );
		$this->assertWPError( $result, 'coupon should be rejected when an excluded product is in the cart' );
		$this->assertEquals( $coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_EXCLUDED_PRODUCTS ), $result->get_error_message() );
	}

	/**
	 * @testdox is_coupon_valid rejects an email-restricted coupon for a non-matching customer.
	 */
	public function test_is_coupon_valid_rejects_disallowed_email() {
		update_option( 'woocommerce_calc_taxes', 'no' );
		WC()->cart->empty_cart();

		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 20 ) );
		$coupon  = new WC_Coupon();
		$coupon->set_props(
			array(
				'discount_type'      => 'fixed_cart',
				'amount'             => 25,
				'email_restrictions' => array( 'allowed@example.com' ),
			)
		);
		$coupon->save();

		wp_set_current_user( 0 );
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$discounts = new WC_Discounts( WC()->cart );

		$result = $discounts->is_coupon_valid( $coupon );
		$this->assertWPError( $result, 'email-restricted coupon should be invalid for a non-matching customer' );
		$this->assertEquals( $coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_NOT_YOURS_REMOVED ), $result->get_error_message() );
	}

	/**
	 * @testdox The woocommerce_coupon_validate_product_ids filter can bypass or force product ID validation.
	 */
	public function test_product_ids_validation_filter() {
		$in_cart     = WC_Helper_Product::create_simple_product();
		$not_in_cart = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $in_cart->get_id(), 1 );
		$discounts = new WC_Discounts( WC()->cart );

		$coupon = new WC_Coupon();
		$coupon->set_props(
			array(
				'discount_type' => 'fixed_cart',
				'amount'        => 10,
				'product_ids'   => array( $not_in_cart->get_id() ),
			)
		);
		$coupon->save();

		$result = $discounts->is_coupon_valid( $coupon );
		$this->assertWPError( $result, 'A coupon restricted to a product not in the cart should be invalid.' );
		$this->assertStringContainsString( 'is not applicable to selected products', $result->get_error_message(), 'The failure should come from the product_ids check.' );

		add_filter( 'woocommerce_coupon_validate_product_ids', '__return_true' );
		$this->assertTrue( $discounts->is_coupon_valid( $coupon ), 'Returning true from the filter should bypass the product ID restriction.' );
		remove_filter( 'woocommerce_coupon_validate_product_ids', '__return_true' );

		$this->assertWPError( $discounts->is_coupon_valid( $coupon ), 'Normal validation should resume once the filter is removed.' );

		$valid_coupon = new WC_Coupon();
		$valid_coupon->set_props(
			array(
				'discount_type' => 'fixed_cart',
				'amount'        => 10,
				'product_ids'   => array( $in_cart->get_id() ),
			)
		);
		$valid_coupon->save();

		$this->assertTrue( $discounts->is_coupon_valid( $valid_coupon ), 'A coupon restricted to a product in the cart should be valid.' );
		add_filter( 'woocommerce_coupon_validate_product_ids', '__return_false' );
		$result = $discounts->is_coupon_valid( $valid_coupon );
		$this->assertWPError( $result, 'Returning false from the filter should force rejection.' );
		$this->assertStringContainsString( 'is not applicable to selected products', $result->get_error_message(), 'Forced rejection should surface the product_ids error.' );
		remove_filter( 'woocommerce_coupon_validate_product_ids', '__return_false' );
	}

	/**
	 * @testdox The woocommerce_coupon_validate_product_categories filter can bypass category validation.
	 */
	public function test_product_categories_validation_filter() {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$discounts = new WC_Discounts( WC()->cart );

		$term   = wp_insert_term( 'coupon-cat-' . uniqid(), 'product_cat' );
		$coupon = new WC_Coupon();
		$coupon->set_props(
			array(
				'discount_type'      => 'fixed_cart',
				'amount'             => 10,
				'product_categories' => array( $term['term_id'] ),
			)
		);
		$coupon->save();

		$result = $discounts->is_coupon_valid( $coupon );
		$this->assertWPError( $result, 'A coupon restricted to a category none of the cart items belong to should be invalid.' );
		$this->assertStringContainsString( 'is not applicable to selected products', $result->get_error_message(), 'The failure should come from the product_categories check.' );

		add_filter( 'woocommerce_coupon_validate_product_categories', '__return_true' );
		$this->assertTrue( $discounts->is_coupon_valid( $coupon ), 'Returning true from the filter should bypass the category restriction.' );
		remove_filter( 'woocommerce_coupon_validate_product_categories', '__return_true' );
	}

	/**
	 * @testdox The woocommerce_coupon_validate_excluded_items filter can bypass excluded item validation.
	 */
	public function test_excluded_items_validation_filter() {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$discounts = new WC_Discounts( WC()->cart );

		// excluded_items only runs for product-type coupons (e.g. percent).
		$coupon = new WC_Coupon();
		$coupon->set_props(
			array(
				'discount_type'        => 'percent',
				'amount'               => 10,
				'excluded_product_ids' => array( $product->get_id() ),
			)
		);
		$coupon->save();

		$result = $discounts->is_coupon_valid( $coupon );
		$this->assertWPError( $result, 'A product coupon whose only cart item is excluded should be invalid.' );
		$this->assertStringContainsString( 'is not applicable to selected products', $result->get_error_message(), 'The failure should come from the excluded_items check.' );

		add_filter( 'woocommerce_coupon_validate_excluded_items', '__return_true' );
		$this->assertTrue( $discounts->is_coupon_valid( $coupon ), 'Returning true from the filter should bypass the excluded item restriction.' );
		remove_filter( 'woocommerce_coupon_validate_excluded_items', '__return_true' );
	}

	/**
	 * @testdox The woocommerce_coupon_validate_sale_items filter can bypass sale item validation.
	 */
	public function test_sale_items_validation_filter() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->set_sale_price( 5 );
		$product->save();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$discounts = new WC_Discounts( WC()->cart );

		// sale_items runs for cart-type coupons that exclude sale items.
		$coupon = new WC_Coupon();
		$coupon->set_props(
			array(
				'discount_type'      => 'fixed_cart',
				'amount'             => 10,
				'exclude_sale_items' => true,
			)
		);
		$coupon->save();

		$result = $discounts->is_coupon_valid( $coupon );
		$this->assertWPError( $result, 'A coupon excluding sale items should be invalid when the cart holds a sale item.' );
		$this->assertStringContainsString( 'is not valid for sale items', $result->get_error_message(), 'The failure should come from the sale_items check.' );

		add_filter( 'woocommerce_coupon_validate_sale_items', '__return_true' );
		$this->assertTrue( $discounts->is_coupon_valid( $coupon ), 'Returning true from the filter should bypass the sale item restriction.' );
		remove_filter( 'woocommerce_coupon_validate_sale_items', '__return_true' );
	}

	/**
	 * @testdox The woocommerce_coupon_validate_excluded_product_ids filter can bypass excluded product ID validation.
	 */
	public function test_excluded_product_ids_validation_filter() {
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$discounts = new WC_Discounts( WC()->cart );

		// excluded_product_ids runs for cart-type coupons.
		$coupon = new WC_Coupon();
		$coupon->set_props(
			array(
				'discount_type'        => 'fixed_cart',
				'amount'               => 10,
				'excluded_product_ids' => array( $product->get_id() ),
			)
		);
		$coupon->save();

		$result = $discounts->is_coupon_valid( $coupon );
		$this->assertWPError( $result, 'A coupon excluding the only cart product should be invalid.' );
		$this->assertStringContainsString( 'is not applicable to the products', $result->get_error_message(), 'The failure should come from the excluded_product_ids check.' );

		add_filter( 'woocommerce_coupon_validate_excluded_product_ids', '__return_true' );
		$this->assertTrue( $discounts->is_coupon_valid( $coupon ), 'Returning true from the filter should bypass the excluded product restriction.' );
		remove_filter( 'woocommerce_coupon_validate_excluded_product_ids', '__return_true' );

		// Force-reject when no cart item matched: the generic message is used, not a dangling empty list.
		$unmatched_coupon = new WC_Coupon();
		$unmatched_coupon->set_props(
			array(
				'discount_type'        => 'fixed_cart',
				'amount'               => 10,
				'excluded_product_ids' => array( $product->get_id() + 999 ),
			)
		);
		$unmatched_coupon->save();

		$this->assertTrue( $discounts->is_coupon_valid( $unmatched_coupon ), 'A coupon excluding a product not in the cart is valid by default.' );
		add_filter( 'woocommerce_coupon_validate_excluded_product_ids', '__return_false' );
		$result = $discounts->is_coupon_valid( $unmatched_coupon );
		$this->assertWPError( $result, 'Returning false should reject even when no excluded product is in the cart.' );
		$this->assertStringContainsString( 'is not applicable to selected products', $result->get_error_message(), 'With no matched products the generic message is used.' );
		$this->assertStringNotContainsString( 'products: .', $result->get_error_message(), 'The rejection message should not contain an empty product list.' );
		remove_filter( 'woocommerce_coupon_validate_excluded_product_ids', '__return_false' );
	}

	/**
	 * @testdox The woocommerce_coupon_validate_excluded_product_categories filter can bypass excluded category validation.
	 */
	public function test_excluded_product_categories_validation_filter() {
		$term    = wp_insert_term( 'excl-cat-' . uniqid(), 'product_cat' );
		$product = WC_Helper_Product::create_simple_product();
		$product->set_category_ids( array( $term['term_id'] ) );
		$product->save();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$discounts = new WC_Discounts( WC()->cart );

		// excluded_product_categories runs for cart-type coupons.
		$coupon = new WC_Coupon();
		$coupon->set_props(
			array(
				'discount_type'               => 'fixed_cart',
				'amount'                      => 10,
				'excluded_product_categories' => array( $term['term_id'] ),
			)
		);
		$coupon->save();

		$result = $discounts->is_coupon_valid( $coupon );
		$this->assertWPError( $result, 'A coupon excluding the cart product\'s category should be invalid.' );
		$this->assertStringContainsString( 'is not applicable to the categories', $result->get_error_message(), 'The failure should come from the excluded_product_categories check.' );

		add_filter( 'woocommerce_coupon_validate_excluded_product_categories', '__return_true' );
		$this->assertTrue( $discounts->is_coupon_valid( $coupon ), 'Returning true from the filter should bypass the excluded category restriction.' );
		remove_filter( 'woocommerce_coupon_validate_excluded_product_categories', '__return_true' );
	}
}
