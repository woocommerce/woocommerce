<?php
declare( strict_types = 1 );

/**
 * class WC_REST_Orders_V2_Controller_Tests.
 * Orders Controller tests for V2 REST API.
 */
class WC_REST_Orders_V2_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected static $administrator_id;

	/**
	 * Create fixtures for tests.
	 *
	 * @param WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * @testdox Creating an order returns an error response instead of a fatal error when an unexpected exception is thrown.
	 */
	public function test_create_order_returns_error_response_on_unexpected_exception(): void {
		wp_set_current_user( self::$administrator_id );

		$throw_exception = function () {
			throw new Exception( 'Simulated unexpected failure.' );
		};
		add_filter( 'woocommerce_rest_pre_insert_shop_order_object', $throw_exception );

		$request = new WP_REST_Request( 'POST', '/wc/v2/orders' );
		$request->set_body_params( array( 'status' => 'pending' ) );

		$response = $this->server->dispatch( $request );

		remove_filter( 'woocommerce_rest_pre_insert_shop_order_object', $throw_exception );

		$this->assertEquals( 400, $response->get_status(), 'The unexpected exception should surface as a 400 error response' );
		$this->assertEquals( 'woocommerce_rest_shop_order_not_created', $response->get_data()['code'] );
	}


	/**
	 * @testdox Creating an order returns an error response when the order save is silently aborted by an exception.
	 */
	public function test_create_order_returns_error_response_when_save_is_silently_aborted(): void {
		wp_set_current_user( self::$administrator_id );

		$throw_exception = function () {
			throw new Exception( 'Simulated save abort.' );
		};
		add_action( 'woocommerce_before_order_object_save', $throw_exception );

		$request = new WP_REST_Request( 'POST', '/wc/v2/orders' );
		$request->set_body_params( array( 'status' => 'pending' ) );

		$response = $this->server->dispatch( $request );

		remove_action( 'woocommerce_before_order_object_save', $throw_exception );

		$this->assertEquals( 400, $response->get_status(), 'A silently aborted order save should surface as a 400 error response' );
		$this->assertEquals( 'woocommerce_rest_shop_order_not_created', $response->get_data()['code'] );
	}
}
