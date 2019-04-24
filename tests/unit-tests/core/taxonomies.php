<?php
/**
 * Taxonomy tests class.
 *
 * @package WooCommerce\Tests\Core
 */

/**
 * WC_Test_Taxonomies class.
 */
class WC_Test_Taxonomies extends WC_Unit_Test_Case {

	/**
	 * Setup test.
	 */
	public function setUp() {
		parent::setUp();
		$category_1 = wp_insert_term( 'Zulu Category', 'product_cat' );
		$category_2 = wp_insert_term( 'Alpha Category', 'product_cat' );
		$category_3 = wp_insert_term( 'Beta Category', 'product_cat' );
		update_term_meta( $category_1['term_id'], 'order', 2 );
		update_term_meta( $category_2['term_id'], 'order', 1 );
		update_term_meta( $category_3['term_id'], 'order', 3 );
	}

	/**
	 * Test get_terms sorting.
	 */
	public function test_get_terms_orderby() {
		$default_category_id = absint( get_option( 'default_product_cat', 0 ) );

		// Default sort (menu_order).
		$terms = array_values(
			wp_list_pluck(
				get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => false,
						'exclude'    => $default_category_id,
					)
				),
				'name'
			)
		);
		$this->assertEquals(
			array(
				'Alpha Category',
				'Zulu Category',
				'Beta Category',
			),
			$terms,
			print_r( $terms, true ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		);

		// Force sort by name.
		$terms = array_values(
			wp_list_pluck(
				get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'orderby'    => 'name',
						'hide_empty' => false,
						'exclude'    => $default_category_id,
					)
				),
				'name'
			)
		);
		$this->assertEquals(
			array(
				'Alpha Category',
				'Beta Category',
				'Zulu Category',
			),
			$terms,
			print_r( $terms, true ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		);

		// Force sort by ID.
		$terms = array_values(
			wp_list_pluck(
				get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'orderby'    => 'term_id',
						'hide_empty' => false,
						'exclude'    => $default_category_id,
					)
				),
				'name'
			)
		);
		$this->assertEquals(
			array(
				'Zulu Category',
				'Alpha Category',
				'Beta Category',
			),
			$terms,
			print_r( $terms, true ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		);
	}
}
