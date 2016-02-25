<?php

namespace WooCommerce\Tests\Coupon;

/**
 * Class CRUD
 * @package WooCommerce\Tests\Coupon
 */
class CouponCRUD extends \WC_Unit_Test_Case {

	/**
	 * Test coupon create.
	 * @since 2.6.0
	 */
	function test_coupon_create() {
		$code = 'coupon-' . time();
		$coupon = new \WC_Coupon;
		$coupon->set_code( $code );
		$coupon->set_description( 'This is a test comment.' );
		$coupon->create();

		$this->assertEquals( $code, $coupon->get_code() );
		$this->assertNotEquals( 0, $coupon->get_id() );
	}

	/**
	 * Test coupon deletion.
	 * @since 2.6.0
	 */
	function test_coupon_delete() {
		$coupon = \WC_Helper_Coupon::create_coupon();
		$coupon_id = $coupon->get_id();
		$this->assertNotEquals( 0, $coupon_id  );
		$coupon->delete();
		$coupon->read( $coupon_id );
		$this->assertEquals( 0, $coupon->get_id() );
	}

	/**
	 * Test coupon update.
	 * @since 2.6.0
	 */
	function test_coupon_update() {
		$coupon = \WC_Helper_Coupon::create_coupon();
		$coupon_id = $coupon->get_id();
		$this->assertEquals( 'dummycoupon', $coupon->get_code() );
		$coupon->set_code( 'dummycoupon2' );
		$coupon->update();
		$coupon->read( $coupon_id );
		$this->assertEquals( 'dummycoupon2', $coupon->get_code() );
	}

	/**
	 * Test coupon reading from the DB.
	 * @since 2.6.0
	 */
	function test_coupon_read() {
		$code = 'coupon-' . time();
		$coupon = new \WC_Coupon;
		$coupon->set_code( $code );
		$coupon->set_description( 'This is a test coupon.' );
		$coupon->set_usage_count( 5 );
		$coupon->create();
		$coupon_id = $coupon->get_id();

		$coupon_read = new \WC_Coupon;
		$coupon_read->read( $coupon_id );

		$this->assertEquals( 5, $coupon_read->get_usage_count() );
		$this->assertEquals( $code, $coupon_read->get_code() );
		$this->assertEquals( 'This is a test coupon.', $coupon_read->get_description() );
	}

	/**
	 * Test coupon saving.
	 * @since 2.6.0
	 */
	function test_coupon_save() {
		$coupon = \WC_Helper_Coupon::create_coupon();
		$coupon_id = $coupon->get_id();
		$coupon->set_code( 'dummycoupon2' );
		$coupon->save();
		$coupon->read( $coupon_id ); // Read from DB to retest
		$this->assertEquals( 'dummycoupon2', $coupon->get_code() );
		$this->assertEquals( $coupon_id, $coupon->get_id() );

		$new_coupon = new \WC_Coupon;
		$new_coupon->set_code( 'dummycoupon3' );
		$new_coupon->save();
		$new_coupon_id = $new_coupon->get_id();
		$this->assertEquals( 'dummycoupon3', $new_coupon->get_code() );
		$this->assertNotEquals( 0, $new_coupon_id  );
	}

	/**
	 * Test that properties can still be accessed directly for backwards
	 * compat sake. They throw a deprecated notice.
	 * @since 2.6.0
	 */
	public function test_coupon_backwards_compat_props_use_correct_getters() {
		// Accessing properties directly will throw some wanted deprected notices
		// So we need to let PHPUnit know we are expecting them and it's fine to continue
		$legacy_keys = array(
			'id', 'exists', 'coupon_custom_fields', 'type', 'discount_type', 'amount', 'code',
			'individual_use', 'product_ids', 'exclude_product_ids', 'usage_limit', 'usage_limit_per_user',
			'limit_usage_to_x_items', 'usage_count', 'expiry_date', 'product_categories',
			'exclude_product_categories', 'minimum_amount', 'maximum_amount', 'customer_email',
		);
		$this->expected_doing_it_wrong = array_merge( $this->expected_doing_it_wrong, $legacy_keys );

		$coupon = \WC_Helper_Coupon::create_coupon();
		$this->assertEquals( $coupon->get_id(), $coupon->id );
		$this->assertEquals( ( ( $coupon->get_id() > 0 ) ? true : false ), $coupon->exists );
		$this->assertEquals( $coupon->get_custom_fields(), $coupon->coupon_custom_fields );
		$this->assertEquals( $coupon->get_discount_type(), $coupon->type );
		$this->assertEquals( $coupon->get_discount_type(), $coupon->discount_type );
		$this->assertEquals( $coupon->get_amount(), $coupon->amount );
		$this->assertEquals( $coupon->get_code(), $coupon->code );
		$this->assertEquals( $coupon->get_individual_use(), ( 'yes' === $coupon->individual_use ? true : false ) );
		$this->assertEquals( $coupon->get_product_ids(), $coupon->product_ids );
		$this->assertEquals( $coupon->get_excluded_product_ids(), $coupon->exclude_product_ids );
		$this->assertEquals( $coupon->get_usage_limit(), $coupon->usage_limit );
		$this->assertEquals( $coupon->get_usage_limit_per_user(), $coupon->usage_limit_per_user );
		$this->assertEquals( $coupon->get_limit_usage_to_x_items(), $coupon->limit_usage_to_x_items );
		$this->assertEquals( $coupon->get_usage_count(), $coupon->usage_count );
		$this->assertEquals( $coupon->get_expiry_date(), $coupon->expiry_date );
		$this->assertEquals( $coupon->get_product_categories(), $coupon->product_categories );
		$this->assertEquals( $coupon->get_excluded_product_categories(), $coupon->exclude_product_categories );
		$this->assertEquals( $coupon->get_minimum_amount(), $coupon->minimum_amount );
		$this->assertEquals( $coupon->get_maximum_amount(), $coupon->maximum_amount );
		$this->assertEquals( $coupon->get_email_restrictions(), $coupon->customer_email );
	}

	/**
	 * Developers can create manual coupons (code only). This test will make sure this works correctly
	 * and some of our backwards compat handling works correctly as well.
	 * @since 2.6.0
	 */
	public function test_read_manual_coupon() {
		$code = 'manual_coupon_' . time();
		$coupon = new \WC_Coupon( $code );
		$coupon->read_manual_coupon( $code, array(
			'id'                         => true,
			'type'                       => 'fixed_cart',
			'amount'                     => 0,
			'individual_use'             => true,
			'product_ids'                => array(),
			'exclude_product_ids'        => array(),
			'usage_limit'                => '',
			'usage_count'                => '',
			'expiry_date'                => '',
			'free_shipping'              => false,
			'product_categories'         => array(),
			'exclude_product_categories' => array(),
			'exclude_sale_items'         => false,
			'minimum_amount'             => '',
			'maximum_amount'             => 100,
			'customer_email'             => ''
		) );
		$this->assertEquals( $code, $coupon->get_code() );
		$this->assertEquals( true, $coupon->get_individual_use() );
		$this->assertEquals( 100, $coupon->get_maximum_amount() );

		/**
		 * test our back compat logic: passing in product_ids/exclude_product_ids in as strings
		 * and passing free_shipping, exclude_sale_items, and individual_use in as yes|no strings.
		 * setting these values this way will also throw a deprecated notice so we will let
		 * PHPUnit know that its okay to continue.
		 */
		$legacy_keys = array( 'product_ids', 'exclude_product_ids', 'individual_use', 'free_shipping', 'exclude_sale_items' );
		$this->expected_doing_it_wrong = array_merge( $this->expected_doing_it_wrong, $legacy_keys );
		$code = 'bc_manual_coupon_' . time();
		$coupon = new \WC_Coupon( $code );
		$coupon->read_manual_coupon( $code, array(
			'id'                         => true,
			'type'                       => 'fixed_cart',
			'amount'                     => 0,
			'individual_use'             => 'yes',
			'product_ids'                => '',
			'exclude_product_ids'        => '5,6',
			'usage_limit'                => '',
			'usage_count'                => '',
			'expiry_date'                => '',
			'free_shipping'              => 'no',
			'product_categories'         => array(),
			'exclude_product_categories' => array(),
			'exclude_sale_items'         => 'no',
			'minimum_amount'             => '',
			'maximum_amount'             => 100,
			'customer_email'             => ''
		) );
		$this->assertEquals( $code, $coupon->get_code() );
		$this->assertEquals( true, $coupon->get_individual_use() );
		$this->assertEquals( false, $coupon->get_free_shipping() );
		$this->assertEquals( false, $coupon->get_exclude_sale_items() );
		$this->assertEquals( array( 5, 6 ), $coupon->get_excluded_product_ids() );
		$this->assertEquals( array(), $coupon->get_product_ids() );
	}


}
