<?php
declare( strict_types = 1 );

/**
 * Tests for wc-template-functions.php.
 *
 * @package WooCommerce\Tests\Includes
 */
class WC_Template_Functions_Tests extends \WC_Unit_Test_Case {

	/**
	 * Helper: create a parent product category with child categories and products.
	 *
	 * @return int Parent category term ID.
	 */
	private function create_category_tree(): int {
		$parent = wp_insert_term( 'Test Parent', 'product_cat' );
		if ( is_wp_error( $parent ) ) {
			throw new \RuntimeException( esc_html( $parent->get_error_message() ) );
		}
		$parent_id = $parent['term_id'];

		update_term_meta( $parent_id, 'display_type', 'both' );

		for ( $i = 1; $i <= 3; $i++ ) {
			$child = wp_insert_term(
				"Test Child $i",
				'product_cat',
				array( 'parent' => $parent_id )
			);
			if ( is_wp_error( $child ) ) {
				throw new \RuntimeException( esc_html( $child->get_error_message() ) );
			}

			$product = \WC_Helper_Product::create_simple_product();
			$product->set_category_ids( array( $child['term_id'] ) );
			$product->save();
		}

		wp_update_term_count_now(
			get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'fields'     => 'ids',
					'hide_empty' => 0,
				)
			),
			'product_cat'
		);

		return $parent_id;
	}

	/**
	 * Clean up cache between tests.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_cache_flush();
	}

	/**
	 * @testdox woocommerce_get_product_subcategories caches results under the expected key.
	 */
	public function test_subcategories_are_cached_under_expected_key(): void {
		$parent_id = $this->create_category_tree();
		$cache_key = 'product-category-hierarchy-' . $parent_id;

		// Cache should be empty before the call.
		$this->assertFalse( wp_cache_get( $cache_key, 'product_cat' ) );

		$result = woocommerce_get_product_subcategories( $parent_id );

		// Cache should be populated after the call.
		$cached = wp_cache_get( $cache_key, 'product_cat' );
		$this->assertNotFalse( $cached );
		$this->assertCount( 3, $cached );
		$this->assertSame( $result, $cached );
	}

	/**
	 * @testdox woocommerce_get_product_subcategories does not cache when taxonomy is cleared by filter.
	 */
	public function test_cache_is_skipped_when_taxonomy_is_cleared(): void {
		$parent_id = $this->create_category_tree();
		$cache_key = 'product-category-hierarchy-' . $parent_id;

		$filter = function ( $args ) {
			$args['taxonomy'] = '';
			return $args;
		};
		add_filter( 'woocommerce_product_subcategories_args', $filter );

		$result = woocommerce_get_product_subcategories( $parent_id );

		// Cache should remain empty because taxonomy was cleared.
		$this->assertFalse( wp_cache_get( $cache_key, 'product_cat' ) );
		// Result should be empty too (query with empty taxonomy returns nothing).
		$this->assertEmpty( $result );

		remove_filter( 'woocommerce_product_subcategories_args', $filter );
	}

	/**
	 * @testdox woocommerce_get_product_subcategories does not cache when taxonomy is missing after filter.
	 */
	public function test_cache_is_skipped_when_taxonomy_is_missing(): void {
		$parent_id = $this->create_category_tree();
		$cache_key = 'product-category-hierarchy-' . $parent_id;

		$filter = function ( $args ) {
			unset( $args['taxonomy'] );
			return $args;
		};
		add_filter( 'woocommerce_product_subcategories_args', $filter );

		woocommerce_get_product_subcategories( $parent_id );

		// Cache should remain empty because taxonomy was removed.
		$this->assertFalse( wp_cache_get( $cache_key, 'product_cat' ) );

		remove_filter( 'woocommerce_product_subcategories_args', $filter );
	}

	/**
	 * @testdox woocommerce_get_product_subcategories caches normally after filter is removed.
	 */
	public function test_cache_works_normally_after_filter_removed(): void {
		$parent_id = $this->create_category_tree();
		$cache_key = 'product-category-hierarchy-' . $parent_id;

		// First call with filter that clears taxonomy.
		$filter = function ( $args ) {
			$args['taxonomy'] = '';
			return $args;
		};
		add_filter( 'woocommerce_product_subcategories_args', $filter );
		woocommerce_get_product_subcategories( $parent_id );
		$this->assertFalse( wp_cache_get( $cache_key, 'product_cat' ) );
		remove_filter( 'woocommerce_product_subcategories_args', $filter );

		// Second call without filter should cache normally.
		$result = woocommerce_get_product_subcategories( $parent_id );
		$cached = wp_cache_get( $cache_key, 'product_cat' );
		$this->assertNotFalse( $cached );
		$this->assertCount( 3, $cached );
	}

	/**
	 * @testdox Quantity input hooks receive the final input type.
	 * @dataProvider quantity_input_type_provider
	 *
	 * @param array  $args          Quantity input arguments.
	 * @param string $expected_type Expected input type.
	 */
	public function test_quantity_input_hooks_receive_input_type( array $args, string $expected_type ): void {
		$before_types = array();
		$after_types  = array();

		$before_callback = static function ( string $type ) use ( &$before_types ): void {
			$before_types[] = $type;
		};
		$after_callback  = static function ( string $type ) use ( &$after_types ): void {
			$after_types[] = $type;
		};

		add_action( 'woocommerce_before_quantity_input_field', $before_callback );
		add_action( 'woocommerce_after_quantity_input_field', $after_callback );

		woocommerce_quantity_input( $args, new WC_Product_Simple(), false );

		remove_action( 'woocommerce_before_quantity_input_field', $before_callback );
		remove_action( 'woocommerce_after_quantity_input_field', $after_callback );

		$this->assertSame( array( $expected_type ), $before_types, 'The before hook should receive the final input type.' );
		$this->assertSame( array( $expected_type ), $after_types, 'The after hook should receive the final input type.' );
	}

	/**
	 * Data provider for quantity input types.
	 *
	 * @return array<string, array{array<string, int|string>, string}>
	 */
	public static function quantity_input_type_provider(): array {
		return array(
			'changeable quantity' => array(
				array(
					'min_value' => 1,
					'max_value' => '',
				),
				'number',
			),
			'fixed quantity'      => array(
				array(
					'min_value' => 1,
					'max_value' => 1,
				),
				'hidden',
			),
		);
	}
}
