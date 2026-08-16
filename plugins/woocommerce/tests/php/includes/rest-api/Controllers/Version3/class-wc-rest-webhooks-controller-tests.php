<?php
declare( strict_types = 1 );

/**
 * Class WC_REST_Webhooks_Controller_Tests.
 */
class WC_REST_Webhooks_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * @testdox A webhook completes its registered V3 CRUD lifecycle and sends one create ping.
	 */
	public function test_webhook_crud_lifecycle(): void {
		$webhook_ids  = array();
		$pings        = array();
		$delivery_url = 'https://example.com/slice-45-crud-webhook';
		$interceptor  = $this->create_ping_interceptor( $pings );

		add_filter( 'pre_http_request', $interceptor, 10, 3 );

		try {
			wp_set_current_user( 1 );

			$response   = $this->do_rest_request(
				'webhooks',
				'POST',
				array(
					'name'         => 'Slice 45 order updates',
					'topic'        => 'order.updated',
					'delivery_url' => $delivery_url,
				)
			);
			$data       = $response->get_data();
			$webhook_id = isset( $data['id'] ) && is_numeric( $data['id'] ) ? (int) $data['id'] : 0;
			if ( $webhook_id > 0 ) {
				$webhook_ids[] = $webhook_id;
			}

			$this->assertSame( 201, $response->get_status() );
			$this->assertGreaterThan( 0, $webhook_id );
			$this->assertSame( rest_url( 'wc/v3/webhooks/' . $webhook_id ), $response->get_headers()['Location'] );
			$expected_webhook = array(
				'id'           => $webhook_id,
				'name'         => 'Slice 45 order updates',
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
			$this->assertSame( 'Slice 45 order updates', $fresh_webhook->get_name() );
			$this->assertSame( 'active', $fresh_webhook->get_status() );
			$this->assertSame( 'order.updated', $fresh_webhook->get_topic() );
			$this->assertSame( $delivery_url, $fresh_webhook->get_delivery_url() );
			$this->assert_ping_contract( $pings, $delivery_url, $webhook_id );

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
					'name'   => 'Slice 45 paused order updates',
					'status' => 'paused',
				)
			);
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( $webhook_id, $response->get_data()['id'] );
			$this->assertSame( 'Slice 45 paused order updates', $response->get_data()['name'] );
			$this->assertSame( 'paused', $response->get_data()['status'] );

			$fresh_webhook = wc_get_webhook( $webhook_id );
			$this->assertInstanceOf( WC_Webhook::class, $fresh_webhook );
			$this->assertSame( 'Slice 45 paused order updates', $fresh_webhook->get_name() );
			$this->assertSame( 'paused', $fresh_webhook->get_status() );

			$response = $this->do_rest_request( 'webhooks/' . $webhook_id, 'DELETE', array( 'force' => true ) );
			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( $webhook_id, $response->get_data()['id'] );
			$this->assertNull( wc_get_webhook( $webhook_id ) );
			$this->assertSame( 404, $this->do_rest_get_request( 'webhooks/' . $webhook_id )->get_status() );
		} finally {
			remove_filter( 'pre_http_request', $interceptor, 10 );
			$this->delete_webhooks( $webhook_ids );
		}
	}

	/**
	 * @testdox Webhooks complete registered V3 batch create, mixed, and delete lifecycles.
	 */
	public function test_webhook_batch_lifecycle(): void {
		$webhook_ids = array();
		$pings       = array();
		$first_url   = 'https://example.com/slice-45-batch-first';
		$second_url  = 'https://example.com/slice-45-batch-second';
		$third_url   = 'https://example.com/slice-45-batch-third';
		$interceptor = $this->create_ping_interceptor( $pings );

		add_filter( 'pre_http_request', $interceptor, 10, 3 );

		try {
			wp_set_current_user( 1 );

			$response = $this->do_rest_request(
				'webhooks/batch',
				'POST',
				array(
					'create' => array(
						array(
							'name'         => 'Slice 45 coupon created',
							'topic'        => 'coupon.created',
							'delivery_url' => $first_url,
						),
						array(
							'name'         => 'Slice 45 customer deleted',
							'topic'        => 'customer.deleted',
							'delivery_url' => $second_url,
						),
					),
				)
			);
			$data     = $response->get_data();
			foreach ( $data['create'] ?? array() as $created ) {
				if ( isset( $created['id'] ) && is_numeric( $created['id'] ) ) {
					$webhook_ids[] = (int) $created['id'];
				}
			}

			$this->assertSame( 200, $response->get_status() );
			$this->assertCount( 2, $data['create'] );
			$this->assertContainsOnly( 'int', wp_list_pluck( $data['create'], 'id' ) );
			$first_id  = $data['create'][0]['id'];
			$second_id = $data['create'][1]['id'];
			$this->assertGreaterThan( 0, $first_id );
			$this->assertGreaterThan( 0, $second_id );
			$this->assertNotSame( $first_id, $second_id );
			$this->assertSame( array( 'Slice 45 coupon created', 'Slice 45 customer deleted' ), wp_list_pluck( $data['create'], 'name' ) );
			$this->assertSame( array( 'coupon.created', 'customer.deleted' ), wp_list_pluck( $data['create'], 'topic' ) );
			$this->assertSame( array( $first_url, $second_url ), wp_list_pluck( $data['create'], 'delivery_url' ) );
			$this->assertCount( 2, $pings );
			$this->assert_ping_contract( $pings, $first_url, $first_id );
			$this->assert_ping_contract( $pings, $second_url, $second_id );

			$first_webhook  = wc_get_webhook( $first_id );
			$second_webhook = wc_get_webhook( $second_id );
			$this->assertInstanceOf( WC_Webhook::class, $first_webhook );
			$this->assertInstanceOf( WC_Webhook::class, $second_webhook );
			$this->assertSame( 'Slice 45 coupon created', $first_webhook->get_name() );
			$this->assertSame( 'Slice 45 customer deleted', $second_webhook->get_name() );

			$response = $this->do_rest_request(
				'webhooks/batch',
				'POST',
				array(
					'create' => array(
						array(
							'name'         => 'Slice 45 order created',
							'topic'        => 'order.created',
							'delivery_url' => $third_url,
						),
					),
					'update' => array(
						array(
							'id'     => $first_id,
							'name'   => 'Slice 45 paused coupon created',
							'status' => 'paused',
						),
					),
					'delete' => array( $second_id ),
				)
			);
			$data     = $response->get_data();
			foreach ( $data['create'] ?? array() as $created ) {
				if ( isset( $created['id'] ) && is_numeric( $created['id'] ) ) {
					$webhook_ids[] = (int) $created['id'];
				}
			}

			$this->assertSame( 200, $response->get_status() );
			$this->assertCount( 1, $data['create'] );
			$this->assertCount( 1, $data['update'] );
			$this->assertCount( 1, $data['delete'] );
			$third_id = $data['create'][0]['id'];
			$this->assertGreaterThan( 0, $third_id );
			$this->assertSame( 'Slice 45 order created', $data['create'][0]['name'] );
			$this->assertSame( 'active', $data['create'][0]['status'] );
			$this->assertSame( 'order.created', $data['create'][0]['topic'] );
			$this->assertSame( $third_url, $data['create'][0]['delivery_url'] );
			$this->assertSame( $first_id, $data['update'][0]['id'] );
			$this->assertSame( 'Slice 45 paused coupon created', $data['update'][0]['name'] );
			$this->assertSame( 'paused', $data['update'][0]['status'] );
			$this->assertSame( $second_id, $data['delete'][0]['id'] );
			$this->assertCount( 3, $pings );
			$this->assert_ping_contract( $pings, $third_url, $third_id );

			$first_webhook = wc_get_webhook( $first_id );
			$third_webhook = wc_get_webhook( $third_id );
			$this->assertInstanceOf( WC_Webhook::class, $first_webhook );
			$this->assertInstanceOf( WC_Webhook::class, $third_webhook );
			$this->assertSame( 'Slice 45 paused coupon created', $first_webhook->get_name() );
			$this->assertSame( 'paused', $first_webhook->get_status() );
			$this->assertSame( 'Slice 45 order created', $third_webhook->get_name() );
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
		} finally {
			remove_filter( 'pre_http_request', $interceptor, 10 );
			$this->delete_webhooks( $webhook_ids );
		}
	}

	/**
	 * Create an HTTP interceptor that captures only Slice 045 webhook pings.
	 *
	 * @param array $pings Captured ping requests.
	 * @return Closure
	 */
	private function create_ping_interceptor( array &$pings ): Closure {
		return static function ( $preempt, $args, $url ) use ( &$pings ) {
			if ( 0 !== strpos( $url, 'https://example.com/slice-45-' ) ) {
				return $preempt;
			}

			$pings[] = array(
				'url'  => $url,
				'args' => $args,
			);

			return array(
				'headers'  => array(),
				'body'     => '',
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
			);
		};
	}

	/**
	 * Assert one exact webhook ping contract.
	 *
	 * @param array  $pings Captured ping requests.
	 * @param string $url Expected delivery URL.
	 * @param int    $webhook_id Expected webhook ID.
	 */
	private function assert_ping_contract( array $pings, string $url, int $webhook_id ): void {
		$matches = array_values(
			array_filter(
				$pings,
				static function ( array $ping ) use ( $url ): bool {
					return $url === $ping['url'];
				}
			)
		);

		$this->assertCount( 1, $matches );
		$this->assertSame( 'webhook_id=' . $webhook_id, $matches[0]['args']['body'] );
		$this->assertSame(
			sprintf(
				'WooCommerce/%s Hookshot (WordPress/%s)',
				\Automattic\Jetpack\Constants::get_constant( 'WC_VERSION' ),
				$GLOBALS['wp_version']
			),
			$matches[0]['args']['user-agent']
		);
	}

	/**
	 * Delete any webhooks left by partial setup or failed assertions.
	 *
	 * @param int[] $webhook_ids Webhook IDs to delete.
	 */
	private function delete_webhooks( array $webhook_ids ): void {
		foreach ( array_reverse( array_unique( $webhook_ids ) ) as $webhook_id ) {
			$webhook = wc_get_webhook( $webhook_id );
			if ( $webhook instanceof WC_Webhook ) {
				$webhook->delete( true );
			}
		}
	}
}
