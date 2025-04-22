<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\Preprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Cleanup_Preprocessor;

/**
 * Unit test for Cleanup_Preprocessor
 */
class Cleanup_Preprocessor_Test extends \Email_Editor_Unit_Test {

	private const PARAGRAPH_BLOCK = array(
		'blockName' => 'core/paragraph',
		'attrs'     => array(),
		'innerHTML' => 'Paragraph content',
	);

	private const COLUMNS_BLOCK = array(
		'blockName'   => 'core/columns',
		'attrs'       => array(),
		'innerBlocks' => array(
			array(
				'blockName'   => 'core/column',
				'attrs'       => array(),
				'innerBlocks' => array(),
			),
		),
	);

	/**
	 * Instance of Cleanup_Preprocessor
	 *
	 * @var Cleanup_Preprocessor
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
		$this->preprocessor = new Cleanup_Preprocessor();
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
	 * Test it removes unwanted blocks
	 */
	public function testItRemovesUnwantedBlocks(): void {
		$blocks = array(
			self::COLUMNS_BLOCK,
			array(
				'blockName' => null,
				'attrs'     => array(),
				'innerHTML' => "\r\n",
			),
			self::PARAGRAPH_BLOCK,
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$this->assertCount( 2, $result );
		$this->assertEquals( self::COLUMNS_BLOCK, $result[0] );
		$this->assertEquals( self::PARAGRAPH_BLOCK, $result[1] );
	}

	/**
	 * Test it preserves all relevant blocks
	 */
	public function testItPreservesAllRelevantBlocks(): void {
		$blocks = array(
			self::COLUMNS_BLOCK,
			self::PARAGRAPH_BLOCK,
			self::COLUMNS_BLOCK,
		);
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$this->assertCount( 3, $result );
		$this->assertEquals( self::COLUMNS_BLOCK, $result[0] );
		$this->assertEquals( self::PARAGRAPH_BLOCK, $result[1] );
		$this->assertEquals( self::COLUMNS_BLOCK, $result[2] );
	}
}
