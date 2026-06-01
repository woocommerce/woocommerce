<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

/**
 * Tests for the ProductFilterAttribute block type.
 */
class ProductFilterAttributeTest extends \WP_UnitTestCase {

	/**
	 * Test that rendering returns empty string when the attribute ID references a deleted taxonomy.
	 *
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/63791
	 */
	public function test_render_returns_empty_for_deleted_attribute() {
		$non_existent_attribute_id = 999999;

		$block_markup = sprintf(
			'<!-- wp:woocommerce/product-filter-attribute {"attributeId":%d,"queryType":"or","sortOrder":"name-asc"} -->
			<div class="wp-block-woocommerce-product-filter-attribute"></div>
			<!-- /wp:woocommerce/product-filter-attribute -->',
			$non_existent_attribute_id
		);

		$blocks = parse_blocks( $block_markup );
		$output = render_block( $blocks[0] );

		$this->assertSame( '', $output );
	}
}
