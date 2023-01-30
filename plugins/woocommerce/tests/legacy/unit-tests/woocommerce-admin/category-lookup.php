<?php
/**
 * CategoryLookup tests
 *
 * @package WooCommerce\Admin\Tests\CategoryLookup
 */

use Automattic\WooCommerce\Internal\Admin\CategoryLookup;

/**
 * WC_Admin_Tests_Admin_Helper Class
 *
 * @package WooCommerce\Admin\Tests\CategoryLookup
 */
class WC_Admin_Tests_Category_Lookup extends WP_UnitTestCase {

	/** @var int parent term id */
	protected $parent_term_id;
	/** @var int parent 2 term id */
	protected $parent2_term_id;
	/** @var int child term id */
	protected $child_term_id;

	/**
	 * Setup
	 */

	public function setUp(): void {
		delete_transient('wc_installing');
		parent::setUp();
		$parent                = wp_insert_term( 'test_parent', 'product_cat' );
		$parent2               = wp_insert_term( 'test_parent_2', 'product_cat' );
		$child                 = wp_insert_term(
			'test_child',
			'product_cat',
			array(
				'parent' => $parent['term_id'],
			)
		);
		$this->parent_term_id  = $parent['term_id'];
		$this->parent2_term_id = $parent2['term_id'];
		$this->child_term_id   = $child['term_id'];
		CategoryLookup::instance()->init();
	}

	/**
	 * Tear Down
	 */
	public function tearDown(): void {
		parent::tearDown();
		wp_delete_term( $this->parent_term_id, 'product_cat' );
		wp_delete_term( $this->parent2_term_id, 'product_cat' );
		wp_delete_term( $this->child_term_id, 'product_cat' );
	}

	/**
	 * @param int $category_id category id.
	 * @return int[] list of category tree ids
	 */
	public function get_category_parent_id( $category_id ) {
		global $wpdb;

		return wp_parse_id_list(
			$wpdb->get_col(
				$wpdb->prepare(
					"SELECT category_tree_id FROM $wpdb->wc_category_lookup WHERE category_id = %d",
					$category_id
				)
			)
		);
	}

	/**
	 * Test on_create callback for when product category is created.
	 */
	public function test_create_product_category_update_lookup_table() {
		$parent_ids = $this->get_category_parent_id( $this->parent_term_id );
		$this->assertCount( 1, $parent_ids );
		$this->assertContains( $this->parent_term_id, $parent_ids );
		$parent2_ids = $this->get_category_parent_id( $this->parent2_term_id );
		$this->assertCount( 1, $parent2_ids );
		$this->assertContains( $this->parent2_term_id, $parent2_ids );
		$child_ids = $this->get_category_parent_id( $this->child_term_id );
		$this->assertCount( 2, $child_ids );
		$this->assertContains( $this->parent_term_id, $child_ids );
	}

	/**
	 * Test update callback for product category update.
	 */
	public function test_product_category_edit_should_update_table() {
		wp_update_term(
			$this->child_term_id,
			'product_cat',
			array(
				'slug' => 'test',
			)
		);
		$child_parent_ids = $this->get_category_parent_id( $this->child_term_id );
		$this->assertCount( 2, $child_parent_ids );
		$this->assertContains( $this->parent_term_id, $child_parent_ids );
	}

	/**
	 * Test update of product category with children.
	 */
	public function test_product_category_update_with_children() {
		wp_update_term(
			$this->parent_term_id,
			'product_cat',
			array(
				'parent' => $this->parent2_term_id,
			)
		);
		$parent_parent_ids = $this->get_category_parent_id( $this->parent_term_id );

		$this->assertCount( 2, $parent_parent_ids );
		$this->assertContains( $this->parent2_term_id, $parent_parent_ids );
	}

	/**
	 * Test deleting old lookup data upon product category update.
	 */
	public function test_product_category_update_should_delete_old_lookups() {
		$parent_parent_ids = $this->get_category_parent_id( $this->parent_term_id );
		$this->assertCount( 1, $parent_parent_ids );
		$this->assertNotContains( $this->parent2_term_id, $parent_parent_ids );

		wp_update_term(
			$this->parent_term_id,
			'product_cat',
			array(
				'parent' => $this->parent2_term_id,
			)
		);
		$parent_parent_ids = $this->get_category_parent_id( $this->parent_term_id );

		$this->assertCount( 2, $parent_parent_ids );
		$this->assertContains( $this->parent2_term_id, $parent_parent_ids );
	}
}
