<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Unit_Test_Case;

/**
 * Tests for the ProductFilterTaxonomy block type.
 */
class ProductFilterTaxonomyTest extends WC_Unit_Test_Case {

	/**
	 * Term counts returned by the product filter data hook.
	 *
	 * @var array
	 */
	private $taxonomy_counts = array();

	/**
	 * Term IDs created during tests.
	 *
	 * @var array
	 */
	private $term_ids = array();

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

		foreach ( $this->term_ids as $term_id ) {
			wp_delete_term( $term_id, 'product_cat' );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should render decoded taxonomy term labels in checkbox filters.
	 */
	public function test_renders_decoded_taxonomy_term_labels_in_checkbox_filters(): void {
		$term = wp_insert_term( 'Indoor & Tropical Bonsai', 'product_cat' );

		$this->assertNotWPError( $term );

		$term_id                       = (int) $term['term_id'];
		$this->term_ids[]              = $term_id;
		$this->taxonomy_counts         = array( $term_id => 1 );
		$stored_term                   = get_term( $term_id, 'product_cat' );
		$expected_serialized_label     = 'Indoor &amp; Tropical Bonsai';
		$expected_context_label        = 'Indoor \\u0026 Tropical Bonsai';
		$double_encoded_serialization  = 'Indoor &amp;amp; Tropical Bonsai';
		$double_encoded_context_entity = '\\u0026amp;';

		$this->assertSame( 'Indoor &amp; Tropical Bonsai', $stored_term->name, 'Term fixture should use WordPress encoded storage.' );

		$output = $this->render_taxonomy_filter_with_checkbox_list();

		$this->assertStringContainsString( $expected_serialized_label, $output, 'Rendered label should be serialized once for HTML output.' );
		$this->assertStringContainsString( $expected_context_label, $output, 'Interactivity context should contain the decoded label.' );
		$this->assertStringNotContainsString( $double_encoded_serialization, $output, 'Rendered label should not be double-encoded.' );
		$this->assertStringNotContainsString( $double_encoded_context_entity, $output, 'Interactivity context should not contain an encoded entity label.' );
	}

	/**
	 * Render a taxonomy filter block with a checkbox list inner block.
	 *
	 * @return string
	 */
	private function render_taxonomy_filter_with_checkbox_list(): string {
		$taxonomy_block = array(
			'blockName'    => 'woocommerce/product-filter-taxonomy',
			'attrs'        => array(
				'taxonomy'  => 'product_cat',
				'sortOrder' => 'name-asc',
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
		$parsed_block   = array(
			'blockName'    => 'woocommerce/product-filters',
			'attrs'        => array( 'showFilterDrawer' => false ),
			'innerBlocks'  => array( $taxonomy_block ),
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
		if ( 'taxonomy' === $filter_type && 'product_cat' === ( $extra['taxonomy'] ?? '' ) ) {
			return $this->taxonomy_counts;
		}

		return $results;
	}
}
