<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Unit_Test_Case;
use WP_Theme_JSON_Data;

/**
 * Tests for the Product Filter Chips block type.
 */
class ProductFilterChipsTest extends WC_Unit_Test_Case {

	/**
	 * Active theme before each test, restored in tearDown.
	 *
	 * @var string
	 */
	private string $original_theme;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_theme = get_stylesheet();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'wp_theme_json_data_user' );
		remove_all_filters( 'wp_theme_json_data_theme' );
		switch_theme( $this->original_theme );
		parent::tearDown();
	}

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
	 * @testdox Block gap is applied to the items container, not the wrapper or chip items.
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\ProductFilterChips::render
	 */
	public function test_applies_block_gap_to_items_container(): void {
		$markup = $this->render_chips(
			array(
				'style' => array(
					'spacing' => array(
						'blockGap' => '12px',
						'padding'  => array(
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
				),
			)
		);

		$wrapper_style = $this->get_style( $markup, 'wc-block-product-filter-chips' );
		$items_style   = $this->get_style( $markup, 'wc-block-product-filter-chips__items' );
		$item_style    = $this->get_style( $markup, 'wc-block-product-filter-chips__item' );

		$this->assertStringContainsString( 'gap:12px', $items_style, 'Items container should have the block gap.' );
		$this->assertStringNotContainsString( 'gap:', $wrapper_style, 'Wrapper should not have the block gap.' );
		$this->assertStringNotContainsString( 'gap:', $item_style, 'Chip items should not have the block gap.' );
		$this->assertStringContainsString( 'padding-top:8px', $item_style, 'Chip items should still get padding.' );
	}

	/**
	 * @testdox Block gap is applied to the items container for visual swatches.
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\ProductFilterChips::render
	 */
	public function test_applies_block_gap_to_swatch_items_container(): void {
		$markup = $this->render_chips(
			array(
				'style' => array(
					'spacing' => array(
						'blockGap' => '20px',
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

		$items_style = $this->get_style( $markup, 'wc-block-product-filter-chips__items' );

		$this->assertStringContainsString( 'is-style-swatch', $markup, 'Visual items should use the swatch style.' );
		$this->assertStringContainsString( 'gap:20px', $items_style, 'Swatch items container should have the block gap.' );
	}

	/**
	 * @testdox Preset and axial block gap values are converted to CSS gap.
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\ProductFilterChips::render
	 */
	public function test_converts_preset_and_axial_block_gap_to_css(): void {
		$preset_markup = $this->render_chips(
			array(
				'style' => array(
					'spacing' => array(
						'blockGap' => 'var:preset|spacing|30',
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
		$axial_markup  = $this->render_chips(
			array(
				'style' => array(
					'spacing' => array(
						'blockGap' => array(
							'top'  => '8px',
							'left' => '16px',
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
				),
			)
		);

		$this->assertStringContainsString(
			'gap:var(--wp--preset--spacing--30)',
			$this->get_style( $preset_markup, 'wc-block-product-filter-chips__items' ),
			'Preset block gap should be converted to a CSS custom property.'
		);
		$this->assertStringContainsString(
			'row-gap:8px',
			$this->get_style( $axial_markup, 'wc-block-product-filter-chips__items' ),
			'Axial block gap should set row-gap from the top value.'
		);
		$this->assertStringContainsString(
			'column-gap:16px',
			$this->get_style( $axial_markup, 'wc-block-product-filter-chips__items' ),
			'Axial block gap should set column-gap from the left value.'
		);
	}

	/**
	 * @testdox Block gap from global styles and theme.json is applied when the instance has none.
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\ProductFilterChips::render
	 * @dataProvider provider_global_block_gap_origins
	 *
	 * @param string $filter_name Theme.json filter to hook.
	 * @param string $origin      Theme.json origin.
	 * @param string $gap         Gap value to inject.
	 */
	public function test_applies_global_and_theme_block_gap( string $filter_name, string $origin, string $gap ): void {
		$this->switch_to_block_theme();
		$this->set_chips_global_block_gap( $filter_name, $origin, $gap );

		$markup = $this->render_chips(
			array(),
			array(
				array(
					'id'       => 'item-red',
					'label'    => 'Red',
					'value'    => 'red',
					'selected' => false,
				),
			)
		);

		$this->assertStringContainsString(
			'gap:' . $gap,
			$this->get_style( $markup, 'wc-block-product-filter-chips__items' ),
			'Items container should use the gap from global styles or theme.json.'
		);
	}

	/**
	 * Origins that contribute block gap when the instance does not set one.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public function provider_global_block_gap_origins(): array {
		return array(
			'user global styles' => array( 'wp_theme_json_data_user', 'user', '18px' ),
			'theme.json'         => array( 'wp_theme_json_data_theme', 'theme', '24px' ),
		);
	}

	/**
	 * @testdox Instance block gap takes priority over global styles.
	 * @covers \Automattic\WooCommerce\Blocks\BlockTypes\ProductFilterChips::render
	 */
	public function test_instance_block_gap_overrides_global_styles(): void {
		$this->switch_to_block_theme();
		$this->set_chips_global_block_gap( 'wp_theme_json_data_user', 'user', '30px' );

		$markup = $this->render_chips(
			array(
				'style' => array(
					'spacing' => array(
						'blockGap' => '12px',
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

		$items_style = $this->get_style( $markup, 'wc-block-product-filter-chips__items' );

		$this->assertStringContainsString( 'gap:12px', $items_style, 'Instance block gap should be applied.' );
		$this->assertStringNotContainsString( 'gap:30px', $items_style, 'Global styles block gap should not override the instance value.' );
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

	/**
	 * Switch to a block theme for global styles resolution.
	 */
	private function switch_to_block_theme(): void {
		switch_theme( 'twentytwentyfour' );
	}

	/**
	 * Inject a blockGap style for the chips block.
	 *
	 * @param string $filter_name Theme.json filter to hook.
	 * @param string $origin      Theme.json origin.
	 * @param string $block_gap   CSS gap value.
	 */
	private function set_chips_global_block_gap( string $filter_name, string $origin, string $block_gap ): void {
		add_filter(
			$filter_name,
			function ( $theme_json ) use ( $origin, $block_gap ) {
				$data = $theme_json->get_data();
				$data['styles']['blocks']['woocommerce/product-filter-chips']['spacing']['blockGap'] = $block_gap;
				return new WP_Theme_JSON_Data( $data, $origin );
			}
		);
	}
}
