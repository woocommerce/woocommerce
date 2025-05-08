<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\Preprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Quote_Preprocessor;

/**
 * Unit test for Quote_Preprocessor
 */
class Quote_Preprocessor_Test extends \Email_Editor_Unit_Test {

	private const PARAGRAPH_BLOCK = array(
		'blockName' => 'core/paragraph',
		'attrs'     => array(),
		'innerHTML' => 'Paragraph content',
	);

	private const QUOTE_BLOCK_RIGHT = array(
		'blockName'   => 'core/quote',
		'attrs'       => array(
			'textAlign' => 'right',
		),
		'innerBlocks' => array(
			array(
				'blockName' => 'core/paragraph',
				'attrs'     => array(),
				'innerHTML' => 'Quote content',
			),
		),
	);

	private const QUOTE_BLOCK_NO_TEXTALIGN = array(
		'blockName'   => 'core/quote',
		'attrs'       => array(),
		'innerBlocks' => array(
			array(
				'blockName' => 'core/paragraph',
				'attrs'     => array(),
				'innerHTML' => 'Quote content',
			),
		),
	);

	private const QUOTE_BLOCK_WITH_PREALIGNED_CHILD = array(
		'blockName'   => 'core/quote',
		'attrs'       => array(
			'textAlign' => 'right',
		),
		'innerBlocks' => array(
			array(
				'blockName' => 'core/paragraph',
				'attrs'     => array(
					'align' => 'left',
				),
				'innerHTML' => 'Quote content',
			),
		),
	);

	private const QUOTE_BLOCK_WITH_CUSTOM_FONT_SIZE = array(
		'blockName'   => 'core/quote',
		'attrs'       => array(
			'style' => array(
				'typography' => array(
					'fontSize' => '18px',
				),
			),
		),
		'innerBlocks' => array(
			array(
				'blockName' => 'core/paragraph',
				'attrs'     => array(),
				'innerHTML' => 'Quote content',
			),
		),
	);

	private const QUOTE_BLOCK_WITH_CHILD_FONT_SIZE = array(
		'blockName'   => 'core/quote',
		'attrs'       => array(
			'style' => array(
				'typography' => array(
					'fontSize' => '18px',
				),
			),
		),
		'innerBlocks' => array(
			array(
				'blockName' => 'core/paragraph',
				'attrs'     => array(
					'style' => array(
						'typography' => array(
							'fontSize' => '16px',
						),
					),
				),
				'innerHTML' => 'Quote content',
			),
		),
	);

	/**
	 * Instance of Quote_Preprocessor
	 *
	 * @var Quote_Preprocessor
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
		$this->preprocessor = new Quote_Preprocessor();
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
			'blocks'  => array(
				'core/quote' => array(
					'typography' => array(
						'fontSize' => '20px',
					),
				),
			),
		);
	}

	/**
	 * Test it applies parent quote alignment to child blocks
	 */
	public function testItAppliesParentQuoteAlignmentToChildBlocks(): void {
		$blocks = array(
			self::QUOTE_BLOCK_RIGHT,
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertEquals( 'right', $result[0]['innerBlocks'][0]['attrs']['textAlign'] );
	}

	/**
	 * Test it preserves existing child block alignment
	 */
	public function testItPreservesExistingChildBlockAlignment(): void {
		$blocks = array(
			self::QUOTE_BLOCK_WITH_PREALIGNED_CHILD,
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertEquals( 'left', $result[0]['innerBlocks'][0]['attrs']['align'] );
	}

	/**
	 * Test it doesn't affect blocks outside quotes
	 */
	public function testItDoesntAffectBlocksOutsideQuotes(): void {
		$blocks = array(
			self::PARAGRAPH_BLOCK,
			self::QUOTE_BLOCK_RIGHT,
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertArrayNotHasKey( 'align', $result[0]['attrs'] );
	}

	/**
	 * Test it handles quotes without alignment properly
	 */
	public function testItHandlesQuotesWithoutAlignmentProperly(): void {
		$blocks = array(
			self::QUOTE_BLOCK_NO_TEXTALIGN,
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertArrayNotHasKey( 'align', $result[0]['innerBlocks'][0]['attrs'] );
	}

	/**
	 * Test it handles nested quotes properly
	 */
	public function testItHandlesNestedQuotesProperly(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/quote',
				'attrs'       => array(
					'textAlign' => 'right',
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/quote',
						'attrs'       => array(
							'textAlign' => 'left',
						),
						'innerBlocks' => array(
							array(
								'blockName' => 'core/paragraph',
								'attrs'     => array(),
								'innerHTML' => 'Nested quote content',
							),
						),
					),
				),
			),
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertEquals( 'left', $result[0]['innerBlocks'][0]['innerBlocks'][0]['attrs']['textAlign'] );
	}

	/**
	 * Test it applies theme typography to quote children
	 */
	public function testItAppliesThemeTypographyToQuoteChildren(): void {
		$blocks = array(
			self::QUOTE_BLOCK_NO_TEXTALIGN,
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertEquals( '20px', $result[0]['innerBlocks'][0]['attrs']['style']['typography']['fontSize'] );
	}

	/**
	 * Test it applies quote typography to children
	 */
	public function testItAppliesQuoteTypographyToChildren(): void {
		$blocks = array(
			self::QUOTE_BLOCK_WITH_CUSTOM_FONT_SIZE,
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertEquals( '18px', $result[0]['innerBlocks'][0]['attrs']['style']['typography']['fontSize'] );
	}

	/**
	 * Test it preserves child typography when set
	 */
	public function testItPreservesChildTypographyWhenSet(): void {
		$blocks = array(
			self::QUOTE_BLOCK_WITH_CHILD_FONT_SIZE,
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertEquals( '16px', $result[0]['innerBlocks'][0]['attrs']['style']['typography']['fontSize'] );
	}

	/**
	 * Test it merges theme and quote typography
	 */
	public function testItMergesThemeAndQuoteTypography(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/quote',
				'attrs'       => array(
					'style' => array(
						'typography' => array(
							'fontStyle' => 'italic',
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName' => 'core/paragraph',
						'attrs'     => array(),
						'innerHTML' => 'Quote content',
					),
				),
			),
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );

		$this->assertEquals( '20px', $result[0]['innerBlocks'][0]['attrs']['style']['typography']['fontSize'] );
		$this->assertEquals( 'italic', $result[0]['innerBlocks'][0]['attrs']['style']['typography']['fontStyle'] );
	}
}
