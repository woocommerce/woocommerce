<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\Preprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Blocks_Width_Preprocessor;

/**
 * Unit test for Blocks_Width_Preprocessor
 */
class Blocks_Width_Preprocessor_Test extends \Email_Editor_Unit_Test {

	/**
	 * Instance of Blocks_Width_Preprocessor
	 *
	 * @var Blocks_Width_Preprocessor
	 */
	private $preprocessor;

	/**
	 * Layout configuration
	 *
	 * @var array{contentSize: string}
	 */
	private array $layout;

	/**
	 * Styles configuration
	 *
	 * @var array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles
	 */
	private array $styles;

	/**
	 * Set up the test
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->preprocessor = new Blocks_Width_Preprocessor();
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
	 * Test it calculates width without padding
	 */
	public function testItCalculatesWidthWithoutPadding(): void {
		$blocks                       = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'width' => '50%',
						),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'width' => '25%',
						),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'width' => '100px',
						),
						'innerBlocks' => array(),
					),
				),
			),
		);
		$styles                       = $this->styles;
		$styles['spacing']['padding'] = array(
			'left'   => '0px',
			'right'  => '0px',
			'top'    => '0px',
			'bottom' => '0px',
		);
		$result                       = $this->preprocessor->preprocess( $blocks, $this->layout, $styles );
		$result                       = $result[0];
		$this->assertEquals( '660px', $result['email_attrs']['width'] );
		$this->assertCount( 3, $result['innerBlocks'] );
		$this->assertEquals( '330px', $result['innerBlocks'][0]['email_attrs']['width'] ); // 660 * 0.5
		$this->assertEquals( '165px', $result['innerBlocks'][1]['email_attrs']['width'] ); // 660 * 0.25
		$this->assertEquals( '100px', $result['innerBlocks'][2]['email_attrs']['width'] );
	}

	/**
	 * Test it calculates width for column with layout padding
	 */
	public function testItCalculatesWidthWithLayoutPadding(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'width' => '33%',
						),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'width' => '100px',
						),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'width' => '20%',
						),
						'innerBlocks' => array(),
					),
				),
			),
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$result = $result[0];
		$this->assertCount( 3, $result['innerBlocks'] );
		$this->assertEquals( '211px', $result['innerBlocks'][0]['email_attrs']['width'] ); // (660 - 10 - 10) * 0.33
		$this->assertEquals( '100px', $result['innerBlocks'][1]['email_attrs']['width'] );
		$this->assertEquals( '128px', $result['innerBlocks'][2]['email_attrs']['width'] ); // (660 - 10 - 10) * 0.2
	}

	/**
	 * Test it calculates width of block in column
	 */
	public function testItCalculatesWidthOfBlockInColumn(): void {
		$blocks       = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'width' => '40%',
							'style' => array(
								'spacing' => array(
									'padding' => array(
										'left'  => '10px',
										'right' => '10px',
									),
								),
							),
						),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/paragraph',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'width' => '60%',
							'style' => array(
								'spacing' => array(
									'padding' => array(
										'left'  => '25px',
										'right' => '15px',
									),
								),
							),
						),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/paragraph',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
		);
		$result       = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$inner_blocks = $result[0]['innerBlocks'];

		$this->assertCount( 2, $inner_blocks );
		$this->assertEquals( '256px', $inner_blocks[0]['email_attrs']['width'] ); // (660 - 10 - 10) * 0.4
		$this->assertEquals( '236px', $inner_blocks[0]['innerBlocks'][0]['email_attrs']['width'] ); // 256 - 10 - 10
		$this->assertEquals( '384px', $inner_blocks[1]['email_attrs']['width'] ); // (660 - 10 - 10) * 0.6
		$this->assertEquals( '344px', $inner_blocks[1]['innerBlocks'][0]['email_attrs']['width'] ); // 384 - 25 - 15
	}

	/**
	 * Test it calculates width for column with padding
	 */
	public function testItAddsMissingColumnWidth(): void {
		$blocks       = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/paragraph',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/paragraph',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/paragraph',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
		);
		$result       = $this->preprocessor->preprocess( $blocks, array( 'contentSize' => '620px' ), $this->styles );
		$inner_blocks = $result[0]['innerBlocks'];

		$this->assertCount( 3, $inner_blocks );
		$this->assertEquals( '200px', $inner_blocks[0]['email_attrs']['width'] ); // (660 - 10 - 10) * 0.33
		$this->assertEquals( '200px', $inner_blocks[0]['innerBlocks'][0]['email_attrs']['width'] );
		$this->assertEquals( '200px', $inner_blocks[1]['email_attrs']['width'] ); // (660 - 10 - 10) * 0.33
		$this->assertEquals( '200px', $inner_blocks[1]['innerBlocks'][0]['email_attrs']['width'] );
		$this->assertEquals( '200px', $inner_blocks[2]['email_attrs']['width'] ); // (660 - 10 - 10) * 0.33
		$this->assertEquals( '200px', $inner_blocks[2]['innerBlocks'][0]['email_attrs']['width'] );
	}

	/**
	 * Test it calculates width for column with padding
	 */
	public function testItCalculatesMissingColumnWidth(): void {
		$blocks       = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(
					'style' => array(
						'spacing' => array(
							'padding' => array(
								'left'  => '25px',
								'right' => '15px',
							),
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'width' => '33.33%',
						),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'width' => '200px',
						),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);
		$result       = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$inner_blocks = $result[0]['innerBlocks'];

		$this->assertCount( 3, $inner_blocks );
		$this->assertEquals( '200px', $inner_blocks[0]['email_attrs']['width'] ); // (620 - 10 - 10) * 0.3333
		$this->assertEquals( '200px', $inner_blocks[1]['email_attrs']['width'] ); // already defined.
		$this->assertEquals( '200px', $inner_blocks[2]['email_attrs']['width'] ); // 600 -200 - 200
	}

	/**
	 * Test it calculates width for column with padding
	 */
	public function testItDoesNotSubtractPaddingForFullWidthBlocks(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(
					'align' => 'full',
				),
				'innerBlocks' => array(),
			),
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(),
			),
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertCount( 2, $result );
		$this->assertEquals( '660px', $result[0]['email_attrs']['width'] ); // full width.
		$this->assertEquals( '640px', $result[1]['email_attrs']['width'] ); // 660 - 10 - 10
	}

	/**
	 * Test it calculates width for column with padding
	 */
	public function testItCalculatesWidthForColumnWithoutDefinition(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(
					'style' => array(
						'spacing' => array(
							'padding' => array(
								'left'  => '25px',
								'right' => '15px',
							),
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'width' => '140px',
							'style' => array(
								'spacing' => array(
									'padding' => array(
										'left'  => '25px',
										'right' => '15px',
									),
								),
							),
						),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'style' => array(
								'spacing' => array(
									'padding' => array(
										'left'  => '10px',
										'right' => '10px',
									),
								),
							),
						),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'style' => array(
								'spacing' => array(
									'padding' => array(
										'left'  => '20px',
										'right' => '20px',
									),
								),
							),
						),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$this->assertCount( 3, $result[0]['innerBlocks'] );
		$this->assertEquals( '140px', $result[0]['innerBlocks'][0]['email_attrs']['width'] );
		$this->assertEquals( '220px', $result[0]['innerBlocks'][1]['email_attrs']['width'] );
		$this->assertEquals( '240px', $result[0]['innerBlocks'][2]['email_attrs']['width'] );

		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'width' => '140px',
							'style' => array(
								'spacing' => array(
									'padding' => array(
										'left'  => '25px',
										'right' => '15px',
									),
								),
							),
						),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$this->assertCount( 2, $result[0]['innerBlocks'] );
		$this->assertEquals( '140px', $result[0]['innerBlocks'][0]['email_attrs']['width'] );
		$this->assertEquals( '500px', $result[0]['innerBlocks'][1]['email_attrs']['width'] );
	}

	/**
	 * Test it calculates width for column with border
	 */
	public function testItCalculatesWidthForColumnWithBorder(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(
					'style' => array(
						'border'  => array(
							'width' => '10px',
						),
						'spacing' => array(
							'padding' => array(
								'left'  => '25px',
								'right' => '15px',
							),
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'width' => '140px',
							'style' => array(
								'border'  => array(
									'left'  => array(
										'width' => '5px',
									),
									'right' => array(
										'width' => '5px',
									),
								),
								'spacing' => array(
									'padding' => array(
										'left'  => '25px',
										'right' => '15px',
									),
								),
							),
						),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/image',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'style' => array(
								'border'  => array(
									'width' => '15px',
								),
								'spacing' => array(
									'padding' => array(
										'left'  => '20px',
										'right' => '20px',
									),
								),
							),
						),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/image',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
		);

		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$this->assertCount( 3, $result[0]['innerBlocks'] );
		$this->assertEquals( '140px', $result[0]['innerBlocks'][0]['email_attrs']['width'] );
		$this->assertEquals( '185px', $result[0]['innerBlocks'][1]['email_attrs']['width'] );
		$this->assertEquals( '255px', $result[0]['innerBlocks'][2]['email_attrs']['width'] );
		$image_block = $result[0]['innerBlocks'][1]['innerBlocks'][0];
		$this->assertEquals( '185px', $image_block['email_attrs']['width'] );
		$image_block = $result[0]['innerBlocks'][2]['innerBlocks'][0];
		$this->assertEquals( '215px', $image_block['email_attrs']['width'] );
	}
}
