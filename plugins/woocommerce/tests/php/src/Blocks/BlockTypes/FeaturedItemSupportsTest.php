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
	 * @testdox Design supports and vertical alignment render without changing the inner flow layout.
	 *
	 * @testWith ["category", "top"]
	 *           ["category", "center"]
	 *           ["category", "bottom"]
	 *           ["product", "top"]
	 *           ["product", "center"]
	 *           ["product", "bottom"]
	 * @param string $kind Featured item kind.
	 * @param string $alignment Vertical content alignment.
	 */
	public function test_support_styles_and_layout_target( string $kind, string $alignment ): void {
		$attributes                      = $this->get_item_attributes( $kind );
		$attributes['anchor']            = 'featured-anchor';
		$attributes['verticalAlignment'] = $alignment;
		$attributes['style']             = array(
			'color'      => array( 'text' => '#123456' ),
			'border'     => array(
				'style' => 'dashed',
				'width' => '3px',
			),
			'typography' => array(
				'textTransform'  => 'uppercase',
				'textDecoration' => 'underline',
			),
			'dimensions' => array( 'aspectRatio' => '16/9' ),
			'shadow'     => '2px 3px 4px #000000',
			'spacing'    => array(
				'margin'   => array( 'top' => '20px' ),
				'blockGap' => '12px',
			),
		);
		$html                            = $this->render_item( $kind, $attributes );
		$tags                            = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $tags->next_tag() );
		$this->assertSame( 'featured-anchor', $tags->get_attribute( 'id' ) );
		$this->assertFalse( $tags->has_class( 'is-layout-flex' ) );
		$this->assertTrue( $tags->has_class( 'is-vertically-aligned-' . $alignment ) );
		foreach ( array( 'color:#123456', 'border-style:dashed', 'text-transform:uppercase', 'text-decoration:underline', 'aspect-ratio:16/9', 'min-height:unset', 'box-shadow:2px 3px 4px #000000', 'margin-top:20px' ) as $style ) {
			$this->assertStringContainsString( $style, $tags->get_attribute( 'style' ) );
		}
		$this->assertStringNotContainsString( 'gap:', $tags->get_attribute( 'style' ) );
		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'wc-block-featured-' . $kind . '__inner-blocks' ) ) );
		$this->assertFalse( $tags->has_class( 'is-layout-flex' ) );
		$this->assertTrue( $tags->has_class( 'has-custom-gap' ) );
		$this->assertStringContainsString( '--wc-featured-item-block-gap:12px', $tags->get_attribute( 'style' ) );
	}

	/**
	 * @testdox Explicit zero spacing is distinguished from untouched inner-block margins.
	 *
	 * @testWith [null, false]
	 *           ["0", true]
	 *           [0, true]
	 * @param mixed $gap Saved block spacing.
	 * @param bool  $has_custom_gap Whether explicit spacing should be rendered.
	 */
	public function test_explicit_zero_spacing( $gap, bool $has_custom_gap ): void {
		$attributes                                 = $this->get_item_attributes( 'category' );
		$attributes['style']['spacing']['blockGap'] = $gap;
		$tags                                       = new WP_HTML_Tag_Processor( $this->render_item( 'category', $attributes ) );
		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'wc-block-featured-category__inner-blocks' ) ) );
		$this->assertSame( $has_custom_gap, $tags->has_class( 'has-custom-gap' ) );
		$this->assertSame( $has_custom_gap ? '--wc-featured-item-block-gap:0' : null, $tags->get_attribute( 'style' ) );
	}

	/**
	 * @testdox Core renders preset styles and wrapper classes for modern and legacy Featured blocks.
	 *
	 * @testWith ["category", false]
	 *           ["category", true]
	 *           ["product", false]
	 *           ["product", true]
	 * @param string $kind Featured item kind.
	 * @param bool   $legacy Whether to render legacy markup.
	 */
	public function test_core_renders_presets( string $kind, bool $legacy ): void {
		$attributes = array_merge(
			$this->get_item_attributes( $kind ),
			array(
				'textColor'       => 'vivid-red',
				'backgroundColor' => 'black',
				'gradient'        => 'vivid-cyan-blue-to-vivid-purple',
				'borderColor'     => 'white',
				'align'           => 'wide',
				'className'       => 'custom-featured',
			)
		);
		if ( $legacy ) {
			$attributes['editMode'] = false;
		}
		$tags = new WP_HTML_Tag_Processor( $this->render_item( $kind, $attributes ) );
		$this->assertTrue( $tags->next_tag() );
		foreach ( array( 'has-vivid-red-color', 'has-black-background-color', 'has-vivid-cyan-blue-to-vivid-purple-gradient-background', 'has-white-border-color', 'alignwide', 'custom-featured' ) as $class ) {
			$this->assertTrue( $tags->has_class( $class ), $class . ' should be rendered by Core block supports.' );
		}
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
