<?php
/**
 * Email Settings V4 controller unit tests.
 *
 * @package WooCommerce\RestApi\UnitTests
 * @since   4.0.0
 */

declare(strict_types=1);

/**
 * Email Settings V4 controller unit tests.
 *
 * @package WooCommerce\RestApi\UnitTests
 * @since   4.0.0
 */
class WC_REST_Email_Settings_V4_Controller_Test extends WC_REST_Unit_Test_Case {

	/**
	 * User ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * @var callable
	 */
	private $feature_filter;

	/**
	 * Previous option values to restore after tests.
	 *
	 * @var array<string, mixed>
	 */
	private $prev_options = array();

	/**
	 * Setup.
	 */
	public function setUp(): void {
		// Enable the v4 REST API feature before bootstrapping.
		$this->feature_filter = function ( $features ) {
			$features[] = 'rest-api-v4';
			return $features;
		};

		add_filter( 'woocommerce_admin_features', $this->feature_filter );

		parent::setUp();

		// Snapshot current option values to restore on tearDown.
		$option_ids = array(
			'woocommerce_email_from_name',
			'woocommerce_email_from_address',
			'woocommerce_email_reply_to_enabled',
			'woocommerce_email_reply_to_name',
			'woocommerce_email_reply_to_address',
		);
		foreach ( $option_ids as $id ) {
			$this->prev_options[ $id ] = get_option( $id, null );
		}

		// Create a user with permissions.
		$this->user_id = $this->factory->user->create(
			array(
				'role' => 'shop_manager',
			)
		);
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		if ( isset( $this->feature_filter ) ) {
			remove_filter( 'woocommerce_admin_features', $this->feature_filter );
		}

		// Restore previous option values.
		foreach ( $this->prev_options as $id => $value ) {
			if ( null === $value ) {
				delete_option( (string) $id );
			} else {
				update_option( (string) $id, $value );
			}
		}

		parent::tearDown();
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v4/settings/email', $routes );
	}

	/**
	 * Test getting email settings.
	 */
	public function test_get_item() {
		wp_set_current_user( $this->user_id );
		$request  = new WP_REST_Request( 'GET', '/wc/v4/settings/email' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'email', $data['id'] );
		$this->assertArrayHasKey( 'groups', $data );
		$this->assertArrayHasKey( 'values', $data );

		// Extra assertions on groups structure.
		$this->assertIsArray( $data['groups'] );
		$this->assertArrayHasKey( 'sender_details', $data['groups'] );
		$this->assertArrayHasKey( 'fields', $data['groups']['sender_details'] );
		$this->assertNotEmpty( $data['groups']['sender_details']['fields'] );

		// Extra assertions on values keys.
		$this->assertIsArray( $data['values'] );
		$this->assertArrayHasKey( 'woocommerce_email_from_name', $data['values'] );
		$this->assertArrayHasKey( 'woocommerce_email_from_address', $data['values'] );
		$this->assertArrayHasKey( 'woocommerce_email_reply_to_enabled', $data['values'] );
		$this->assertArrayHasKey( 'woocommerce_email_reply_to_name', $data['values'] );
		$this->assertArrayHasKey( 'woocommerce_email_reply_to_address', $data['values'] );
	}

	/**
	 * Test updating email settings.
	 */
	public function test_update_item() {
		wp_set_current_user( $this->user_id );
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/email' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'woocommerce_email_from_name'     => 'Test Sender',
						'woocommerce_email_from_address'  => 'sender@example.com',
						'woocommerce_email_reply_to_enabled' => true,
						'woocommerce_email_reply_to_name' => 'Reply Name',
						'woocommerce_email_reply_to_address' => 'reply@example.com',
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );
		$response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Test Sender', get_option( 'woocommerce_email_from_name' ) );
		$this->assertEquals( 'sender@example.com', get_option( 'woocommerce_email_from_address' ) );
		$this->assertEquals( 'yes', get_option( 'woocommerce_email_reply_to_enabled' ) );
		$this->assertEquals( 'Reply Name', get_option( 'woocommerce_email_reply_to_name' ) );
		$this->assertEquals( 'reply@example.com', get_option( 'woocommerce_email_reply_to_address' ) );
	}

	/**
	 * Test updating email settings with invalid reply-to name.
	 * When reply-to is enabled, the name is required.
	 */
	public function test_update_item_with_invalid_reply_to_name() {
		wp_set_current_user( $this->user_id );
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/email' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'woocommerce_email_reply_to_enabled' => true,
						'woocommerce_email_reply_to_name' => '',
						'woocommerce_email_reply_to_address' => '',
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );
		$response->get_data();
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Reply-to name cannot be empty when reply-to is enabled.', $response->get_data()['message'] );
	}

	/**
	 * Test updating email settings with invalid reply-to address.
	 * When reply-to is enabled, the name is required.
	 */
	public function test_update_item_with_invalid_reply_to_address() {
		wp_set_current_user( $this->user_id );
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/email' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'values' => array(
						'woocommerce_email_reply_to_enabled' => true,
						'woocommerce_email_reply_to_name' => 'Name',
						'woocommerce_email_reply_to_address' => 'invalid',
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );
		$response->get_data();
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Please enter a valid reply-to email address.', $response->get_data()['message'] );
	}

	/**
	 * Test getting email settings without permission.
	 */
	public function test_get_item_without_permission() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/wc/v4/settings/email' );
		$response = $this->server->dispatch( $request );
		$response->get_data();

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating email settings without permission.
	 */
	public function test_update_item_without_permission() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/email' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'woocommerce_email_from_name' => 'Test Sender',
				)
			)
		);
		$response = $this->server->dispatch( $request );
		$response->get_data();

		$this->assertEquals( 401, $response->get_status() );
	}
}
