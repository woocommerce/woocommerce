<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData;
use WP_UnitTestCase;

/**
 * Tests for TaxonomyHierarchyData class.
 */
class TaxonomyHierarchyDataTest extends WP_UnitTestCase {

	/**
	 * Instance of TaxonomyHierarchyData for testing.
	 *
	 * @var TaxonomyHierarchyData
	 */
	private $sut;

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

		$container = wc_get_container();
		$this->sut = $container->get( TaxonomyHierarchyData::class );

		// Clear any existing cache.
		$this->sut->clear_cache( $this->taxonomy );
	}

	/**
	 * Clean up test environment.
	 */
	public function tearDown(): void {
		// Clean up test terms.
		foreach ( $this->test_term_ids as $term_id ) {
			wp_delete_term( $term_id, $this->taxonomy );
		}
		$this->test_term_ids = array();

		// Clear cache.
		$this->sut->clear_cache( $this->taxonomy );

		parent::tearDown();
	}

	/**
	 * Create a test term.
	 *
	 * @param string $name      Term name.
	 * @param int    $parent_id Parent term ID.
	 * @return int Term ID.
	 */
	private function create_test_term( string $name, int $parent_id = 0 ): int {
		$term = wp_insert_term( $name, $this->taxonomy, array( 'parent' => $parent_id ) );
		$this->assertIsArray( $term, "Failed to create term: $name" );

		$term_id               = $term['term_id'];
		$this->test_term_ids[] = $term_id;

		return $term_id;
	}

	/**
	 * Test get_hierarchy_map returns empty array for non-hierarchical taxonomy.
	 */
	public function test_get_hierarchy_map_non_hierarchical_taxonomy(): void {
		$result = $this->sut->get_hierarchy_map( 'product_tag' );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_hierarchy_map builds complete hierarchy with pre-computed descendants.
	 */
	public function test_get_hierarchy_map_builds_complete_hierarchy(): void {
		// Create test hierarchy.
		$electronics_id = $this->create_test_term( 'Electronics' );
		$laptops_id     = $this->create_test_term( 'Laptops', $electronics_id );
		$gaming_id      = $this->create_test_term( 'Gaming Laptops', $laptops_id );

		$map = $this->sut->get_hierarchy_map( $this->taxonomy );

		// Should have new map structure.
		$this->assertArrayHasKey( 'descendants', $map );
		$this->assertArrayHasKey( 'ancestors', $map );
		$this->assertArrayHasKey( 'tree', $map );

		// Verify descendants are pre-computed.
		$this->assertContains( $laptops_id, $map['descendants'][ $electronics_id ] );
		$this->assertContains( $gaming_id, $map['descendants'][ $electronics_id ] );
		$this->assertContains( $gaming_id, $map['descendants'][ $laptops_id ] );

		// Verify ancestors are pre-computed.
		$this->assertContains( $electronics_id, $map['ancestors'][ $laptops_id ] );
		$this->assertContains( $electronics_id, $map['ancestors'][ $gaming_id ] );
		$this->assertContains( $laptops_id, $map['ancestors'][ $gaming_id ] );

		// Verify tree structure.
		$this->assertArrayHasKey( $electronics_id, $map['tree'] );
		$this->assertEquals( 'Electronics', $map['tree'][ $electronics_id ]['name'] );
		$this->assertEquals( 0, $map['tree'][ $electronics_id ]['depth'] );
		$this->assertArrayHasKey( 'children', $map['tree'][ $electronics_id ] );
		$this->assertArrayHasKey( $laptops_id, $map['tree'][ $electronics_id ]['children'] );
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

		$electronics_descendants = $this->sut->get_descendants( $electronics_id, $this->taxonomy );
		$this->assertContains( $laptops_id, $electronics_descendants );
		$this->assertContains( $phones_id, $electronics_descendants );
		$this->assertContains( $gaming_id, $electronics_descendants );
		$this->assertContains( $smartphones_id, $electronics_descendants );
		$this->assertCount( 4, $electronics_descendants );

		$laptops_descendants = $this->sut->get_descendants( $laptops_id, $this->taxonomy );
		$this->assertContains( $gaming_id, $laptops_descendants );
		$this->assertCount( 1, $laptops_descendants );

		$gaming_descendants = $this->sut->get_descendants( $gaming_id, $this->taxonomy );
		$this->assertEmpty( $gaming_descendants );

		// Non-existent term should return empty array.
		$this->assertEmpty( $this->sut->get_descendants( 99999, $this->taxonomy ) );
	}

	/**
	 * Test get_ancestors method.
	 */
	public function test_get_ancestors(): void {
		$electronics_id = $this->create_test_term( 'Electronics' );
		$laptops_id     = $this->create_test_term( 'Laptops', $electronics_id );
		$gaming_id      = $this->create_test_term( 'Gaming Laptops', $laptops_id );

		// Root term should have no ancestors.
		$this->assertEmpty( $this->sut->get_ancestors( $electronics_id, $this->taxonomy ) );

		// Second level should have one ancestor.
		$laptops_ancestors = $this->sut->get_ancestors( $laptops_id, $this->taxonomy );
		$this->assertContains( $electronics_id, $laptops_ancestors );
		$this->assertCount( 1, $laptops_ancestors );

		// Third level should have two ancestors.
		$gaming_ancestors = $this->sut->get_ancestors( $gaming_id, $this->taxonomy );
		$this->assertContains( $laptops_id, $gaming_ancestors );
		$this->assertContains( $electronics_id, $gaming_ancestors );
		$this->assertCount( 2, $gaming_ancestors );

		// Non-existent term should return empty array.
		$this->assertEmpty( $this->sut->get_ancestors( 99999, $this->taxonomy ) );
	}

	/**
	 * Test caching functionality.
	 */
	public function test_caching(): void {
		$electronics_id = $this->create_test_term( 'Electronics' );
		$laptops_id     = $this->create_test_term( 'Laptops', $electronics_id );

		// First call should build and cache the map.
		$map1 = $this->sut->get_hierarchy_map( $this->taxonomy );
		$this->assertIsArray( $map1 );

		// Second call should return cached version.
		$map2 = $this->sut->get_hierarchy_map( $this->taxonomy );
		$this->assertEquals( $map1, $map2 );

		// Clear cache for specific taxonomy.
		$this->sut->clear_cache( $this->taxonomy );

		// Should rebuild the map.
		$map3 = $this->sut->get_hierarchy_map( $this->taxonomy );
		$this->assertEquals( $map1, $map3 );
	}

	/**
	 * Test empty taxonomy handling.
	 */
	public function test_empty_taxonomy(): void {
		// Test with taxonomy that has no terms.
		$empty_taxonomy = 'empty_test_taxonomy';
		register_taxonomy( $empty_taxonomy, 'product', array( 'hierarchical' => true ) );

		$map = $this->sut->get_hierarchy_map( $empty_taxonomy );
		$this->assertIsArray( $map );

		// Should return empty arrays for all methods.
		$this->assertEmpty( $this->sut->get_descendants( 1, $empty_taxonomy ) );
		$this->assertEmpty( $this->sut->get_ancestors( 1, $empty_taxonomy ) );

		// Clean up.
		unregister_taxonomy( $empty_taxonomy );
	}

	/**
	 * Test tree structure includes depth and parent information.
	 */
	public function test_tree_structure_includes_depth_and_parent(): void {
		$electronics_id = $this->create_test_term( 'Electronics' );
		$laptops_id     = $this->create_test_term( 'Laptops', $electronics_id );
		$gaming_id      = $this->create_test_term( 'Gaming Laptops', $laptops_id );

		$map = $this->sut->get_hierarchy_map( $this->taxonomy );

		// Check root level term.
		$this->assertEquals( 0, $map['tree'][ $electronics_id ]['depth'] );
		$this->assertEquals( 0, $map['tree'][ $electronics_id ]['parent'] );

		// Check second level term.
		$laptops_tree = $map['tree'][ $electronics_id ]['children'][ $laptops_id ];
		$this->assertEquals( 1, $laptops_tree['depth'] );
		$this->assertEquals( $electronics_id, $laptops_tree['parent'] );

		// Check third level term.
		$gaming_tree = $laptops_tree['children'][ $gaming_id ];
		$this->assertEquals( 2, $gaming_tree['depth'] );
		$this->assertEquals( $laptops_id, $gaming_tree['parent'] );
	}

	/**
	 * Should include menu_order field in tree structure with default value of 0.
	 */
	public function test_tree_structure_includes_menu_order_default(): void {
		$electronics_id = $this->create_test_term( 'Electronics' );

		$map = $this->sut->get_hierarchy_map( $this->taxonomy );

		$this->assertArrayHasKey( 'menu_order', $map['tree'][ $electronics_id ] );
		$this->assertEquals( 0, $map['tree'][ $electronics_id ]['menu_order'] );
	}

	/**
	 * Should include menu_order field from term meta when set.
	 */
	public function test_tree_structure_includes_menu_order_from_meta(): void {
		$electronics_id = $this->create_test_term( 'Electronics' );
		$laptops_id     = $this->create_test_term( 'Laptops', $electronics_id );

		update_term_meta( $electronics_id, 'order', 5 );
		update_term_meta( $laptops_id, 'order', 10 );

		$this->sut->clear_cache( $this->taxonomy );
		$map = $this->sut->get_hierarchy_map( $this->taxonomy );

		$this->assertEquals( 5, $map['tree'][ $electronics_id ]['menu_order'] );
		$this->assertEquals( 10, $map['tree'][ $electronics_id ]['children'][ $laptops_id ]['menu_order'] );
	}

	/**
	 * Should handle non-numeric menu_order meta gracefully.
	 */
	public function test_tree_structure_handles_invalid_menu_order_meta(): void {
		$electronics_id = $this->create_test_term( 'Electronics' );

		update_term_meta( $electronics_id, 'order', 'invalid' );

		$this->sut->clear_cache( $this->taxonomy );
		$map = $this->sut->get_hierarchy_map( $this->taxonomy );

		$this->assertEquals( 0, $map['tree'][ $electronics_id ]['menu_order'] );
	}
}
