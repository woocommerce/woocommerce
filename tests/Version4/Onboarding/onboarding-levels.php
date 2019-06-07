<?php
/**
 * Onboarding Levels REST API Test
 *
 * @package WooCommerce Admin\Tests\API
 */

/**
 * WC Tests API Onboarding Levels
 */
class WC_Tests_API_Onboarding_Levels extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/v1/onboarding/levels';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test that levels are returned by the endpoint.
	 */
	public function test_get_level_items() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'account', $data[0]['id'] );
	}

	/**
	 * Test reports schema.
	 *
	 * @since 3.5.0
	 */
	public function test_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertCount( 2, $properties );
		$this->assert_item_schema( $properties );
	}

	/**
	 * Asserts the item schema is correct.
	 *
	 * @param array $schema Item to check schema.
	 */
	public function assert_item_schema( $schema ) {
		$this->assertArrayHasKey( 'id', $schema );
		$this->assertArrayHasKey( 'tasks', $schema );

		$task_properties = $schema['tasks']['items']['properties'];
		$this->assertCount( 5, $task_properties );
		$this->assertArrayHasKey( 'id', $task_properties );
		$this->assertArrayHasKey( 'label', $task_properties );
		$this->assertArrayHasKey( 'description', $task_properties );
		$this->assertArrayHasKey( 'illustration', $task_properties );
		$this->assertArrayHasKey( 'status', $task_properties );
	}

	/**
	 * Test that levels response changes based on applied filters.
	 */
	public function test_filter_levels() {
		wp_set_current_user( $this->user );

		add_filter(
			'woocommerce_onboarding_levels',
			function( $levels ) {
				$levels['test_level'] = array(
					'id'    => 'test_level',
					'tasks' => array(),
				);
				return $levels;
			}
		);

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'test_level', end( $data )['id'] );

	}
}
