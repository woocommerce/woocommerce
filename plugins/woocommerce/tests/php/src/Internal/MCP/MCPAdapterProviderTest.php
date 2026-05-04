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
	 * Test ability filtering by deprecated WooCommerce MCP exposure metadata.
	 */
	public function test_get_woocommerce_mcp_abilities_filters_by_deprecated_endpoint_exposure_metadata() {
		$exposed_woocommerce_ability = 'woocommerce/test-deprecated-products-a';
		$exposed_external_ability    = 'custom-plugin/test-deprecated-orders-a';
		$unmarked_ability            = 'woocommerce/test-unmarked';
		$opted_out_ability           = 'woocommerce/test-opted-out';
		$invalid_exposure_ability    = 'woocommerce/test-invalid-exposure';

		$this->register_test_ability(
			$exposed_woocommerce_ability,
			array(
				'expose_in_deprecated_woocommerce_mcp' => true,
			)
		);
		$this->register_test_ability(
			$exposed_external_ability,
			array(
				'expose_in_deprecated_woocommerce_mcp' => true,
			)
		);
		$this->register_test_ability(
			$unmarked_ability,
			array()
		);
		$this->register_test_ability(
			$opted_out_ability,
			array(
				'expose_in_deprecated_woocommerce_mcp' => false,
			)
		);
		$this->register_test_ability(
			$invalid_exposure_ability,
			array(
				'expose_in_deprecated_woocommerce_mcp' => 'true',
			)
		);

		$this->mock_abilities_registry
			->method( 'get_abilities_ids' )
			->willReturn(
				array(
					'unregistered-plugin/not-available',
					$exposed_woocommerce_ability,
					$unmarked_ability,
					$exposed_external_ability,
					$opted_out_ability,
					$invalid_exposure_ability,
				)
			);

		$result = $this->get_woocommerce_mcp_abilities();

		$expected = array(
			$exposed_woocommerce_ability,
			$exposed_external_ability,
		);

		$this->assertEquals( $expected, $result, 'Should only return abilities explicitly exposed in the deprecated WooCommerce MCP endpoint.' );
		$this->assertSame( array( 0, 1 ), array_keys( $result ), 'Should re-index array after filtering.' );
	}

	/**
	 * Test ability filtering with custom filter.
	 */
	public function test_get_woocommerce_mcp_abilities_respects_custom_filter() {
		$deprecated_ability = 'woocommerce/test-custom-filter-deprecated';

		$this->register_test_ability(
			$deprecated_ability,
			array(
				'expose_in_deprecated_woocommerce_mcp' => true,
			)
		);

		// Mock abilities registry to return test abilities.
		$this->mock_abilities_registry
			->method( 'get_abilities_ids' )
			->willReturn(
				array(
					$deprecated_ability,
					'custom-plugin/special-action',
					'other-plugin/normal-action',
				)
			);

		// Add custom filter to include abilities from custom-plugin namespace.
		add_filter(
			'woocommerce_mcp_include_ability',
			function ( $should_include, $ability_id ) {
				if ( 'woocommerce/test-custom-filter-deprecated' === $ability_id ) {
					return false;
				}
				if ( str_starts_with( $ability_id, 'custom-plugin/' ) ) {
					return true;
				}
				return $should_include;
			},
			10,
			2
		);

		$result = $this->get_woocommerce_mcp_abilities();

		$expected = array(
			'custom-plugin/special-action',
		);

		$this->assertEquals( $expected, $result, 'Should respect custom filter for including abilities' );
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

		$result = $this->get_woocommerce_mcp_abilities();

		$this->assertEquals( array(), $result, 'Should handle empty abilities array correctly' );
	}

	/**
	 * Test that unregistered abilities are filtered out.
	 */
	public function test_filters_out_unregistered_abilities() {
		$this->mock_abilities_registry
			->method( 'get_abilities_ids' )
			->willReturn(
				array(
					'other-plugin/action-1',
					'another-namespace/action-2',
					'custom/action-3',
				)
			);

		$result = $this->get_woocommerce_mcp_abilities();

		$this->assertEquals( array(), $result, 'Should filter out all unregistered abilities.' );
	}

	/**
	 * Get WooCommerce MCP abilities through the private provider method.
	 *
	 * @return array
	 */
	private function get_woocommerce_mcp_abilities(): array {
		$reflection = new \ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'get_woocommerce_mcp_abilities' );
		$method->setAccessible( true );

		return $method->invoke( $this->sut );
	}

	/**
	 * Register a minimal ability for provider tests.
	 *
	 * @param string $ability_id Ability ID.
	 * @param array  $meta Ability meta.
	 */
	private function register_test_ability( string $ability_id, array $meta ): void {
		$this->ensure_test_ability_category( 'woocommerce-rest' );

		$ability  = null;
		$callback = null;
		$callback = function () use ( &$ability, $ability_id, $meta, &$callback ) {
			remove_action( 'wp_abilities_api_init', $callback );

			$ability = wp_register_ability(
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
		};

		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Test bootstrap for Abilities API registration.
		remove_action( 'wp_abilities_api_init', $callback );

		$this->assertNotWPError( $ability, 'Test ability should register successfully.' );
		$this->assertNotNull( $ability, 'Test ability should register successfully.' );
		$this->registered_ability_ids[] = $ability_id;
	}

	/**
	 * Register the test ability category if the suite has not already registered it.
	 *
	 * @param string $category_id Ability category ID.
	 */
	private function ensure_test_ability_category( string $category_id ): void {
		if ( ! function_exists( 'wp_register_ability_category' ) || ! function_exists( 'wp_has_ability_category' ) ) {
			return;
		}

		if ( wp_has_ability_category( $category_id ) ) {
			return;
		}

		$category = null;
		$callback = null;
		$callback = function () use ( &$category, $category_id, &$callback ) {
			remove_action( 'wp_abilities_api_categories_init', $callback );

			if ( wp_has_ability_category( $category_id ) ) {
				return;
			}

			$category = wp_register_ability_category(
				$category_id,
				array(
					'label'       => 'WooCommerce REST API',
					'description' => 'REST API operations for WooCommerce resources.',
				)
			);
		};

		add_action( 'wp_abilities_api_categories_init', $callback );
		do_action( 'wp_abilities_api_categories_init' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Test bootstrap for Abilities API registration.
		remove_action( 'wp_abilities_api_categories_init', $callback );

		if ( null !== $category ) {
			$this->assertNotWPError( $category, 'Test ability category should register successfully.' );
			$this->assertNotNull( $category, 'Test ability category should register successfully.' );
			$this->registered_ability_category_ids[] = $category_id;
		}

		$this->assertTrue( wp_has_ability_category( $category_id ), 'Test ability category should be available.' );
	}
}
