<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * Tests for the WordPress Abilities API integration with WooCommerce
 *
 * @since 10.4.0
 */
class AbilitiesApiIntegrationTest extends \WC_REST_Unit_Test_Case {

	/**
	 * Array to track abilities registered during tests for cleanup.
	 *
	 * @var array
	 */
	private $registered_abilities = array();

	/**
	 * Set up before each test
	 */
	public function set_up() {
		/*
		 * Explicitly ensure the abilities API bootstrap file is loaded for tests.
		 * The bootstrap file has an ABSPATH check that ensures it only loads in a proper
		 * WordPress context, which may require manual loading in test environments.
		 */
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$bootstrap_file = WP_PLUGIN_DIR . '/woocommerce/vendor/wordpress/abilities-api/includes/bootstrap.php';
			if ( file_exists( $bootstrap_file ) ) {
				require $bootstrap_file;
			}
		}

		// Ensure REST API routes are registered (hook may be cleared by parent tear_down).
		if ( class_exists( 'WP_REST_Abilities_Init' ) ) {
			add_action( 'rest_api_init', array( 'WP_REST_Abilities_Init', 'register_routes' ) );
		}

		parent::set_up();
	}

	/**
	 * Tear down the test environment.
	 */
	public function tear_down() {
		// Clean up any abilities registered during tests.
		foreach ( $this->registered_abilities as $ability_id ) {
			$this->cleanup_ability( $ability_id );
		}
		$this->registered_abilities = array();

		// Reset abilities registry singleton to allow fresh abilities_api_init in next test.
		if ( class_exists( 'WP_Abilities_Registry' ) ) {
			$reflection        = new \ReflectionClass( 'WP_Abilities_Registry' );
			$instance_property = $reflection->getProperty( 'instance' );
			$instance_property->setAccessible( true );
			$instance_property->setValue( null );
		}

		// Reset action counter to allow abilities_api_init to fire again.
		global $wp_actions;
		if ( isset( $wp_actions['abilities_api_init'] ) ) {
			unset( $wp_actions['abilities_api_init'] );
		}

		// Reset user.
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Test that core Abilities API classes are available.
	 *
	 * @group abilities-api
	 */
	public function test_core_classes_are_available() {
		$this->assertTrue(
			class_exists( 'WP_Ability' ),
			'WP_Ability class should be available'
		);
		$this->assertTrue(
			class_exists( 'WP_Abilities_Registry' ),
			'WP_Abilities_Registry class should be available'
		);
	}

	/**
	 * Test that global Abilities API functions are available.
	 *
	 * @group abilities-api
	 */
	public function test_global_functions_are_available() {
		$this->assertTrue(
			function_exists( 'wp_register_ability' ),
			'wp_register_ability() function should be available'
		);
		$this->assertTrue(
			function_exists( 'wp_get_ability' ),
			'wp_get_ability() function should be available'
		);
		$this->assertTrue(
			function_exists( 'wp_get_abilities' ),
			'wp_get_abilities() function should be available'
		);
		$this->assertTrue(
			function_exists( 'wp_unregister_ability' ),
			'wp_unregister_ability() function should be available'
		);
	}

	/**
	 * Test that we can retrieve a registered ability.
	 *
	 * @group abilities-api
	 */
	public function test_can_get_registered_ability() {
		$ability_id                   = 'woocommerce-test/get-test';
		$this->registered_abilities[] = $ability_id;

		// Hook ability registration to the init action.
		add_action(
			'abilities_api_init',
			function () use ( $ability_id ) {
				wp_register_ability(
					$ability_id,
					array(
						'label'            => 'Get Test Ability',
						'description'      => 'A test ability for testing retrieval',
						'input_schema'     => array( 'type' => 'object' ),
						'output_schema'    => array( 'type' => 'object' ),
						'execute_callback' => function ( $input ) {
							return array(
								'success' => true,
								'input'   => $input,
							);
						},
					)
				);
			}
		);

		// Test retrieval.
		$ability = wp_get_ability( $ability_id );

		$this->assertNotNull( $ability, 'Ability should be retrievable' );
		$this->assertInstanceOf( 'WP_Ability', $ability, 'Retrieved object should be a WP_Ability instance' );
		$this->assertEquals( $ability_id, $ability->get_name(), 'Ability name should match' );
		$this->assertEquals( 'Get Test Ability', $ability->get_label(), 'Ability label should match' );
	}

	/**
	 * Test that we can execute a registered ability.
	 *
	 * @group abilities-api
	 */
	public function test_can_execute_ability() {
		$ability_id                   = 'woocommerce-test/execute-test';
		$this->registered_abilities[] = $ability_id;

		// Hook ability registration to the init action.
		add_action(
			'abilities_api_init',
			function () use ( $ability_id ) {
				wp_register_ability(
					$ability_id,
					array(
						'label'            => 'Execute Test Ability',
						'description'      => 'A test ability for testing execution',
						'input_schema'     => array(
							'type'       => 'object',
							'properties' => array(
								'input_value' => array(
									'type' => 'string',
								),
							),
						),
						'output_schema'    => array(
							'type'       => 'object',
							'properties' => array(
								'processed_value' => array(
									'type' => 'string',
								),
							),
						),
						'execute_callback' => function ( $input ) {
							return array(
								'processed_value' => 'Processed: ' . ( $input['input_value'] ?? 'empty' ),
							);
						},
					)
				);
			}
		);

		// Get and execute the ability.
		$ability = wp_get_ability( $ability_id );
		$this->assertNotNull( $ability );

		$result = $ability->execute( array( 'input_value' => 'test data' ) );

		$this->assertIsArray( $result, 'Execution should return an array' );
		$this->assertArrayHasKey( 'processed_value', $result, 'Result should have expected key' );
		$this->assertEquals( 'Processed: test data', $result['processed_value'], 'Result should have expected value' );
	}

	/**
	 * Test that REST API endpoints are registered.
	 *
	 * @group abilities-api
	 */
	public function test_rest_endpoints_are_registered() {
		// Ensure the bootstrap file has been loaded by checking for the class.
		$this->assertTrue( class_exists( 'WP_REST_Abilities_Init' ), 'Bootstrap should load WP_REST_Abilities_Init class' );

		$routes = $this->server->get_routes();

		// Check for abilities list endpoint.
		$this->assertArrayHasKey( '/wp/v2/abilities', $routes, 'Abilities list endpoint should be registered' );

		// Check for ability run endpoint - look for the exact pattern from the controller.
		$run_endpoint = '/wp/v2/abilities/(?P<name>[a-zA-Z0-9\\-\\/]+?)/run';
		$this->assertArrayHasKey( $run_endpoint, $routes, 'Ability run endpoint should be registered' );
	}

	/**
	 * Test fetching abilities via REST API.
	 *
	 * @group abilities-api
	 */
	public function test_rest_fetch_abilities() {
		$ability_id_1                 = 'woocommerce-test/rest-fetch-1';
		$ability_id_2                 = 'woocommerce-test/rest-fetch-2';
		$this->registered_abilities[] = $ability_id_1;
		$this->registered_abilities[] = $ability_id_2;

		// Hook ability registration to the init action.
		add_action(
			'abilities_api_init',
			function () use ( $ability_id_1, $ability_id_2 ) {
				wp_register_ability(
					$ability_id_1,
					array(
						'label'            => 'REST Fetch Test 1',
						'description'      => 'First ability for REST API testing',
						'input_schema'     => array( 'type' => 'object' ),
						'output_schema'    => array( 'type' => 'object' ),
						'execute_callback' => function ( $input ) {
							return array( 'input' => $input );
						},
					)
				);

				wp_register_ability(
					$ability_id_2,
					array(
						'label'            => 'REST Fetch Test 2',
						'description'      => 'Second ability for REST API testing',
						'input_schema'     => array( 'type' => 'object' ),
						'output_schema'    => array( 'type' => 'object' ),
						'execute_callback' => function ( $input ) {
							return array( 'input' => $input );
						},
					)
				);
			}
		);

		// Create REST request.
		$request = new \WP_REST_Request( 'GET', '/wp/v2/abilities' );
		// Set up authentication for admin user.
		wp_set_current_user( 1 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'REST API should return 200 status' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be an array' );

		// Find our test abilities in the response.
		$found_abilities = array();
		foreach ( $data as $ability_data ) {
			if ( isset( $ability_data['name'] ) && in_array( $ability_data['name'], array( $ability_id_1, $ability_id_2 ), true ) ) {
				$found_abilities[] = $ability_data['name'];

				// Verify ability structure.
				$this->assertArrayHasKey( 'label', $ability_data, 'Ability should have label' );
				$this->assertArrayHasKey( 'description', $ability_data, 'Ability should have description' );
			}
		}

		$this->assertCount( 2, $found_abilities, 'Should find both test abilities in REST response' );
		$this->assertContains( $ability_id_1, $found_abilities, 'First test ability should be in REST response' );
		$this->assertContains( $ability_id_2, $found_abilities, 'Second test ability should be in REST response' );
	}

	/**
	 * Test executing abilities via REST API.
	 *
	 * @group abilities-api
	 */
	public function test_rest_execute_ability() {
		$ability_id                   = 'woocommerce-test/rest-execute-test';
		$this->registered_abilities[] = $ability_id;

		// Hook ability registration to the init action.
		add_action(
			'abilities_api_init',
			function () use ( $ability_id ) {
				wp_register_ability(
					$ability_id,
					array(
						'label'            => 'REST Execute Test',
						'description'      => 'Test ability for REST API execution',
						'input_schema'     => array(
							'type'       => 'object',
							'properties' => array(
								'test_value' => array(
									'type' => 'string',
								),
							),
						),
						'output_schema'    => array(
							'type'       => 'object',
							'properties' => array(
								'result' => array(
									'type' => 'string',
								),
							),
						),
						'execute_callback' => function ( $input ) {
							$test_value = isset( $input['test_value'] ) ? $input['test_value'] : 'default';
							return array(
								'result'     => 'Executed with: ' . $test_value,
								'input_echo' => $input,
							);
						},
					)
				);
			}
		);

		// Create REST request for execution.
		$request = new \WP_REST_Request( 'POST', '/wp/v2/abilities/' . $ability_id . '/run' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'input' => array(
						'test_value' => 'REST API test data',
					),
				)
			)
		);
		// Set up authentication for admin user.
		wp_set_current_user( 1 );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'REST API execution should return 200 status' );

		$data = $response->get_data();
		$this->assertIsArray( $data, 'Response should be an array' );
		$this->assertArrayHasKey( 'result', $data, 'Response should contain result' );
		$this->assertEquals( 'Executed with: REST API test data', $data['result'], 'Result should match expected value' );
		$this->assertArrayHasKey( 'input_echo', $data, 'Response should echo input' );
		$this->assertEquals( 'REST API test data', $data['input_echo']['test_value'], 'Input should be echoed correctly' );
	}

	/**
	 * Test that we can list registered abilities.
	 *
	 * @group abilities-api
	 */
	public function test_can_list_abilities() {
		$ability_id_1                 = 'woocommerce-test/list-test-1';
		$ability_id_2                 = 'woocommerce-test/list-test-2';
		$this->registered_abilities[] = $ability_id_1;
		$this->registered_abilities[] = $ability_id_2;

		// Hook ability registration to the init action.
		add_action(
			'abilities_api_init',
			function () use ( $ability_id_1, $ability_id_2 ) {
				wp_register_ability(
					$ability_id_1,
					array(
						'label'            => 'List Test 1',
						'description'      => 'First test ability',
						'input_schema'     => array( 'type' => 'object' ),
						'output_schema'    => array( 'type' => 'object' ),
						'execute_callback' => function ( $input ) {
							return array( 'input' => $input );
						},
					)
				);

				wp_register_ability(
					$ability_id_2,
					array(
						'label'            => 'List Test 2',
						'description'      => 'Second test ability',
						'input_schema'     => array( 'type' => 'object' ),
						'output_schema'    => array( 'type' => 'object' ),
						'execute_callback' => function ( $input ) {
							return array( 'input' => $input );
						},
					)
				);
			}
		);

		// Get all abilities.
		$abilities = wp_get_abilities();

		$this->assertIsArray( $abilities, 'wp_get_abilities should return an array' );

		// Find our test abilities in the list.
		$found_abilities = array();
		foreach ( $abilities as $ability ) {
			if ( in_array( $ability->get_name(), array( $ability_id_1, $ability_id_2 ), true ) ) {
				$found_abilities[] = $ability->get_name();
			}
		}

		$this->assertContains( $ability_id_1, $found_abilities, 'First test ability should be in the list' );
		$this->assertContains( $ability_id_2, $found_abilities, 'Second test ability should be in the list' );
	}

	/**
	 * Helper method to clean up abilities after testing.
	 *
	 * @param string $ability_id The ability ID to clean up.
	 */
	private function cleanup_ability( $ability_id ) {
		if ( function_exists( 'wp_unregister_ability' ) ) {
			wp_unregister_ability( $ability_id );
		}
	}
}
