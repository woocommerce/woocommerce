<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use WC_Unit_Test_Case;
use WP_Block;

/**
 * Tests for Product Collection query utilities.
 */
class UtilsTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should remove nested matching clauses without changing saved query values.
	 */
	public function test_removes_nested_filters_without_changing_saved_constraints(): void {
		$saved_taxonomy = array(
			'taxonomy'         => 'product_cat',
			'terms'            => array( 12, 0 ),
			'include_children' => false,
		);
		$saved_meta     = array(
			'key'     => '_price',
			'value'   => 0,
			'compare' => '>=',
		);
		$queries        = array(
			'relation' => 'AND',
			'saved'    => $saved_taxonomy,
			'price'    => $saved_meta,
			'shopper'  => array(
				'relation' => 'OR',
				'other'    => array(
					'taxonomy' => 'product_tag',
					'terms'    => array( 23 ),
				),
				'rating'   => array(
					'taxonomy'      => 'product_visibility',
					'terms'         => array( 34 ),
					'rating_filter' => true,
				),
			),
		);

		$result = ProductCollectionUtils::remove_query_array( $queries, 'rating_filter', true );

		$this->assertArrayNotHasKey( 'rating', $result['shopper'] );
		$this->assertSame( $saved_taxonomy, $result['saved'], 'Saved taxonomy values, including false and zero, must remain exact.' );
		$this->assertSame( $saved_meta, $result['price'], 'Zero-valued price constraints must not change.' );
		$this->assertSame( 'OR', $result['shopper']['relation'] );
		$this->assertSame( array( 23 ), $result['shopper']['other']['terms'] );
		$this->assertSame( 'AND', $result['relation'] );
	}

	/**
	 * @testdox Should prune groups after removing their last matching clause.
	 */
	public function test_prunes_empty_query_groups(): void {
		$queries = array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array(
					'taxonomy' => 'product_cat',
					'terms'    => array( 12 ),
				),
			),
		);

		$result = ProductCollectionUtils::remove_query_array( $queries, 'taxonomy', 'product_cat' );

		$this->assertSame( array(), $result );
	}

	/**
	 * @testdox Should preserve empty term lists and falsy values in untouched clauses.
	 */
	public function test_preserves_untouched_leaf_values(): void {
		$queries = array(
			array(
				'taxonomy'         => 'product_cat',
				'terms'            => array(),
				'include_children' => false,
			),
			array(
				'key'   => '_custom_value',
				'value' => array( false, 0, '0', '', null ),
			),
		);

		$result = ProductCollectionUtils::remove_query_array( $queries, 'rating_filter', true );

		$this->assertSame( $queries, $result );
	}

	/**
	 * @testdox Should preserve empty term lists in clauses without a taxonomy.
	 */
	public function test_preserves_term_taxonomy_id_clauses_without_taxonomy(): void {
		$queries = array(
			array(
				'field' => 'term_taxonomy_id',
				'terms' => array(),
			),
		);

		$result = ProductCollectionUtils::remove_query_array( $queries, 'rating_filter', true );

		$this->assertSame( $queries, $result );
	}

	/**
	 * @testdox Should match filter values strictly, including false.
	 */
	public function test_matches_filter_values_strictly(): void {
		$queries = array(
			array(
				'rating_filter' => true,
				'taxonomy'      => 'product_visibility',
			),
			array(
				'rating_filter' => false,
				'taxonomy'      => 'product_visibility',
			),
			array(
				'rating_filter' => 0,
				'taxonomy'      => 'product_visibility',
			),
		);

		$result = ProductCollectionUtils::remove_query_array( $queries, 'rating_filter', false );

		$this->assertArrayNotHasKey( 1, $result );
		$this->assertTrue( $result[0]['rating_filter'] );
		$this->assertArrayHasKey( 'rating_filter', $result[2] );
		$this->assertSame( 0, $result[2]['rating_filter'] );
	}

	/**
	 * @testdox Should build query vars from the block unless the collection inherits the global query.
	 * @testWith [{"postType": "product", "inherit": false}, "block"]
	 *           [{"postType": "product"}, "block"]
	 *           [{"postType": "product", "inherit": true}, "global"]
	 *           [null, "global"]
	 *
	 * @param array|null $query_context   Query block context, or null when the block has none.
	 * @param string     $expected_source Which query the vars must come from.
	 */
	public function test_get_query_vars_uses_global_query_only_when_inherited( ?array $query_context, string $expected_source ): void {
		global $wp_query;
		$wp_query->query_vars = array( 'source' => 'global' );
		add_filter(
			'query_loop_block_query_vars',
			static function () {
				return array( 'source' => 'block' );
			},
			PHP_INT_MAX
		);

		$block = new WP_Block(
			array( 'blockName' => 'core/post-template' ),
			null === $query_context ? array() : array( 'query' => $query_context )
		);

		$query_vars = ProductCollectionUtils::get_query_vars( $block, 1 );

		$this->assertSame( $expected_source, $query_vars['source'] ?? null );
	}
}
