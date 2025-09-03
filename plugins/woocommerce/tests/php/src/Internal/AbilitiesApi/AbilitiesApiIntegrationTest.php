<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * Tests for the WordPress Abilities API integration with WooCommerce
 *
 * @since 10.4.0
 */
class AbilitiesApiIntegrationTest extends \WC_Unit_Test_Case {

	/**
	 * Array to track abilities registered during tests for cleanup.
	 *
	 * @var array
	 */
	private $registered_abilities = array();

	/**
	 * Set up the test environment.
	 */
	public function set_up() {
		parent::set_up();

		// Trigger the abilities API initialization if it hasn't been done yet.
		if ( did_action( 'abilities_api_init' ) === 0 ) {
			/**
			 * This action is documented in vendor/wordpress/abilities-api/bootstrap.php
			 *
			 * @since 11.0.0
			 */
			do_action( 'abilities_api_init' );
		}
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
	 * Test that we can register a simple ability.
	 *
	 * @group abilities-api
	 */
	public function test_can_register_ability() {
		$ability_id                   = 'woocommerce-test/simple-test';
		$this->registered_abilities[] = $ability_id;

		$result = wp_register_ability(
			$ability_id,
			array(
				'label'            => 'Simple Test Ability',
				'description'      => 'A simple test ability for unit testing',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'message' => array(
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
					return array(
						'result' => 'Test executed with: ' . ( $input['message'] ?? 'no message' ),
					);
				},
			)
		);

		$this->assertInstanceOf( 'WP_Ability', $result, 'Ability registration should return a WP_Ability instance' );
	}

	/**
	 * Test that we can retrieve a registered ability.
	 *
	 * @group abilities-api
	 */
	public function test_can_get_registered_ability() {
		$ability_id                   = 'woocommerce-test/get-test';
		$this->registered_abilities[] = $ability_id;

		// Register the ability first.
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

		// Register the ability.
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

		// Get and execute the ability.
		$ability = wp_get_ability( $ability_id );
		$this->assertNotNull( $ability );

		$result = $ability->execute( array( 'input_value' => 'test data' ) );

		$this->assertIsArray( $result, 'Execution should return an array' );
		$this->assertArrayHasKey( 'processed_value', $result, 'Result should have expected key' );
		$this->assertEquals( 'Processed: test data', $result['processed_value'], 'Result should have expected value' );
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

		// Register two test abilities.
		wp_register_ability(
			$ability_id_1,
			array(
				'label'            => 'List Test 1',
				'description'      => 'First test ability',
				'input_schema'     => array( 'type' => 'object' ),
				'output_schema'    => array( 'type' => 'object' ),
				'execute_callback' => function ( $input ) {
					return array( 'input' => $input ); },
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
					return array( 'input' => $input ); },
			)
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
