<?php
/**
 * MCPAdapterProviderTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\MCP;

use Automattic\WooCommerce\Internal\MCP\MCPAdapterProvider;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Tests for the MCPAdapterProvider class.
 */
class MCPAdapterProviderTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var MCPAdapterProvider
	 */
	private $sut;

	/**
	 * Mock abilities registry.
	 *
	 * @var AbilitiesRegistry|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_abilities_registry;

	/**
	 * Original abilities registry instance.
	 *
	 * @var AbilitiesRegistry
	 */
	private $original_abilities_registry;

	/**
	 * Ability IDs registered by these tests.
	 *
	 * @var array
	 */
	private $registered_ability_ids = array();

	/**
	 * Ability category IDs registered by these tests.
	 *
	 * @var array
	 */
	private $registered_ability_category_ids = array();

	/**
	 * Original value of $wp_actions['wp_abilities_api_init'] to restore in tearDown.
	 *
	 * @var int|null
	 */
	private $original_wp_abilities_api_init_action_count;

	/**
	 * Original value of $wp_actions['wp_abilities_api_categories_init'] to restore in tearDown.
	 *
	 * @var int|null
	 */
	private $original_wp_abilities_api_categories_init_action_count;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		global $wp_actions;

		parent::setUp();

		$this->original_wp_abilities_api_init_action_count            = $wp_actions['wp_abilities_api_init'] ?? null;
		$this->original_wp_abilities_api_categories_init_action_count = $wp_actions['wp_abilities_api_categories_init'] ?? null;

		// Bootstrap the WordPress Abilities API for tests.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			$abilities_bootstrap = WP_PLUGIN_DIR . '/woocommerce/vendor/wordpress/abilities-api/includes/bootstrap.php';
			if ( file_exists( $abilities_bootstrap ) ) {
				require_once $abilities_bootstrap;
			}
		}

		// Bootstrap the MCP Adapter for tests.
		if ( ! class_exists( 'WP\\MCP\\Core\\McpAdapter' ) ) {
			$mcp_bootstrap = WP_PLUGIN_DIR . '/woocommerce/vendor/wordpress/mcp-adapter/includes/Autoloader.php';
			if ( file_exists( $mcp_bootstrap ) ) {
				require_once $mcp_bootstrap;
				// Initialize the autoloader.
				if ( class_exists( 'WP\\MCP\\Autoloader' ) ) {
					\WP\MCP\Autoloader::autoload();
				}
			}
		}

		// Create mock abilities registry.
		$this->mock_abilities_registry = $this->createMock( AbilitiesRegistry::class );

		// Capture original abilities registry before replacing.
		$container                         = wc_get_container();
		$this->original_abilities_registry = $container->get( AbilitiesRegistry::class );

		// Replace in container for testing.
		$container->replace( AbilitiesRegistry::class, $this->mock_abilities_registry );

		$this->sut = new MCPAdapterProvider();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		global $wp_actions;

		// Restore original abilities registry if it was captured.
		if ( $this->original_abilities_registry ) {
			$container = wc_get_container();
			$container->replace( AbilitiesRegistry::class, $this->original_abilities_registry );
			$this->original_abilities_registry = null;
		}

		// Reset any filters that might have been added.
		remove_all_filters( 'woocommerce_mcp_include_ability' );
		remove_all_filters( 'woocommerce_mcp_allow_insecure_transport' );
		remove_all_filters( 'mcp_validation_enabled' );

		foreach ( $this->registered_ability_ids as $ability_id ) {
			if ( function_exists( 'wp_unregister_ability' ) ) {
				wp_unregister_ability( $ability_id );
			}
		}
		$this->registered_ability_ids = array();

		foreach ( $this->registered_ability_category_ids as $category_id ) {
			if ( function_exists( 'wp_unregister_ability_category' ) ) {
				wp_unregister_ability_category( $category_id );
			}
		}
		$this->registered_ability_category_ids = array();

		// Remove actions registered by the system under test.
		remove_action( 'rest_api_init', array( $this->sut, 'maybe_initialize' ), 10 );
		remove_action( 'mcp_adapter_init', array( $this->sut, 'initialize_mcp_server' ), 10 );

		// Clean up feature flag options.
		delete_option( 'woocommerce_feature_mcp_integration_enabled' );

		if ( null !== $this->original_wp_abilities_api_init_action_count ) {
			$wp_actions['wp_abilities_api_init'] = $this->original_wp_abilities_api_init_action_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} elseif ( isset( $wp_actions['wp_abilities_api_init'] ) ) {
			unset( $wp_actions['wp_abilities_api_init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( null !== $this->original_wp_abilities_api_categories_init_action_count ) {
			$wp_actions['wp_abilities_api_categories_init'] = $this->original_wp_abilities_api_categories_init_action_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} elseif ( isset( $wp_actions['wp_abilities_api_categories_init'] ) ) {
			unset( $wp_actions['wp_abilities_api_categories_init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		parent::tearDown();
	}

	/**
	 * Test that maybe_initialize respects feature flag when disabled.
	 */
	public function test_maybe_initialize_respects_feature_flag_disabled() {
		// Ensure MCP feature is disabled via option.
		update_option( 'woocommerce_feature_mcp_integration_enabled', 'no' );

		$this->sut->maybe_initialize();

		$this->assertFalse( $this->sut->is_initialized(), 'Should not initialize when feature flag is disabled' );
	}

	/**
	 * Test that maybe_initialize respects feature flag when enabled.
	 */
	public function test_maybe_initialize_respects_feature_flag_enabled() {

		// Enable MCP feature via option.
		update_option( 'woocommerce_feature_mcp_integration_enabled', 'yes' );

		$this->sut->maybe_initialize();

		$this->assertTrue( $this->sut->is_initialized(), 'Should initialize when feature flag is enabled' );
	}

	/**
	 * Test that double initialization is prevented.
	 */
	public function test_prevents_double_initialization() {
		// Enable MCP feature via option.
		update_option( 'woocommerce_feature_mcp_integration_enabled', 'yes' );

		$this->sut->maybe_initialize();
		$first_initialized = $this->sut->is_initialized();

		// Try to initialize again.
		$this->sut->maybe_initialize();
		$second_initialized = $this->sut->is_initialized();

		$this->assertEquals( $first_initialized, $second_initialized, 'Should prevent double initialization' );
	}

	/**
	 * Test ability filtering by namespace.
	 */
	public function test_get_woocommerce_mcp_abilities_filters_by_namespace() {
		// Mock abilities registry to return test abilities.
		$this->mock_abilities_registry
			->method( 'get_abilities_ids' )
			->willReturn(
				array(
					'woocommerce/products-list',
					'woocommerce/orders-get',
					'other-plugin/custom-action',
					'another/namespace/action',
				)
			);

		// Use reflection to test the private method.
		$reflection = new \ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'get_woocommerce_mcp_abilities' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->sut );

		$expected = array(
			'woocommerce/products-list',
			'woocommerce/orders-get',
		);

		$this->assertEquals( $expected, $result, 'Should only return woocommerce namespaced abilities' );
	}

	/**
	 * Test ability filtering with custom filter.
	 */
	public function test_get_woocommerce_mcp_abilities_respects_custom_filter() {
		// Mock abilities registry to return test abilities.
		$this->mock_abilities_registry
			->method( 'get_abilities_ids' )
			->willReturn(
				array(
					'woocommerce/products-list',
					'custom-plugin/special-action',
					'other-plugin/normal-action',
				)
			);

		// Add custom filter to include abilities from custom-plugin namespace.
		add_filter(
			'woocommerce_mcp_include_ability',
			function ( $should_include, $ability_id ) {
				if ( str_starts_with( $ability_id, 'custom-plugin/' ) ) {
					return true;
				}
				return $should_include;
			},
			10,
			2
		);

		// Use reflection to test the private method.
		$reflection = new \ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'get_woocommerce_mcp_abilities' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->sut );

		$expected = array(
			'woocommerce/products-list',
			'custom-plugin/special-action',
		);

		$this->assertEquals( $expected, $result, 'Should respect custom filter for including abilities' );
	}

	/**
	 * Test ability exposure metadata controls the default MCP surface.
	 */
	public function test_get_woocommerce_mcp_abilities_respects_explicit_exposure_metadata() {
		$legacy_ability   = 'woocommerce/test-legacy-rest';
		$semantic_ability = 'woocommerce/test-semantic';

		$this->register_test_ability(
			$legacy_ability,
			array(
				'woocommerce_mcp_expose'     => true,
				'woocommerce_mcp_projection' => 'legacy-rest',
			)
		);
		$this->register_test_ability(
			$semantic_ability,
			array(
				'woocommerce_mcp_expose'     => false,
				'woocommerce_mcp_projection' => 'semantic-flat-experimental',
			)
		);

		$this->mock_abilities_registry
			->method( 'get_abilities_ids' )
			->willReturn(
				array(
					$legacy_ability,
					$semantic_ability,
				)
			);

		$reflection = new \ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'get_woocommerce_mcp_abilities' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->sut );

		$this->assertEquals( array( $legacy_ability ), $result, 'Should include legacy projection abilities and exclude semantic abilities from the default MCP surface.' );
	}

	/**
	 * Test MCP validation disable workaround.
	 */
	public function test_disable_mcp_validation_returns_false() {
		$result = MCPAdapterProvider::disable_mcp_validation();

		$this->assertFalse( $result, 'disable_mcp_validation should always return false' );
	}

	/**
	 * Test initialization state tracking.
	 */
	public function test_is_initialized_tracks_state() {
		$this->assertFalse( $this->sut->is_initialized(), 'Should start as not initialized' );

		// Enable MCP feature via option.
		update_option( 'woocommerce_feature_mcp_integration_enabled', 'yes' );

		$this->sut->maybe_initialize();
		$this->assertTrue( $this->sut->is_initialized(), 'Should track initialized state' );
	}

	/**
	 * Test that abilities with empty array are handled correctly.
	 */
	public function test_handles_empty_abilities_array() {
		// Mock abilities registry to return empty array.
		$this->mock_abilities_registry
			->method( 'get_abilities_ids' )
			->willReturn( array() );

		// Use reflection to test the private method.
		$reflection = new \ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'get_woocommerce_mcp_abilities' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->sut );

		$this->assertEquals( array(), $result, 'Should handle empty abilities array correctly' );
	}

	/**
	 * Test that non-woocommerce abilities are filtered out.
	 */
	public function test_filters_out_non_woocommerce_abilities() {
		// Mock abilities registry to return only non-woocommerce abilities.
		$this->mock_abilities_registry
			->method( 'get_abilities_ids' )
			->willReturn(
				array(
					'other-plugin/action-1',
					'another-namespace/action-2',
					'custom/action-3',
				)
			);

		// Use reflection to test the private method.
		$reflection = new \ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'get_woocommerce_mcp_abilities' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->sut );

		$this->assertEquals( array(), $result, 'Should filter out all non-woocommerce abilities' );
	}

	/**
	 * Test array re-indexing after filtering.
	 */
	public function test_reindexes_array_after_filtering() {
		// Mock abilities registry to return mixed abilities.
		$this->mock_abilities_registry
			->method( 'get_abilities_ids' )
			->willReturn(
				array(
					'other-plugin/action-1',
					'woocommerce/products-list',
					'another-namespace/action-2',
					'woocommerce/orders-get',
				)
			);

		// Use reflection to test the private method.
		$reflection = new \ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'get_woocommerce_mcp_abilities' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->sut );

		// Check that array is properly re-indexed (keys should be 0, 1).
		$this->assertEquals( array( 0, 1 ), array_keys( $result ), 'Should re-index array after filtering' );
		$this->assertEquals(
			array(
				'woocommerce/products-list',
				'woocommerce/orders-get',
			),
			array_values( $result ),
			'Should maintain correct values after re-indexing'
		);
	}

	/**
	 * Register a minimal ability for provider tests.
	 *
	 * @param string $ability_id Ability ID.
	 * @param array  $meta Ability meta.
	 */
	private function register_test_ability( string $ability_id, array $meta ): void {
		global $wp_actions;

		$wp_actions['wp_abilities_api_init'] = 1; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->ensure_test_ability_category( 'woocommerce-rest' );

		wp_register_ability(
			$ability_id,
			array(
				'label'               => 'Test ability',
				'description'         => 'Test ability.',
				'category'            => 'woocommerce-rest',
				'input_schema'        => array( 'type' => 'object' ),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => static function () {
					return array();
				},
				'permission_callback' => static function () {
					return true;
				},
				'meta'                => array_merge(
					array(
						'show_in_rest' => true,
					),
					$meta
				),
			)
		);

		$this->registered_ability_ids[] = $ability_id;
	}

	/**
	 * Register the test ability category if the suite has not already registered it.
	 *
	 * @param string $category_id Ability category ID.
	 */
	private function ensure_test_ability_category( string $category_id ): void {
		global $wp_actions;

		if ( ! function_exists( 'wp_register_ability_category' ) || ! function_exists( 'wp_has_ability_category' ) ) {
			return;
		}

		if ( wp_has_ability_category( $category_id ) ) {
			return;
		}

		$wp_actions['wp_abilities_api_categories_init'] = 1; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		wp_register_ability_category(
			$category_id,
			array(
				'label'       => 'WooCommerce REST API',
				'description' => 'REST API operations for WooCommerce resources.',
			)
		);

		$this->registered_ability_category_ids[] = $category_id;
	}
}
