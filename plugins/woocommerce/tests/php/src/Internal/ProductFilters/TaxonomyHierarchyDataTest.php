<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData;
use WC_Cache_Helper;
use WP_UnitTestCase;

/**
 * Testable subclass for strategy testing.
 */
class TestableTaxonomyHierarchyData extends TaxonomyHierarchyData {
	/**
	 * Test threshold override.
	 *
	 * @var int|null
	 */
	private $test_threshold = null;

	/**
	 * Set threshold for testing.
	 *
	 * @param int $threshold The threshold value.
	 */
	public function set_threshold( int $threshold ): void {
		$this->test_threshold = $threshold;
	}

	/**
	 * Get threshold with test override.
	 *
	 * @return int The threshold value.
	 */
	protected function get_threshold(): int {
		return $this->test_threshold ?? parent::get_threshold();
	}
}

/**
 * Tests for TaxonomyHierarchyData class.
 */
class TaxonomyHierarchyDataTest extends WP_UnitTestCase {

	/**
	 * Instance of TaxonomyHierarchyData for testing.
	 *
	 * @var TaxonomyHierarchyData
	 */
	private $hierarchy_data;

	/**
	 * Test taxonomy name.
	 *
	 * @var string
	 */
	private $taxonomy = 'product_cat';

	/**
	 * Test term IDs for cleanup.
	 *
	 * @var array
	 */
	private $test_term_ids = array();

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->hierarchy_data = new TaxonomyHierarchyData();

		// Clear any existing cache
		$this->hierarchy_data->clear_cache( $this->taxonomy );
	}

	/**
	 * Clean up test environment.
	 */
	public function tearDown(): void {
		// Clean up test terms
		foreach ( $this->test_term_ids as $term_id ) {
			wp_delete_term( $term_id, $this->taxonomy );
		}
		$this->test_term_ids = array();

		// Clear cache
		$this->hierarchy_data->clear_cache( $this->taxonomy );

		parent::tearDown();
	}

	/**
	 * Create a test term.
	 *
	 * @param string $name Term name.
	 * @param int    $parent Parent term ID.
	 * @return int Term ID.
	 */
	private function create_test_term( string $name, int $parent = 0 ): int {
		$term = wp_insert_term( $name, $this->taxonomy, array( 'parent' => $parent ) );
		$this->assertIsArray( $term, "Failed to create term: $name" );

		$term_id               = $term['term_id'];
		$this->test_term_ids[] = $term_id;

		return $term_id;
	}

	/**
	 * Test get_hierarchy_map returns empty array for non-hierarchical taxonomy.
	 */
	public function test_get_hierarchy_map_non_hierarchical_taxonomy(): void {
		$result = $this->hierarchy_data->get_hierarchy_map( 'product_tag' );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_hierarchy_map with small taxonomy uses full map strategy.
	 */
	public function test_get_hierarchy_map_small_taxonomy_full_map(): void {
		// Create test hierarchy
		$electronics_id = $this->create_test_term( 'Electronics' );
		$laptops_id     = $this->create_test_term( 'Laptops', $electronics_id );
		$gaming_id      = $this->create_test_term( 'Gaming Laptops', $laptops_id );

		$map = $this->hierarchy_data->get_hierarchy_map( $this->taxonomy );

		// Should have full map structure
		$this->assertArrayHasKey( 'parents', $map );
		$this->assertArrayHasKey( 'children', $map );
		$this->assertArrayHasKey( 'descendants', $map );

		// Verify parents mapping
		$this->assertEquals( 0, $map['parents'][ $electronics_id ] );
		$this->assertEquals( $electronics_id, $map['parents'][ $laptops_id ] );
		$this->assertEquals( $laptops_id, $map['parents'][ $gaming_id ] );

		// Verify children mapping
		$this->assertContains( $electronics_id, $map['children'][0] );
		$this->assertContains( $laptops_id, $map['children'][ $electronics_id ] );
		$this->assertContains( $gaming_id, $map['children'][ $laptops_id ] );

		// Verify descendants are pre-computed
		$this->assertContains( $laptops_id, $map['descendants'][ $electronics_id ] );
		$this->assertContains( $gaming_id, $map['descendants'][ $electronics_id ] );
		$this->assertContains( $gaming_id, $map['descendants'][ $laptops_id ] );
	}

	/**
	 * Test get_parent method.
	 */
	public function test_get_parent(): void {
		$electronics_id = $this->create_test_term( 'Electronics' );
		$laptops_id     = $this->create_test_term( 'Laptops', $electronics_id );
		$gaming_id      = $this->create_test_term( 'Gaming Laptops', $laptops_id );

		$this->assertEquals( 0, $this->hierarchy_data->get_parent( $electronics_id, $this->taxonomy ) );
		$this->assertEquals( $electronics_id, $this->hierarchy_data->get_parent( $laptops_id, $this->taxonomy ) );
		$this->assertEquals( $laptops_id, $this->hierarchy_data->get_parent( $gaming_id, $this->taxonomy ) );

		// Non-existent term should return 0
		$this->assertEquals( 0, $this->hierarchy_data->get_parent( 99999, $this->taxonomy ) );
	}


	/**
	 * Test get_descendants method.
	 */
	public function test_get_descendants(): void {
		$electronics_id = $this->create_test_term( 'Electronics' );
		$laptops_id     = $this->create_test_term( 'Laptops', $electronics_id );
		$phones_id      = $this->create_test_term( 'Phones', $electronics_id );
		$gaming_id      = $this->create_test_term( 'Gaming Laptops', $laptops_id );
		$smartphones_id = $this->create_test_term( 'Smartphones', $phones_id );

		$electronics_descendants = $this->hierarchy_data->get_descendants( $electronics_id, $this->taxonomy );
		$this->assertContains( $laptops_id, $electronics_descendants );
		$this->assertContains( $phones_id, $electronics_descendants );
		$this->assertContains( $gaming_id, $electronics_descendants );
		$this->assertContains( $smartphones_id, $electronics_descendants );
		$this->assertCount( 4, $electronics_descendants );

		$laptops_descendants = $this->hierarchy_data->get_descendants( $laptops_id, $this->taxonomy );
		$this->assertContains( $gaming_id, $laptops_descendants );
		$this->assertCount( 1, $laptops_descendants );

		$gaming_descendants = $this->hierarchy_data->get_descendants( $gaming_id, $this->taxonomy );
		$this->assertEmpty( $gaming_descendants );

		// Non-existent term should return empty array
		$this->assertEmpty( $this->hierarchy_data->get_descendants( 99999, $this->taxonomy ) );
	}

	/**
	 * Test caching functionality.
	 */
	public function test_caching(): void {
		$electronics_id = $this->create_test_term( 'Electronics' );
		$laptops_id     = $this->create_test_term( 'Laptops', $electronics_id );

		// First call should build and cache the map
		$map1 = $this->hierarchy_data->get_hierarchy_map( $this->taxonomy );
		$this->assertIsArray( $map1 );

		// Second call should return cached version
		$map2 = $this->hierarchy_data->get_hierarchy_map( $this->taxonomy );
		$this->assertEquals( $map1, $map2 );

		// Clear cache for specific taxonomy
		$this->hierarchy_data->clear_cache( $this->taxonomy );

		// Should rebuild the map
		$map3 = $this->hierarchy_data->get_hierarchy_map( $this->taxonomy );
		$this->assertEquals( $map1, $map3 );
	}

	/**
	 * Test circular reference protection.
	 */
	public function test_circular_reference_protection(): void {
		$term1_id = $this->create_test_term( 'Term1' );
		$term2_id = $this->create_test_term( 'Term2', $term1_id );

		// Manually create circular reference in database
		global $wpdb;
		$wpdb->update(
			$wpdb->term_taxonomy,
			array( 'parent' => $term2_id ),
			array(
				'term_id'  => $term1_id,
				'taxonomy' => $this->taxonomy,
			)
		);

		// Should not cause infinite loop when getting descendants
		$descendants1 = $this->hierarchy_data->get_descendants( $term1_id, $this->taxonomy );
		$descendants2 = $this->hierarchy_data->get_descendants( $term2_id, $this->taxonomy );

		// Should handle circular reference gracefully
		$this->assertIsArray( $descendants1 );
		$this->assertIsArray( $descendants2 );
	}

	/**
	 * Test empty taxonomy handling.
	 */
	public function test_empty_taxonomy(): void {
		// Test with taxonomy that has no terms
		$empty_taxonomy = 'empty_test_taxonomy';
		register_taxonomy( $empty_taxonomy, 'product', array( 'hierarchical' => true ) );

		$map = $this->hierarchy_data->get_hierarchy_map( $empty_taxonomy );
		$this->assertIsArray( $map );

		// Should return empty arrays for all methods
		$this->assertEmpty( $this->hierarchy_data->get_descendants( 1, $empty_taxonomy ) );
		$this->assertEquals( 0, $this->hierarchy_data->get_parent( 1, $empty_taxonomy ) );

		// Clean up
		unregister_taxonomy( $empty_taxonomy );
	}

	/**
	 * Test full map strategy for small taxonomies.
	 */
	public function test_full_map_strategy(): void {
		$testable_hierarchy = new TestableTaxonomyHierarchyData();
		$testable_hierarchy->set_threshold( 1000 );

		// Create test terms (well below threshold)
		$electronics_id = $this->create_test_term( 'Electronics' );
		$laptops_id     = $this->create_test_term( 'Laptops', $electronics_id );

		$map = $testable_hierarchy->get_hierarchy_map( $this->taxonomy );

		// Full map should have pre-computed descendants
		$this->assertArrayHasKey( 'parents', $map );
		$this->assertArrayHasKey( 'children', $map );
		$this->assertArrayHasKey( 'descendants', $map );

		// Verify descendants are pre-computed
		$this->assertContains( $laptops_id, $map['descendants'][ $electronics_id ] );
	}

	/**
	 * Test adjacency strategy for large taxonomies.
	 */
	public function test_adjacency_strategy(): void {
		$testable_hierarchy = new TestableTaxonomyHierarchyData();
		$testable_hierarchy->set_threshold( 1 ); // Force adjacency strategy

		// Create test terms (above threshold)
		$electronics_id = $this->create_test_term( 'Electronics' );
		$laptops_id     = $this->create_test_term( 'Laptops', $electronics_id );

		$map = $testable_hierarchy->get_hierarchy_map( $this->taxonomy );

		// Adjacency map should NOT have pre-computed descendants
		$this->assertArrayHasKey( 'parents', $map );
		$this->assertArrayHasKey( 'children', $map );
		$this->assertArrayNotHasKey( 'descendants', $map );

		// But get_descendants should still work (computed on-demand)
		$descendants = $testable_hierarchy->get_descendants( $electronics_id, $this->taxonomy );
		$this->assertContains( $laptops_id, $descendants );
	}
}
