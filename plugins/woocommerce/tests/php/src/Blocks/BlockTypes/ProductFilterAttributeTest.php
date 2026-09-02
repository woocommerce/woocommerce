<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Unit_Test_Case;
use WP_HTML_Tag_Processor;

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
	 * Attribute terms created during tests, keyed by taxonomy.
	 *
	 * @var array<string, int[]>
	 */
	private $attribute_term_ids = array();

	/**
	 * Attribute taxonomies registered by this test class.
	 *
	 * @var string[]
	 */
	private $registered_taxonomies = array();

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

		foreach ( $this->attribute_term_ids as $taxonomy => $term_ids ) {
			foreach ( $term_ids as $term_id ) {
				wp_delete_term( $term_id, $taxonomy );
			}
		}

		foreach ( $this->attribute_ids as $attribute_id ) {
			wc_delete_attribute( $attribute_id );
		}

		foreach ( $this->registered_taxonomies as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				unregister_taxonomy( $taxonomy );
			}
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
			$this->registered_taxonomies[] = 'pa_material';
		}

		$term = wp_insert_term( 'Cotton & Linen', 'pa_material' );

		$this->assertNotWPError( $term );

		$term_id                                   = (int) $term['term_id'];
		$this->attribute_term_ids['pa_material'][] = $term_id;
		$this->attribute_counts                    = array( $term_id => 1 );
		$stored_term                               = get_term( $term_id, 'pa_material' );
		$expected_serialized_label                 = 'Cotton &amp; Linen';
		$expected_context_label                    = 'Cotton \\u0026 Linen';
		$double_encoded_serialization              = 'Cotton &amp;amp; Linen';
		$double_encoded_context_entity             = '\\u0026amp;';

		$this->assertSame( 'Cotton &amp; Linen', $stored_term->name, 'Term fixture should use WordPress encoded storage.' );

		$output = $this->render_attribute_filter_with_checkbox_list( $attribute_id );

		$this->assertStringContainsString( $expected_serialized_label, $output, 'Rendered label should be serialized once for HTML output.' );
		$this->assertStringContainsString( $expected_context_label, $output, 'Interactivity context should contain the decoded label.' );
		$this->assertStringNotContainsString( $double_encoded_serialization, $output, 'Rendered label should not be double-encoded.' );
		$this->assertStringNotContainsString( $double_encoded_context_entity, $output, 'Interactivity context should not contain an encoded entity label.' );
	}

	/**
	 * @testdox Should render ordered attribute options and counts in the interactivity context.
	 */
	public function test_renders_ordered_attribute_options_and_counts_in_interactivity_context(): void {
		$attribute_slug     = 'filter-count-color';
		$attribute_taxonomy = 'pa_' . $attribute_slug;
		$filter_type        = 'attribute/' . $attribute_slug;
		$attribute_id       = wc_create_attribute(
			array(
				'name' => 'Color',
				'slug' => $attribute_slug,
			)
		);

		$this->assertIsInt( $attribute_id, 'Attribute should be created.' );
		$this->attribute_ids[] = $attribute_id;

		if ( ! taxonomy_exists( $attribute_taxonomy ) ) {
			register_taxonomy( $attribute_taxonomy, array( 'product' ), array( 'labels' => array( 'name' => 'Color' ) ) );
			$this->registered_taxonomies[] = $attribute_taxonomy;
		}

		$blue = wp_insert_term( 'Blue', $attribute_taxonomy );
		$this->assertNotWPError( $blue );
		$blue_id = (int) $blue['term_id'];
		$this->attribute_term_ids[ $attribute_taxonomy ][] = $blue_id;

		$red = wp_insert_term( 'Red', $attribute_taxonomy );
		$this->assertNotWPError( $red );
		$red_id = (int) $red['term_id'];
		$this->attribute_term_ids[ $attribute_taxonomy ][] = $red_id;
		$this->attribute_counts                            = array(
			$blue_id => 7,
			$red_id  => 3,
		);

		$output_without_counts  = $this->render_attribute_filter_with_checkbox_list( $attribute_id );
		$context_without_counts = $this->get_attribute_filter_context( $output_without_counts, $filter_type );

		$this->assertIsArray( $context_without_counts, 'The attribute filter should expose an interactivity context without counts.' );
		foreach ( $context_without_counts['items'] as $item ) {
			$this->assertArrayNotHasKey( 'count', $item );
		}

		$output  = $this->render_attribute_filter_with_checkbox_list( $attribute_id, true );
		$context = $this->get_attribute_filter_context( $output, $filter_type );

		$this->assertIsArray( $context, 'The attribute filter should expose an interactivity context.' );
		$this->assertSame( 'Color: {{label}}', $context['activeLabelTemplate'] );
		$this->assertSame( $filter_type, $context['filterType'] );
		$this->assertSame(
			array(
				array(
					'id'                 => $filter_type . '-blue',
					'label'              => 'Blue',
					'ariaLabel'          => 'Blue',
					'value'              => 'blue',
					'selected'           => false,
					'type'               => $filter_type,
					'attributeQueryType' => 'or',
					'count'              => 7,
				),
				array(
					'id'                 => $filter_type . '-red',
					'label'              => 'Red',
					'ariaLabel'          => 'Red',
					'value'              => 'red',
					'selected'           => false,
					'type'               => $filter_type,
					'attributeQueryType' => 'or',
					'count'              => 3,
				),
			),
			$context['items']
		);
	}

	/**
	 * Get a matching interactivity context from rendered block markup.
	 *
	 * @param string $output      Rendered block markup.
	 * @param string $filter_type Expected filter type.
	 * @return array|null
	 */
	private function get_attribute_filter_context( string $output, string $filter_type ): ?array {
		$processor = new WP_HTML_Tag_Processor( $output );

		while ( $processor->next_tag() ) {
			$encoded_context = $processor->get_attribute( 'data-wp-context' );
			if ( ! is_string( $encoded_context ) ) {
				continue;
			}

			$candidate = json_decode( $encoded_context, true );
			if ( is_array( $candidate ) && ( $candidate['filterType'] ?? null ) === $filter_type ) {
				return $candidate;
			}
		}

		return null;
	}

	/**
	 * Render an attribute filter block with a checkbox list inner block.
	 *
	 * @param int  $attribute_id Attribute ID.
	 * @param bool $show_counts  Whether to include product counts.
	 * @return string
	 */
	private function render_attribute_filter_with_checkbox_list( int $attribute_id, bool $show_counts = false ): string {
		$attribute_block = array(
			'blockName'    => 'woocommerce/product-filter-attribute',
			'attrs'        => array(
				'attributeId' => $attribute_id,
				'hideEmpty'   => false,
				'queryType'   => 'or',
				'showCounts'  => $show_counts,
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
