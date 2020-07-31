<?php

/**
 * Class WC_Tests_Webhook file.
 */
class WC_Webhook_Test extends \WC_Unit_Test_Case {

	/**
	 * Test: get_retry_count
	 */
	public function test_get_retry_count() {
		$object   = new WC_Webhook();
		$expected = 3;
		$object->set_retry_count( $expected );
		$this->assertEquals( $expected, $object->get_retry_count() );
	}

	/**
	 * Test: get_retry_enabled
	 */
	public function test_get_retry_enabled() {
		$object   = new WC_Webhook();
		$expected = true;
		$object->set_retry_enabled( $expected );
		$this->assertEquals( $expected, $object->get_retry_enabled() );
	}

	/**
	 * Test: should_retry
	 */
	public function test_should_retry() {
		$object = new WC_Webhook();
		$object->set_retry_enabled( false );
		$this->assertFalse( $object->should_retry() );
		$object->set_retry_enabled( true );
		$this->assertTrue( $object->should_retry() );
		$object->set_retry_count( 5 );
		$this->assertTrue( $object->should_retry() );
		$object->set_retry_count( 6 );
		$this->assertFalse( $object->should_retry() );
	}

	/**
	 * Test: failed_delivery
	 */
	public function test_failed_delivery_enqueues_retry() {
		$this->assertFalse( has_action( 'woocommerce_webhook_delivery' ) );

		$expected = 1;
		$object = new WC_Webhook();
		$object->set_retry_enabled( true );
		$request = $this->mock_request();
		$response = new WP_Error( 500, '' );
		$object->set_retry_count( $expected );
		$object->log_delivery( '$delivery_id', $request, $response, 0.0 );

		$this->assertTrue( has_action( 'woocommerce_webhook_delivery' ) );
		$this->assertEquals( $expected, $object->get_retry_count() ); // no change.
	}

	/**
	 * Test: failed_delivery
	 */
	public function test_failed_delivery_resets_retry_count() {
		$object = new WC_Webhook();
		$object->set_retry_enabled( true );
		$object->set_retry_count( 6 );
		$request = $this->mock_request();
		$response = new WP_Error( 500, '' );
		$object->log_delivery( '$delivery_id', $request, $response, 0.0 );
		$this->assertFalse( has_action( 'woocommerce_webhook_delivery' ) );
		$this->assertEquals( 0, $this->test_get_retry_count() );
	}

	/**
	 * Test: retry_delivery
	 */
	public function test_retry_delivery() {
		$wc_queued_webhooks = array();
		$lambda = function( $action_id ) use ( &$wc_queued_webhooks ) {
			$wc_queued_webhooks[] = $action_id;
		};
		add_action( 'action_scheduler_stored_action', $lambda );

		$start_time = time();

		$expected_retry_count = 2;
		$object = new WC_Webhook();
		$object->set_retry_enabled( true );
		$object->set_retry_count( $expected_retry_count - 1 );
		$object->retry_delivery();

		$this->assertEquals( 1, count( $wc_queued_webhooks ) );
		$store = ActionScheduler_Store::instance();
		$action = $store->fetch_action( $wc_queued_webhooks[0] );
		$timestamp = $action->get_schedule()->get_date()->getTimestamp();
		$this->assertGreaterThanOrEqual( $start_time + 400, $timestamp );
		$this->assertLessThan( $start_time + 500, $timestamp );
		$this->assertEquals( $expected_retry_count, $object->get_retry_count() );
	}

	/**
	 * Make a mock request.
	 *
	 * @return array
	 */
	private function mock_request() {
		return array(
			'method'      => 'POST',
			'user-agent'  => 'WooCommerce/Test Hookshot (WordPress)',
			'body'        => '',
			'headers'     => array(
				'Content-Type' => 'application/json',
			),
		);
	}
}
