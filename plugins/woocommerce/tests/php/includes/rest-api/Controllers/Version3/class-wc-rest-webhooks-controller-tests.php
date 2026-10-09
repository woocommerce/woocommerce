<?php
declare( strict_types = 1 );

/**
 * Class WC_REST_Webhooks_Controller_Tests.
 */
class WC_REST_Webhooks_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * The prefix every webhook delivery URL in this class shares.
	 */
	private const PING_URL_PREFIX = 'https://example.com/';

	/**
	 * Answer webhook pings through the HTTP fixture inherited from WP_HTTP_TestCase.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->http_responder = array( $this, 'respond_to_ping' );
	}

	/**
	 * @testdox A webhook completes its registered V3 CRUD lifecycle and sends one create ping.
	 */
	public function test_webhook_crud_lifecycle(): void {
		$delivery_url = self::PING_URL_PREFIX . 'crud-webhook';

		wp_set_current_user( 1 );

		$response = $this->do_rest_request(
			'webhooks',
			'POST',
			array(
				'name'         => 'order updates',
				'topic'        => 'order.updated',
				'delivery_url' => $delivery_url,
			)
		);
		$data     = $response->get_data();

		$this->assertSame( 201, $response->get_status() );
		$webhook_id = $data['id'];
		$this->assertIsInt( $webhook_id );
		$this->assertSame( rest_url( 'wc/v3/webhooks/' . $webhook_id ), $response->get_headers()['Location'] );
		$expected_webhook = array(
			'id'           => $webhook_id,
			'name'         => 'order updates',
			'status'       => 'active',
			'topic'        => 'order.updated',
			'hooks'        => array(
				'woocommerce_update_order',
				'woocommerce_order_refunded',
			),
			'delivery_url' => $delivery_url,
		);
		$this->assertSame(
			$expected_webhook,
			array_intersect_key(
				$data,
				array_flip( array( 'id', 'name', 'status', 'topic', 'delivery_url', 'hooks' ) )
			)
		);
		$create_links = $response->get_links();
		$this->assertSame( rest_url( 'wc/v3/webhooks/' . $webhook_id ), $create_links['self'][0]['href'] );
		$this->assertSame( rest_url( 'wc/v3/webhooks' ), $create_links['collection'][0]['href'] );

		$fresh_webhook = wc_get_webhook( $webhook_id );
		$this->assertInstanceOf( WC_Webhook::class, $fresh_webhook );
		$this->assertSame( 'order updates', $fresh_webhook->get_name() );
		$this->assertSame( 'active', $fresh_webhook->get_status() );
		$this->assertSame( 'order.updated', $fresh_webhook->get_topic() );
		$this->assertSame( $delivery_url, $fresh_webhook->get_delivery_url() );
		$this->assert_ping_contract( $delivery_url, $webhook_id );

		$response = $this->do_rest_get_request( 'webhooks/' . $webhook_id );
		$this->assertSame( 200, $response->get_status() );
		$item_data = $response->get_data();
		$this->assertSame(
			$expected_webhook,
			array_intersect_key(
				$item_data,
				array_flip( array( 'id', 'name', 'status', 'topic', 'delivery_url', 'hooks' ) )
			)
		);
		$item_links = $response->get_links();
		$this->assertSame( rest_url( 'wc/v3/webhooks/' . $webhook_id ), $item_links['self'][0]['href'] );
		$this->assertSame( rest_url( 'wc/v3/webhooks' ), $item_links['collection'][0]['href'] );

		$response = $this->do_rest_get_request( 'webhooks', array( 'include' => array( $webhook_id ) ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( $webhook_id ), wp_list_pluck( $response->get_data(), 'id' ) );

		$response = $this->do_rest_request(
			'webhooks/' . $webhook_id,
			'PUT',
			array(
				'name'   => 'paused order updates',
				'status' => 'paused',
			)
		);
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $webhook_id, $response->get_data()['id'] );
		$this->assertSame( 'paused order updates', $response->get_data()['name'] );
		$this->assertSame( 'paused', $response->get_data()['status'] );

		$fresh_webhook = wc_get_webhook( $webhook_id );
		$this->assertInstanceOf( WC_Webhook::class, $fresh_webhook );
		$this->assertSame( 'paused order updates', $fresh_webhook->get_name() );
		$this->assertSame( 'paused', $fresh_webhook->get_status() );

		$response = $this->do_rest_request( 'webhooks/' . $webhook_id, 'DELETE', array( 'force' => true ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $webhook_id, $response->get_data()['id'] );
		$this->assertNull( wc_get_webhook( $webhook_id ) );
		$this->assertSame( 404, $this->do_rest_get_request( 'webhooks/' . $webhook_id )->get_status() );
	}

	/**
	 * @testdox Webhooks complete registered V3 batch create, mixed, and delete lifecycles.
	 */
	public function test_webhook_batch_lifecycle(): void {
		$first_url  = self::PING_URL_PREFIX . 'batch-first';
		$second_url = self::PING_URL_PREFIX . 'batch-second';
		$third_url  = self::PING_URL_PREFIX . 'batch-third';

		wp_set_current_user( 1 );

		$response = $this->do_rest_request(
			'webhooks/batch',
			'POST',
			array(
				'create' => array(
					array(
						'name'         => 'coupon created',
						'topic'        => 'coupon.created',
						'delivery_url' => $first_url,
					),
					array(
						'name'         => 'customer deleted',
						'topic'        => 'customer.deleted',
						'delivery_url' => $second_url,
					),
				),
			)
		);
		$data     = $response->get_data();
		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 2, $data['create'] );
		$this->assertContainsOnly( 'int', wp_list_pluck( $data['create'], 'id' ) );
		$first_id  = $data['create'][0]['id'];
		$second_id = $data['create'][1]['id'];
		$this->assertGreaterThan( 0, $first_id );
		$this->assertGreaterThan( 0, $second_id );
		$this->assertNotSame( $first_id, $second_id );
		$this->assertSame( array( 'coupon created', 'customer deleted' ), wp_list_pluck( $data['create'], 'name' ) );
		$this->assertSame( array( 'coupon.created', 'customer.deleted' ), wp_list_pluck( $data['create'], 'topic' ) );
		$this->assertSame( array( $first_url, $second_url ), wp_list_pluck( $data['create'], 'delivery_url' ) );
		$this->assertCount( 2, $this->captured_pings() );
		$this->assert_ping_contract( $first_url, $first_id );
		$this->assert_ping_contract( $second_url, $second_id );

		$first_webhook  = wc_get_webhook( $first_id );
		$second_webhook = wc_get_webhook( $second_id );
		$this->assertInstanceOf( WC_Webhook::class, $first_webhook );
		$this->assertInstanceOf( WC_Webhook::class, $second_webhook );
		$this->assertSame( 'coupon created', $first_webhook->get_name() );
		$this->assertSame( 'customer deleted', $second_webhook->get_name() );

		$response = $this->do_rest_request(
			'webhooks/batch',
			'POST',
			array(
				'create' => array(
					array(
						'name'         => 'order created',
						'topic'        => 'order.created',
						'delivery_url' => $third_url,
					),
				),
				'update' => array(
					array(
						'id'     => $first_id,
						'name'   => 'paused coupon created',
						'status' => 'paused',
					),
				),
				'delete' => array( $second_id ),
			)
		);
		$data     = $response->get_data();
		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $data['create'] );
		$this->assertCount( 1, $data['update'] );
		$this->assertCount( 1, $data['delete'] );
		$third_id = $data['create'][0]['id'];
		$this->assertGreaterThan( 0, $third_id );
		$this->assertSame( 'order created', $data['create'][0]['name'] );
		$this->assertSame( 'active', $data['create'][0]['status'] );
		$this->assertSame( 'order.created', $data['create'][0]['topic'] );
		$this->assertSame( $third_url, $data['create'][0]['delivery_url'] );
		$this->assertSame( $first_id, $data['update'][0]['id'] );
		$this->assertSame( 'paused coupon created', $data['update'][0]['name'] );
		$this->assertSame( 'paused', $data['update'][0]['status'] );
		$this->assertSame( $second_id, $data['delete'][0]['id'] );
		$this->assertCount( 3, $this->captured_pings() );
		$this->assert_ping_contract( $third_url, $third_id );

		$first_webhook = wc_get_webhook( $first_id );
		$third_webhook = wc_get_webhook( $third_id );
		$this->assertInstanceOf( WC_Webhook::class, $first_webhook );
		$this->assertInstanceOf( WC_Webhook::class, $third_webhook );
		$this->assertSame( 'paused coupon created', $first_webhook->get_name() );
		$this->assertSame( 'paused', $first_webhook->get_status() );
		$this->assertSame( 'order created', $third_webhook->get_name() );
		$this->assertSame( 'active', $third_webhook->get_status() );
		$this->assertSame( 'order.created', $third_webhook->get_topic() );
		$this->assertSame( $third_url, $third_webhook->get_delivery_url() );
		$this->assertNull( wc_get_webhook( $second_id ) );
		$this->assertSame( 404, $this->do_rest_get_request( 'webhooks/' . $second_id )->get_status() );

		$response = $this->do_rest_request(
			'webhooks/batch',
			'POST',
			array( 'delete' => array( $first_id, $third_id ) )
		);
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( $first_id, $third_id ), wp_list_pluck( $response->get_data()['delete'], 'id' ) );
		$this->assertNull( wc_get_webhook( $first_id ) );
		$this->assertNull( wc_get_webhook( $third_id ) );
		$this->assertSame( 404, $this->do_rest_get_request( 'webhooks/' . $first_id )->get_status() );
		$this->assertSame( 404, $this->do_rest_get_request( 'webhooks/' . $third_id )->get_status() );
	}

	/**
	 * Answer a webhook ping with a 200 so no delivery leaves the test.
	 *
	 * @param array  $request Request arguments.
	 * @param string $url Request URL.
	 * @return array|false
	 */
	public function respond_to_ping( $request, $url ) {
		if ( 0 !== strpos( $url, self::PING_URL_PREFIX ) ) {
			return false;
		}

		return array(
			'headers'  => array(),
			'body'     => '',
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
		);
	}

	/**
	 * The webhook pings the inherited fixture captured.
	 *
	 * @return array
	 */
	private function captured_pings(): array {
		return array_values(
			array_filter(
				$this->http_requests,
				static function ( array $request ): bool {
					return 0 === strpos( $request['url'], self::PING_URL_PREFIX );
				}
			)
		);
	}

	/**
	 * Assert one exact webhook ping contract.
	 *
	 * @param string $url Expected delivery URL.
	 * @param int    $webhook_id Expected webhook ID.
	 */
	private function assert_ping_contract( string $url, int $webhook_id ): void {
		$matches = array_values(
			array_filter(
				$this->captured_pings(),
				static function ( array $ping ) use ( $url ): bool {
					return $url === $ping['url'];
				}
			)
		);

		$this->assertCount( 1, $matches );
		$this->assertSame( 'webhook_id=' . $webhook_id, $matches[0]['request']['body'] );
	}
}
