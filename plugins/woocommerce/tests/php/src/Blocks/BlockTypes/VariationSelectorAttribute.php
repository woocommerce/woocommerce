<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsVariationSelectorAttributeMock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\AddToCartWithOptionsVariationSelectorAttributeNameMock;
use WC_Unit_Test_Case;

/**
 * Tests for the VariationSelectorAttribute block type.
 */
class VariationSelectorAttribute extends WC_Unit_Test_Case {

	/**
	 * Tracks whether blocks have been registered.
	 *
	 * @var bool
	 */
	protected static $are_blocks_registered = false;

	/**
	 * Register blocks required for do_blocks tests.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! self::$are_blocks_registered && ! \WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/add-to-cart-with-options-variation-selector-attribute' ) ) {
			new AddToCartWithOptionsVariationSelectorAttributeMock();
			new AddToCartWithOptionsVariationSelectorAttributeNameMock();
		}

		self::$are_blocks_registered = true;
	}

	/**
	 * Data provider for legacy Attribute Options block replacement styles.
	 *
	 * @return array<string, array{0: array<string, string>, 1: string, 2: string}>
	 */
	public function legacy_attribute_options_block_styles_provider(): array {
		return array(
			'dropdown' => array(
				array( 'optionStyle' => 'dropdown' ),
				'wc-block-dropdown',
				'wc-block-product-filter-chips',
			),
			'pills'    => array(
				array( 'optionStyle' => 'pills' ),
				'wc-block-product-filter-chips',
				'wc-block-dropdown',
			),
			'default'  => array(
				array(),
				'wc-block-product-filter-chips',
				'wc-block-dropdown',
			),
		);
	}

	/**
	 * Tests that the block returns empty string for non-variable products.
	 *
	 * @testdox VariationSelectorAttribute returns an empty string for non-variable products.
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationSelectorAttribute::render
	 */
	public function test_returns_empty_for_non_variable_products(): void {
		global $product;
		$original_product = $product;

		$block_markup = '<!-- wp:woocommerce/add-to-cart-with-options-variation-selector-attribute /-->';

		try {
			$product = null;
			$this->assertSame( '', do_blocks( $block_markup ), 'VariationSelectorAttribute should return empty string when the global product is null.' );

			$product = false;
			$this->assertSame( '', do_blocks( $block_markup ), 'VariationSelectorAttribute should return empty string when the global product is false.' );

			$simple_product = new \WC_Product_Simple();
			$simple_product->set_regular_price( 10 );
			$simple_product->save();

			$product = $simple_product;
			$this->assertSame( '', do_blocks( $block_markup ), 'VariationSelectorAttribute should return empty string for simple products.' );

			$grouped_product = new \WC_Product_Grouped();
			$grouped_product->save();

			$product = $grouped_product;
			$this->assertSame( '', do_blocks( $block_markup ), 'VariationSelectorAttribute should return empty string for grouped products.' );

			$external_product = new \WC_Product_External();
			$external_product->save();

			$product = $external_product;
			$this->assertSame( '', do_blocks( $block_markup ), 'VariationSelectorAttribute should return empty string for external products.' );
		} finally {
			$product = $original_product;
		}
	}

	/**
	 * Tests that legacy Attribute Options blocks are replaced with dropdown or chips blocks when rendered.
	 *
	 * @testdox Legacy Attribute Options block renders with %2$s and not %3$s.
	 * @dataProvider legacy_attribute_options_block_styles_provider
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationSelectorAttribute::replace_legacy_attribute_options_block
	 *
	 * @param array  $options_attrs          Legacy options block attributes.
	 * @param string $expected_output_class  CSS class expected in rendered output.
	 * @param string $unexpected_output_class CSS class that should not appear in rendered output.
	 */
	public function test_replaces_legacy_attribute_options_block_when_rendered( array $options_attrs, string $expected_output_class, string $unexpected_output_class ): void {
		$variable_product = $this->create_variable_product_with_variations();
		$inner_blocks     = $this->get_attribute_name_block_markup() . $this->get_legacy_options_block_markup( $options_attrs );

		$markup = $this->render_variation_selector_attribute( $variable_product, $inner_blocks );

		$this->assertStringContainsString( 'variation-selector-attribute-name', $markup, 'Attribute name block should render.' );
		$this->assertStringContainsString( $expected_output_class, $markup, 'Legacy Attribute Options block should render as the replacement block.' );
		$this->assertStringNotContainsString( $unexpected_output_class, $markup, 'Legacy Attribute Options block should not render as the other option style.' );
	}

	/**
	 * Tests that autoselect and disabledAttributesAction are migrated from legacy Attribute Options blocks.
	 *
	 * @testdox autoselect and disabledAttributesAction are migrated from legacy Attribute Options block.
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationSelectorAttribute::replace_legacy_attribute_options_block
	 */
	public function test_migrates_legacy_attribute_options_settings_when_rendered(): void {
		$variable_product = $this->create_variable_product_with_variations();
		$inner_blocks     = $this->get_attribute_name_block_markup() . $this->get_legacy_options_block_markup(
			array(
				'autoselect'               => true,
				'disabledAttributesAction' => 'hide',
			)
		);

		$markup = $this->render_variation_selector_attribute( $variable_product, $inner_blocks );

		$this->assertStringContainsString( '"autoselect":true', $markup, 'Legacy autoselect should be applied to the interactivity context.' );
		$this->assertStringContainsString( '"disabledAttributesAction":"hide"', $markup, 'Legacy disabledAttributesAction should be applied to the interactivity context.' );
	}

	/**
	 * Tests that non-visual attribute terms do not render swatch markup.
	 *
	 * @testdox VariationSelectorAttribute does not render swatches for non-visual attributes.
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationSelectorAttribute::build_variation_selectable_items
	 */
	public function test_does_not_render_swatches_for_non_visual_attribute(): void {
		$variable_product = $this->create_variable_product_with_variations();
		$inner_blocks     = $this->get_attribute_name_block_markup() . $this->get_chips_block_markup();

		$markup = $this->render_variation_selector_attribute( $variable_product, $inner_blocks );

		$this->assertStringNotContainsString( 'is-style-swatch', $markup, 'Chips wrapper should not use swatch style for non-visual attributes.' );
		$this->assertStringNotContainsString( 'wc-block-product-filter-chips__swatch', $markup, 'Swatch elements should not be rendered for non-visual attributes.' );
	}

	/**
	 * Tests that wc-visual attribute terms render chips with swatch markup and classes.
	 *
	 * @testdox VariationSelectorAttribute renders wc-visual attribute options with swatch classes and colors.
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationSelectorAttribute::build_variation_selectable_items
	 */
	public function test_renders_wc_visual_attribute_with_swatch_classes(): void {
		global $wpdb;

		$fixtures     = new FixtureData();
		$attribute    = FixtureData::get_product_attribute(
			'vswatch',
			array( 'Tone A', 'Tone B' )
		);
		$attribute_id = $attribute['attribute_id'];
		$taxonomy     = $attribute['attribute_taxonomy'];
		$term_ids     = $attribute['term_ids'];
		$term_a       = get_term( $term_ids[0] );
		$term_b       = get_term( $term_ids[1] );
		$this->assertInstanceOf( \WP_Term::class, $term_a );
		$this->assertInstanceOf( \WP_Term::class, $term_b );

		$wpdb->update(
			$wpdb->prefix . 'woocommerce_attribute_taxonomies',
			array( 'attribute_type' => 'wc-visual' ),
			array( 'attribute_id' => $attribute_id ),
			array( '%s' ),
			array( '%d' )
		);
		delete_transient( 'wc_attribute_taxonomies' );
		\WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );

		$image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Variation selector swatch image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		update_post_meta( $image_id, '_wp_attached_file', 'variation-selector-swatch-image.jpg' );
		update_term_meta( $term_a->term_id, 'image', $image_id );
		update_term_meta( $term_b->term_id, 'color', '#0000aa' );

		try {
			$variable_product = $fixtures->get_variable_product(
				array(),
				array( $attribute )
			);

			$product_id = $variable_product->get_id();

			$fixtures->get_variation_product(
				$product_id,
				array( $taxonomy => $term_a->slug ),
				array(
					'regular_price' => 10,
					'stock_status'  => ProductStockStatus::IN_STOCK,
				)
			);

			$fixtures->get_variation_product(
				$product_id,
				array( $taxonomy => $term_b->slug ),
				array(
					'regular_price' => 12,
					'stock_status'  => ProductStockStatus::IN_STOCK,
				)
			);

			\WC_Product_Variable::sync( $product_id );

			$variable_product = wc_get_product( $product_id );
			$this->assertInstanceOf( \WC_Product_Variable::class, $variable_product );

			$inner_blocks = $this->get_attribute_name_block_markup() . $this->get_chips_block_markup();
			$markup       = $this->render_variation_selector_attribute( $variable_product, $inner_blocks );

			$this->assertStringContainsString( 'is-style-swatch', $markup, 'Chips wrapper should use swatch style when colors are present.' );
			$this->assertStringContainsString( 'wc-block-product-filter-chips__swatch', $markup, 'Swatch elements should be rendered for wc-visual terms.' );
			$this->assertStringContainsString( 'background-image:url(', $markup, 'First term swatch should use its term image meta.' );
			$this->assertStringContainsString( 'background-color:#0000aa', $markup, 'Second term swatch should use its term color meta.' );
		} finally {
			delete_term_meta( $term_a->term_id, 'image' );
			delete_term_meta( $term_b->term_id, 'color' );
			if ( $image_id ) {
				wp_delete_attachment( $image_id, true );
			}
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_attribute_taxonomies',
				array( 'attribute_type' => 'select' ),
				array( 'attribute_id' => $attribute_id ),
				array( '%s' ),
				array( '%d' )
			);
			delete_transient( 'wc_attribute_taxonomies' );
			\WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
		}
	}

	/**
	 * Tests that legacy Attribute Options blocks nested in a group are replaced when rendered.
	 *
	 * @testdox Legacy Attribute Options block nested in a group is replaced with a dropdown when rendered.
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions\VariationSelectorAttribute::replace_legacy_attribute_options_block
	 */
	public function test_replaces_nested_legacy_attribute_options_block_when_rendered(): void {
		$variable_product = $this->create_variable_product_with_variations();
		$inner_blocks     = sprintf(
			'<!-- wp:group -->%s%s<!-- /wp:group -->',
			$this->get_attribute_name_block_markup(),
			$this->get_legacy_options_block_markup( array( 'optionStyle' => 'dropdown' ) )
		);

		$markup = $this->render_variation_selector_attribute( $variable_product, $inner_blocks );

		$this->assertStringContainsString( 'variation-selector-attribute-name', $markup, 'Attribute name block should render.' );
		$this->assertStringContainsString( 'wc-block-dropdown', $markup, 'Nested legacy Attribute Options block should render as a dropdown.' );
		$this->assertStringNotContainsString( 'wc-block-product-filter-chips', $markup, 'Nested legacy Attribute Options block should not render as chips.' );
	}

	/**
	 * Create a variable product with color variations for rendering tests.
	 *
	 * @return \WC_Product_Variable
	 */
	private function create_variable_product_with_variations(): \WC_Product_Variable {
		$fixtures = new FixtureData();

		$product = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'color', array( 'red', 'blue' ) ),
			)
		);

		$product_id = $product->get_id();

		$fixtures->get_variation_product(
			$product_id,
			array(
				'pa_color' => 'red-slug',
			),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);

		$fixtures->get_variation_product(
			$product_id,
			array(
				'pa_color' => 'blue-slug',
			),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);

		\WC_Product_Variable::sync( $product_id );

		$variable_product = wc_get_product( $product_id );

		$this->assertInstanceOf( \WC_Product_Variable::class, $variable_product );

		return $variable_product;
	}

	/**
	 * Render the variation selector attribute block with the given inner block markup.
	 *
	 * @param \WC_Product_Variable $variable_product  Variable product to use as context.
	 * @param string               $inner_blocks_markup Inner blocks markup.
	 * @return string Rendered block output.
	 */
	private function render_variation_selector_attribute( \WC_Product_Variable $variable_product, string $inner_blocks_markup ): string {
		global $product;

		$original_product = $product;
		$product          = $variable_product;

		$block_markup = sprintf(
			'<!-- wp:woocommerce/add-to-cart-with-options-variation-selector-attribute -->%s<!-- /wp:woocommerce/add-to-cart-with-options-variation-selector-attribute -->',
			$inner_blocks_markup
		);

		try {
			return do_blocks( $block_markup );
		} finally {
			$product = $original_product;
		}
	}

	/**
	 * Get block markup for the attribute name inner block.
	 *
	 * @return string
	 */
	private function get_attribute_name_block_markup(): string {
		return '<!-- wp:woocommerce/add-to-cart-with-options-variation-selector-attribute-name /-->';
	}

	/**
	 * Block markup for the Chips inner block (variation selector default).
	 *
	 * @return string
	 */
	private function get_chips_block_markup(): string {
		return '<!-- wp:woocommerce/product-filter-chips /-->';
	}

	/**
	 * Get block markup for the legacy Attribute Options inner block.
	 *
	 * @param array $attrs Legacy options block attributes.
	 * @return string
	 */
	private function get_legacy_options_block_markup( array $attrs ): string {
		if ( empty( $attrs ) ) {
			return '<!-- wp:woocommerce/add-to-cart-with-options-variation-selector-attribute-options /-->';
		}

		return sprintf(
			'<!-- wp:woocommerce/add-to-cart-with-options-variation-selector-attribute-options %s /-->',
			wp_json_encode( $attrs )
		);
	}
}
