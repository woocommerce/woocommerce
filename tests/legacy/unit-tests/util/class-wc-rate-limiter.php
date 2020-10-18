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
	public function setUp() {
		parent::setUp();
	}

	/**
	 * Run tear down code for unit tests.
	 */
	public function tearDown() {
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

		WC_Rate_Limiter::set_rate_limit( $rate_limit_id_1, 1 );

		$this->assertEquals( true, WC_Rate_Limiter::retried_too_soon( $rate_limit_id_1 ), 'retried_too_soon allowed action to run too soon before the delay.' );
		$this->assertEquals( false, WC_Rate_Limiter::retried_too_soon( $rate_limit_id_2 ), 'retried_too_soon did not allow action to run for another user before the delay.' );

		// As retired_too_soon bails if current time <= limit, the actual time needs to be at least 1 second after the limit.
		sleep( 2 );

		$this->assertEquals( false, WC_Rate_Limiter::retried_too_soon( $rate_limit_id_1 ), 'retried_too_soon did not allow action to run after the designated delay.' );
		$this->assertEquals( false, WC_Rate_Limiter::retried_too_soon( $rate_limit_id_2 ), 'retried_too_soon did not allow action to run for another user after the designated delay.' );
	}

}
