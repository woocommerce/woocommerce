<?php
/**
 * Class WC_Tests_Session_Handler file.
 *
 * @package WooCommerce\Tests\Session
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * Tests for the WC_Session_Handler class.
 */
class WC_Tests_Session_Handler extends WC_Unit_Test_Case {

	const DESTROY_EMPTY_SESSION_FEATURE = 'destroy-empty-sessions';

	/**
	 * Session handler instance under test.
	 * @var WC_Session_Handler
	 */
	private $handler;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->handler = new WC_Session_Handler();
		$this->create_session();
	}

	/**
	 * Teardown.
	 */
	public function tearDown(): void {
		// Reset any feature settings.
		$features_controller = wc_get_container()->get( FeaturesController::class );
		$features            = $features_controller->get_features( true );
		$features_controller->change_feature_enable( self::DESTROY_EMPTY_SESSION_FEATURE, ! empty( $features[ self::DESTROY_EMPTY_SESSION_FEATURE ]['enabled_by_default'] ) );

		parent::tearDown();
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
	 * @testdox Test that get_session should use cache.
	 */
	public function test_get_session_should_use_cache() {
		$session = $this->handler->get_session( $this->session_key );
		$this->assertEquals( array( 'cart' => 'fake cart' ), $session );
	}

	/**
	 * @testdox Test that get_session loads properly on cache miss.
	 */
	public function test_get_session_loads_on_cache_miss() {
		wp_cache_delete( $this->cache_prefix . $this->session_key, WC_SESSION_CACHE_GROUP );
		$session = $this->handler->get_session( $this->session_key );
		$this->assertEquals( array( 'cart' => 'fake cart' ), $session );
	}

	/**
	 * @testdox Test that get_session should return default value.
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
	 * @testdox Test that session from cookie is destroyed if expired.
	 */
	public function test_destroy_session_cookie_expired() {
		$customer_id        = '1';
		$session_expiration = time() - 10000;
		$session_expiring   = time() - 1000;
		$cookie_hash        = '';
		$this->session_key  = $customer_id;

		$handler = $this
			->getMockBuilder( WC_Session_Handler::class )
			->setMethods( array( 'get_session_cookie' ) )
			->getMock();

		$handler
			->method( 'get_session_cookie' )
			->willReturn( array( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) );

		add_filter( 'woocommerce_set_cookie_enabled', '__return_false' );

		$handler->init_session_cookie();

		remove_filter( 'woocommerce_set_cookie_enabled', '__return_false' );

		$this->assertFalse( wp_cache_get( $this->cache_prefix . $this->session_key, WC_SESSION_CACHE_GROUP ) );
		$this->assertNull( $this->get_session_from_db( $this->session_key ) );
	}

	/**
	 * @testdox Test that session from cookie is destroyed if user is logged out.
	 */
	public function test_destroy_session_user_logged_out() {
		$customer_id        = '1';
		$session_expiration = time() + 50000;
		$session_expiring   = time() + 5000;
		$cookie_hash        = '';
		$this->session_key  = $customer_id;

		// Simulate a log out.
		wp_set_current_user( 0 );

		$handler = $this
			->getMockBuilder( WC_Session_Handler::class )
			->setMethods( array( 'get_session_cookie' ) )
			->getMock();

		$handler
			->method( 'get_session_cookie' )
			->willReturn( array( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) );

		add_filter( 'woocommerce_set_cookie_enabled', '__return_false' );

		$handler->init_session_cookie();

		remove_filter( 'woocommerce_set_cookie_enabled', '__return_false' );

		$this->assertFalse( wp_cache_get( $this->cache_prefix . $this->session_key, WC_SESSION_CACHE_GROUP ) );
		$this->assertNull( $this->get_session_from_db( $this->session_key ) );
	}

	/**
	 * @testdox Test that session from cookie is destroyed if logged in user doesn't match.
	 */
	public function test_destroy_session_user_mismatch() {
		$customer           = WC_Helper_Customer::create_customer();
		$customer_id        = (string) $customer->get_id();
		$session_expiration = time() + 50000;
		$session_expiring   = time() + 5000;
		$cookie_hash        = '';

		$handler = $this
			->getMockBuilder( WC_Session_Handler::class )
			->setMethods( array( 'get_session_cookie' ) )
			->getMock();

		wp_set_current_user( $customer->get_id() );

		$handler->set( 'cart', 'fake cart' );
		$handler->save_data();

		$handler
			->method( 'get_session_cookie' )
			->willReturn( array( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) );

		wp_set_current_user( 1 );

		add_filter( 'woocommerce_set_cookie_enabled', '__return_false' );

		$handler->init_session_cookie();

		remove_filter( 'woocommerce_set_cookie_enabled', '__return_false' );

		$this->assertFalse( wp_cache_get( $this->cache_prefix . $customer_id, WC_SESSION_CACHE_GROUP ) );
		$this->assertNull( $this->get_session_from_db( $customer_id ) );
		$this->assertNotNull( $this->get_session_from_db( '1' ) );
	}

	/**
	 * Test that method destroys session when all conditions are met.
	 */
	public function test_destroy_session_if_empty_should_destroy_session_when_all_conditions_met() {
		// Use a logged out user.
		wp_set_current_user( 0 );

		// Enable the empty session feature.
		wc_get_container()->get( FeaturesController::class )->change_feature_enable( self::DESTROY_EMPTY_SESSION_FEATURE, true );

		// Spy on destroy_session method - this time expect it to be called.
		$session_handler_spy = $this->getMockBuilder( WC_Session_Handler::class )
			->onlyMethods( array( 'destroy_session' ) )
			->getMock();

		$reflection = new ReflectionClass( $session_handler_spy );

		// Setup the session cookie how most extensions trigger it.
		$session_handler_spy->set_customer_session_cookie( true );

		// Set the $_COOOKIE value as if it were passed by the browser.
		$cookie_property = $reflection->getProperty( '_cookie' );
		$cookie_property->setAccessible( true );
		$cookie_name             = $cookie_property->getValue( $session_handler_spy );
		$_COOKIE[ $cookie_name ] = 'test_cookie_value';

		// Make sure the cart is empty.
		wc_empty_cart();

		// Verify that the session won't get destroyed based on all passing conditions except the one we're currently testing.
		$session_handler_spy->expects( $this->once() )->method( 'destroy_session' );

		$session_handler_spy->destroy_session_if_empty();
	}

	/**
	 * Test that method returns early if user is logged in.
	 */
	public function test_should_return_early_if_user_is_logged_in() {
		// Create and log in a user.
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		// Enable the empty session feature.
		wc_get_container()->get( FeaturesController::class )->change_feature_enable( self::DESTROY_EMPTY_SESSION_FEATURE, true );

		// Spy on destroy_session method - this time expect it to be called.
		$session_handler_spy = $this->getMockBuilder( WC_Session_Handler::class )
			->onlyMethods( array( 'destroy_session' ) )
			->getMock();

		$reflection = new ReflectionClass( $session_handler_spy );

		// Setup the session cookie how most extensions trigger it.
		$session_handler_spy->set_customer_session_cookie( true );

		// Set the $_COOOKIE value as if it were passed by the browser.
		$cookie_property = $reflection->getProperty( '_cookie' );
		$cookie_property->setAccessible( true );
		$cookie_name             = $cookie_property->getValue( $session_handler_spy );
		$_COOKIE[ $cookie_name ] = 'test_cookie_value';

		// Make sure the cart is empty.
		wc_empty_cart();

		// Verify that the session won't get destroyed based on all passing conditions except the one we're currently testing.
		$session_handler_spy->expects( $this->never() )->method( 'destroy_session' );

		$session_handler_spy->destroy_session_if_empty();
	}

	/**
	 * Test that method returns early if no cookie exists.
	 */
	public function test_should_return_early_if_has_cookie_is_false() {
		// Make sure the user isn't currently logged in.
		wp_set_current_user( 0 );

		// Enable the empty session feature.
		wc_get_container()->get( FeaturesController::class )->change_feature_enable( self::DESTROY_EMPTY_SESSION_FEATURE, true );

		// Spy on destroy_session method - this time expect it to be called.
		$session_handler_spy = $this->getMockBuilder( WC_Session_Handler::class )
			->onlyMethods( array( 'destroy_session' ) )
			->getMock();

		$reflection = new ReflectionClass( $session_handler_spy );

		// Make sure ::_has_cookie is false to mimic cases where something already destroyed the session or the session wasn't loaded for some reason.
		$has_cookie_property = $reflection->getProperty( '_has_cookie' );
		$has_cookie_property->setAccessible( true );
		$has_cookie_property->setValue( $session_handler_spy, false );

		// Set the $_COOOKIE value as if it were passed by the browser.
		$cookie_property = $reflection->getProperty( '_cookie' );
		$cookie_property->setAccessible( true );
		$cookie_name             = $cookie_property->getValue( $session_handler_spy );
		$_COOKIE[ $cookie_name ] = 'test_cookie_value';

		// Make sure the cart is empty.
		wc_empty_cart();

		// Verify that the session won't get destroyed based on all passing conditions except the one we're currently testing.
		$session_handler_spy->expects( $this->never() )->method( 'destroy_session' );

		$session_handler_spy->destroy_session_if_empty();
	}

	/**
	 * Test that method returns early if $_COOKIE is not set but cookie was set during request.
	 */
	public function test_should_return_early_if_cookie_set_during_request() {
		// Make sure the user isn't currently logged in.
		wp_set_current_user( 0 );

		// Enable the empty session feature.
		wc_get_container()->get( FeaturesController::class )->change_feature_enable( self::DESTROY_EMPTY_SESSION_FEATURE, true );

		// Spy on destroy_session method - this time expect it to be called.
		$session_handler_spy = $this->getMockBuilder( WC_Session_Handler::class )
			->onlyMethods( array( 'destroy_session' ) )
			->getMock();

		$reflection = new ReflectionClass( $session_handler_spy );

		// Setup the session cookie how most extensions trigger it.
		$session_handler_spy->set_customer_session_cookie( true );

		// Clear the $_COOOKIE to show that the browser didn't send it - meaning it was set during this request.
		$cookie_property = $reflection->getProperty( '_cookie' );
		$cookie_property->setAccessible( true );
		$cookie_name = $cookie_property->getValue( $session_handler_spy );
		unset( $_COOKIE[ $cookie_name ] );

		// Make sure the cart is empty.
		wc_empty_cart();

		// Verify that the session won't get destroyed based on all passing conditions except the one we're currently testing.
		$session_handler_spy->expects( $this->never() )->method( 'destroy_session' );

		$session_handler_spy->destroy_session_if_empty();
	}

	/**
	 * Test that method returns early if session data is not empty.
	 */
	public function test_should_return_early_if_session_data_not_empty() {
		// Make sure the user isn't currently logged in.
		wp_set_current_user( 0 );

		// Enable the empty session feature.
		wc_get_container()->get( FeaturesController::class )->change_feature_enable( self::DESTROY_EMPTY_SESSION_FEATURE, true );

		// Spy on destroy_session method - this time expect it to be called.
		$session_handler_spy = $this->getMockBuilder( WC_Session_Handler::class )
			->onlyMethods( array( 'destroy_session' ) )
			->getMock();

		$reflection = new ReflectionClass( $session_handler_spy );

		// Setup the session cookie how most extensions trigger it.
		$session_handler_spy->set_customer_session_cookie( true );

		// Set the $_COOOKIE value as if it were passed by the browser.
		$cookie_property = $reflection->getProperty( '_cookie' );
		$cookie_property->setAccessible( true );
		$cookie_name             = $cookie_property->getValue( $session_handler_spy );
		$_COOKIE[ $cookie_name ] = 'test_cookie_value';

		// Make sure the cart is empty.
		wc_empty_cart();

		// Add some data to the session so it isn't empty.
		$session_handler_spy->set( 'foo', 'bar' );

		// Verify that the session won't get destroyed based on all passing conditions except the one we're currently testing.
		$session_handler_spy->expects( $this->never() )->method( 'destroy_session' );

		$session_handler_spy->destroy_session_if_empty();
	}

	/**
	 * Tests expired sessions cleanup: indirectly verifies that batched deletion works and targeted caches cleanup.
	 *
	 * @return void
	 */
	public function test_cleanup_sessions(): void {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( 'REPLACE INTO %i (session_key, session_value, session_expiry) VALUES (%s, %s, %d)', "{$wpdb->prefix}woocommerce_sessions", 'guest', 'expired', time() - DAY_IN_SECONDS ) );
		$wpdb->query( $wpdb->prepare( 'REPLACE INTO %i (session_key, session_value, session_expiry) VALUES (%s, %s, %d)', "{$wpdb->prefix}woocommerce_sessions", 'customer', 'active', time() + DAY_IN_SECONDS ) );

		$handler = $this->getMockBuilder( WC_Session_Handler::class )->setMethodsExcept( array( 'cleanup_sessions' ) )->getMock();
		$handler->cleanup_sessions();

		// Verify the DB and cache cleanup results.
		$this->assertSame( array( array( 'customer' ) ), $wpdb->get_results( $wpdb->prepare( "SELECT session_key FROM %i WHERE session_key IN ('guest', 'customer')", "{$wpdb->prefix}woocommerce_sessions" ), ARRAY_N ) );
	}

	/**
	 * Helper function to create a WC session and save it to the DB.
	 */
	protected function create_session() {
		wp_set_current_user( 1 );
		$this->handler->init();
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
