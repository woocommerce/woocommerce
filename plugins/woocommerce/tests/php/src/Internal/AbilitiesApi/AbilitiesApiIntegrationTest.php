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
	 * Action name for abilities API initialization.
	 *
	 * @var string
	 */
	private $abilities_init_action;

	/**
	 * Action name for abilities API categories initialization.
	 *
	 * @var string
	 */
	private $abilities_categories_init_action;

	/**
	 * Category registry class name.
	 *
	 * @var string
	 */
	private $category_registry_class;

	/**
	 * Original value of $wp_actions['init'] to restore in tear_down.
	 *
	 * @var int|null
	 */
	private $original_init_action_count;

	/**
	 * Check if Abilities API is in WordPress core.
	 *
	 * WordPress 6.9+ has the Abilities API in core with different class names than the vendor package.
	 * We detect this by checking for the core class name (plural "Categories" instead of singular "Category").
	 *
	 * @return bool True if Abilities API is in core, false if using vendor package.
	 */
	private function are_abilities_in_wp_core(): bool {
		return class_exists( 'WP_Ability_Categories_Registry' );
	}

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		global $wp_actions;

		$this->abilities_init_action            = 'wp_abilities_api_init';
		$this->abilities_categories_init_action = 'wp_abilities_api_categories_init';
		$this->category_registry_class          = 'WP_Ability_Categories_Registry';

		/*
		 * Explicitly ensure the abilities API bootstrap file is loaded for tests.
		 * WordPress 6.9+ has abilities API in core, so we don't need to load the vendor version.
		 */
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$bootstrap_file = WP_PLUGIN_DIR . '/woocommerce/vendor/wordpress/abilities-api/includes/bootstrap.php';
			if ( file_exists( $bootstrap_file ) ) {
				require $bootstrap_file;
			}
		}

		// WordPress 6.9+ requires did_action('init') to return truthy before abilities API can be used.
		// Save the original value and set to 1. Doesn't hurt pre-6.9 versions.
		$this->original_init_action_count = $wp_actions['init'] ?? null;
		$wp_actions['init']               = 1; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::setUp();

		// Ensure Abilities API REST routes are registered if needed.
		if ( function_exists( 'wp_register_ability' ) && class_exists( 'WP_REST_Abilities_Init' ) ) {
			$routes = $this->server->get_routes();

			// Check if any Abilities API routes are already registered.
			$has_abilities_routes = false;
			foreach ( $routes as $route => $handlers ) {
				if ( strpos( $route, '/wp-abilities/v1/' ) === 0 ) {
					$has_abilities_routes = true;
					break;
				}
			}

			if ( ! $has_abilities_routes ) {
				\WP_REST_Abilities_Init::register_routes( $this->server );
			}
		}
	}

	/**
	 * Tear down the test environment.
	 */
	public function tear_down() {
		global $wp_actions;

		// Clean up any abilities registered during tests.
		foreach ( $this->registered_abilities as $ability_id ) {
			$this->cleanup_ability( $ability_id );
		}
		$this->registered_abilities = array();

		// Clean up test category.
		$this->cleanup_category( 'test' );

		// Reset abilities registry singleton to allow fresh abilities_api_init in next test.
		if ( class_exists( 'WP_Abilities_Registry' ) ) {
			$reflection        = new \ReflectionClass( 'WP_Abilities_Registry' );
			$instance_property = $reflection->getProperty( 'instance' );
			$instance_property->setAccessible( true );
			$instance_property->setValue( null );
		}

		// Reset category registry singleton to allow fresh category registration in next test.
		if ( class_exists( $this->category_registry_class ) ) {
			$reflection        = new \ReflectionClass( $this->category_registry_class );
			$instance_property = $reflection->getProperty( 'instance' );
			$instance_property->setAccessible( true );
			$instance_property->setValue( null );
		}

		// Reset action counters to allow init actions to fire again.
		if ( isset( $wp_actions[ $this->abilities_init_action ] ) ) {
			unset( $wp_actions[ $this->abilities_init_action ] );
		}
		if ( isset( $wp_actions[ $this->abilities_categories_init_action ] ) ) {
			unset( $wp_actions[ $this->abilities_categories_init_action ] );
		}

		// Reset user.
		wp_set_current_user( 0 );

		parent::tear_down();

		// Restore 'init' action counter to its original value.
		if ( null !== $this->original_init_action_count ) {
			$wp_actions['init'] = $this->original_init_action_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} elseif ( isset( $wp_actions['init'] ) ) {
			unset( $wp_actions['init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
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
	 * Test that permission_callback is required for ability registration.
	 *
	 * @group abilities-api
	 */
	public function test_permission_callback_is_required() {
		$ability_id                   = 'woocommerce-test/missing-permission';
		$this->registered_abilities[] = $ability_id;

		// Expect incorrect usage notices when permission_callback is missing.
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::register' );
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		// Register test category for abilities used in tests.
		add_action(
			$this->abilities_categories_init_action,
			function () {
				wp_register_ability_category(
					'test',
					array(
						'label'       => 'Test',
						'description' => 'Test abilities for unit tests',
					)
				);
			}
		);

		// Hook ability registration without permission_callback.
		add_action(
			$this->abilities_init_action,
			function () use ( $ability_id ) {
				wp_register_ability(
					$ability_id,
					array(
						'label'            => 'Test Missing Permission',
						'description'      => 'Ability without permission_callback',
						'input_schema'     => array( 'type' => 'object' ),
						'output_schema'    => array( 'type' => 'object' ),
						'category'         => 'test',
						'execute_callback' => function () {
							return array( 'success' => true );
						},
						// Note: permission_callback intentionally omitted.
					)
				);
			}
		);

		// Attempt to retrieve the ability - should fail or return null.
		$ability = wp_get_ability( $ability_id );

		// In trunk, abilities without permission_callback should not be registered.
		$this->assertNull(
			$ability,
			'Ability without permission_callback should not be registered.'
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

		// Register test category for abilities used in tests.
		add_action(
			$this->abilities_categories_init_action,
			function () {
				wp_register_ability_category(
					'test',
					array(
						'label'       => 'Test',
						'description' => 'Test abilities for unit tests',
					)
				);
			}
		);

		// Hook ability registration to the init action.
		add_action(
			$this->abilities_init_action,
			function () use ( $ability_id ) {
				wp_register_ability(
					$ability_id,
					array(
						'label'               => 'Get Test Ability',
						'description'         => 'A test ability for testing retrieval',
						'category'            => 'test',
						'input_schema'        => array( 'type' => 'object' ),
						'output_schema'       => array( 'type' => 'object' ),
						'execute_callback'    => function ( $input ) {
							return array(
								'success' => true,
								'input'   => $input,
							);
						},
						'permission_callback' => function () {
							return true;
						},
						'meta'                => array(),
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

		// Register test category for abilities used in tests.
		add_action(
			$this->abilities_categories_init_action,
			function () {
				wp_register_ability_category(
					'test',
					array(
						'label'       => 'Test',
						'description' => 'Test abilities for unit tests',
					)
				);
			}
		);

		// Hook ability registration to the init action.
		add_action(
			$this->abilities_init_action,
			function () use ( $ability_id ) {
				wp_register_ability(
					$ability_id,
					array(
						'label'               => 'Execute Test Ability',
						'description'         => 'A test ability for testing execution',
						'category'            => 'test',
						'input_schema'        => array(
							'type'       => 'object',
							'properties' => array(
								'input_value' => array(
									'type' => 'string',
								),
							),
						),
						'output_schema'       => array(
							'type'       => 'object',
							'properties' => array(
								'processed_value' => array(
									'type' => 'string',
								),
							),
						),
						'execute_callback'    => function ( $input ) {
							return array(
								'processed_value' => 'Processed: ' . ( $input['input_value'] ?? 'empty' ),
							);
						},
						'permission_callback' => function () {
							return true;
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
		// WordPress 6.9+ uses core controllers with different namespace, earlier versions use vendor package.
		$are_abilities_in_wp_core = $this->are_abilities_in_wp_core();
		if ( $are_abilities_in_wp_core ) {
			$this->assertTrue( class_exists( 'WP_REST_Abilities_V1_List_Controller' ), 'WordPress 6.9+ should have WP_REST_Abilities_V1_List_Controller class' );
		} else {
			$this->assertTrue( class_exists( '\WP_REST_Abilities_Init' ), 'Bootstrap should load WP_REST_Abilities_Init class' );
		}

		$list_endpoint = '/wp-abilities/v1/abilities';
		$run_endpoint  = '/wp-abilities/v1/abilities/(?P<name>[a-zA-Z0-9\\-\\/]+?)/run';

		$routes = $this->server->get_routes();

		// Check for abilities list endpoint.
		$this->assertArrayHasKey( $list_endpoint, $routes, 'Abilities list endpoint should be registered' );

		// Check for ability run endpoint - look for the exact pattern from the controller.
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

		// Register test category for abilities used in tests.
		add_action(
			$this->abilities_categories_init_action,
			function () {
				wp_register_ability_category(
					'test',
					array(
						'label'       => 'Test',
						'description' => 'Test abilities for unit tests',
					)
				);
			}
		);

		// Hook ability registration to the init action.
		add_action(
			$this->abilities_init_action,
			function () use ( $ability_id_1, $ability_id_2 ) {
				wp_register_ability(
					$ability_id_1,
					array(
						'label'               => 'REST Fetch Test 1',
						'description'         => 'First ability for REST API testing',
						'category'            => 'test',
						'input_schema'        => array( 'type' => 'object' ),
						'output_schema'       => array( 'type' => 'object' ),
						'execute_callback'    => function ( $input ) {
							return array( 'input' => $input );
						},
						'permission_callback' => function () {
							return true;
						},
						'meta'                => array(
							'show_in_rest' => true,
						),
					)
				);

				wp_register_ability(
					$ability_id_2,
					array(
						'label'               => 'REST Fetch Test 2',
						'description'         => 'Second ability for REST API testing',
						'category'            => 'test',
						'input_schema'        => array( 'type' => 'object' ),
						'output_schema'       => array( 'type' => 'object' ),
						'execute_callback'    => function ( $input ) {
							return array( 'input' => $input );
						},
						'permission_callback' => function () {
							return true;
						},
						'meta'                => array(
							'show_in_rest' => true,
						),
					)
				);
			}
		);

		// Create REST request.
		$list_endpoint = '/wp-abilities/v1/abilities';
		$request       = new \WP_REST_Request( 'GET', $list_endpoint );
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

		// Register test category for abilities used in tests.
		add_action(
			$this->abilities_categories_init_action,
			function () {
				wp_register_ability_category(
					'test',
					array(
						'label'       => 'Test',
						'description' => 'Test abilities for unit tests',
					)
				);
			}
		);

		// Hook ability registration to the init action.
		add_action(
			$this->abilities_init_action,
			function () use ( $ability_id ) {
				wp_register_ability(
					$ability_id,
					array(
						'label'               => 'REST Execute Test',
						'description'         => 'Test ability for REST API execution',
						'category'            => 'test',
						'input_schema'        => array(
							'type'       => 'object',
							'properties' => array(
								'test_value' => array(
									'type' => 'string',
								),
							),
						),
						'output_schema'       => array(
							'type'       => 'object',
							'properties' => array(
								'result' => array(
									'type' => 'string',
								),
							),
						),
						'execute_callback'    => function ( $input ) {
							$test_value = isset( $input['test_value'] ) ? $input['test_value'] : 'default';
							return array(
								'result'     => 'Executed with: ' . $test_value,
								'input_echo' => $input,
							);
						},
						'permission_callback' => function () {
							return true;
						},
						'meta'                => array(
							'show_in_rest' => true,
						),
					)
				);
			}
		);

		// Create REST request for execution.
		$namespace = '/wp-abilities/v1';
		$request   = new \WP_REST_Request( 'POST', $namespace . '/abilities/' . $ability_id . '/run' );
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

		// Register test category for abilities used in tests.
		add_action(
			$this->abilities_categories_init_action,
			function () {
				wp_register_ability_category(
					'test',
					array(
						'label'       => 'Test',
						'description' => 'Test abilities for unit tests',
					)
				);
			}
		);

		// Hook ability registration to the init action.
		add_action(
			$this->abilities_init_action,
			function () use ( $ability_id_1, $ability_id_2 ) {
				wp_register_ability(
					$ability_id_1,
					array(
						'label'               => 'List Test 1',
						'description'         => 'First test ability',
						'category'            => 'test',
						'input_schema'        => array( 'type' => 'object' ),
						'output_schema'       => array( 'type' => 'object' ),
						'execute_callback'    => function ( $input ) {
							return array( 'input' => $input );
						},
						'permission_callback' => function () {
							return true;
						},
						'meta'                => array(
							'show_in_rest' => true,
						),
					)
				);

				wp_register_ability(
					$ability_id_2,
					array(
						'label'               => 'List Test 2',
						'description'         => 'Second test ability',
						'category'            => 'test',
						'input_schema'        => array( 'type' => 'object' ),
						'output_schema'       => array( 'type' => 'object' ),
						'execute_callback'    => function ( $input ) {
							return array( 'input' => $input );
						},
						'permission_callback' => function () {
							return true;
						},
						'meta'                => array(
							'show_in_rest' => true,
						),
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

	/**
	 * Helper method to clean up categories after testing.
	 *
	 * @param string $category_id The category ID to clean up.
	 */
	private function cleanup_category( $category_id ) {
		if ( function_exists( 'wp_unregister_ability_category' ) ) {
			wp_unregister_ability_category( $category_id );
		}
	}
}
