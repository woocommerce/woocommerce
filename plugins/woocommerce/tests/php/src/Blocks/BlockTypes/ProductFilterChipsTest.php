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
			)
		);

		$wrapper_style = $this->get_style( $markup, 'wc-block-product-filter-chips' );
		$item_style    = $this->get_style( $markup, 'wc-block-product-filter-chips__item' );

		$this->assertStringContainsString( 'text-transform:uppercase', $wrapper_style );
		$this->assertStringContainsString( 'border-radius:12px', $item_style );
		$this->assertStringNotContainsString( 'border-radius', $wrapper_style );
	}

	/**
	 * Render the Chips block with the given attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered markup.
	 */
	private function render_chips( array $attributes ): string {
		$block = new \WP_Block(
			array(
				'blockName'    => 'woocommerce/product-filter-chips',
				'attrs'        => $attributes,
				'innerContent' => array(),
			),
			array(
				'woocommerce/selectableItems' => array(
					'items'          => array(
						array(
							'id'       => 'item-red',
							'label'    => 'Red',
							'value'    => 'red',
							'selected' => false,
						),
					),
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
		$this->assertTrue( $processor->next_tag( array( 'class_name' => $class_name ) ) );

		$style = $processor->get_attribute( 'style' );

		return is_string( $style ) ? $style : '';
	}
}
