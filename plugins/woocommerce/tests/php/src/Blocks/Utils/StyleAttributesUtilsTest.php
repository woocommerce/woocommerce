<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use WC_Unit_Test_Case;

/**
 * Tests for StyleAttributesUtils.
 */
class StyleAttributesUtilsTest extends WC_Unit_Test_Case {

	/**
	 * @testdox get_classes_and_styles_by_attributes() includes or excludes the requested handlers.
	 *
	 * @dataProvider provider_style_handler_dispatch
	 *
	 * @param array<int, string> $properties       Properties to request.
	 * @param array<int, string> $exclude          Properties to exclude.
	 * @param string             $expected_classes Expected class output.
	 * @param string             $expected_styles  Expected style output.
	 */
	public function test_get_classes_and_styles_by_attributes_dispatches_expected_handlers( array $properties, array $exclude, string $expected_classes, string $expected_styles ): void {
		$attributes = array(
			'className' => 'extra',
			'fontSize'  => 'large',
			'textColor' => 'vivid-red',
			'style'     => array(
				'spacing' => array(
					'padding' => array(
						'top' => '8px',
					),
				),
			),
		);

		$result = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, $properties, $exclude );

		$this->assertSame( $expected_classes, $result['classes'] );
		$this->assertSame( $expected_styles, $result['styles'] );
	}

	/**
	 * @return array<string, array{array<int, string>, array<int, string>, string, string}>
	 */
	public function provider_style_handler_dispatch(): array {
		$all_classes = 'has-font-size has-large-font-size has-text-color has-vivid-red-color extra';
		$padding     = 'padding-top:8px;';

		return array(
			'empty properties'                 => array( array(), array(), $all_classes, $padding ),
			'requested properties'             => array( array( 'font_size', 'extra_classes' ), array(), 'has-font-size has-large-font-size extra', '' ),
			'exclude extra_classes'            => array( array(), array( 'extra_classes' ), 'has-font-size has-large-font-size has-text-color has-vivid-red-color', $padding ),
			'exclude font_size'                => array( array(), array( 'font_size' ), 'has-text-color has-vivid-red-color extra', $padding ),
			'exclude padding'                  => array( array(), array( 'padding' ), $all_classes, '' ),
			'requested and excluded font_size' => array( array( 'font_size' ), array( 'font_size' ), '', '' ),
		);
	}
}
