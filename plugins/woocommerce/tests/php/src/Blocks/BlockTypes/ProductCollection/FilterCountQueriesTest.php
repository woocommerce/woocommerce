<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductFilterAttribute;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductFilterRating;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductFilterStatus;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductFilterTaxonomy;
use WC_Unit_Test_Case;

/**
 * Tests Product Collection constraints in Product Filter count queries.
 */
class FilterCountQueriesTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Saved Product Collection constraints remain in Product Filter count queries.
	 */
	public function test_saved_collection_constraints_remain_in_count_queries(): void {
		global $wp_query;

		$previous_query_vars = $wp_query->query_vars;
		$saved_tax_query     = array(
			array(
				'taxonomy' => 'pa_color',
				'field'    => 'term_id',
				'terms'    => array( 1 ),
			),
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => array( 2 ),
			),
		);
		$query_vars          = array(
			'isProductCollection' => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'          => array(
				array(
					'key'   => '_stock_status',
					'value' => 'instock',
				),
			),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'           => array_merge(
				$saved_tax_query,
				array(
					array(
						'taxonomy'      => 'product_visibility',
						'field'         => 'term_taxonomy_id',
						'terms'         => array( 3 ),
						'rating_filter' => true,
					),
				)
			),
		);
		$wp_query->query_vars = $query_vars;

		$block = (object) array(
			'context' => array(
				'filterParams' => array(),
				'query'        => array( 'inherit' => true ),
			),
		);

		$captured_query_vars = array();
		$capture_query_vars  = function ( $results, $filter_type, $filter_query_vars ) use ( &$captured_query_vars ) {
			$captured_query_vars[ $filter_type ] = $filter_query_vars;
			return array();
		};

		add_filter( 'woocommerce_pre_product_filter_data', $capture_query_vars, 10, 3 );
		try {
			$this->invoke_private_method( ProductFilterStatus::class, 'get_stock_status_counts', array( $block ) );
			$this->invoke_private_method( ProductFilterAttribute::class, 'get_attribute_counts', array( $block, 'pa_color', 'or' ) );
			$this->invoke_private_method( ProductFilterTaxonomy::class, 'get_taxonomy_term_counts', array( $block, 'product_cat' ) );
			$this->invoke_private_method( ProductFilterRating::class, 'get_rating_counts', array( $block ) );
		} finally {
			remove_filter( 'woocommerce_pre_product_filter_data', $capture_query_vars, 10 );
			$wp_query->query_vars = $previous_query_vars;
		}

		$this->assertSame( $query_vars['meta_query'], $captured_query_vars['stock']['meta_query'] );
		$this->assertSame( $query_vars['tax_query'], $captured_query_vars['stock']['tax_query'] );
		$this->assertSame( $query_vars['tax_query'], $captured_query_vars['attribute']['tax_query'] );
		$this->assertSame( $query_vars['tax_query'], $captured_query_vars['taxonomy']['tax_query'] );
		$this->assertSame( $saved_tax_query, $captured_query_vars['rating']['tax_query'] );
	}

	/**
	 * Invoke a private method without running the block constructor.
	 *
	 * @param string $class_name  Block class name.
	 * @param string $method_name Method name.
	 * @param array  $args        Method arguments.
	 * @return mixed
	 */
	private function invoke_private_method( string $class_name, string $method_name, array $args ) {
		$reflection = new \ReflectionClass( $class_name );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $reflection->newInstanceWithoutConstructor(), $args );
	}
}
