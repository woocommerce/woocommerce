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


}
