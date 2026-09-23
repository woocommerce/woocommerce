<?php
/**
 * Featured item block support rendering tests.
 *
 * @package WooCommerce\Tests\Blocks
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WC_Unit_Test_Case;
use WP_HTML_Tag_Processor;

/**
 * Tests the shared Featured Category and Featured Product support renderer.
 */
class FeaturedItemSupportsTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Core supports must reach the visible wrapper and its actual content container.
	 *
	 * @testWith ["category"]
	 *           ["product"]
	 * @param string $kind Featured item kind.
	 */
	public function test_support_styles_and_layout_target( string $kind ): void {
		$attributes           = $this->get_item_attributes( $kind );
		$attributes['anchor'] = 'featured-anchor';
		$attributes['layout'] = array(
			'type'              => 'flex',
			'orientation'       => 'vertical',
			'verticalAlignment' => 'category' === $kind ? 'top' : 'bottom',
		);
		$attributes['style']  = array(
			'border'     => array(
				'style' => 'dashed',
				'width' => '3px',
			),
			'typography' => array(
				'letterSpacing' => '2px',
				'fontWeight'    => '600',
			),
			'dimensions' => array( 'aspectRatio' => '16/9' ),
			'shadow'     => '2px 3px 4px #000000',
			'spacing'    => array(
				'margin'   => array( 'top' => '20px' ),
				'blockGap' => '12px',
			),
		);
		$html                 = $this->render_item( $kind, $attributes );
		$tags                 = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $tags->next_tag() );
		$this->assertSame( 'featured-anchor', $tags->get_attribute( 'id' ) );
		$this->assertFalse( $tags->has_class( 'is-layout-flex' ) );
		foreach ( array( 'border-style:dashed', 'letter-spacing:2px', 'font-weight:600', 'aspect-ratio:16/9', 'min-height:unset', 'box-shadow:2px 3px 4px #000000', 'margin-top:20px' ) as $style ) {
			$this->assertStringContainsString( $style, $tags->get_attribute( 'style' ) );
		}
		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'wc-block-featured-' . $kind . '__inner-blocks' ) ) );
		$this->assertTrue( $tags->has_class( 'is-layout-flex' ) );
		$this->assertTrue( $tags->has_class( 'is-vertical' ) );
		$this->assertStringContainsString( 'justify-content:' . ( 'category' === $kind ? 'flex-start' : 'flex-end' ), wp_style_engine_get_stylesheet_from_context( 'block-supports' ) );
	}

	/**
	 * @testdox Existing posts keep their height and do not acquire flow spacing.
	 *
	 * @testWith ["category"]
	 *           ["product"]
	 * @param string $kind Featured item kind.
	 */
	public function test_legacy_rendering_keeps_content_and_height( string $kind ): void {
		$attributes              = $this->get_item_attributes( $kind );
		$attributes['editMode']  = false;
		$attributes['minHeight'] = 620;
		$attributes['showDesc']  = true;
		$html                    = $this->render_item( $kind, $attributes );
		$this->assertStringContainsString( 'min-height:620px', $html );
		$this->assertStringContainsString( 'wc-block-featured-' . $kind . '__title', $html );
		$this->assertStringNotContainsString( 'is-layout-flow', $html );
		$this->assertStringNotContainsString( 'is-layout-flex', $html );
		$this->assertStringContainsString( 'Saved content', $html );
	}

	/**
	 * @testdox Dynamic images remain attached to the featured entity, unless customized.
	 *
	 * @testWith ["category"]
	 *           ["product"]
	 * @param string $kind Featured item kind.
	 */
	public function test_dynamic_and_custom_image_sources( string $kind ): void {
		$attributes             = $this->get_item_attributes( $kind );
		$attributes['imageFit'] = 'cover';
		$first                  = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$second                 = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$id                     = $attributes[ $kind . 'Id' ];
		foreach ( array( $first, $second ) as $image_id ) {
			if ( 'category' === $kind ) {
				update_term_meta( $id, 'thumbnail_id', $image_id );
			} else {
				$product = wc_get_product( $id );
				$product->set_image_id( $image_id );
				$product->save();
			}
			$this->assertStringContainsString( esc_url( wp_get_attachment_image_url( $image_id, 'large' ) ), $this->render_item( $kind, $attributes ) );
		}
		$attributes['mediaId'] = $first;
		$this->assertStringContainsString( esc_url( wp_get_attachment_image_url( $first, 'large' ) ), $this->render_item( $kind, $attributes ) );
	}

	/**
	 * @testdox Terms Query context continues to supply the category and inner-block title.
	 */
	public function test_inherited_category_with_supports(): void {
		$attributes = $this->get_item_attributes( 'category' );
		$block      = new \WP_Block(
			parse_blocks( '<!-- wp:woocommerce/featured-category {"style":{"dimensions":{"aspectRatio":"1"}}} --><!-- wp:woocommerce/category-title /--><!-- /wp:woocommerce/featured-category -->' )[0],
			array(
				'termId'       => $attributes['categoryId'],
				'termTaxonomy' => 'product_cat',
			)
		);
		$html       = $block->render();
		$this->assertStringContainsString( 'Featured category', $html );
		$this->assertStringContainsString( 'aspect-ratio:1', $html );
	}

	/**
	 * Create the selected entity without an image.
	 *
	 * @param string $kind Featured item kind.
	 * @return array
	 */
	private function get_item_attributes( string $kind ): array {
		$id = 'category' === $kind
			? $this->factory->term->create(
				array(
					'taxonomy' => 'product_cat',
					'name'     => 'Featured category',
				)
			)
			: WC_Helper_Product::create_simple_product()->get_id();
		return array( $kind . 'Id' => $id );
	}

	/**
	 * Render saved markup through all Core block support filters.
	 *
	 * @param string $kind Featured item kind.
	 * @param array  $attributes Block attributes.
	 * @return string
	 */
	private function render_item( string $kind, array $attributes ): string {
		return do_blocks( '<!-- wp:woocommerce/featured-' . $kind . ' ' . wp_json_encode( $attributes ) . ' --><!-- wp:paragraph --><p>Saved content</p><!-- /wp:paragraph --><!-- /wp:woocommerce/featured-' . $kind . ' -->' );
	}
}
