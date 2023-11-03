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
	 * Test get_terms sorting.
	 */
	public function test_get_terms_orderby() {
		$default_category_id = absint( get_option( 'default_product_cat', 0 ) );
		$category_1          = wp_insert_term( 'Zulu Category', 'product_cat' );
		$category_2          = wp_insert_term( 'Alpha Category', 'product_cat' );
		$category_3          = wp_insert_term( 'Beta Category', 'product_cat' );
		update_term_meta( $category_1['term_id'], 'order', 2 );
		update_term_meta( $category_2['term_id'], 'order', 1 );
		update_term_meta( $category_3['term_id'], 'order', 3 );

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

		// Explicit menu_order sort, backwards.
		$terms = array_values(
			wp_list_pluck(
				get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => false,
						'orderby'    => 'menu_order',
						'order'      => 'DESC',
						'exclude'    => $default_category_id,
					)
				),
				'name'
			)
		);
		$this->assertEquals(
			array(
				'Beta Category',
				'Zulu Category',
				'Alpha Category',
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

	/**
	 * Test get_terms sorting for name_num.
	 */
	public function test_get_terms_orderby_name_num() {
		$default_category_id = absint( get_option( 'default_product_cat', 0 ) );
		wp_insert_term( '1', 'product_cat' );
		wp_insert_term( '2', 'product_cat' );
		wp_insert_term( '3', 'product_cat' );
		wp_insert_term( '4', 'product_cat' );
		wp_insert_term( '5', 'product_cat' );
		wp_insert_term( '10', 'product_cat' );
		wp_insert_term( '9', 'product_cat' );
		wp_insert_term( '8', 'product_cat' );
		wp_insert_term( '7', 'product_cat' );
		wp_insert_term( '6', 'product_cat' );

		// by name.
		$terms = array_values(
			wp_list_pluck(
				get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => false,
						'orderby'    => 'name',
						'exclude'    => $default_category_id,
					)
				),
				'name'
			)
		);
		$this->assertEquals(
			array(
				'1',
				'10',
				'2',
				'3',
				'4',
				'5',
				'6',
				'7',
				'8',
				'9',
			),
			$terms,
			print_r( $terms, true ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		);

		// by numeric name.
		$terms = array_values(
			wp_list_pluck(
				get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => false,
						'orderby'    => 'name_num',
						'exclude'    => $default_category_id,
					)
				),
				'name'
			)
		);
		$this->assertEquals(
			array(
				'1',
				'2',
				'3',
				'4',
				'5',
				'6',
				'7',
				'8',
				'9',
				'10',
			),
			$terms,
			print_r( $terms, true ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		);
	}
}
