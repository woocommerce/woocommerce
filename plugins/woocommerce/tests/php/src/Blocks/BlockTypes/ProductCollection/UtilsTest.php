<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use WC_Unit_Test_Case;

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
}
