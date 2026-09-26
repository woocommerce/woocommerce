<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Unit_Test_Case;

/**
 * Tests for the Product Filter Chips block type.
 */
class ProductFilterChipsTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Typography is applied to the wrapper and border radius to chip items.
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\ProductFilterChips::render
	 */
	public function test_renders_typography_on_wrapper_and_border_radius_on_items(): void {
		$markup = $this->render_chips(
			array(
				'style' => array(
					'border'     => array(
						'radius' => '12px',
					),
					'typography' => array(
						'textTransform' => 'uppercase',
					),
				),
			),
			array(
				array(
					'id'       => 'item-red',
					'label'    => 'Red',
					'value'    => 'red',
					'selected' => false,
				),
			)
		);

		$wrapper_style = $this->get_style( $markup, 'wc-block-product-filter-chips' );
		$item_style    = $this->get_style( $markup, 'wc-block-product-filter-chips__item' );

		$this->assertStringContainsString( 'text-transform:uppercase', $wrapper_style, 'Wrapper should have text-transform: uppercase.' );
		$this->assertStringContainsString( 'border-radius:12px', $item_style, 'Item should have border-radius: 12px.' );
		$this->assertStringNotContainsString( 'border-radius', $wrapper_style, 'Wrapper should not have border-radius.' );
	}

	/**
	 * @testdox Padding and border radius are not applied to visual swatch chip items.
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\ProductFilterChips::render
	 */
	public function test_does_not_apply_padding_or_border_radius_to_swatch_items(): void {
		$markup = $this->render_chips(
			array(
				'style' => array(
					'border'  => array(
						'radius' => '0px',
					),
					'spacing' => array(
						'padding' => array(
							'top'    => '8px',
							'right'  => '16px',
							'bottom' => '8px',
							'left'   => '16px',
						),
					),
				),
			),
			array(
				array(
					'id'       => 'item-red',
					'label'    => 'Red',
					'value'    => 'red',
					'selected' => false,
					'visual'   => array(
						'type'  => 'color',
						'color' => '#ff0000',
					),
				),
			)
		);

		$item_style = $this->get_style( $markup, 'wc-block-product-filter-chips__item' );

		$this->assertStringContainsString( 'is-style-swatch', $markup, 'Visual items should use the swatch style.' );
		$this->assertStringNotContainsString( 'border-radius', $item_style, 'Swatch items should not get an inline border radius.' );
		$this->assertStringNotContainsString( 'padding-top', $item_style, 'Swatch items should not get inline padding.' );
	}

	/**
	 * Render the Chips block with the given attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $items      Selectable items.
	 * @return string Rendered markup.
	 */
	private function render_chips( array $attributes, array $items ): string {
		$block = new \WP_Block(
			array(
				'blockName'    => 'woocommerce/product-filter-chips',
				'attrs'        => array_merge( $attributes, array( 'className' => 'wc-block-product-filter-chips' ) ),
				'innerContent' => array(),
			),
			array(
				'woocommerce/selectableItems' => array(
					'items'          => $items,
					'selectionMode'  => 'multiple',
					'storeNamespace' => 'woocommerce/product-filters',
				),
			)
		);

		return $block->render();
	}

	/**
	 * Get the style attribute of the first element with the given class.
	 *
	 * @param string $markup     Rendered markup.
	 * @param string $class_name Class name to find.
	 * @return string
	 */
	private function get_style( string $markup, string $class_name ): string {
		$processor = new \WP_HTML_Tag_Processor( $markup );
		$this->assertTrue( $processor->next_tag( array( 'class_name' => $class_name ) ), 'Should find the first element with the given class name.' );

		$style = $processor->get_attribute( 'style' );

		return is_string( $style ) ? $style : '';
	}
}
