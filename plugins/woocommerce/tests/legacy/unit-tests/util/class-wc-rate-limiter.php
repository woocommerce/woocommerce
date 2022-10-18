<?php
/**
 * File for the WC_Rate_Limiter class.
 *
 * @package WooCommerce\Tests\Util
 */

/**
 * Test class for WC_Rate_Limiter.
 * @since 3.9.0
 */
class WC_Tests_Rate_Limiter extends WC_Unit_Test_Case {


	/**
	 * Run setup code for unit tests.
	 */
	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * Run tear down code for unit tests.
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test setting the limit and running rate limited actions.
	 */
	public function test_rate_limit_limits() {
		$action_identifier = 'action_1';
		$user_1_id         = 10;
		$user_2_id         = 15;

		$rate_limit_id_1 = $action_identifier . $user_1_id;
		$rate_limit_id_2 = $action_identifier . $user_2_id;

		WC_Rate_Limiter::set_rate_limit( $rate_limit_id_1, 0 );

		$this->assertEquals( true, WC_Rate_Limiter::retried_too_soon( $rate_limit_id_1 ), 'retried_too_soon allowed action to run too soon before the delay.' );
		$this->assertEquals( false, WC_Rate_Limiter::retried_too_soon( $rate_limit_id_2 ), 'retried_too_soon did not allow action to run for another user before the delay.' );

		// As retired_too_soon bails if current time <= limit, the actual time needs to be at least 1 second after the limit.
		sleep( 1 );

		$this->assertEquals( false, WC_Rate_Limiter::retried_too_soon( $rate_limit_id_1 ), 'retried_too_soon did not allow action to run after the designated delay.' );
		$this->assertEquals( false, WC_Rate_Limiter::retried_too_soon( $rate_limit_id_2 ), 'retried_too_soon did not allow action to run for another user after the designated delay.' );
	}

	/**
	 * Test setting the limit and running rate limited actions when missing cache.
	 */
	public function test_rate_limit_limits_without_cache() {
		$action_identifier = 'action_1';
		$user_1_id         = 10;
		$user_2_id         = 15;

		$rate_limit_id_1 = $action_identifier . $user_1_id;
		$rate_limit_id_2 = $action_identifier . $user_2_id;

		WC_Rate_Limiter::set_rate_limit( $rate_limit_id_1, 0 );
		// Clear cached value for user 1.
		wp_cache_delete( WC_Cache_Helper::get_cache_prefix( 'rate_limit' . $rate_limit_id_1 ), WC_Rate_Limiter::CACHE_GROUP );

		$this->assertEquals( true, WC_Rate_Limiter::retried_too_soon( $rate_limit_id_1 ), 'retried_too_soon allowed action to run too soon before the delay.' );
		$this->assertEquals( false, WC_Rate_Limiter::retried_too_soon( $rate_limit_id_2 ), 'retried_too_soon did not allow action to run for another user before the delay.' );

		// Clear cached values for both users.
		wp_cache_delete( WC_Cache_Helper::get_cache_prefix( 'rate_limit' . $rate_limit_id_1 ), WC_Rate_Limiter::CACHE_GROUP );
		wp_cache_delete( WC_Cache_Helper::get_cache_prefix( 'rate_limit' . $rate_limit_id_2 ), WC_Rate_Limiter::CACHE_GROUP );

		// As retired_too_soon bails if current time <= limit, the actual time needs to be at least 1 second after the limit.
		sleep( 1 );

		$this->assertEquals( false, WC_Rate_Limiter::retried_too_soon( $rate_limit_id_1 ), 'retried_too_soon did not allow action to run after the designated delay.' );
		$this->assertEquals( false, WC_Rate_Limiter::retried_too_soon( $rate_limit_id_2 ), 'retried_too_soon did not allow action to run for another user after the designated delay.' );
	}

	/**
	 * Test that rate limit option migration only processes unexpired limits.
	 */
	public function test_rate_limit_option_migration() {
		global $wpdb;

		// Add some options to be migrated.
		add_option( 'woocommerce_rate_limit_add_payment_method_123', time() + 1000 );
		add_option( 'woocommerce_rate_limit_add_payment_method_234', time() - 1 );

		// Run the migration function.
		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/wc-update-functions.php';
		wc_update_600_migrate_rate_limit_options();

		// Ensure that only the _123 limit was migrated.
		$migrated = $wpdb->get_col( "SELECT rate_limit_key FROM {$wpdb->prefix}wc_rate_limits" );

		$this->assertCount( 1, $migrated );
		$this->assertEquals( 'add_payment_method_123', $migrated[0] );

		// Verify that all rate limit options were deleted.
		$this->assertFalse( get_option( 'woocommerce_rate_limit_add_payment_method_123' ) );
		$this->assertFalse( get_option( 'woocommerce_rate_limit_add_payment_method_234' ) );
	}
}
