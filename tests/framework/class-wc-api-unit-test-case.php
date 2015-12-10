<?php
/**
 * WC API Unit Test Case
 *
 * Provides REST API specific setup/tear down/assert methods, along with some helper.
 * functions.
 *
 * @since 2.2
 */
class WC_API_Unit_Test_Case extends WC_Unit_Test_Case {

	/**
	 * Setup the test case case.
	 *
	 * @since 2.2
	 * @see WC_Unit_Test_Case::setUp()
	 */
	public function setUp() {

		parent::setUp();

		// load API classes
		WC()->api->includes();

		// set user
		$this->user_id = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $this->user_id );

		// this isn't used, but it causes a warning unless set
		$_SERVER['REQUEST_METHOD'] = null;

		// mock the API server to prevent headers from being sent
		$this->mock_server = $this->getMock( 'WC_API_Server', array ('header' ), array( '/' ) );

		WC()->api->register_resources( $this->mock_server );
	}

	/**
	 * Assert the given response is an API error with a specific code and status.
	 *
	 * @since 2.2
	 * @param string $code error code, e.g. `woocommerce_api_user_cannot_read_orders_count`
	 * @param int|null $status HTTP status code associated with error, e.g. 400
	 * @param WP_Error $response
	 * @param string $message optional message to render when assertion fails
	 */
	public function assertHasAPIError( $code, $status = null, $response, $message = '' ) {

		$this->assertWPError( $response, $message );

		// code
		$this->assertEquals( $code, $response->get_error_code(), $message );

		// status
		$data = $response->get_error_data();

		$this->assertArrayHasKey( 'status', $data, $message );
		$this->assertEquals( $status, $data['status'], $message );
	}


	/**
	 * Disable the given capability for the current user, used for testing.
	 * permission checking.
	 *
	 * @since 2.2
	 * @param string $capability, e.g. `read_private_shop_orders`
	 */
	protected function disable_capability( $capability ) {

		$user = wp_get_current_user();
		$user->add_cap( $capability, false );

		// flush capabilities, see https://core.trac.wordpress.org/ticket/28374
		$user->get_role_caps();
		$user->update_user_level_from_caps();
	}

}
