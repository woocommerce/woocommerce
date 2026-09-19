<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\Params;

require_once WC_ABSPATH . '/includes/class-wc-brands.php';

/**
 * Tests for the Params class.
 */
class ParamsTest extends AbstractProductFiltersTest {
	/**
	 * This class only reads its product catalog inside per-test transactions.
	 */
	protected static function uses_class_product_filter_fixtures(): bool {
		return true;
	}

	/**
	 * The system under test.
	 *
	 * @var Params
	 */
	private $sut;

	/**
	 * Callback added to the woocommerce_product_filter_taxonomy_params filter during a test.
	 *
	 * @var callable|null
	 */
	private $taxonomy_params_filter;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure brands taxonomy is registered for testing.
		\WC_Brands::init_taxonomy();

		$container = wc_get_container();
		$this->sut = $container->get( Params::class );
		$this->clear_params_cache();
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		try {
			if ( null !== $this->taxonomy_params_filter ) {
				remove_filter( 'woocommerce_product_filter_taxonomy_params', $this->taxonomy_params_filter );
				$this->taxonomy_params_filter = null;
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Test that get_param_keys returns all expected parameter types.
	 */
	public function test_get_param_keys_returns_all_parameter_types() {
		$param_keys = $this->sut->get_param_keys();

		// Should contain price parameters.
		$this->assertContains( 'min_price', $param_keys );
		$this->assertContains( 'max_price', $param_keys );

		// Should contain rating parameters.
		$this->assertContains( 'rating_filter', $param_keys );

		// Should contain status parameters.
		$this->assertContains( 'filter_stock_status', $param_keys );

		// Should contain taxonomy parameters with prettier names.
		$this->assertContains( 'categories', $param_keys );
		$this->assertContains( 'tags', $param_keys );
		$this->assertContains( 'brands', $param_keys );

		// Should contain attribute parameters created in setup.
		$this->assertContains( 'filter_color', $param_keys );
		$this->assertContains( 'query_type_color', $param_keys );
	}


	/**
	 * Test get_param method with different parameter types.
	 *
	 * @dataProvider param_type_provider
	 * @param string $type The parameter type to test.
	 * @param array  $expected_structure The expected structure for the parameter type.
	 */
	public function test_get_param_with_different_types( $type, $expected_structure ) {
		$params = $this->sut->get_param( $type );

		$this->assertIsArray( $params );

		if ( ! empty( $expected_structure ) ) {
			foreach ( $expected_structure as $expected_param ) {
				$this->assertContains( $expected_param, $params );
			}
		}
	}

	/**
	 * Data provider for parameter types.
	 */
	public function param_type_provider() {
		return array(
			'price parameters'     => array(
				'price',
				array( 'min_price', 'max_price' ),
			),
			'rating parameters'    => array(
				'rating',
				array( 'rating_filter' ),
			),
			'status parameters'    => array(
				'status',
				array( 'filter_stock_status' ),
			),
			'taxonomy parameters'  => array(
				'taxonomy',
				array( 'categories', 'tags', 'brands' ),
			),
			'attribute parameters' => array(
				'attribute',
				array( 'filter_color' ), // Color attribute is created in setup.
			),
			'non-existent type'    => array(
				'non_existent',
				array(),
			),
		);
	}

	/**
	 * Test taxonomy parameter mapping for built-in taxonomies.
	 */
	public function test_taxonomy_parameter_mapping() {
		$taxonomy_params = $this->sut->get_param( 'taxonomy' );

		// Test built-in taxonomy mappings.
		$this->assertContains( 'categories', $taxonomy_params );
		$this->assertContains( 'tags', $taxonomy_params );
		$this->assertContains( 'brands', $taxonomy_params );
	}

	/**
	 * Test that custom taxonomies use filter_ prefix.
	 */
	public function test_custom_taxonomy_parameter_generation() {
		// Register a custom taxonomy.
		register_taxonomy(
			'product_custom_tax',
			'product',
			array(
				'label'  => 'Custom Taxonomy',
				'public' => true,
			)
		);

		// Clear cached params to force re-initialization.
		$this->clear_params_cache();

		$taxonomy_params = $this->sut->get_param( 'taxonomy' );

		// Should contain custom taxonomy with filter_ prefix.
		$this->assertContains( 'filter_product_custom_tax', $taxonomy_params );

		// Clean up.
		unregister_taxonomy( 'product_custom_tax' );
	}

	/**
	 * Test static caching behavior.
	 */
	public function test_static_caching_behavior() {
		// Clear cache to start fresh.
		$this->clear_params_cache();

		// First call should initialize params.
		$first_call = $this->sut->get_param_keys();

		// Modify the cached params to test if caching is working.
		$reflection      = new \ReflectionClass( Params::class );
		$params_property = $reflection->getProperty( 'params' );
		$params_property->setAccessible( true );
		$cached_params = $params_property->getValue();

		// Add a test parameter to the cached data.
		$cached_params['test_type'] = array( 'test_param' );
		$params_property->setValue( $cached_params );

		// Second call should return the modified cached data (proving caching works).
		$second_call = $this->sut->get_param_keys();

		// If caching works, the second call should include our test parameter.
		$this->assertContains( 'test_param', $second_call, 'Second call should return modified cached data, proving caching works' );

		// Test that get_param also uses the modified cached data.
		$test_params = $this->sut->get_param( 'test_type' );
		$this->assertEquals( array( 'test_param' ), $test_params, 'get_param should return modified cached data' );
	}


	/**
	 * Test edge cases and error handling.
	 */
	public function test_edge_cases_and_error_handling() {
		// Test with empty string parameter type.
		$empty_params = $this->sut->get_param( '' );
		$this->assertIsArray( $empty_params );
		$this->assertEmpty( $empty_params );

		// Test with non-existent parameter type.
		$non_existent_params = $this->sut->get_param( 'non_existent_type' );
		$this->assertIsArray( $non_existent_params );
		$this->assertEmpty( $non_existent_params );
	}

	/**
	 * Test that param keys are unique.
	 */
	public function test_param_keys_are_unique() {
		$param_keys  = $this->sut->get_param_keys();
		$unique_keys = array_unique( $param_keys );

		$this->assertEquals( count( $param_keys ), count( $unique_keys ), 'Parameter keys should be unique' );
	}

	/**
	 * Test that taxonomy parameters only include public taxonomies with UI.
	 */
	public function test_taxonomy_parameters_only_include_public_taxonomies_with_ui() {
		// Register a private taxonomy.
		register_taxonomy(
			'product_private_tax',
			'product',
			array(
				'label'   => 'Private Taxonomy',
				'public'  => false,
				'show_ui' => true,
			)
		);

		// Register a public taxonomy without UI.
		register_taxonomy(
			'product_no_ui_tax',
			'product',
			array(
				'label'   => 'No UI Taxonomy',
				'public'  => true,
				'show_ui' => false,
			)
		);

		// Clear cached params.
		$this->clear_params_cache();

		$taxonomy_params = $this->sut->get_param( 'taxonomy' );

		// Should not contain private taxonomy.
		$this->assertNotContains( 'filter_product_private_tax', $taxonomy_params );

		// Should not contain public taxonomy without UI.
		$this->assertNotContains( 'filter_product_no_ui_tax', $taxonomy_params );

		// Clean up.
		unregister_taxonomy( 'product_private_tax' );
		unregister_taxonomy( 'product_no_ui_tax' );
	}

	/**
	 * @testdox Taxonomy params can be renamed via the woocommerce_product_filter_taxonomy_params filter.
	 */
	public function test_taxonomy_params_are_filterable_by_rename(): void {
		$this->taxonomy_params_filter = function ( array $taxonomy_params ): array {
			$taxonomy_params['product_brand'] = 'wc_brands';
			return $taxonomy_params;
		};
		add_filter( 'woocommerce_product_filter_taxonomy_params', $this->taxonomy_params_filter );

		$taxonomy_params = $this->sut->get_param( 'taxonomy' );
		$param_keys      = $this->sut->get_param_keys();

		$this->assertSame( 'wc_brands', $taxonomy_params['product_brand'], 'Renamed taxonomy param should use the new key.' );
		$this->assertContains( 'wc_brands', $param_keys, 'get_param_keys() should expose the renamed param.' );
		$this->assertNotContains( 'brands', $param_keys, 'get_param_keys() should not expose the original param name once renamed.' );
	}

	/**
	 * @testdox Taxonomy params can be released via the woocommerce_product_filter_taxonomy_params filter.
	 */
	public function test_taxonomy_params_are_filterable_by_release(): void {
		$this->taxonomy_params_filter = function ( array $taxonomy_params ): array {
			unset( $taxonomy_params['product_brand'] );
			return $taxonomy_params;
		};
		add_filter( 'woocommerce_product_filter_taxonomy_params', $this->taxonomy_params_filter );

		$taxonomy_params = $this->sut->get_param( 'taxonomy' );
		$param_keys      = $this->sut->get_param_keys();

		$this->assertArrayNotHasKey( 'product_brand', $taxonomy_params, 'Released taxonomy should be removed from the param map.' );
		$this->assertNotContains( 'brands', $param_keys, 'Released taxonomy param should not appear in get_param_keys().' );
		$this->assertContains( 'categories', $param_keys, 'Unrelated taxonomy params should be unaffected.' );
		$this->assertContains( 'tags', $param_keys, 'Unrelated taxonomy params should be unaffected.' );
	}

	/**
	 * @testdox The woocommerce_product_filter_taxonomy_params filter receives the taxonomy-to-param map.
	 */
	public function test_taxonomy_params_filter_receives_expected_shape(): void {
		$received = null;

		$this->taxonomy_params_filter = function ( array $taxonomy_params ) use ( &$received ): array {
			if ( null === $received ) {
				$received = $taxonomy_params;
			}
			return $taxonomy_params;
		};
		add_filter( 'woocommerce_product_filter_taxonomy_params', $this->taxonomy_params_filter );

		$this->sut->get_param( 'taxonomy' );

		$this->assertIsArray( $received, 'The filter should receive an array.' );
		$this->assertSame( 'categories', $received['product_cat'] ?? null, 'The filter should receive the default product_cat mapping.' );
		$this->assertSame( 'tags', $received['product_tag'] ?? null, 'The filter should receive the default product_tag mapping.' );
		$this->assertSame( 'brands', $received['product_brand'] ?? null, 'The filter should receive the default product_brand mapping.' );
	}

	/**
	 * @testdox The taxonomy params filter still applies after the static params cache has already been warmed.
	 */
	public function test_taxonomy_params_filter_applies_after_static_cache_is_warm(): void {
		// Warm the static cache before any callback is registered.
		$this->sut->get_param( 'taxonomy' );

		$this->taxonomy_params_filter = function ( array $taxonomy_params ): array {
			$taxonomy_params['product_brand'] = 'wc_brands';
			return $taxonomy_params;
		};
		add_filter( 'woocommerce_product_filter_taxonomy_params', $this->taxonomy_params_filter );

		// No cache-clearing call here: the filter must apply on a warm cache too.
		$taxonomy_params = $this->sut->get_param( 'taxonomy' );

		$this->assertSame( 'wc_brands', $taxonomy_params['product_brand'], 'The filter must apply even when the static params cache was warmed before the callback was added.' );
	}

	/**
	 * @testdox A taxonomy params callback that returns a non-array value falls back to the unfiltered map.
	 *
	 * @testWith ["null"]
	 *           ["string"]
	 *           ["false"]
	 *
	 * @param string $return_type Which non-array value the callback returns.
	 */
	public function test_taxonomy_params_filter_falls_back_when_callback_returns_a_non_array( string $return_type ): void {
		$return_values = array(
			'null'   => null,
			'string' => 'brands',
			'false'  => false,
		);

		$this->taxonomy_params_filter = static function () use ( $return_values, $return_type ) {
			return $return_values[ $return_type ];
		};
		add_filter( 'woocommerce_product_filter_taxonomy_params', $this->taxonomy_params_filter );

		$taxonomy_params = $this->sut->get_param( 'taxonomy' );

		$this->assertSame( 'categories', $taxonomy_params['product_cat'] ?? null, "A callback returning {$return_type} should leave the default map in place." );
		$this->assertSame( 'brands', $taxonomy_params['product_brand'] ?? null, "A callback returning {$return_type} should leave the default map in place." );
		$this->assertContains( 'categories', $this->sut->get_param_keys(), 'get_param_keys() should still resolve after a callback returns a non-array.' );
	}

	/**
	 * @testdox Taxonomy param entries that are not name => string pairs are discarded.
	 */
	public function test_taxonomy_params_filter_discards_unusable_entries(): void {
		$this->taxonomy_params_filter = static function ( array $taxonomy_params ): array {
			$taxonomy_params['product_cat']   = array( 'categories', 'cats' );
			$taxonomy_params['product_tag']   = 42;
			$taxonomy_params['product_brand'] = '';
			$taxonomy_params[]                = 'orphan';

			return $taxonomy_params;
		};
		add_filter( 'woocommerce_product_filter_taxonomy_params', $this->taxonomy_params_filter );

		$taxonomy_params = $this->sut->get_param( 'taxonomy' );
		$param_keys      = $this->sut->get_param_keys();

		$this->assertArrayNotHasKey( 'product_cat', $taxonomy_params, 'An array param value should be discarded.' );
		$this->assertArrayNotHasKey( 'product_tag', $taxonomy_params, 'A non-string param value should be discarded.' );
		$this->assertArrayNotHasKey( 'product_brand', $taxonomy_params, 'An empty param value should be discarded.' );
		$this->assertArrayNotHasKey( 0, $taxonomy_params, 'A numerically keyed entry should be discarded.' );

		foreach ( $param_keys as $param_key ) {
			$this->assertIsString( $param_key, 'get_param_keys() must only expose strings, since they become public query vars.' );
		}
	}
}
