<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\FilterParamNormalizer;

/**
 * Tests for FilterParamNormalizer.
 */
class FilterParamNormalizerTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox Sorting filter values: red,blue and blue,red produce the same result.
	 */
	public function test_normalize_sorts_multi_value_filter_param() {
		$a = FilterParamNormalizer::normalize( array( 'filter_color' => 'red,blue' ) );
		$b = FilterParamNormalizer::normalize( array( 'filter_color' => 'blue,red' ) );

		$this->assertSame( $a['filter_color'], $b['filter_color'] );
		$this->assertSame( 'blue,red', $a['filter_color'] );
	}

	/**
	 * @testdox Normalising filter values: uppercase and whitespace are stripped.
	 */
	public function test_normalize_lowercases_and_trims_filter_values() {
		$result = FilterParamNormalizer::normalize( array( 'filter_color' => ' Red , BLUE ' ) );

		$this->assertSame( 'blue,red', $result['filter_color'] );
	}

	/**
	 * @testdox rating_filter values are sorted and normalised.
	 */
	public function test_normalize_sorts_rating_filter() {
		$a = FilterParamNormalizer::normalize( array( 'rating_filter' => '5,3,4' ) );
		$b = FilterParamNormalizer::normalize( array( 'rating_filter' => '3,4,5' ) );

		$this->assertSame( $a['rating_filter'], $b['rating_filter'] );
		$this->assertSame( '3,4,5', $a['rating_filter'] );
	}

	/**
	 * @testdox query_type_* values are lowercased and trimmed.
	 */
	public function test_normalize_lowercases_query_type_param() {
		$result = FilterParamNormalizer::normalize( array( 'query_type_color' => ' AND ' ) );

		$this->assertSame( 'and', $result['query_type_color'] );
	}

	/**
	 * @testdox min_price and max_price are trimmed.
	 */
	public function test_normalize_trims_price_params() {
		$result = FilterParamNormalizer::normalize(
			array(
				'min_price' => ' 10 ',
				'max_price' => ' 100 ',
			)
		);

		$this->assertSame( '10', $result['min_price'] );
		$this->assertSame( '100', $result['max_price'] );
	}

	/**
	 * @testdox Keys are sorted alphabetically regardless of insertion order.
	 */
	public function test_normalize_sorts_keys_alphabetically() {
		$a = FilterParamNormalizer::normalize(
			array(
				'post_type'    => 'product',
				'filter_color' => 'red',
				'min_price'    => '10',
			)
		);

		$b = FilterParamNormalizer::normalize(
			array(
				'min_price'    => '10',
				'post_type'    => 'product',
				'filter_color' => 'red',
			)
		);

		$this->assertSame( array_keys( $a ), array_keys( $b ) );
		$this->assertSame( wp_json_encode( $a ), wp_json_encode( $b ) );
	}

	/**
	 * @testdox Non-string values are left untouched.
	 */
	public function test_normalize_leaves_non_string_values_intact() {
		$result = FilterParamNormalizer::normalize(
			array(
				'posts_per_page' => -1,
				'tax_query'      => array( 'relation' => 'AND' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'post_status'    => 'publish',
			)
		);

		$this->assertSame( -1, $result['posts_per_page'] );
		$this->assertSame( array( 'relation' => 'AND' ), $result['tax_query'] );
	}

	/**
	 * @testdox Two calls with identical filter params but different orderings produce the same JSON hash.
	 */
	public function test_normalize_produces_identical_json_for_equivalent_filter_params() {
		$vars_a = array(
			'filter_color' => 'red,blue,green',
			'filter_size'  => 'xl,s,m',
			'min_price'    => '10',
			'post_type'    => 'product',
		);

		$vars_b = array(
			'post_type'    => 'product',
			'filter_size'  => 'm,xl,s',
			'min_price'    => '10',
			'filter_color' => 'green,red,blue',
		);

		$a = FilterParamNormalizer::normalize( $vars_a );
		$b = FilterParamNormalizer::normalize( $vars_b );

		$this->assertSame( wp_json_encode( $a ), wp_json_encode( $b ) );
	}

	/**
	 * @testdox filter_stock_status (starts with filter_) is treated as multi-value.
	 */
	public function test_normalize_sorts_filter_stock_status() {
		$a = FilterParamNormalizer::normalize( array( 'filter_stock_status' => 'instock,outofstock' ) );
		$b = FilterParamNormalizer::normalize( array( 'filter_stock_status' => 'outofstock,instock' ) );

		$this->assertSame( $a['filter_stock_status'], $b['filter_stock_status'] );
	}
}
