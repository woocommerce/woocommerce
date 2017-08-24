<?php

/**
 * Class WC_Shortcodes.
 * @package WooCommerce\Tests\Shortcodes
 */
class WC_Tests_WC_Shortcodes extends WC_Unit_Test_Case {

	/**
	 * Test: WC_Shortcodes::products
	 */
	public function test_products() {

		// Empty shortcode
		$this->assertEquals( '', WC_Shortcodes::products( '' ) );
	}

	/**
	 * Test: WC_Shortcodes::products_query_args
	 */
	public function test_products_query_args_defaults() {

		$default_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'no_found_rows'       => 1,
			'orderby'             => 'menu_order title', // default from WC()->query->get_catalog_ordering_args()
			'order'               => 'DESC',
			'posts_per_page'      => '1',
			'tax_query'           => WC()->query->get_tax_query(),
			'meta_query'          => WC()->query->get_meta_query(),
		);

		// All of the default args should be the same
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'ids' => '99',
			),
			$default_args
		) );

		// orderby has been changed
		$this->assertNotEmpty( $this->query_args_array_diff(
			array(
				'orderby' => 'date'
			),
			$default_args
			) );
	}

	/**
	 * Test: WC_Shortcodes::products_query_args
	 */
	public function test_products_query_args_ordering() {

		// menu_order (falls back on title automatically)
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'orderby' => 'menu_order',
				'order'   => 'desc',
			),
			array(
				'orderby' => 'menu_order title',
				'order'   => 'DESC',
			)
		) );

		// title
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'orderby' => 'title',
				'order'   => 'asc',
			),
			array(
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		) );

		// date (falls back on ID automatically)
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'orderby' => 'date',
				'order'   => 'asc',
			),
			array(
				'orderby' => 'date ID',
				'order'   => 'ASC',
			)
		) );

		// random
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'orderby' => 'rand',
			),
			array(
				'orderby' => 'rand',
			)
		) );

		// TODO: add price orderby

		// popularity (overrides order)
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'orderby' => 'popularity',
				'order'   => 'asc',
			),
			array(
				'meta_key' => 'total_sales',
				'orderby'  => array(
					'meta_value_num' => 'DESC',
					'post_date'      => 'DESC',
				),
			)
		) );

		// rating
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'orderby' => 'rating',
				'order'   => 'desc',
			),
			array(
				'meta_key' => '_wc_average_rating',
				'orderby'  => array(
					'meta_value_num' => 'DESC',
					'ID'             => 'ASC',
				),
			)
		) );

		// id
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'orderby' => 'id',
				'order'   => 'asc',
			),
			array(
				'orderby' => 'ID',
				'order'   => 'ASC',
			)
		) );
	}

	/**
	 * Test: WC_Shortcodes::products_query_args
	 */
	public function test_products_query_args_taxonomy() {

		// Visibility tax query will always be present for now
		$visibility = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_taxonomy_id',
			'operator' => 'NOT IN',
		);

		// visibility
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'orderby' => 'title',
				'order'   => 'desc',
			),
			array(
				'tax_query' => array( $visibility ),
			)
		) );

		// category
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'category'     => '12, 19',
				'cat_operator' => 'NOT IN',
			),
			array(
				'tax_query' => array(
					$visibility,
					array(
						'taxonomy' => 'product_cat',
						'terms'    => array( 12, 19 ),
						'field'    => 'slug',
						'operator' => 'NOT IN',
					)
				),
		)
	) );

		// featured
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'featured' => true,
			),
			array(
				'tax_query' => array(
					$visibility,
					array(
						'taxonomy' => 'product_visibility',
						'terms'    => 'featured',
						'field'    => 'name',
						'operator' => 'IN',
					)
				),
		)
	) );

		// attribute
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'attribute'     => 'color',
				'attr_terms'    => 'black, white',
				'attr_operator' => 'AND',
			),
			array(
				'tax_query' => array(
					$visibility,
					array(
						'taxonomy' => 'pa_color',
						'terms'    => array( 'black', 'white' ),
						'field'    => 'slug',
						'operator' => 'AND'
					)
				),
		)
	) );
	}

	/**
	 * Test: WC_Shortcodes::products_query_args
	 */
	public function test_products_query_args_misc() {

		// Product SKUs
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'skus' => 'woo1, woo2',
			),
			array(
				'meta_query' => array( array(
				'key'        => '_sku',
				'compare'    => 'IN',
				'value'      => array( 'woo1', 'woo2' ),
				) )
		)
	) );

		// Product IDs
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'ids' => '97, 98',
			),
			array(
				'post__in' => array( '97', '98' ),
			)
		) );

		// on_sale
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'on_sale' => true,
			),
			array(
				'post__in' => array_merge( array( 0 ), wc_get_product_ids_on_sale() ),
				)
			) );

		// posts_per_page
		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'limit' => '19',
			),
			array(
				'posts_per_page' => '19',
			)
		) );

		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'ids'   => '97, 98',
				'skus'  => 'woo1, woo2',
				'limit' => '1',
			),
			array(
				'posts_per_page' => '4',
			)
		) );

		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'ids'   => '97, 98',
				'limit' => '-1',
			),
			array(
				'posts_per_page' => '-1',
			)
		) );

		$this->assertEmpty( $this->query_args_array_diff(
			array(
				'limit'    => '20',
				'per_page' => '10'
			),
			array(
				'posts_per_page' => '10',
			)
		) );
	}

	/**
	 * Helper method for products_query_args array_diff checks.
	 *
	 * All of the $results array should be found based on what is
	 * returned from the shortcodes atts query.
	 *
	 * @param array $shortcode_atts (what would have been passed into shortcode)
	 * @param array $results (the resulting query args)
	 * @return array
	 */
	private function query_args_array_diff( $shortcode_atts, $results ) {

		$query_args = WC_Shortcodes::products_query_args( $shortcode_atts, 'products' );

		return $this->array_diff_assoc_recursive( $results, $query_args );
	 }

	/**
	 * Helper method to do a recursive array diff check.
	 * http://php.net/manual/en/function.array-diff-assoc.php#73972
	 *
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 */
	private function array_diff_assoc_recursive( $array1, $array2 ) {

		foreach( $array1 as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( ! isset( $array2[ $key ] ) ) {
					$difference[ $key ] = $value;
				} else if ( ! is_array( $array2[ $key ] ) ) {
					$difference[ $key ] = $value;
				} else {
					$new_diff = $this->array_diff_assoc_recursive( $value, $array2[ $key ] );
					if( $new_diff != FALSE ) {
						$difference[ $key ] = $new_diff;
					}
				}
			} else if ( ! isset( $array2[ $key ]) || $array2[ $key ] != $value ) {
				$difference[ $key ] = $value;
			}
		}

		return  ! isset( $difference ) ? 0 : $difference;
	}

}
