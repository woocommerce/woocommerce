<?php
/**
 * Data Tests: Tests WC_Coupon's WC_Data Implementation.
 * @package WooCommerce\Tests\Coupon
 */
class WC_Tests_Coupon_Data extends WC_Unit_Test_Case {

	/**
	 * Some of our get/setters were renamed. This will return the function
	 * name we want.
	 * @param string $function
	 * @return string
	 * @since 3.0.0
	 */
	public function get_function_name( $function ) {
		if ( 'exclude_product_ids' === $function ) {
			$function = 'excluded_product_ids';
		} elseif ( 'exclude_product_categories' === $function ) {
			$function = 'excluded_product_categories';
		} elseif ( 'customer_email' === $function ) {
			$function = 'email_restrictions';
		}

		return $function;
	}

	/**
	 * Test that properties can still be accessed directly for backwards
	 * compat sake. They throw a deprecated notice.
	 * @since 3.0.0
	 */
	public function test_coupon_backwards_compat_props_use_correct_getters() {
		// Accessing properties directly will throw some wanted deprected notices
		// So we need to let PHPUnit know we are expecting them and it's fine to continue
		$legacy_keys                   = array(
			'id',
			'exists',
			'coupon_custom_fields',
			'type',
			'discount_type',
			'amount',
			'coupon_amount',
			'code',
			'individual_use',
			'product_ids',
			'exclude_product_ids',
			'usage_limit',
			'usage_limit_per_user',
			'limit_usage_to_x_items',
			'usage_count',
			'expiry_date',
			'product_categories',
			'exclude_product_categories',
			'minimum_amount',
			'maximum_amount',
			'customer_email',
		);
		$this->expected_doing_it_wrong = array_merge( $this->expected_doing_it_wrong, $legacy_keys );

		$coupon = WC_Helper_Coupon::create_coupon();
		$coupon->add_meta_data( 'test_coupon_field', 'testing', true );
		$coupon->save_meta_data();
		$coupon = new WC_Coupon( $coupon->get_id() );

		$this->assertEquals( $coupon->get_id(), $coupon->id );
		$this->assertEquals( ( ( $coupon->get_id() > 0 ) ? true : false ), $coupon->exists );
		$coupon_cf = $coupon->get_meta( 'test_coupon_field' );
		$this->assertEquals( $coupon_cf, $coupon->coupon_custom_fields['test_coupon_field'][0] );
		$this->assertEquals( $coupon->get_discount_type(), $coupon->type );
		$this->assertEquals( $coupon->get_discount_type(), $coupon->discount_type );
		$this->assertEquals( $coupon->get_amount(), $coupon->amount );
		$this->assertEquals( $coupon->get_amount(), $coupon->coupon_amount );
		$this->assertEquals( $coupon->get_code(), $coupon->code );
		$this->assertEquals( $coupon->get_individual_use(), ( 'yes' === $coupon->individual_use ? true : false ) );
		$this->assertEquals( $coupon->get_product_ids(), $coupon->product_ids );
		$this->assertEquals( $coupon->get_excluded_product_ids(), $coupon->exclude_product_ids );
		$this->assertEquals( $coupon->get_usage_limit(), $coupon->usage_limit );
		$this->assertEquals( $coupon->get_usage_limit_per_user(), $coupon->usage_limit_per_user );
		$this->assertEquals( $coupon->get_limit_usage_to_x_items(), $coupon->limit_usage_to_x_items );
		$this->assertEquals( $coupon->get_usage_count(), $coupon->usage_count );
		$this->assertEquals( $coupon->get_date_expires(), $coupon->expiry_date );
		$this->assertEquals( $coupon->get_product_categories(), $coupon->product_categories );
		$this->assertEquals( $coupon->get_excluded_product_categories(), $coupon->exclude_product_categories );
		$this->assertEquals( $coupon->get_minimum_amount(), $coupon->minimum_amount );
		$this->assertEquals( $coupon->get_maximum_amount(), $coupon->maximum_amount );
		$this->assertEquals( $coupon->get_email_restrictions(), $coupon->customer_email );
	}

	/**
	 * Developers can create manual coupons (code only). This test will make sure this works correctly
	 * and some of our backwards compat handling works correctly as well.
	 * @since 3.0.0
	 */
	public function test_read_manual_coupon() {
		$code   = 'manual_coupon_' . time();
		$coupon = new WC_Coupon( $code );
		$coupon->read_manual_coupon(
			$code,
			array(
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
				'customer_email'             => '',
			)
		);
		$this->assertEquals( $code, $coupon->get_code() );
		$this->assertTrue( $coupon->get_individual_use() );
		$this->assertEquals( 100, $coupon->get_maximum_amount() );

		/**
		 * test our back compat logic: passing in product_ids/exclude_product_ids in as strings
		 * and passing free_shipping, exclude_sale_items, and individual_use in as yes|no strings.
		 * setting these values this way will also throw a deprecated notice so we will let
		 * PHPUnit know that its okay to continue.
		 */
		$legacy_keys                   = array(
			'product_ids',
			'exclude_product_ids',
			'individual_use',
			'free_shipping',
			'exclude_sale_items',
		);
		$this->expected_doing_it_wrong = array_merge( $this->expected_doing_it_wrong, $legacy_keys );
		$code                          = 'bc_manual_coupon_' . time();
		$coupon                        = new WC_Coupon( $code );
		$coupon->read_manual_coupon(
			$code,
			array(
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
				'customer_email'             => '',
			)
		);
		$this->assertEquals( $code, $coupon->get_code() );
		$this->assertTrue( $coupon->get_individual_use() );
		$this->assertFalse( $coupon->get_free_shipping() );
		$this->assertFalse( $coupon->get_exclude_sale_items() );
		$this->assertEquals( array( 5, 6 ), $coupon->get_excluded_product_ids() );
		$this->assertEquals( array(), $coupon->get_product_ids() );
	}

	/**
	 * Test standard coupon getters & setters.
	 * @since 3.0.0
	 */
	public function test_coupon_getters_and_setters() {
		$standard_getters_and_setters = array(
			'code'                       => 'test',
			'description'                => 'hello world',
			'discount_type'              => 'percent',
			'amount'                     => 10.50,
			'usage_count'                => 5,
			'individual_use'             => true,
			'product_ids'                => array( 5, 10 ),
			'exclude_product_ids'        => array( 2, 1 ),
			'usage_limit'                => 2,
			'usage_limit_per_user'       => 10,
			'limit_usage_to_x_items'     => 2,
			'free_shipping'              => true,
			'product_categories'         => array( 6 ),
			'exclude_product_categories' => array( 8 ),
			'exclude_sale_items'         => true,
			'minimum_amount'             => 2,
			'maximum_amount'             => 1000,
			'customer_email'             => array( 'test@woo.local' ),
			'used_by'                    => array( 1 ),
		);

		$coupon = new WC_Coupon();
		foreach ( $standard_getters_and_setters as $function => $value ) {
			$function = $this->get_function_name( $function );
			$coupon->{"set_{$function}"}( $value );
			$this->assertEquals( $value, $coupon->{"get_{$function}"}(), $function );
		}
	}

	/**
	 * Test getting custom fields.
	 * @since 3.0.0
	 */
	public function test_get_custom_fields() {
		$coupon     = WC_Helper_Coupon::create_coupon();
		$coupon_id  = $coupon->get_id();
		$meta_value = time() . '-custom-value';
		$coupon->add_meta_data( 'test_coupon_field', $meta_value, true );
		$coupon->save_meta_data();

		$coupon        = new WC_Coupon( $coupon_id );
		$custom_fields = $coupon->get_meta_data();
		$this->assertCount( 1, $custom_fields );
		$this->assertEquals( $meta_value, $coupon->get_meta( 'test_coupon_field' ) );
	}

	/**
	 * Test setting custom fields.
	 * @since 3.0.0
	 */
	public function test_set_custom_fields() {
		$coupon     = WC_Helper_Coupon::create_coupon();
		$coupon_id  = $coupon->get_id();
		$meta_value = time() . '-custom-value';
		$coupon->add_meta_data( 'my-custom-field', $meta_value, true );
		$coupon->save();
		$coupon = new WC_Coupon( $coupon_id );
		$this->assertEquals( $meta_value, $coupon->get_meta( 'my-custom-field' ) );
	}
}
