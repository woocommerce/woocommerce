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
		$this->assertEquals( '218px', $result['innerBlocks'][0]['email_attrs']['width'] ); // 660 * 0.33
		$this->assertEquals( '100px', $result['innerBlocks'][1]['email_attrs']['width'] );
		$this->assertEquals( '132px', $result['innerBlocks'][2]['email_attrs']['width'] ); // 660 * 0.2
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
		$this->assertEquals( '264px', $inner_blocks[0]['email_attrs']['width'] ); // 660 * 0.4
		$this->assertEquals( '244px', $inner_blocks[0]['innerBlocks'][0]['email_attrs']['width'] ); // 264 - 10 - 10
		$this->assertEquals( '396px', $inner_blocks[1]['email_attrs']['width'] ); // 660 * 0.6
		$this->assertEquals( '356px', $inner_blocks[1]['innerBlocks'][0]['email_attrs']['width'] ); // 396 - 25 - 15
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
		$this->assertEquals( '206.67px', $inner_blocks[0]['email_attrs']['width'] ); // 620 / 3 rounded to 2 decimal places.
		$this->assertEquals( '207px', $inner_blocks[0]['innerBlocks'][0]['email_attrs']['width'] ); // Rounded to integer.
		$this->assertEquals( '206.67px', $inner_blocks[1]['email_attrs']['width'] ); // 620 / 3 rounded to 2 decimal places.
		$this->assertEquals( '207px', $inner_blocks[1]['innerBlocks'][0]['email_attrs']['width'] ); // Rounded to integer.
		$this->assertEquals( '206.67px', $inner_blocks[2]['email_attrs']['width'] ); // 620 / 3 rounded to 2 decimal places.
		$this->assertEquals( '207px', $inner_blocks[2]['innerBlocks'][0]['email_attrs']['width'] ); // Rounded to integer.
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
		$this->assertEquals( '207px', $inner_blocks[0]['email_attrs']['width'] ); // 620 * 0.3333
		$this->assertEquals( '200px', $inner_blocks[1]['email_attrs']['width'] ); // already defined.
		$this->assertEquals( '213px', $inner_blocks[2]['email_attrs']['width'] ); // 620 - 207 - 200
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
		$this->assertEquals( '660px', $result[1]['email_attrs']['width'] ); // no root padding in email_attrs, full width.
	}

	/**
	 * Test it subtracts root padding from block widths when set in email_attrs by Spacing_Preprocessor
	 */
	public function testItSubtractsRootPaddingFromEmailAttrs(): void {
		$blocks = array(
			// Block with root padding (set by Spacing_Preprocessor).
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'email_attrs' => array(
					'root-padding-left'  => '10px',
					'root-padding-right' => '10px',
				),
				'innerBlocks' => array(),
			),
			// Block without root padding (e.g., container that manages its own layout).
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array(),
			),
			// Full-width block with root padding should not be subtracted.
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(
					'align' => 'full',
				),
				'email_attrs' => array(
					'root-padding-left'  => '10px',
					'root-padding-right' => '10px',
				),
				'innerBlocks' => array(),
			),
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertCount( 3, $result );
		$this->assertEquals( '640px', $result[0]['email_attrs']['width'] ); // 660 - 10 - 10.
		$this->assertEquals( '660px', $result[1]['email_attrs']['width'] ); // No root padding, full width.
		$this->assertEquals( '660px', $result[2]['email_attrs']['width'] ); // Full-width, not subtracted.
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
		$this->assertEquals( '230px', $result[0]['innerBlocks'][1]['email_attrs']['width'] );
		$this->assertEquals( '250px', $result[0]['innerBlocks'][2]['email_attrs']['width'] );

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
		$this->assertEquals( '520px', $result[0]['innerBlocks'][1]['email_attrs']['width'] );
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
		$this->assertEquals( '195px', $result[0]['innerBlocks'][1]['email_attrs']['width'] );
		$this->assertEquals( '265px', $result[0]['innerBlocks'][2]['email_attrs']['width'] );
		$image_block = $result[0]['innerBlocks'][1]['innerBlocks'][0];
		$this->assertEquals( '195px', $image_block['email_attrs']['width'] ); // 195 column width, no column border or padding.
		$image_block = $result[0]['innerBlocks'][2]['innerBlocks'][0];
		$this->assertEquals( '195px', $image_block['email_attrs']['width'] ); // 265 - 30 (15px border each side) - 40 (20px padding each side).
	}

	/**
	 * Test it subtracts container padding from block widths
	 */
	public function testItSubtractsContainerPaddingFromEmailAttrs(): void {
		$blocks = array(
			// Block with both root and container padding.
			array(
				'blockName'   => 'core/paragraph',
				'attrs'       => array(),
				'email_attrs' => array(
					'root-padding-left'       => '10px',
					'root-padding-right'      => '10px',
					'container-padding-left'  => '20px',
					'container-padding-right' => '20px',
				),
				'innerBlocks' => array(),
			),
			// Alignfull block: container padding should NOT be subtracted.
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(
					'align' => 'full',
				),
				'email_attrs' => array(),
				'innerBlocks' => array(),
			),
			// Block with only container padding.
			array(
				'blockName'   => 'core/paragraph',
				'attrs'       => array(),
				'email_attrs' => array(
					'container-padding-left'  => '15px',
					'container-padding-right' => '15px',
				),
				'innerBlocks' => array(),
			),
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertCount( 3, $result );
		// 660 - 10 - 10 (root) - 20 - 20 (container) = 600.
		$this->assertEquals( '600px', $result[0]['email_attrs']['width'] );
		// Full-width: 660px (nothing subtracted).
		$this->assertEquals( '660px', $result[1]['email_attrs']['width'] );
		// 660 - 15 - 15 (container only) = 630.
		$this->assertEquals( '630px', $result[2]['email_attrs']['width'] );
	}

	/**
	 * Test it handles non-string width values
	 */
	public function testItHandlesNonStringWidthValues(): void {
		$styles                       = $this->styles;
		$styles['spacing']['padding'] = array(
			'left'   => '0px',
			'right'  => '0px',
			'top'    => '0px',
			'bottom' => '0px',
		);

		// Test numeric width (should be treated as percentage).
		$blocks = array(
			array(
				'blockName'   => 'core/paragraph',
				'attrs'       => array(
					'width' => 50,
				),
				'innerBlocks' => array(),
			),
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $styles );
		$this->assertEquals( '330px', $result[0]['email_attrs']['width'] ); // 660 * 0.5

		// Test array width (should default to 100%).
		$blocks = array(
			array(
				'blockName'   => 'core/paragraph',
				'attrs'       => array(
					'width' => array( 'value' => 50 ),
				),
				'innerBlocks' => array(),
			),
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $styles );
		$this->assertEquals( '660px', $result[0]['email_attrs']['width'] ); // 100% of 660

		// Test boolean width (should default to 100%).
		$blocks = array(
			array(
				'blockName'   => 'core/paragraph',
				'attrs'       => array(
					'width' => true,
				),
				'innerBlocks' => array(),
			),
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $styles );
		$this->assertEquals( '660px', $result[0]['email_attrs']['width'] ); // 100% of 660
	}

	/**
	 * Test children of a two-column grid are sized to one cell, so images don't overflow it.
	 */
	public function testItSizesTwoColumnGridChildrenToOneCell(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(
					'layout' => array(
						'type'        => 'grid',
						'columnCount' => 2,
					),
					'style'  => array(
						'spacing' => array(
							'padding' => array(
								'left'  => '15px',
								'right' => '15px',
							),
						),
					),
				),
				'email_attrs' => array(
					'two-column-grid' => true,
					'grid-column-gap' => '20px',
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/image',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/image',
						'attrs'       => array(
							'width' => '400px',
							'style' => array( 'border' => array( 'width' => '10px' ) ),
						),
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/group',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/image',
								'attrs'       => array( 'width' => '400px' ),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
			array(
				'blockName'   => 'core/image',
				'attrs'       => array( 'width' => '800px' ),
				'innerBlocks' => array(),
			),
		);

		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$grid   = $result[0];

		$this->assertEquals( '660px', $grid['email_attrs']['width'] );
		// (660 - 15 - 15 - 20) / 2 = 305.
		$this->assertEquals( '305px', $grid['innerBlocks'][0]['email_attrs']['width'] );
		$this->assertEquals( '285px', $grid['innerBlocks'][1]['email_attrs']['width'], 'A bordered image should leave room for its border, which is drawn outside it.' );
		$this->assertEquals( '305px', $grid['innerBlocks'][2]['email_attrs']['width'] );
		$this->assertEquals( '305px', $grid['innerBlocks'][2]['innerBlocks'][0]['email_attrs']['width'], 'A fixed width wider than the cell should be capped to the cell.' );
		$this->assertEquals( '800px', $result[1]['email_attrs']['width'], 'Fixed widths outside a grid are left as they are.' );
	}

	/**
	 * Test a responsive grid (column count plus minimum column width) stays two columns only when each cell meets the minimum width.
	 *
	 * @testWith ["12rem", true]
	 *           ["320px", true]
	 *           ["321px", false]
	 * @param string $minimum_width Minimum column width.
	 * @param bool   $side_by_side Whether the grid should stay two columns.
	 */
	public function testItStacksResponsiveGridNarrowerThanItsMinimumColumnWidth( string $minimum_width, bool $side_by_side ): void {
		$paragraph = array(
			'blockName'   => 'core/paragraph',
			'attrs'       => array(),
			'email_attrs' => array( 'two-column-grid-item' => true ),
			'innerBlocks' => array(),
		);
		$blocks    = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(
					'layout' => array(
						'type'               => 'grid',
						'columnCount'        => 2,
						'minimumColumnWidth' => $minimum_width,
					),
				),
				'email_attrs' => array(
					'two-column-grid' => true,
					'grid-row-gap'    => '24px',
					'grid-column-gap' => '20px',
				),
				'innerBlocks' => array( $paragraph, $paragraph ),
			),
		);

		// Each cell is (660 - 20px gap) / 2 = 320px.
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$grid   = $result[0];

		if ( $side_by_side ) {
			$this->assertTrue( $grid['email_attrs']['two-column-grid'] );
			$this->assertEquals( '320px', $grid['innerBlocks'][0]['email_attrs']['width'] );
			return;
		}

		$this->assertArrayNotHasKey( 'two-column-grid', $grid['email_attrs'] );
		$this->assertArrayNotHasKey( 'two-column-grid-item', $grid['innerBlocks'][1]['email_attrs'] );
		$this->assertArrayNotHasKey( 'margin-top', $grid['innerBlocks'][0]['email_attrs'] );
		$this->assertEquals( '24px', $grid['innerBlocks'][1]['email_attrs']['margin-top'], 'Stacked items are spaced by the row gap.' );
		$this->assertEquals( '660px', $grid['innerBlocks'][1]['email_attrs']['width'] );
	}
}
