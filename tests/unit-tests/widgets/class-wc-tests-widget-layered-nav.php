<?php
/**
 * Testing WC_Widget_Layered_Nav functionality.
 *
 * @package WooCommerce/Tests/Widgets
 */

/**
 * Class for testing WC_Widget_Layered_Nav functionality.
 */
class WC_Tests_Widget_Layered_Nav extends WC_Unit_Test_Case {

	/**
	 * Get an instance of the tested widget, and simulate filtering in the incoming request.
	 *
	 * @param string $filter_operation Operation supplied in the filter, 'or' or 'and'.
	 * @param array  $filter_colors Slugs of the colors supplied in the filters.
	 *
	 * @return WC_Widget_Layered_Nav An instance of WC_Widget_Layered_Nav ready to test.
	 */
	private function get_widget( $filter_operation, $filter_colors = array() ) {
		$tax_query = array(
			'relation' => 'and',
			0          => array(
				'taxonomy' => 'product_visibility',
				'terms'    => array(
					get_term_by( 'slug', 'outofstock', 'product_visibility' )->term_taxonomy_id,
					get_term_by( 'slug', 'exclude-from-catalog', 'product_visibility' )->term_taxonomy_id,
				),
				'field'    => 'term_taxonomy_id',
				'operator' => 'NOT IN',
			),
		);

		if ( ! empty( $filter_colors ) ) {
			array_push(
				$tax_query,
				array(
					'taxonomy' => 'pa_color',
					'terms'    => $filter_colors,
					'field'    => 'slug',
					'operator' => $filter_operation,
				)
			);
		}

		$sut = $this
			->getMockBuilder( WC_Widget_Layered_Nav::class )
			->setMethods( array( 'get_main_tax_query', 'get_main_meta_query', 'get_main_search_query_sql' ) )
			->getMock();

		$sut->method( 'get_main_tax_query' )->willReturn( $tax_query );
		$sut->method( 'get_main_meta_query' )->willReturn( array() );
		$sut->method( 'get_main_search_query_sql' )->willReturn( null );

		return $sut;
	}

	/**
	 * Create a simple or variable product that has color attributes.
	 * If a variable product is created, a variation will be created for each color.
	 *
	 * @param string $name Name of the product.
	 * @param array  $colors_in_stock Slugs of the colors whose variations will have stock. If null, a simple product is created.
	 * @param array  $colors_disabled Slugs of the colors whose variations will be disabled, N/A for a simple product.
	 *
	 * @return WC_Product_Simple|WC_Product_Variable The created product.
	 */
	private function create_colored_product( $name, $colors_in_stock, $colors_disabled = array() ) {
		$create_as_simple = is_null( $colors_in_stock );
		$main_product     = $create_as_simple ? new WC_Product_Simple() : new WC_Product_Variable();

		$main_product->set_props(
			array(
				'name' => $name,
				'sku'  => 'SKU for' . $name,
			)
		);

		$existing_colors = array( 'black', 'brown', 'blue', 'green', 'pink', 'yellow' );
		$attributes      = array( WC_Helper_Product::create_product_attribute_object( 'color', $existing_colors ) );
		$main_product->set_attributes( $attributes );
		$main_product->save();

		if ( $create_as_simple ) {
			return $main_product;
		}

		$variation_objects = array();
		foreach ( $existing_colors as $color ) {
			$variation_object = WC_Helper_Product::create_product_variation_object(
				$main_product->get_id(),
				"SKU for $color $name",
				10,
				array( 'pa_color' => $color )
			);
			if ( ! in_array( $color, $colors_in_stock, true ) ) {
				$variation_object->set_stock_status( 'outofstock' );
			}
			$variation_object->save();

			if ( in_array( $color, $colors_disabled, true ) ) {
				wp_update_post(
					array(
						'ID'          => $variation_object->get_id(),
						'post_status' => 'draft',
					)
				);
			}

			array_push( $variation_objects, $variation_object->get_id() );
		}

		$main_product->set_children( $variation_objects );

		return $main_product;
	}

	/**
	 * Invoke a protected method in an object.
	 *
	 * @param object $object Object whose method will be invoked.
	 * @param string $method Name of the method to invoke.
	 * @param array  $args Arguments for the method.
	 *
	 * @return mixed Result from the method invocation.
	 * @throws ReflectionException Error when dealing with reflection.
	 */
	private function invoke_protected( $object, $method, $args ) {
		$class  = new ReflectionClass( $object );
		$method = $class->getMethod( $method );
		$method->setAccessible( true );
		return $method->invokeArgs( $object, $args );
	}

	/**
	 * Invokes the get_filtered_term_product_counts method on an instance the widget,
	 * for a given filtering request, and returns the resulting counts.
	 *
	 * @param string $operator Operator in the filtering request.
	 * @param array  $colors Slugs of the colors included in the filtering request.
	 *
	 * @return array An associative array where the keys are the color slugs and the values are the counts for each color.
	 * @throws ReflectionException Error when dealing with reflection to invoke the method.
	 */
	private function run_get_filtered_term_product_counts( $operator, $colors ) {
		$sut = $this->get_widget( $operator, $colors );

		$color_terms       = get_terms( 'pa_color', array( 'hide_empty' => '1' ) );
		$color_term_ids    = wp_list_pluck( $color_terms, 'term_id' );
		$color_term_names  = wp_list_pluck( $color_terms, 'slug' );
		$color_names_by_id = array_combine( $color_term_ids, $color_term_names );

		$counts = $this->invoke_protected(
			$sut,
			'get_filtered_term_product_counts',
			array(
				$color_term_ids,
				'pa_color',
				$operator,
			)
		);

		$counts_by_name = array();
		foreach ( $counts as $id => $count ) {
			$counts_by_name[ $color_names_by_id[ $id ] ] = $count;
		}

		return $counts_by_name;
	}

	/**
	 * Changes the status of a post to 'draft'.
	 *
	 * @param int $post_id Id of the post to change.
	 */
	private function set_post_as_draft( $post_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( 'update ' . $wpdb->posts . " set post_status='draft' where ID=" . $post_id );
	}

	/**
	 * Data provider for test_product_count_per_attribute.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_product_count_per_attribute() {
		return array(
			// OR filtering, no attributes selected.
			// Should count all the visible variations of all the products.
			array(
				'or',
				array(),
				false,
				array(
					'black'  => 1,
					'brown'  => 2,
					'blue'   => 2,
					'green'  => 2,
					'pink'   => 1,
					'yellow' => 1,
				),
			),

			// OR filtering, some attributes selected
			// (doesn't matter, the result is the same as in the previous case).
			array(
				'or',
				array( 'black', 'green' ),
				false,
				array(
					'black'  => 1,
					'brown'  => 2,
					'blue'   => 2,
					'green'  => 2,
					'pink'   => 1,
					'yellow' => 1,
				),
			),

			// OR filtering, no attributes selected. Simple product is created too.
			// Now it should include all the attributes of the simple product too.
			array(
				'or',
				array(),
				true,
				array(
					'black'  => 2,
					'brown'  => 3,
					'blue'   => 3,
					'green'  => 3,
					'pink'   => 2,
					'yellow' => 2,
				),
			),

			// OR filtering, some attributes selected, Simple product is created too.
			// Again, the attributes selected don't change the result.
			array(
				'or',
				array( 'black', 'green' ),
				true,
				array(
					'black'  => 2,
					'brown'  => 3,
					'blue'   => 3,
					'green'  => 3,
					'pink'   => 2,
					'yellow' => 2,
				),
			),

			// AND filtering, no attributes selected.
			// Should count all the visible variations of all the products as in the 'or' case.
			array(
				'and',
				array(),
				false,
				array(
					'black'  => 1,
					'brown'  => 2,
					'blue'   => 2,
					'green'  => 2,
					'pink'   => 1,
					'yellow' => 1,
				),
			),

			// AND filtering, one attribute selected.
			// Should count the visible variations for all products that have the variation for
			// the selected attribute visible.
			// E.g. 2 products have 'green' visible, and of those, one has also 'blue'
			// and other has also 'pink' and 'yellow'.
			array(
				'and',
				array( 'green' ),
				false,
				array(
					'green'  => 2,
					'blue'   => 1,
					'pink'   => 1,
					'yellow' => 1,
				),
			),

			// AND filtering, more than one attribute selected.
			// Same as the previous one, but the products must have the variations for all selected attributes
			// visible.
			// E.g. only one product has both 'green' and 'pink' visible, and it has also 'yellow' visible.
			array(
				'and',
				array( 'green', 'pink' ),
				false,
				array(
					'green'  => 1,
					'pink'   => 1,
					'yellow' => 1,
				),
			),

			// AND filtering, no attributes selected, include simple product too.
			// Same case as 'or': it should include all the attributes of the simple product too.
			array(
				'and',
				array(),
				true,
				array(
					'black'  => 2,
					'brown'  => 3,
					'blue'   => 3,
					'green'  => 3,
					'pink'   => 2,
					'yellow' => 2,
				),
			),

			// AND filtering, select one attribute, include simple product too.
			// The simple product is now included in all counters, since it has the selected attribute.
			array(
				'and',
				array( 'green' ),
				true,
				array(
					'black'  => 1,
					'brown'  => 1,
					'blue'   => 2,
					'green'  => 3,
					'pink'   => 2,
					'yellow' => 2,
				),
			),

			// AND filtering, select a couple of attributes, include simple product too.
			// The simple product is included too in all counter, since it has all of the selected attributes.
			array(
				'and',
				array( 'green', 'pink' ),
				true,
				array(
					'black'  => 1,
					'brown'  => 1,
					'blue'   => 1,
					'green'  => 2,
					'pink'   => 2,
					'yellow' => 2,
				),
			),
		);
	}

	/**
	 * @testdox Test that the counters are correct for different filtering combinations, see the data provider method for details.
	 *
	 * @dataProvider data_provider_for_test_product_count_per_attribute
	 *
	 * @param string $filter_operator Filtering operator to use, 'or' or 'and'.
	 * @param array  $filter_terms Slugs of the colors selected for filtering.
	 * @param bool   $create_simple_product_too If true, create one simple product too. If false, create only the variable products.
	 * @param array  $expected_counts An associative array where the keys are the color slugs and the values are the counts for each color.
	 */
	public function test_product_count_per_attribute( $filter_operator, $filter_terms, $create_simple_product_too, $expected_counts ) {
		if ( $create_simple_product_too ) {
			$this->create_colored_product( 'Something with many colors', null );
		}
		$this->create_colored_product( 'Big shoes', array( 'black', 'brown' ) );
		$this->create_colored_product( 'Medium shoes', array( 'blue', 'brown' ) );
		$this->create_colored_product( 'Small shoes', array( 'blue', 'green' ) );
		$this->create_colored_product( 'Kids shoes', array( 'green', 'pink', 'yellow' ) );

		$counts = $this->run_get_filtered_term_product_counts( $filter_operator, $filter_terms );
		$this->assertEquals( $expected_counts, $counts );
	}

	/**
	 * @testdox When a variable product is not published, none of its variations should be included in the counts.
	 *
	 * @throws ReflectionException Error when dealing with reflection to invoke the tested method.
	 */
	public function test_product_count_per_attribute_with_parent_not_published() {
		$this->create_colored_product( 'Big shoes', array( 'black', 'brown' ) );
		$medium = $this->create_colored_product( 'Medium shoes', array( 'blue', 'brown' ) );
		$this->set_post_as_draft( $medium->get_id() );

		$actual = $this->run_get_filtered_term_product_counts( 'or', array() );

		$expected = array(
			'black' => 1,
			'brown' => 1,
		);
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox When a variation is not published it should not be included in the counts (but other variations of the same product should).
	 *
	 * @throws ReflectionException Error when dealing with reflection to invoke the tested method.
	 */
	public function test_product_count_per_attribute_with_variation_not_published() {
		$this->create_colored_product( 'Big shoes', array( 'black', 'brown' ) );
		$this->create_colored_product( 'Medium shoes', array( 'blue', 'brown' ), array( 'brown' ) );

		$actual = $this->run_get_filtered_term_product_counts( 'or', array() );

		$expected = array(
			'black' => 1,
			'brown' => 1,
			'blue'  => 1,
		);
		$this->assertEquals( $expected, $actual );
	}
}
