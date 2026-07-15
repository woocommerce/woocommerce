<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\CacheController;
use Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData;
use WC_Unit_Test_Case;

/**
 * Tests for CacheController.
 */
class CacheControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CacheController
	 */
	private $sut;

	/**
	 * Taxonomy hierarchy data service.
	 *
	 * @var TaxonomyHierarchyData
	 */
	private $taxonomy_hierarchy_data;

	/**
	 * Test taxonomy.
	 *
	 * @var string
	 */
	private $taxonomy;

	/**
	 * Test term IDs.
	 *
	 * @var int[]
	 */
	private $term_ids = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$container                     = wc_get_container();
		$this->sut                     = $container->get( CacheController::class );
		$this->taxonomy_hierarchy_data = $container->get( TaxonomyHierarchyData::class );
		$this->taxonomy                = 'wc_cache_' . substr( md5( $this->getName() ), 0, 12 );

		register_taxonomy( $this->taxonomy, 'product', array( 'hierarchical' => true ) );
		$this->remove_controller_hooks();
		delete_transient( CacheController::CACHE_GROUP . '-transient-version' );
		$this->sut->register();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->term_ids as $term_id ) {
			wp_delete_term( $term_id, $this->taxonomy );
		}

		unregister_taxonomy( $this->taxonomy );
		delete_option( $this->get_hierarchy_option_name() );
		$this->remove_controller_hooks();
		delete_transient( CacheController::CACHE_GROUP . '-transient-version' );
		$this->sut->register();

		parent::tearDown();
	}

	/**
	 * @testdox Taxonomy hooks are registered without enabling filter data cache cleanup.
	 */
	public function test_registers_taxonomy_hooks_without_filter_data_cache_cleanup(): void {
		$this->assertFalse( get_transient( CacheController::CACHE_GROUP . '-transient-version' ) );
		$this->assertSame( 10, has_action( 'created_term', array( $this->sut, 'clear_taxonomy_hierarchy_cache' ) ) );
		$this->assertSame( 10, has_action( 'edited_term', array( $this->sut, 'clear_taxonomy_hierarchy_cache' ) ) );
		$this->assertSame( 10, has_action( 'delete_term', array( $this->sut, 'clear_taxonomy_hierarchy_cache' ) ) );
		$this->assertSame( 10, has_action( 'added_term_meta', array( $this->sut, 'clear_taxonomy_hierarchy_cache_on_meta_update' ) ) );
		$this->assertSame( 10, has_action( 'updated_term_meta', array( $this->sut, 'clear_taxonomy_hierarchy_cache_on_meta_update' ) ) );
		$this->assertSame( 10, has_action( 'deleted_term_meta', array( $this->sut, 'clear_taxonomy_hierarchy_cache_on_meta_update' ) ) );
		$this->assertFalse( has_action( 'woocommerce_after_product_object_save', array( $this->sut, 'invalidate_filter_data_cache' ) ) );
		$this->assertFalse( has_action( 'woocommerce_delete_product_transients', array( $this->sut, 'invalidate_filter_data_cache' ) ) );
	}

	/**
	 * @testdox A real term hierarchy mutation invalidates cached hierarchy data without a filter data transient.
	 */
	public function test_term_hierarchy_mutation_invalidates_hierarchy_data_without_filter_data_transient(): void {
		$first_parent_id  = $this->create_term( 'First parent' );
		$second_parent_id = $this->create_term( 'Second parent' );
		$child_id         = $this->create_term( 'Child', $first_parent_id );
		$initial_map      = $this->taxonomy_hierarchy_data->get_hierarchy_map( $this->taxonomy );

		update_option( $this->get_hierarchy_option_name(), $initial_map, false );
		$this->assertSame( $initial_map, get_option( $this->get_hierarchy_option_name() ) );
		$this->assertSame( array( $first_parent_id ), $this->taxonomy_hierarchy_data->get_ancestors( $child_id, $this->taxonomy ) );
		$this->assertFalse( get_transient( CacheController::CACHE_GROUP . '-transient-version' ) );

		$result = wp_update_term( $child_id, $this->taxonomy, array( 'parent' => $second_parent_id ) );

		$this->assertIsArray( $result );
		$this->assertFalse( get_option( $this->get_hierarchy_option_name() ) );
		$this->assertSame( array( $second_parent_id ), $this->taxonomy_hierarchy_data->get_ancestors( $child_id, $this->taxonomy ) );
	}

	/**
	 * @testdox A real order meta mutation invalidates cached hierarchy data without a filter data transient.
	 */
	public function test_term_meta_mutation_invalidates_hierarchy_data_without_filter_data_transient(): void {
		$term_id     = $this->create_term( 'Ordered term' );
		$initial_map = $this->taxonomy_hierarchy_data->get_hierarchy_map( $this->taxonomy );

		update_option( $this->get_hierarchy_option_name(), $initial_map, false );
		$this->assertSame( $initial_map, get_option( $this->get_hierarchy_option_name() ) );
		$this->assertSame( 0, $initial_map['tree'][ $term_id ]['menu_order'] );
		$this->assertFalse( get_transient( CacheController::CACHE_GROUP . '-transient-version' ) );

		update_term_meta( $term_id, 'order', 7 );

		$this->assertFalse( get_option( $this->get_hierarchy_option_name() ) );
		$updated_map = $this->taxonomy_hierarchy_data->get_hierarchy_map( $this->taxonomy );
		$this->assertSame( 7, $updated_map['tree'][ $term_id ]['menu_order'] );
	}

	/**
	 * Create a test term.
	 *
	 * @param string $name      Term name.
	 * @param int    $parent_id Parent term ID.
	 * @return int Term ID.
	 */
	private function create_term( string $name, int $parent_id = 0 ): int {
		$term = wp_insert_term( $name, $this->taxonomy, array( 'parent' => $parent_id ) );
		$this->assertIsArray( $term );

		$term_id          = $term['term_id'];
		$this->term_ids[] = $term_id;

		return $term_id;
	}

	/**
	 * Remove CacheController hooks registered by the test environment.
	 */
	private function remove_controller_hooks(): void {
		remove_action( 'created_term', array( $this->sut, 'clear_taxonomy_hierarchy_cache' ), 10 );
		remove_action( 'edited_term', array( $this->sut, 'clear_taxonomy_hierarchy_cache' ), 10 );
		remove_action( 'delete_term', array( $this->sut, 'clear_taxonomy_hierarchy_cache' ), 10 );
		remove_action( 'added_term_meta', array( $this->sut, 'clear_taxonomy_hierarchy_cache_on_meta_update' ), 10 );
		remove_action( 'updated_term_meta', array( $this->sut, 'clear_taxonomy_hierarchy_cache_on_meta_update' ), 10 );
		remove_action( 'deleted_term_meta', array( $this->sut, 'clear_taxonomy_hierarchy_cache_on_meta_update' ), 10 );
		remove_action( 'woocommerce_after_product_object_save', array( $this->sut, 'invalidate_filter_data_cache' ) );
		remove_action( 'woocommerce_delete_product_transients', array( $this->sut, 'invalidate_filter_data_cache' ) );
	}

	/**
	 * Get the hierarchy option name for the test taxonomy.
	 *
	 * @return string
	 */
	private function get_hierarchy_option_name(): string {
		return 'wc_taxonomy_hierarchy_' . $this->taxonomy;
	}
}
