<?php

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Tests relating to WC_REST_Order_Refunds_V1_Controller.
 */
class WC_REST_Order_Refunds_V1_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * Stores the previous HPOS state.
	 *
	 * @var bool
	 */
	private static $hpos_prev_state;

	/**
	 * Prepare for running the tests. Disables HPOS, as the V1 REST API operates on legacy posts.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		self::$hpos_prev_state = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		OrderHelper::toggle_cot_feature_and_usage( false );
	}

	/**
	 * Restore the previous HPOS state once the tests have finished.
	 */
	public static function tearDownAfterClass(): void {
		OrderHelper::toggle_cot_feature_and_usage( self::$hpos_prev_state );

		parent::tearDownAfterClass();
	}

	/**
	 * Test that the V1 POST response on the refunds endpoint includes the
	 * read-only refunded_payment property, matching the GET response.
	 *
	 * Regression test for woocommerce#27296.
	 */
	public function test_create_refund_response_includes_refunded_payment() {
		wp_set_current_user( 1 );
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->save();

		$request = new WP_REST_Request( 'POST', '/wc/v1/orders/' . $order->get_id() . '/refunds' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'amount'     => '1.00',
					'api_refund' => false,
				)
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'refunded_payment', $data );
		$this->assertFalse( $data['refunded_payment'] );

		// Confirm parity with the GET response for the same refund.
		$get_request  = new WP_REST_Request( 'GET', '/wc/v1/orders/' . $order->get_id() . '/refunds/' . $data['id'] );
		$get_response = $this->server->dispatch( $get_request );
		$get_data     = $get_response->get_data();
		$this->assertArrayHasKey( 'refunded_payment', $get_data );
		$this->assertSame( $data['refunded_payment'], $get_data['refunded_payment'] );
	}
}
