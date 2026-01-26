<?php
/**
 * Data Store Tests: Tests WC_Coupon's WC_Data_Store.
 * @package WooCommerce\Tests\Coupon
 */
class WC_Tests_Coupon_Data_Store extends WC_Unit_Test_Case {

	/**
	 * Make sure the coupon store loads.
	 *
	 * @since 3.0.0
	 */
	public function test_coupon_store_loads() {
		$store = new WC_Data_Store( 'coupon' );
		$this->assertTrue( is_callable( array( $store, 'read' ) ) );
		$this->assertEquals( 'WC_Coupon_Data_Store_CPT', $store->get_current_class_name() );
	}

	/**
	 * Test coupon create.
	 * @since 3.0.0
	 */
	public function test_coupon_create() {
		$code   = 'coupon-' . time();
		$coupon = new WC_Coupon();
		$coupon->set_code( $code );
		$coupon->set_description( 'This is a test comment.' );
		$coupon->save();

		$this->assertEquals( $code, $coupon->get_code() );
		$this->assertNotEquals( 0, $coupon->get_id() );
		$this->assertNotEquals( 'publish', $coupon->get_status() );
	}

	/**
	 * Test coupon deletion.
	 * @since 3.0.0
	 */
	public function test_coupon_delete() {
		$coupon    = WC_Helper_Coupon::create_coupon();
		$coupon_id = $coupon->get_id();
		$this->assertNotEquals( 0, $coupon_id );
		$coupon->delete( true );
		$this->assertEquals( 0, $coupon->get_id() );
		// Test loading a deleted coupon exception.
		try {
			$coupon = new WC_Coupon( $coupon_id );
		} catch ( Exception $e ) {
			$this->assertEquals( 'Invalid coupon.', $e->getMessage() );
		}

	}

	/**
	 * Test coupon accurately cleans up object cache upon deletion.
	 */
	public function test_coupon_cache_deletion() {
		$coupon = WC_Helper_Coupon::create_coupon( 'test' );

		// Prime the cache.
		$hashed_code = md5( wc_strtolower( $coupon->get_code() ) );
		$cache_name  = WC_Cache_Helper::get_cache_prefix( 'coupons' ) . 'coupon_id_from_code_' . $hashed_code;
		wc_get_coupon_id_by_code( $coupon->get_code() );

		$ids = wp_cache_get( $cache_name, 'coupons' );

		$this->assertNotEquals( false, $ids, sprintf( 'Object cache for %s was not primed correctly.', $cache_name ) );

		$coupon->delete( true );

		$ids = wp_cache_get( $cache_name, 'coupons' );

		$this->assertEquals( false, $ids, sprintf( 'Object cache for %s was not removed upon deletion of coupon.', $cache_name ) );
	}

	/**
	 * Test coupon update.
	 * @since 3.0.0
	 */
	public function test_coupon_update() {
		$coupon    = WC_Helper_Coupon::create_coupon();
		$coupon_id = $coupon->get_id();
		$this->assertEquals( 'dummycoupon', $coupon->get_code() );
		$coupon->set_code( 'dummycoupon2' );
		$coupon->save();
		$coupon = new WC_Coupon( $coupon->get_id() );
		$this->assertEquals( 'dummycoupon2', $coupon->get_code() );
	}

	/**
	 * Test coupon reading from the DB.
	 * @since 3.0.0
	 */
	public function test_coupon_read() {
		$code   = 'coupon-' . time();
		$coupon = new WC_Coupon();
		$coupon->set_code( $code );
		$coupon->set_description( 'This is a test coupon.' );
		$coupon->set_amount( '' );
		$coupon->set_usage_count( 5 );
		$coupon->save();
		$coupon_id = $coupon->get_id();

		$coupon_read = new WC_Coupon( $coupon_id );

		$this->assertEquals( 5, $coupon_read->get_usage_count() );
		$this->assertEquals( $code, $coupon_read->get_code() );
		$this->assertEquals( 'publish', $coupon_read->get_status() );
		$this->assertEquals( 0, $coupon->get_amount() );
		$this->assertEquals( 'This is a test coupon.', $coupon_read->get_description() );
	}

	/**
	 * Test coupon saving.
	 * @since 3.0.0
	 */
	public function test_coupon_save() {
		$coupon    = WC_Helper_Coupon::create_coupon();
		$coupon_id = $coupon->get_id();
		$coupon->set_code( 'dummycoupon2' );
		$coupon->save();
		$coupon = new WC_Coupon( $coupon_id ); // Read from DB to retest
		$this->assertEquals( 'dummycoupon2', $coupon->get_code() );
		$this->assertEquals( $coupon_id, $coupon->get_id() );

		$new_coupon = new WC_Coupon();
		$new_coupon->set_code( 'dummycoupon3' );
		$new_coupon->save();
		$new_coupon_id = $new_coupon->get_id();
		$this->assertEquals( 'dummycoupon3', $new_coupon->get_code() );
		$this->assertNotEquals( 0, $new_coupon_id );
	}

	/**
	 * Test coupon date saving/loading.
	 * @since 3.0.0
	 */
	public function test_coupon_date_saving() {
		$expiry_date = time() - 10;

		$coupon = WC_Helper_Coupon::create_coupon( 'coupon-' . time() );
		$coupon->set_date_expires( $expiry_date );
		$coupon->save();

		$coupon_read = new WC_Coupon( $coupon->get_id() );

		$this->assertEquals( date( 'Y-m-d', $expiry_date ), date( 'Y-m-d', $coupon_read->get_date_expires()->getTimestamp() ) );
	}

	/**
	 * Test coupon increase, decrease, user usage count methods.
	 * @since 3.0.0
	 */
	public function test_coupon_usage_magic_methods() {
		$coupon  = WC_Helper_Coupon::create_coupon();
		$user_id = 1;

		$this->assertEquals( 0, $coupon->get_usage_count() );
		$this->assertEmpty( $coupon->get_used_by() );

		$coupon->increase_usage_count( 'woo@woo.local' );

		$this->assertEquals( 1, $coupon->get_usage_count() );
		$this->assertEquals( array( 'woo@woo.local' ), $coupon->get_used_by() );

		$coupon->increase_usage_count( $user_id );
		$coupon->increase_usage_count( $user_id );

		$data_store = WC_Data_Store::load( 'coupon' );
		$this->assertEquals( 2, $data_store->get_usage_by_user_id( $coupon, $user_id ) );

		$coupon->decrease_usage_count( 'woo@woo.local' );
		$coupon->decrease_usage_count( $user_id );
		$this->assertEquals( 1, $coupon->get_usage_count() );
		$this->assertEquals( array( 1 ), $coupon->get_used_by() );
	}

	/**
	 * Test coupon status update from publish to draft.
	 * @since 10.4.0
	 */
	public function test_coupon_status_update_to_draft() {
		$coupon = WC_Helper_Coupon::create_coupon( 'update-test-coupon' );
		$coupon->save();
		$this->assertEquals( 'publish', $coupon->get_status() );

		$coupon->set_status( 'draft' );
		$coupon->save();

		$updated_coupon = new WC_Coupon( $coupon->get_id() );
		$this->assertEquals( 'draft', $updated_coupon->get_status() );
	}

	/**
	 * Test coupon creation with specific status.
	 * @since 10.4.0
	 */
	public function test_coupon_create_with_status() {
		$code   = 'create-test-coupon-' . time();
		$coupon = new WC_Coupon();
		$coupon->set_code( $code );
		$coupon->set_status( 'draft' );
		$coupon->save();

		$this->assertEquals( 'draft', $coupon->get_status() );
		$this->assertNotEquals( 0, $coupon->get_id() );

		$read_coupon = new WC_Coupon( $coupon->get_id() );
		$this->assertEquals( 'draft', $read_coupon->get_status() );
	}

}
