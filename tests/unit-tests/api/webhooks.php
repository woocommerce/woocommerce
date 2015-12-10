<?php

namespace WooCommerce\Tests\API;

/**
 * Class Webhooks.
 * @package WooCommerce\Tests\API
 * @since 2.2
 */
class Webhooks extends \WC_API_Unit_Test_Case {

	/** @var \WC_API_Webhooks instance */
	protected $endpoint;

	/** @var \WC_Webhook instance */
	protected $webhook;

	/** @var int webhook delivery (comment) ID */
	protected $webhook_delivery_id;

	/**
	 * Setup test webhook data.
	 *
	 * @see WC_API_UnitTestCase::setup()
	 * @since 2.2
	 */
	public function setUp() {

		parent::setUp();

		$this->endpoint = WC()->api->WC_API_Webhooks;

		// mock webhook
		$this->webhook = $this->factory->webhook->create_and_get();

		// mock webhook delivery
		$this->webhook_delivery_id = $this->factory->webhook_delivery->create( array( 'comment_post_ID' => $this->webhook->id ) );
	}


	/**
	 * Test route registration.
	 *
	 * @since 2.2
	 */
	public function test_register_routes() {

		$routes = $this->endpoint->register_routes( array() );

		$this->assertArrayHasKey( '/webhooks', $routes );
		$this->assertArrayHasKey( '/webhooks/count', $routes );
		$this->assertArrayHasKey( '/webhooks/(?P<id>\d+)', $routes );
		$this->assertArrayHasKey( '/webhooks/(?P<webhook_id>\d+)/deliveries', $routes );
		$this->assertArrayHasKey( '/webhooks/(?P<webhook_id>\d+)/deliveries/(?P<id>\d+)', $routes );
	}

	/**
	 * Test GET /webhooks/{id}.
	 *
	 * @since 2.2
	 */
	public function test_get_webhook() {

		// invalid ID
		$response = $this->endpoint->get_webhook( 0 );
		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_id', 404, $response );

		// valid request
		$response = $this->endpoint->get_webhook( $this->webhook->id );

		$this->assertNotWPError( $response );
		$this->assertArrayHasKey( 'webhook', $response );

		$this->check_get_webhook_response( $response['webhook'], $this->webhook );
	}

	/**
	 * Test GET /webhooks/{id} without valid permissions.
	 *
	 * @since 2.2
	 */
	public function test_get_webhook_without_permission() {

		$this->disable_capability( 'read_private_shop_webhooks' );

		$response = $this->endpoint->get_webhook( $this->webhook->id );

		$this->assertHasAPIError( 'woocommerce_api_user_cannot_read_webhook', 401, $response );
	}

	/**
	 * Test GET /webhooks.
	 *
	 * @since 2.2
	 */
	public function test_get_webhooks() {

		// valid request
		$response = $this->endpoint->get_webhooks( null, null, 'active' );

		$this->assertNotWPError( $response );
		$this->assertArrayHasKey( 'webhooks', $response );
		$this->assertCount( 1, $response['webhooks'] );

		$this->check_get_webhook_response( $response['webhooks'][0], $this->webhook );
	}

	/**
	 * Test GET /webhooks without valid permissions.
	 *
	 * @since 2.2
	 */
	public function test_get_webhooks_without_permission() {

		$this->disable_capability( 'read_private_shop_webhooks' );

		$response = $this->endpoint->get_webhooks();

		$this->assertArrayHasKey( 'webhooks', $response );
		$this->assertEmpty( $response['webhooks'] );
	}

	/**
	 * Test GET /webhooks/count.
	 *
	 * @since 2.2
	 */
	public function test_get_webhooks_count() {

		// paused status
		$response = $this->endpoint->get_webhooks_count( 'paused' );
		$this->assertArrayHasKey( 'count', $response );
		$this->assertEquals( 0, $response['count'] );

		// disabled status
		$response = $this->endpoint->get_webhooks_count( 'disabled' );
		$this->assertArrayHasKey( 'count', $response );
		$this->assertEquals( 0, $response['count'] );

		// an invalid status defaults to 'active'
		$response = $this->endpoint->get_webhooks_count( 'bogus' );
		$this->assertArrayHasKey( 'count', $response );
		$this->assertEquals( 1, $response['count'] );

		// valid request
		$response = $this->endpoint->get_webhooks_count();
		$this->assertArrayHasKey( 'count', $response );
		$this->assertEquals( 1, $response['count'] );
	}

	/**
	 * Test GET /webhooks/count without valid permissions.
	 *
	 * @since 2.2
	 */
	public function test_get_webhooks_count_without_permission() {

		// invalid permissions
		$this->disable_capability( 'read_private_shop_webhooks' );

		$response = $this->endpoint->get_webhooks_count();

		$this->assertHasAPIError( 'woocommerce_api_user_cannot_read_webhooks_count', 401, $response );
	}

	/**
	 * Test POST /webhooks.
	 *
	 * @since 2.2
	 */
	public function test_create_webhook() {

		$response = $this->endpoint->create_webhook( $this->get_defaults() );

		$this->check_create_webhook_response( $response );
	}

	/**
	 * Test POST /webhooks without valid permissions.
	 *
	 * @since 2.2
	 */
	public function test_create_webhook_without_permission() {

		$this->disable_capability( 'publish_shop_webhooks' );

		$response = $this->endpoint->create_webhook( $this->get_defaults() );

		$this->assertHasAPIError( 'woocommerce_api_user_cannot_create_webhooks', 401, $response );
	}

	/**
	 * Test POST /webhooks with custom topic.
	 *
	 * @since 2.2
	 */
	public function test_create_webhook_custom_topic() {

		$response = $this->endpoint->create_webhook( $this->get_defaults( array( 'topic' => 'action.woocommerce_cart_updated' ) ) );

		$this->check_edit_webhook_response( $response );
	}

	/**
	 * Test an invalid or empty topic for POST /webhooks.
	 *
	 * @since 2.2
	 */
	public function test_create_webhook_invalid_topic() {

		// empty
		$response = $this->endpoint->create_webhook( $this->get_defaults( array( 'topic' => null ) ) );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_topic', 400, $response );

		// invalid - missing event
		$response = $this->endpoint->create_webhook( $this->get_defaults( array( 'topic' => 'invalid' ) ) );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_topic', 400, $response );

		// invalid
		$response = $this->endpoint->create_webhook( $this->get_defaults( array( 'topic' => 'invalid.topic' ) ) );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_topic', 400, $response );
	}

	/**
	 * Test an invalid or empty delivery for POST /webhooks.
	 *
	 * @since 2.2
	 */
	public function test_create_webhook_invalid_delivery_url() {

		// empty
		$response = $this->endpoint->create_webhook( $this->get_defaults( array( 'delivery_url' => null ) ) );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_delivery_url', 400, $response );

		// invalid - scheme must be HTTP or HTTPS
		$response = $this->endpoint->create_webhook( $this->get_defaults( array( 'delivery_url' => 'foo://bar' ) ) );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_delivery_url', 400, $response );

		// invalid - must be valid URL
		$response = $this->endpoint->create_webhook( $this->get_defaults( array( 'delivery_url' => 'https://foobar!' ) ) );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_delivery_url', 400, $response );
	}

	/**
	 * Test wp_insert_post() failure for POST /webhooks.
	 *
	 * @since 2.2
	 */
	public function test_create_webhook_insert_post_failure() {

		add_filter( 'wp_insert_post_empty_content', '__return_true' );

		$response = $this->endpoint->create_webhook( $this->get_defaults() );

		$this->assertHasAPIError( 'woocommerce_api_cannot_create_webhook', 500, $response );
	}

	/**
	 * Test PUT /webhooks/{id}.
	 *
	 * @since 2.2
	 */
	public function test_edit_webhook() {

		// invalid ID
		$response = $this->endpoint->edit_webhook( 0, $this->get_defaults() );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_id', 404, $response );

		$args = array(
			'secret' => rand_str(),
			'status' => 'paused',
		);

		// valid request
		$response = $this->endpoint->edit_webhook( $this->webhook->id, $this->get_defaults( $args ) );

		$this->check_edit_webhook_response( $response );
	}

	/**
	 * Test PUT /webhooks/{id} without valid permissions.
	 *
	 * @since 2.2
	 */
	public function test_edit_webhook_without_permission() {

		$this->disable_capability( 'edit_published_shop_webhooks' );

		$response = $this->endpoint->edit_webhook( $this->webhook->id, $this->get_defaults() );

		$this->assertHasAPIError( 'woocommerce_api_user_cannot_edit_webhook', 401, $response );
	}

	/**
	 * Test PUT /webhooks/{id} with updated topic.
	 *
	 * @since 2.2
	 */
	public function test_edit_webhook_change_topic() {

		// invalid topic
		$response = $this->endpoint->edit_webhook( $this->webhook->id, $this->get_defaults( array( 'topic' => 'invalid.topic' ) ) );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_topic', 400, $response );

		// valid request
		$response = $this->endpoint->edit_webhook( $this->webhook->id, $this->get_defaults( array( 'topic' => 'order.updated' ) ) );

		$this->check_edit_webhook_response( $response );
	}

	/**
	 * Test PUT /webhooks/{id} with updated delivery URL.
	 *
	 * @since 2.2
	 */
	public function test_edit_webhook_change_delivery_url() {

		// invalid delivery URL
		$response = $this->endpoint->edit_webhook( $this->webhook->id, $this->get_defaults( array( 'delivery_url' => 'foo://bar' ) ) );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_delivery_url', 400, $response );

		// valid request
		$response = $this->endpoint->edit_webhook( $this->webhook->id, $this->get_defaults( array( 'delivery_url' => 'http://www.skyverge.com' ) ) );

		$this->check_edit_webhook_response( $response );
	}

	/**
	 * Test DELETE /webhooks/{id}.
	 *
	 * @since 2.2
	 */
	public function test_delete_webhook() {

		// invalid ID
		$response = $this->endpoint->delete_webhook( 0 );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_id', 404, $response );

		// valid request
		$response = $this->endpoint->delete_webhook( $this->webhook->id );
		$this->assertArrayHasKey( 'message', $response );
		$this->assertEquals( 'Permanently deleted webhook', $response['message'] );
	}


	/**
	 * Test GET /webhooks/{id}/deliveries.
	 *
	 * @since 2.2
	 */
	public function test_get_webhook_deliveries() {

		// invalid ID
		$response = $this->endpoint->get_webhook_deliveries( 0 );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_id', '404', $response );

		// valid request
		$response = $this->endpoint->get_webhook_deliveries( $this->webhook->id );

		$this->assertNotWPError( $response );
		$this->assertArrayHasKey( 'webhook_deliveries', $response );
		$this->assertCount( 1, $response['webhook_deliveries'] );

		$this->check_get_webhook_delivery_response( $response['webhook_deliveries'][0], $this->webhook->get_delivery_log( $response['webhook_deliveries'][0]['id'] ) );
	}


	/**
	 * Test GET /webhooks/{id}/deliveries/{id}.
	 *
	 * @since 2.2
	 */
	public function test_get_webhook_delivery() {

		$response = $this->endpoint->get_webhook_delivery( $this->webhook->id, $this->webhook_delivery_id );

		$this->assertNotWPError( $response );
		$this->assertArrayHasKey( 'webhook_delivery', $response );
		$this->assertNotEmpty( $response['webhook_delivery'] );

		$this->check_get_webhook_delivery_response( $response['webhook_delivery'], $this->webhook->get_delivery_log( $response['webhook_delivery']['id'] ) );
	}

	/**
	 * Test GET /webhooks/{id}/deliveries/{id} with invalid webhook & delivery IDs.
	 *
	 * @since 2.2
	 */
	public function test_get_webhook_delivery_invalid_ids() {

		// invalid webhook ID
		$response = $this->endpoint->get_webhook_delivery( 0, 0 );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_id', 404, $response );

		// invalid delivery ID
		$response = $this->endpoint->get_webhook_delivery( $this->webhook->id, 0 );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_delivery_id', 404, $response );

		$post_id = $this->factory->post->create();
		$mock_comment_id = $this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );

		// invalid delivery (valid comment, but not the correct type)
		$response = $this->endpoint->get_webhook_delivery( $this->webhook->id, $mock_comment_id );

		$this->assertHasAPIError( 'woocommerce_api_invalid_webhook_delivery_id', 400, $response );
	}

	/**
	 * Get default arguments for creating/editing a webhook.
	 *
	 * @since 2.2
	 * @param array $args
	 * @return array
	 */
	protected function get_defaults( $args = array() ) {

		$defaults = array(
			'name'         => rand_str(),
			'topic'        => 'coupon.created',
			'delivery_url' => 'http://example.org',
		);

		return array( 'webhook' => wp_parse_args( $args, $defaults ) );
	}

	/**
	 * Ensure a valid response when creating a webhook.
	 * @since 2.2
	 * @param $response
	 */
	protected function check_create_webhook_response( $response )  {

		$this->assertNotWPError( $response );
		$this->assertArrayHasKey( 'webhook', $response );

		$this->check_get_webhook_response( $response['webhook'], new \WC_Webhook( $response['webhook']['id'] ) );
	}

	/**
	 * Ensure a valid response after editing a webhook.
	 *
	 * @since 2.2
	 * @param $response
	 */
	protected function check_edit_webhook_response( $response ) {

		$this->assertNotWPError( $response );
		$this->assertArrayHasKey( 'webhook', $response );

		$this->check_get_webhook_response( $response['webhook'], new \WC_Webhook( $response['webhook']['id'] ) );
	}

	/**
	 * Ensure valid webhook data response.
	 *
	 * @since 2.2
	 * @param array $response
	 * @param WC_Webhook $webhook
	 */
	protected function check_get_webhook_response( $response, $webhook ) {

		$this->assertEquals( $webhook->id, $response['id'] );
		$this->assertEquals( $webhook->get_name(), $response['name'] );
		$this->assertEquals( $webhook->get_status() , $response['status'] );
		$this->assertEquals( $webhook->get_topic(), $response['topic'] );
		$this->assertEquals( $webhook->get_resource(), $response['resource'] );
		$this->assertEquals( $webhook->get_event(), $response['event'] );
		$this->assertEquals( $webhook->get_hooks(), $response['hooks'] );
		$this->assertEquals( $webhook->get_delivery_url(), $response['delivery_url'] );
		$this->assertArrayHasKey( 'created_at', $response );
		$this->assertArrayHasKey( 'updated_at', $response );
	}

	/**
	 * Ensure valid webhook delivery response.
	 *
	 * @since 2.2
	 * @param array $response
	 * @param array $delivery
	 */
	protected function check_get_webhook_delivery_response( $response, $delivery ) {

		// normalize data
		unset( $response['created_at'] );
		unset( $delivery['comment'] );

		$this->assertEquals( $delivery, $response );
	}

}
