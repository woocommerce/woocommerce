<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Renderer;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductCollection Renderer collection-root context bag.
 */
class RendererTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Renderer
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Renderer();
	}

	/**
	 * Decode the `data-wp-context` attribute from the rendered collection-root markup.
	 *
	 * @param string $block_content The rendered block markup.
	 * @return array The decoded context bag.
	 */
	private function get_context_from_markup( $block_content ) {
		$p = new \WP_HTML_Tag_Processor( $block_content );
		$p->next_tag( array( 'class_name' => 'wp-block-woocommerce-product-collection' ) );

		return json_decode( $p->get_attribute( 'data-wp-context' ), true );
	}

	/**
	 * Read the `data-wp-router-region` attribute from the rendered collection-root markup.
	 *
	 * @param string $block_content The rendered block markup.
	 * @return string|null The router-region attribute value, or null when absent.
	 */
	private function get_router_region_from_markup( $block_content ) {
		$p = new \WP_HTML_Tag_Processor( $block_content );
		$p->next_tag( array( 'class_name' => 'wp-block-woocommerce-product-collection' ) );

		return $p->get_attribute( 'data-wp-router-region' );
	}

	/**
	 * @testdox Should not include a queryId key in the emitted context bag.
	 */
	public function test_context_bag_does_not_include_query_id(): void {
		$this->sut->set_parsed_block(
			array(
				'attrs' => array( 'queryId' => 7 ),
			)
		);

		$block_content = $this->sut->enhance_product_collection_with_interactivity(
			'<div class="wp-block-woocommerce-product-collection"></div>',
			array(
				'attrs' => array(
					'queryId' => 7,
					'query'   => array( 'isProductCollectionBlock' => true ),
				),
			)
		);

		$context = $this->get_context_from_markup( $block_content );

		$this->assertArrayNotHasKey( 'queryId', $context );
	}

	/**
	 * @testdox Should keep all previously present context bag keys unchanged.
	 */
	public function test_context_bag_keeps_existing_keys(): void {
		$this->sut->set_parsed_block(
			array(
				'attrs' => array( 'queryId' => 3 ),
			)
		);

		$block_content = $this->sut->enhance_product_collection_with_interactivity(
			'<div class="wp-block-woocommerce-product-collection"></div>',
			array(
				'attrs' => array(
					'queryId'    => 3,
					'query'      => array( 'isProductCollectionBlock' => true ),
					'collection' => 'woocommerce/product-collection/featured',
				),
			)
		);

		$context = $this->get_context_from_markup( $block_content );

		$this->assertArrayHasKey( 'notices', $context );
		$this->assertSame( array(), $context['notices'] );
		$this->assertArrayHasKey( 'hideNextPreviousButtons', $context );
		$this->assertFalse( $context['hideNextPreviousButtons'] );
		$this->assertArrayHasKey( 'isDisabledPrevious', $context );
		$this->assertTrue( $context['isDisabledPrevious'] );
		$this->assertArrayHasKey( 'isDisabledNext', $context );
		$this->assertFalse( $context['isDisabledNext'] );
		$this->assertArrayHasKey( 'ariaLabelPrevious', $context );
		$this->assertSame( 'Previous products', $context['ariaLabelPrevious'] );
		$this->assertArrayHasKey( 'ariaLabelNext', $context );
		$this->assertSame( 'Next products', $context['ariaLabelNext'] );
		$this->assertArrayHasKey( 'collection', $context );
		$this->assertSame( 'woocommerce/product-collection/featured', $context['collection'] );
	}

	/**
	 * @testdox Should keep the router-region attribute keyed by the block's query id.
	 */
	public function test_router_region_attribute_keeps_query_id(): void {
		$this->sut->set_parsed_block(
			array(
				'attrs' => array( 'queryId' => 7 ),
			)
		);

		$block_content = $this->sut->enhance_product_collection_with_interactivity(
			'<div class="wp-block-woocommerce-product-collection"></div>',
			array(
				'attrs' => array(
					'queryId' => 7,
					'query'   => array( 'isProductCollectionBlock' => true ),
				),
			)
		);

		$this->assertSame( 'wc-product-collection-7', $this->get_router_region_from_markup( $block_content ) );
	}

	/**
	 * @testdox Should fall back to '0' for the router-region attribute when the parsed block attribute is missing.
	 */
	public function test_router_region_attribute_defaults_to_zero(): void {
		$this->sut->set_parsed_block(
			array(
				'attrs' => array(),
			)
		);

		$block_content = $this->sut->enhance_product_collection_with_interactivity(
			'<div class="wp-block-woocommerce-product-collection"></div>',
			array(
				'attrs' => array(
					'query' => array( 'isProductCollectionBlock' => true ),
				),
			)
		);

		$this->assertSame( 'wc-product-collection-0', $this->get_router_region_from_markup( $block_content ) );
	}
}
