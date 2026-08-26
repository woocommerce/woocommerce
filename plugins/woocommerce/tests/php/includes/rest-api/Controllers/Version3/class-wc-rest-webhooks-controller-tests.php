<?php
declare( strict_types = 1 );

/**
 * Webhooks controller tests for the V3 REST API.
 */
class WC_REST_Webhooks_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * IDs of the webhooks created for each test, in creation order.
	 *
	 * @var int[]
	 */
	private $webhook_ids = array();

	/**
	 * Create an admin user and five webhooks to paginate over.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$this->webhook_ids = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$webhook = new WC_Webhook();
			$webhook->set_name( "Webhook {$i}" );
			$webhook->set_topic( 'order.created' );
			$webhook->set_delivery_url( "https://example.com/webhook-{$i}" );
			$webhook->save();
			$this->webhook_ids[] = $webhook->get_id();
		}
	}

	/**
	 * List webhooks with the given query parameters and return their IDs.
	 *
	 * @param array $params Query parameters for the request.
	 * @return int[]
	 */
	private function get_listed_ids( array $params ): array {
		$request = new WP_REST_Request( 'GET', '/wc/v3/webhooks' );
		$request->set_query_params(
			array_merge(
				array(
					'orderby' => 'id',
					'order'   => 'asc',
				),
				$params
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		return array_column( $response->get_data(), 'id' );
	}

	/**
	 * @testdox The offset parameter skips the given number of results.
	 */
	public function test_offset_offsets_the_result_set() {
		$this->assertSame(
			array_slice( $this->webhook_ids, 2, 2 ),
			$this->get_listed_ids(
				array(
					'per_page' => 2,
					'offset'   => 2,
				)
			)
		);
	}

	/**
	 * @testdox The offset and page parameters produce the same slice for equivalent values.
	 */
	public function test_offset_matches_equivalent_page() {
		$page_ids = $this->get_listed_ids(
			array(
				'per_page' => 2,
				'page'     => 2,
			)
		);

		$this->assertSame( array_slice( $this->webhook_ids, 2, 2 ), $page_ids );
		$this->assertSame(
			$page_ids,
			$this->get_listed_ids(
				array(
					'per_page' => 2,
					'offset'   => 2,
				)
			)
		);
	}

	/**
	 * @testdox An offset past the end of the result set returns no results.
	 */
	public function test_offset_past_the_end_returns_empty() {
		$this->assertSame(
			array(),
			$this->get_listed_ids(
				array(
					'per_page' => 2,
					'offset'   => count( $this->webhook_ids ),
				)
			)
		);
	}
}
