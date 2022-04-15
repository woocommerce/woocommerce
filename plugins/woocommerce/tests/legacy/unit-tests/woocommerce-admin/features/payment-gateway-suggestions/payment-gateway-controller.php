<?php
/**
 * Test the class that extends the payment gateway REST response.
 *
 * @package WooCommerce\Admin\Tests\PaymentGatewaySuggestions
 */

use Automattic\WooCommerce\Admin\Features\RemotePaymentMethods\PaymentGatewaysController;

/**
 * class WC_Admin_Tests_PaymentGatewaySuggestions_PaymentGatewaysController
 */
class WC_Admin_Tests_PaymentGatewaySuggestions_PaymentGatewaysController extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v3/payment_gateways';

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );

		add_filter(
			'woocommerce_payment_gateways',
			function( $gateways ) {
				$gateways[] = 'WC_Mock_Payment_Gateway';
				$gateways[] = 'WC_Mock_Enhanced_Payment_Gateway';

				return $gateways;
			}
		);
		WC()->payment_gateways()->init();

		$this->gateway = new WC_Mock_Enhanced_Payment_Gateway();
	}

	/**
	 * Get mock gateway.
	 */
	public function get_mock_gateway_response() {
		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/mock-enhanced' );
		$response = $this->server->dispatch( $request );
		return $response->get_data();
	}

	/**
	 * Tests a gateway that has not been enhanced with the new methods.
	 */
	public function test_non_enhanced_gateway() {
		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/mock' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'needs_setup', $data );
		$this->assertEquals( array(), $data['post_install_scripts'] );
		$this->assertEquals(
			admin_url( 'admin.php?page=wc-settings&tab=checkout&section=mock' ),
			$data['settings_url']
		);
		$this->assertEquals( null, $data['connection_url'] );
		$this->assertEquals( null, $data['setup_help_text'] );
		$this->assertEquals( array(), $data['required_settings_keys'] );
	}

	/**
	 * Tests that needs_setup is initially false.
	 */
	public function test_payment_gateway_needs_setup() {
		$response = $this->get_mock_gateway_response();
		$this->assertFalse( $response['needs_setup'] );
	}

	/**
	 * Tests that the gateway no longer needs setup.
	 */
	public function test_payment_gateway_setup() {
		$this->gateway->update_option( 'api_key', '123' );
		$response = $this->get_mock_gateway_response();
		$this->assertTrue( $response['needs_setup'] );
	}

	/**
	 * Tests the gateways post install script handles and returned script dependencies.
	 */
	public function test_post_install_scripts() {
		$response = $this->get_mock_gateway_response();
		$this->assertCount( 1, $response['post_install_scripts'] );
		$this->assertEquals( 'post-install-script', $response['post_install_scripts'][0]->handle );
	}

	/**
	 * Tests the gateway's settings URL.
	 */
	public function test_settings_url() {
		$response = $this->get_mock_gateway_response();
		$this->assertEquals(
			admin_url( 'admin.php?page=wc-settings&tab=checkout&section=mock-enhanced' ),
			$response['settings_url']
		);
	}

	/**
	 * Tests the gateway connection URL.
	 */
	public function test_connection_url() {
		$response = $this->get_mock_gateway_response();
		$this->assertEquals(
			'http://testconnection.com?return=' . wc_admin_url( '&task=payments&connection-return=mock-enhanced&_wpnonce=' . wp_create_nonce( 'connection-return' ) ),
			$response['connection_url']
		);
	}

	/**
	 * Tests the setup help text.
	 */
	public function test_setup_help_text() {
		$response = $this->get_mock_gateway_response();
		$this->assertEquals(
			'Test help text.',
			$response['setup_help_text']
		);
	}

	/**
	 * Tests the gateways post install script handles and returned script dependencies.
	 */
	public function test_required_settings_keys() {
		$response = $this->get_mock_gateway_response();
		$this->assertCount( 1, $response['required_settings_keys'] );
		$this->assertEquals( 'api_key', $response['required_settings_keys'][0] );
	}

}

