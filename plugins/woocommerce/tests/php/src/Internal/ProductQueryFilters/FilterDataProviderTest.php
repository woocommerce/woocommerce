<?php

namespace Automattic\WooCommerce\Tests\Internal\ProductQueryFilters;

use Automattic\WooCommerce\Internal\ProductQueryFilters\FilterDataProvider;

/**
 * Tests related to FilterClausesGenerator service.
 */
class FilterDataProviderTest extends AbstractProductQueryFiltersTest {
	/**
	 * The system under test.
	 *
	 * @var DataRegenerator
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$container = wc_get_container();
		$this->sut = $container->get( FilterDataProvider::class );
	}

	public function test_get_filtered_price() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );

		$this->test_get_filtered_price_with( $wp_query );


		$wp_query->set( 'filter_stock_status', 'instock' );
		$this->test_get_filtered_price_with( $wp_query, function( $product_data ) {
			return $product_data['stock_status'] === 'instock';
		} );

		$wp_query->set( 'filter_stock_status', 'outofstock,onbackorder' );
		$this->test_get_filtered_price_with( $wp_query, function( $product_data ) {
			return $product_data['stock_status'] === 'outofstock' ||
				$product_data['stock_status'] === 'onbackorder';
		} );
	}

	public function test_get_stock_status_counts() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );

		$this->test_get_stock_status_counts_with( $wp_query );

		$wp_query->set( 'min_price', 20 );
		$this->test_get_stock_status_counts_with( $wp_query, function( $product_data ) {
			if ( ! isset( $product_data['variations'] ) ) {
				return $product_data['regular_price'] >= 20;
			}

			foreach( $product_data['variations'] as $variation_data ) {
				if( $variation_data['props']['regular_price'] < 20 ) {
					return false;
				}
			}
			return true;
		} );
	}

	private function test_get_stock_status_counts_with( $wp_query, $filter_callback = null ) {
		$query_vars = array_filter( $wp_query->query_vars );

		$actual_stock_status_counts = (array) $this->sut->get_stock_status_counts( $query_vars );

		$expected_stock_status_counts = array(
			'instock' => 0,
			'outofstock' => 0,
			'onbackorder' => 0,
		);

		if( $filter_callback ) {
			$filtered_product_data = array_filter(
				$this->products_data,
				$filter_callback
			);
		} else {
			$filtered_product_data = $this->products_data;
		}

		foreach($filtered_product_data as $product_data ) {
			$expected_stock_status_counts[$product_data['stock_status']] += 1;
		}
		var_dump( $expected_stock_status_counts, $actual_stock_status_counts );

		$this->assertEqualsCanonicalizing( $expected_stock_status_counts, $actual_stock_status_counts );
	}

	private function test_get_filtered_price_with( $wp_query, $filter_callback = null ) {
		$query_vars = array_filter( $wp_query->query_vars );

		$prices = array();

		if( $filter_callback ) {
			$filtered_product_data = array_filter(
				$this->products_data,
				$filter_callback
			);
		} else {
			$filtered_product_data = $this->products_data;
		}

		foreach( $filtered_product_data as $product_data ) {
			$prices[] = $product_data['regular_price'] ?? null;

			if ( isset( $product_data['variations'] ) ) {
				foreach( $product_data['variations'] as $variation_data ) {
					$prices[] = $variation_data['props']['regular_price'] ?? null;
				}
			}
		}

		$prices = array_filter( $prices );
		$prices = array_map( 'intval', $prices );

		$expected_price_range = array(
			'min_price' => min($prices),
			'max_price' => max($prices),
		);

		$actual_price_range = (array) $this->sut->get_filtered_price( $query_vars );

		$this->assertEqualsCanonicalizing( $expected_price_range, $actual_price_range );
	}


}
