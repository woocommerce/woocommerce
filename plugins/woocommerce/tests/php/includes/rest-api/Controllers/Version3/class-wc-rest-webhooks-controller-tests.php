<?php

/**
 * class WC_REST_Webhooks_Controller_Tests.
 * Webhooks Controller tests for V3 REST API.
 */
class WC_REST_Webhooks_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * Runs before any test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * @testdox An explicit `offset` query param is honored, instead of being silently ignored in favor of always returning the first page.
	 */
	public function test_get_items_honors_explicit_offset() {
		wp_set_current_user( $this->user );

		$webhook_ids = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$webhook = new WC_Webhook();
			$webhook->set_name( 'Test webhook ' . $i );
			$webhook->set_topic( 'order.created' );
			$webhook->set_delivery_url( 'https://example.com/webhook' );
			$webhook->save();
			$webhook_ids[] = $webhook->get_id();
		}

		$query_params = array(
			'per_page' => 1,
			'orderby'  => 'id',
			'order'    => 'asc',
		);

		$response_no_offset = $this->do_rest_get_request( 'webhooks', $query_params );
		$this->assertSame( 200, $response_no_offset->get_status() );
		$this->assertSame( $webhook_ids[0], $response_no_offset->get_data()[0]['id'] );

		// An explicit offset of 1 should skip the first webhook and return the second one.
		$response_with_offset = $this->do_rest_get_request( 'webhooks', array_merge( $query_params, array( 'offset' => 1 ) ) );
		$this->assertSame( 200, $response_with_offset->get_status() );
		$this->assertSame( $webhook_ids[1], $response_with_offset->get_data()[0]['id'] );

		// A larger offset returns the third webhook, proving offset is not just being ignored in favor of the first page every time.
		$response_with_larger_offset = $this->do_rest_get_request( 'webhooks', array_merge( $query_params, array( 'offset' => 2 ) ) );
		$this->assertSame( 200, $response_with_larger_offset->get_status() );
		$this->assertSame( $webhook_ids[2], $response_with_larger_offset->get_data()[0]['id'] );
	}
}
