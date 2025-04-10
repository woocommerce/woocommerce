<?php
/**
 * Leaderboards REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

/**
 * WC Tests API Leaderboards
 */
class WC_Admin_Tests_API_Leaderboards extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/leaderboards';

	/**
	 * Setup test data. Called before every test.
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
	 * Test that leaderboards are returned by the endpoint.
	 */
	public function test_get_leaderboards() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'customers', $data[0]['id'] );
		$this->assertEquals( 'coupons', $data[1]['id'] );
		$this->assertEquals( 'categories', $data[2]['id'] );
		$this->assertEquals( 'products', $data[3]['id'] );
	}

	/**
	 * Test that the customers leaderboard are returned by the endpoint.
	 */
	public function test_get_leaderboards_customers() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/customers' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'customers', $data[0]['id'] );
	}

	/**
	 * Test that the coupons leaderboard are returned by the endpoint.
	 */
	public function test_get_leaderboards_coupons() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/coupons' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'coupons', $data[0]['id'] );
	}

	/**
	 * Test that the categories leaderboard are returned by the endpoint.
	 */
	public function test_get_leaderboards_categories() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/categories' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'categories', $data[0]['id'] );
	}

	/**
	 * Test that the products leaderboard are returned by the endpoint.
	 */
	public function test_get_leaderboards_products() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/products' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'products', $data[0]['id'] );
	}

	/**
	 * Test reports schema.
	 */
	public function test_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertCount( 4, $properties );
		$this->assert_item_schema( $properties );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint . '/allowed' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertCount( 3, $properties );
		$this->assert_allowed_item_schema( $properties );
	}

	/**
	 * Asserts the item schema is correct.
	 *
	 * @param array $schema Item to check schema.
	 */
	public function assert_item_schema( $schema ) {
		$this->assertArrayHasKey( 'id', $schema );
		$this->assertArrayHasKey( 'label', $schema );
		$this->assertArrayHasKey( 'headers', $schema );
		$this->assertArrayHasKey( 'rows', $schema );

		$header_properties = $schema['headers']['items']['properties'];
		$this->assertCount( 1, $header_properties );
		$this->assertArrayHasKey( 'label', $header_properties );

		$row_properties = $schema['rows']['items']['properties'];
		$this->assertCount( 3, $row_properties );
		$this->assertArrayHasKey( 'display', $row_properties );
		$this->assertArrayHasKey( 'value', $row_properties );
		$this->assertArrayHasKey( 'format', $row_properties );
	}

	/**
	 * Asserts the allowed item schema is correct.
	 *
	 * @param array $schema Item to check schema.
	 */
	public function assert_allowed_item_schema( $schema ) {
		$this->assertArrayHasKey( 'id', $schema );
		$this->assertArrayHasKey( 'label', $schema );
		$this->assertArrayHasKey( 'headers', $schema );

		$header_properties = $schema['headers']['items']['properties'];
		$this->assertCount( 1, $header_properties );
		$this->assertArrayHasKey( 'label', $header_properties );
	}

	/**
	 * Test that leaderboards response changes based on applied filters.
	 */
	public function test_filter_leaderboards() {
		wp_set_current_user( $this->user );

		add_filter(
			'woocommerce_leaderboards',
			function( $leaderboards, $per_page, $after, $before, $persisted_query ) {
				$leaderboards[] = array(
					'id'      => 'top_widgets',
					'label'   => 'Top Widgets',
					'headers' => array(
						array(
							'label' => 'Widget Link',
						),
					),
					'rows'    => array(
						array(
							'display' => wc_admin_url( '/test/path', $persisted_query ),
							'value'   => null,
						),
					),
				);
				return $leaderboards;
			},
			10,
			5
		);
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'persisted_query' => '{ "persisted_param": 1 }' ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$widgets_leaderboard = end( $data );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'top_widgets', $widgets_leaderboard['id'] );
		$this->assertEquals( admin_url( 'admin.php?page=wc-admin&path=/test/path&persisted_param=1' ), $widgets_leaderboard['rows'][0]['display'] );

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/allowed' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$widgets_leaderboard = end( $data );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'top_widgets', $widgets_leaderboard['id'] );
	}
}
