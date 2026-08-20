<?php
/**
 * Unit tests for Webhook functions.
 * @package WooCommerce\Tests\Webhook
 */

/**
 * Class Functions.
 * @package WooCommerce\Tests\Webhook
 * @since 2.3
 */
class WC_Tests_Webhook_Functions extends WC_Unit_Test_Case {

	/**
	 * Temporarily store webhook delivery counters.
	 * @var array
	 */
	public $delivery_counter = array();

	/**
	 * Data provider for test_wc_is_webhook_valid_topic.
	 *
	 * @since 3.2.0
	 */
	public function data_provider_test_wc_is_webhook_valid_topic() {
		return array(
			array( true, wc_is_webhook_valid_topic( 'action.woocommerce_add_to_cart' ) ),
			array( true, wc_is_webhook_valid_topic( 'action.wc_add_to_cart' ) ),
			array( true, wc_is_webhook_valid_topic( 'product.created' ) ),
			array( true, wc_is_webhook_valid_topic( 'product.updated' ) ),
			array( true, wc_is_webhook_valid_topic( 'product.deleted' ) ),
			array( true, wc_is_webhook_valid_topic( 'product.restored' ) ),
			array( true, wc_is_webhook_valid_topic( 'product.published' ) ),
			array( true, wc_is_webhook_valid_topic( 'order.created' ) ),
			array( true, wc_is_webhook_valid_topic( 'order.updated' ) ),
			array( true, wc_is_webhook_valid_topic( 'order.deleted' ) ),
			array( true, wc_is_webhook_valid_topic( 'order.restored' ) ),
			array( true, wc_is_webhook_valid_topic( 'customer.created' ) ),
			array( true, wc_is_webhook_valid_topic( 'customer.updated' ) ),
			array( true, wc_is_webhook_valid_topic( 'customer.deleted' ) ),
			array( true, wc_is_webhook_valid_topic( 'coupon.created' ) ),
			array( true, wc_is_webhook_valid_topic( 'coupon.updated' ) ),
			array( true, wc_is_webhook_valid_topic( 'coupon.deleted' ) ),
			array( true, wc_is_webhook_valid_topic( 'coupon.restored' ) ),
			array( false, wc_is_webhook_valid_topic( 'coupon.upgraded' ) ),
			array( false, wc_is_webhook_valid_topic( 'wc.product.updated' ) ),
			array( false, wc_is_webhook_valid_topic( 'missingdot' ) ),
			array( false, wc_is_webhook_valid_topic( 'with space' ) ),
			array( false, wc_is_webhook_valid_topic( 'action.woocommerce_login_credentials' ) ),
		);
	}

	/**
	 * Test wc_is_webhook_valid_topic().
	 *
	 * @dataProvider data_provider_test_wc_is_webhook_valid_topic
	 * @since 3.2.0
	 * @param bool  $assert Expected value.
	 * @param array $values Values to test.
	 */
	public function test_wc_is_webhook_valid_topic( $assert, $values ) {
		$this->assertEquals( $assert, $values );
	}

	/**
	 * Default-resource + default-event topics with no registered hook are rejected,
	 * to prevent ghost webhooks that save as Active and never deliver (issue #64502).
	 */
	public function test_wc_is_webhook_valid_topic_rejects_unregistered_pairs() {
		$this->assertFalse( wc_is_webhook_valid_topic( 'order.published' ) );
		$this->assertFalse( wc_is_webhook_valid_topic( 'coupon.published' ) );
		$this->assertFalse( wc_is_webhook_valid_topic( 'customer.published' ) );
		$this->assertFalse( wc_is_webhook_valid_topic( 'customer.restored' ) );
	}

	/**
	 * The default-pair allowlist is derived from `WC_Webhook::get_default_topic_hooks()`,
	 * so the validator and the topic-hooks map cannot drift. Any default-pair
	 * topic the map registers a hook for must validate; any that the map leaves
	 * empty must be rejected.
	 */
	public function test_wc_is_webhook_valid_topic_matches_topic_hooks_map() {
		$topic_hooks       = WC_Webhook::get_default_topic_hooks();
		$default_resources = array( 'coupon', 'customer', 'order', 'product' );
		$default_events    = array( 'created', 'updated', 'deleted', 'restored', 'published' );

		foreach ( $default_resources as $resource ) {
			foreach ( $default_events as $event ) {
				$topic    = "{$resource}.{$event}";
				$expected = ! empty( $topic_hooks[ $topic ] );
				$this->assertSame(
					$expected,
					wc_is_webhook_valid_topic( $topic ),
					"Validator out of sync with WC_Webhook::get_default_topic_hooks() for `{$topic}`."
				);
			}
		}
	}

	/**
	 * Plugins extending the default-pair allowlist via `woocommerce_webhook_topic_hooks`
	 * (e.g. wiring `untrashed_post` to fire `customer.restored`) are honored: the
	 * validator accepts the topic so the webhook can save and deliver.
	 */
	public function test_wc_is_webhook_valid_topic_passes_filter_extended_default_pair() {
		$this->assertFalse( wc_is_webhook_valid_topic( 'customer.restored' ) );

		$add_default_pair_hook = function ( $hooks ) {
			$hooks['customer.restored'] = array( 'untrashed_post' );
			return $hooks;
		};

		try {
			add_filter( 'woocommerce_webhook_topic_hooks', $add_default_pair_hook );
			$this->assertTrue( wc_is_webhook_valid_topic( 'customer.restored' ) );
		} finally {
			remove_filter( 'woocommerce_webhook_topic_hooks', $add_default_pair_hook );
		}

		$this->assertFalse( wc_is_webhook_valid_topic( 'customer.restored' ) );
	}

	/**
	 * Inverse of the filter-extended case: a plugin that empties a previously-valid
	 * default pair via `woocommerce_webhook_topic_hooks` causes the validator to
	 * reject that topic, since there are no hooks left to deliver it.
	 */
	public function test_wc_is_webhook_valid_topic_rejects_filter_emptied_default_pair() {
		$this->assertTrue( wc_is_webhook_valid_topic( 'order.created' ) );

		$empty_default_pair_hooks = function ( $hooks ) {
			$hooks['order.created'] = array();
			return $hooks;
		};

		try {
			add_filter( 'woocommerce_webhook_topic_hooks', $empty_default_pair_hooks );
			$this->assertFalse( wc_is_webhook_valid_topic( 'order.created' ) );
		} finally {
			remove_filter( 'woocommerce_webhook_topic_hooks', $empty_default_pair_hooks );
		}

		$this->assertTrue( wc_is_webhook_valid_topic( 'order.created' ) );
	}

	/**
	 * Plugins extending the resource/event lists via the marginal-set filters keep
	 * working: their topics include a custom resource or event, which skips the
	 * default-pair check.
	 */
	public function test_wc_is_webhook_valid_topic_passes_filter_extended_resource_or_event() {
		$add_event    = function ( $events ) {
			$events[] = 'refunded';
			return $events;
		};
		$add_resource = function ( $resources ) {
			$resources[] = 'subscription';
			return $resources;
		};
		add_filter( 'woocommerce_valid_webhook_events', $add_event );
		add_filter( 'woocommerce_valid_webhook_resources', $add_resource );

		try {
			$this->assertTrue( wc_is_webhook_valid_topic( 'order.refunded' ) );
			$this->assertTrue( wc_is_webhook_valid_topic( 'subscription.created' ) );
		} finally {
			remove_filter( 'woocommerce_valid_webhook_events', $add_event );
			remove_filter( 'woocommerce_valid_webhook_resources', $add_resource );
		}
	}

	/**
	 * Data provider for test_wc_is_webhook_valid_status.
	 *
	 * @since 3.5.3
	 */
	public function data_provider_test_wc_is_webhook_valid_status() {
		return array(
			array( true, wc_is_webhook_valid_status( 'active' ) ),
			array( true, wc_is_webhook_valid_status( 'paused' ) ),
			array( true, wc_is_webhook_valid_status( 'disabled' ) ),
			array( false, wc_is_webhook_valid_status( 'pending' ) ),
		);
	}

	/**
	 * Test wc_is_webhook_valid_status
	 *
	 * @dataProvider data_provider_test_wc_is_webhook_valid_status
	 * @since 3.5.3
	 * @param bool  $assert Expected outcome.
	 * @param array $values Values to test.
	 * @return void
	 */
	public function test_wc_is_webhook_valid_status( $assert, $values ) {
		$this->assertEquals( $assert, $values );
	}

	/**
	 * Test wc_get_webhook_statuses().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_webhook_statuses() {
		$expected = array(
			'active'   => 'Active',
			'paused'   => 'Paused',
			'disabled' => 'Disabled',
		);

		$this->assertEquals( $expected, wc_get_webhook_statuses() );
	}

	/**
	 * Test wc_load_webhooks().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_load_webhooks() {
		$webhook = $this->create_webhook();
		$this->assertTrue( wc_load_webhooks() );
		$webhook->delete( true );
		$this->assertFalse( wc_load_webhooks() );
	}

	/**
	 * Provide webhook statuses for tests.
	 *
	 * @since 3.5.0
	 */
	public function provider_webhook_statuses() {

		$webhook_statuses = array();

		foreach ( wc_get_webhook_statuses() as $status_key => $status_string ) {
			$webhook_statuses[] = array( $status_key );
		}

		return $webhook_statuses;
	}

	/**
	 * Test the $status param on wc_load_webhooks().
	 *
	 * @dataProvider provider_webhook_statuses
	 * @param string $status The status of the webhook to test.
	 * @since 3.5.0
	 */
	public function test_wc_load_webhooks_status( $status ) {

		$webhook = $this->create_webhook( 'action.woocommerce_some_action', $status );

		$this->assertTrue( wc_load_webhooks( '' ) );
		$this->assertTrue( wc_load_webhooks( $status ) );

		// Find a different, but still valid status.
		$other_status = ( 'active' === $status ) ? 'disabled' : 'active';

		$this->assertFalse( wc_load_webhooks( $other_status ) );

		$webhook->delete( true );
		$this->assertFalse( wc_load_webhooks( $status ) );
	}

	public function test_wc_load_webhooks_status_invalid() {
		$this->expectException( InvalidArgumentException::class );

		wc_load_webhooks( 'invalid_status' );
	}

	/**
	 * Test the $limit param on wc_load_webhooks().
	 *
	 * @since 3.5.0
	 */
	public function test_wc_load_webhooks_limit() {
		global $wp_filter;

		$webhook_one = $this->create_webhook( 'action.woocommerce_one_test' );
		$webhook_two = $this->create_webhook( 'action.woocommerce_two_test' );

		$this->assertFalse( wc_load_webhooks( '', 0 ) );
		$this->assertFalse( isset( $wp_filter['woocommerce_one_test'] ) );
		$this->assertFalse( isset( $wp_filter['woocommerce_two_test'] ) );

		$this->assertTrue( wc_load_webhooks( '', 1 ) );
		$this->assertFalse( isset( $wp_filter['woocommerce_one_test'] ) );
		$this->assertTrue( isset( $wp_filter['woocommerce_two_test'] ) );

		$webhook_two->delete( true );

		$this->assertTrue( wc_load_webhooks( '', 1 ) );
		$this->assertTrue( isset( $wp_filter['woocommerce_one_test'] ) );

		$webhook_one->delete( true );

		$this->assertFalse( wc_load_webhooks( '', 1 ) );
	}

	/**
	 * Test the $status and $limit param on wc_load_webhooks().
	 *
	 * @dataProvider provider_webhook_statuses
	 * @param string $status The status of the webhook to test.
	 */
	public function test_wc_load_webhooks_status_and_limit( $status ) {
		global $wp_filter;

		$action_one  = 'woocommerce_one_test_status_' . $status;
		$webhook_one = $this->create_webhook( 'action.' . $action_one, $status );
		$action_two  = 'woocommerce_two_test_status_' . $status;
		$webhook_two = $this->create_webhook( 'action.' . $action_two, $status );

		$this->assertTrue( wc_load_webhooks( $status, 1 ) );
		$this->assertFalse( isset( $wp_filter[ $action_one ] ) );
		$this->assertTrue( isset( $wp_filter[ $action_two ] ) );

		$webhook_two->delete( true );

		$this->assertTrue( wc_load_webhooks( $status, 1 ) );
		$this->assertTrue( isset( $wp_filter[ $action_one ] ) );

		$webhook_one->delete( true );
		$this->assertFalse( wc_load_webhooks( $status, 1 ) );
	}

	/**
	 * Verify that a webhook that has multiple hooks defined (in WC_Webhook::get_topic_hooks()),
	 * is only delivered once.
	 *
	 * This example uses Customer Created (which has 3 hooks defined), to verify that creating a customer
	 * will only deliver the payload once per webhook.
	 */
	public function test_woocommerce_webhook_is_delivered_only_once() {
		global $wc_queued_webhooks;
		$this->assertNull( $wc_queued_webhooks );

		$webhook1 = wc_get_webhook( $this->create_webhook( 'customer.created' )->get_id() );
		$webhook2 = wc_get_webhook( $this->create_webhook( 'customer.created' )->get_id() );
		wc_load_webhooks( 'active' );
		add_action( 'woocommerce_webhook_process_delivery', array( $this, 'woocommerce_webhook_process_delivery' ), 1, 2 );
		$customer1 = WC_Helper_Customer::create_customer( 'test1', 'pw1', 'user1@example.com' );
		$customer2 = WC_Helper_Customer::create_customer( 'test2', 'pw2', 'user2@example.com' );
		$this->assertEquals( 1, $this->delivery_counter[ $webhook1->get_id() . $customer1->get_id() ] );
		$this->assertEquals( 1, $this->delivery_counter[ $webhook2->get_id() . $customer1->get_id() ] );
		$this->assertEquals( 1, $this->delivery_counter[ $webhook1->get_id() . $customer2->get_id() ] );
		$this->assertEquals( 1, $this->delivery_counter[ $webhook2->get_id() . $customer2->get_id() ] );

		$this->assertCount( 4, $wc_queued_webhooks );
		$this->assertEquals( $webhook2->get_id(), $wc_queued_webhooks[0]['webhook']->get_id() );
		$this->assertEquals( $customer1->get_id(), $wc_queued_webhooks[0]['arg'] );
		$this->assertEquals( $webhook1->get_id(), $wc_queued_webhooks[1]['webhook']->get_id() );
		$this->assertEquals( $customer1->get_id(), $wc_queued_webhooks[1]['arg'] );
		$this->assertEquals( $webhook2->get_id(), $wc_queued_webhooks[2]['webhook']->get_id() );
		$this->assertEquals( $customer2->get_id(), $wc_queued_webhooks[2]['arg'] );
		$this->assertEquals( $webhook1->get_id(), $wc_queued_webhooks[3]['webhook']->get_id() );
		$this->assertEquals( $customer2->get_id(), $wc_queued_webhooks[3]['arg'] );
		$wc_queued_webhooks = null;

		$webhook1->delete( true );
		$webhook2->delete( true );
		$customer1->delete( true );
		$customer2->delete( true );
		remove_action( 'woocommerce_webhook_process_delivery', array( $this, 'woocommerce_webhook_process_delivery' ), 1, 2 );
	}

	/**
	 * Helper function to keep track of which webhook (and corresponding arg) has been delivered
	 * within the current request.
	 *
	 * @param WC_Webhook $webhook Webhook that is processing delivery.
	 * @param mixed      $arg Webhook arg (usually resource ID).
	 */
	public function woocommerce_webhook_process_delivery( $webhook, $arg ) {
		if ( ! isset( $this->delivery_counter[ $webhook->get_id() . $arg ] ) ) {
			$this->delivery_counter[ $webhook->get_id() . $arg ] = 0;
		}
		$this->delivery_counter[ $webhook->get_id() . $arg ] ++;
	}

	/**
	 * Create and save a webhook for testing.
	 *
	 * @param string $topic The webhook topic for the test.
	 * @param string $status The status of the webhook to be tested.
	 */
	public function create_webhook( $topic = 'action.woocommerce_some_action', $status = 'active' ) {
		$webhook = new WC_Webhook();
		$webhook->set_props(
			array(
				'status'       => $status,
				'name'         => 'Testing webhook',
				'user_id'      => 0,
				'delivery_url' => 'https://requestb.in/17jajv31',
				'secret'       => 'secret',
				'topic'        => $topic,
				'api_version'  => 2,
			)
		);
		$webhook->save();

		return $webhook;
	}
}
