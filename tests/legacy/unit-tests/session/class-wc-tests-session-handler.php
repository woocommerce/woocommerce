<?php
/**
 * Class WC_Tests_Session_Handler file.
 *
 * @package WooCommerce\Tests\Session
 */

/**
 * Tests for the WC_Session_Handler class.
 */
class WC_Tests_Session_Handler extends WC_Unit_Test_Case {

	/**
	 * Setup.
	 */
	public function setUp() {
		parent::setUp();

		$this->handler = new WC_Session_Handler();
		$this->create_session();
	}

	/**
	 * @testdox Test that save data should insert new row.
	 */
	public function test_save_data_should_insert_new_row() {
		$current_session_data = $this->get_session_from_db( $this->session_key );
		// delete session to make sure a new row is created in the DB.
		$this->handler->delete_session( $this->session_key );
		$this->assertFalse( wp_cache_get( $this->cache_prefix . $this->session_key, WC_SESSION_CACHE_GROUP ) );

		$this->handler->set( 'cart', 'new cart' );
		$this->handler->save_data();

		$updated_session_data = $this->get_session_from_db( $this->session_key );

		$this->assertEquals( $current_session_data->session_id + 1, $updated_session_data->session_id );
		$this->assertEquals( $this->session_key, $updated_session_data->session_key );
		$this->assertEquals( maybe_serialize( array( 'cart' => 'new cart' ) ), $updated_session_data->session_value );
		$this->assertTrue( is_numeric( $updated_session_data->session_expiry ) );
		$this->assertEquals( array( 'cart' => 'new cart' ), wp_cache_get( $this->cache_prefix . $this->session_key, WC_SESSION_CACHE_GROUP ) );
	}

	/**
	 * @testdox Test that save data should replace existing row.
	 */
	public function test_save_data_should_replace_existing_row() {
		$current_session_data = $this->get_session_from_db( $this->session_key );

		$this->handler->set( 'cart', 'new cart' );
		$this->handler->save_data();

		$updated_session_data = $this->get_session_from_db( $this->session_key );

		$this->assertEquals( $current_session_data->session_id, $updated_session_data->session_id );
		$this->assertEquals( $this->session_key, $updated_session_data->session_key );
		$this->assertEquals( maybe_serialize( array( 'cart' => 'new cart' ) ), $updated_session_data->session_value );
		$this->assertTrue( is_numeric( $updated_session_data->session_expiry ) );
	}

	/**
	 * @testdox Test that get_setting() should use cache.
	 */
	public function test_get_session_should_use_cache() {
		$session = $this->handler->get_session( $this->session_key );
		$this->assertEquals( array( 'cart' => 'fake cart' ), $session );
	}

	/**
	 * @testdox Test that get_setting() shouldn't use cache.
	 */
	public function test_get_session_should_not_use_cache() {
		wp_cache_delete( $this->cache_prefix . $this->session_key, WC_SESSION_CACHE_GROUP );
		$session = $this->handler->get_session( $this->session_key );
		$this->assertEquals( array( 'cart' => 'fake cart' ), $session );
	}

	/**
	 * @testdox Test that get_setting() should return default value.
	 */
	public function test_get_session_should_return_default_value() {
		$default_session = array( 'session' => 'default' );
		$session         = $this->handler->get_session( 'non-existent key', $default_session );
		$this->assertEquals( $default_session, $session );
	}

	/**
	 * @testdox Test delete_session().
	 */
	public function test_delete_session() {
		global $wpdb;

		$this->handler->delete_session( $this->session_key );

		$session_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `session_id` FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s",
				$this->session_key
			)
		);

		$this->assertFalse( wp_cache_get( $this->cache_prefix . $this->session_key, WC_SESSION_CACHE_GROUP ) );
		$this->assertNull( $session_id );
	}

	/**
	 * @testdox Test update_session_timestamp().
	 */
	public function test_update_session_timestamp() {
		global $wpdb;

		$timestamp = 1537970882;

		$this->handler->update_session_timestamp( $this->session_key, $timestamp );

		$session_expiry = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT session_expiry FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s",
				$this->session_key
			)
		);
		$this->assertEquals( $timestamp, $session_expiry );
	}

	/**
	 * @testdox Test that nonce of user logged out is only changed by WooCommerce.
	 */
	public function test_maybe_update_nonce_user_logged_out() {
		$this->assertEquals( 1, $this->handler->maybe_update_nonce_user_logged_out( 1, 'wp_rest' ) );
		$this->assertEquals( $this->handler->get_customer_unique_id(), $this->handler->maybe_update_nonce_user_logged_out( 1, 'woocommerce-something' ) );
	}

	/**
	 * Helper function to create a WC session and save it to the DB.
	 */
	protected function create_session() {
		$this->handler->init();
		wp_set_current_user( 1 );
		$this->handler->set( 'cart', 'fake cart' );
		$this->handler->save_data();
		$this->session_key  = $this->handler->get_customer_id();
		$this->cache_prefix = WC_Cache_Helper::get_cache_prefix( WC_SESSION_CACHE_GROUP );
	}

	/**
	 * Helper function to get session data from DB.
	 *
	 * @param string $session_key Session key.
	 * @return stdClass
	 */
	protected function get_session_from_db( $session_key ) {
		global $wpdb;

		$session_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}woocommerce_sessions WHERE `session_key` = %s",
				$session_key
			)
		);

		return $session_data;
	}
}
