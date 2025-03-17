<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\Preprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Spacing_Preprocessor;

/**
 * Unit test for Spacing_Preprocessor
 */
class Spacing_Preprocessor_Test extends \Email_Editor_Unit_Test {

	/**
	 * Spacing_Preprocessor instance
	 *
	 * @var Spacing_Preprocessor
	 */
	private $preprocessor;

	/**
	 * Layout settings
	 *
	 * @var array{contentSize: string}
	 */
	private array $layout;

	/**
	 * Styles settings
	 *
	 * @var array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles
	 */
	private array $styles;

	/**
	 * Set up the test
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->preprocessor = new Spacing_Preprocessor();
		$this->layout       = array( 'contentSize' => '660px' );
		$this->styles       = array(
			'spacing' => array(
				'padding'  => array(
					'left'   => '10px',
					'right'  => '10px',
					'top'    => '10px',
					'bottom' => '10px',
				),
				'blockGap' => '10px',
			),
		);
	}

	/**
	 * Test it adds default horizontal spacing
	 */
	public function testItAddsDefaultVerticalSpacing(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/list',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
							array(
								'blockName'   => 'core/img',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$this->assertCount( 2, $result );
		$first_columns             = $result[0];
		$second_columns            = $result[1];
		$nested_column             = $first_columns['innerBlocks'][0];
		$nested_column_first_item  = $nested_column['innerBlocks'][0];
		$nested_column_second_item = $nested_column['innerBlocks'][1];

		// First elements should not have margin-top, but others should.
		$this->assertArrayNotHasKey( 'margin-top', $first_columns['email_attrs'] );
		$this->assertEquals( '10px', $nested_column_second_item['email_attrs']['margin-top'] );
		$this->assertArrayNotHasKey( 'margin-top', $nested_column['email_attrs'] );
		$this->assertArrayNotHasKey( 'margin-top', $nested_column_first_item['email_attrs'] );
		$this->assertArrayHasKey( 'margin-top', $nested_column_second_item['email_attrs'] );
		$this->assertEquals( '10px', $nested_column_second_item['email_attrs']['margin-top'] );
	}
}
