<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Unit_Test_Case;

/**
 * Tests for the ProductFilterAttribute block type.
 */
class ProductFilterAttributeTest extends WC_Unit_Test_Case {

	/**
	 * Attribute counts returned by the product filter data hook.
	 *
	 * @var array
	 */
	private $attribute_counts = array();

	/**
	 * Attribute IDs created during tests.
	 *
	 * @var array
	 */
	private $attribute_ids = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'woocommerce_pre_product_filter_data', array( $this, 'filter_product_filter_data' ), 10, 4 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_pre_product_filter_data', array( $this, 'filter_product_filter_data' ), 10 );

		foreach ( $this->attribute_ids as $attribute_id ) {
			wc_delete_attribute( $attribute_id );
		}

		if ( taxonomy_exists( 'pa_material' ) ) {
			unregister_taxonomy( 'pa_material' );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should render empty output when the attribute ID references a deleted taxonomy.
	 *
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/63791.
	 */
	public function test_render_returns_empty_for_deleted_attribute(): void {
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

	/**
	 * @testdox Should render decoded attribute term labels in checkbox filters.
	 */
	public function test_renders_decoded_attribute_term_labels_in_checkbox_filters(): void {
		$attribute_id = wc_create_attribute(
			array(
				'name' => 'Material',
				'slug' => 'material',
			)
		);

		$this->assertIsInt( $attribute_id, 'Attribute should be created.' );

		$this->attribute_ids[] = $attribute_id;

		if ( ! taxonomy_exists( 'pa_material' ) ) {
			register_taxonomy( 'pa_material', array( 'product' ), array( 'labels' => array( 'name' => 'Material' ) ) );
		}

		$term = wp_insert_term( 'Cotton & Linen', 'pa_material' );

		$this->assertNotWPError( $term );

		$term_id                       = (int) $term['term_id'];
		$this->attribute_counts        = array( $term_id => 1 );
		$stored_term                   = get_term( $term_id, 'pa_material' );
		$expected_serialized_label     = 'Cotton &amp; Linen';
		$expected_context_label        = 'Cotton \\u0026 Linen';
		$double_encoded_serialization  = 'Cotton &amp;amp; Linen';
		$double_encoded_context_entity = '\\u0026amp;';

		$this->assertSame( 'Cotton &amp; Linen', $stored_term->name, 'Term fixture should use WordPress encoded storage.' );

		$output = $this->render_attribute_filter_with_checkbox_list( $attribute_id );

		$this->assertStringContainsString( $expected_serialized_label, $output, 'Rendered label should be serialized once for HTML output.' );
		$this->assertStringContainsString( $expected_context_label, $output, 'Interactivity context should contain the decoded label.' );
		$this->assertStringNotContainsString( $double_encoded_serialization, $output, 'Rendered label should not be double-encoded.' );
		$this->assertStringNotContainsString( $double_encoded_context_entity, $output, 'Interactivity context should not contain an encoded entity label.' );
	}

	/**
	 * Render an attribute filter block with a checkbox list inner block.
	 *
	 * @param int $attribute_id Attribute ID.
	 * @return string
	 */
	private function render_attribute_filter_with_checkbox_list( int $attribute_id ): string {
		$attribute_block = array(
			'blockName'    => 'woocommerce/product-filter-attribute',
			'attrs'        => array(
				'attributeId' => $attribute_id,
				'hideEmpty'   => false,
				'queryType'   => 'or',
				'sortOrder'   => 'name-asc',
			),
			'innerBlocks'  => array(
				array(
					'blockName'    => 'woocommerce/product-filter-checkbox-list',
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				),
			),
			'innerHTML'    => '',
			'innerContent' => array( null ),
		);
		$parsed_block    = array(
			'blockName'    => 'woocommerce/product-filters',
			'attrs'        => array( 'showFilterDrawer' => false ),
			'innerBlocks'  => array( $attribute_block ),
			'innerHTML'    => '',
			'innerContent' => array( null ),
		);

		return ( new \WP_Block( $parsed_block ) )->render();
	}

	/**
	 * Filter product filter data for tests.
	 *
	 * @param mixed  $results     Filter data results.
	 * @param string $filter_type Filter type.
	 * @param array  $query_vars  Query variables.
	 * @param array  $extra       Extra filter arguments.
	 * @return mixed
	 */
	public function filter_product_filter_data( $results, string $filter_type, array $query_vars, array $extra ) {
		if ( 'attribute' === $filter_type ) {
			return $this->attribute_counts;
		}

		return $results;
	}
}
