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
		// Clean up. test terms.
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

		// Should have full map structure.
		$this->assertArrayHasKey( 'parents', $map );
		$this->assertArrayHasKey( 'children', $map );
		$this->assertArrayHasKey( 'descendants', $map );

		// Verify parents mapping.
		$this->assertEquals( 0, $map['parents'][ $electronics_id ] );
		$this->assertEquals( $electronics_id, $map['parents'][ $laptops_id ] );
		$this->assertEquals( $laptops_id, $map['parents'][ $gaming_id ] );

		// Verify children mapping.
		$this->assertContains( $electronics_id, $map['children'][0] );
		$this->assertContains( $laptops_id, $map['children'][ $electronics_id ] );
		$this->assertContains( $gaming_id, $map['children'][ $laptops_id ] );

		// Verify descendants are pre-computed.
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

		$this->assertEquals( 0, $this->sut->get_parent( $electronics_id, $this->taxonomy ) );
		$this->assertEquals( $electronics_id, $this->sut->get_parent( $laptops_id, $this->taxonomy ) );
		$this->assertEquals( $laptops_id, $this->sut->get_parent( $gaming_id, $this->taxonomy ) );

		// Non-existent term should return 0.
		$this->assertEquals( 0, $this->sut->get_parent( 99999, $this->taxonomy ) );
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
		$this->assertEquals( 0, $this->sut->get_parent( 1, $empty_taxonomy ) );

		// Clean up.
		unregister_taxonomy( $empty_taxonomy );
	}
}
