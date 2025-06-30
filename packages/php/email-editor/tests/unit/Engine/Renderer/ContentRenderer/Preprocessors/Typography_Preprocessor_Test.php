<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\Preprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Typography_Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;

/**
 * Unit test for Typography_Preprocessor
 */
class Typography_Preprocessor_Test extends \Email_Editor_Unit_Test {

	/**
	 * Instance of Typography_Preprocessor
	 *
	 * @var Typography_Preprocessor
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
		$settings_mock = $this->createMock( Settings_Controller::class );
		$theme_mock    = $this->createMock( \WP_Theme_JSON::class );
		$theme_mock->method( 'get_data' )->willReturn(
			array(
				'styles'   => array(
					'color'      => array(
						'text' => '#000000',
					),
					'typography' => array(
						'fontSize'   => '13px',
						'fontFamily' => 'Arial',
					),
				),
				'settings' => array(
					'typography' => array(
						'fontFamilies' => array(
							array(
								'slug'       => 'arial-slug',
								'name'       => 'Arial Name',
								'fontFamily' => 'Arial',
							),
							array(
								'slug'       => 'georgia-slug',
								'name'       => 'Georgia Name',
								'fontFamily' => 'Georgia',
							),
						),
					),
				),
			)
		);
		$settings_mock->method( 'get_theme' )->willReturn( $theme_mock );
		// This slug translate mock expect slugs in format slug-10px and will return 10px.
		$settings_mock->method( 'translate_slug_to_font_size' )->willReturnCallback(
			function ( $slug ) {
				return str_replace( 'slug-', '', $slug );
			}
		);
		$this->preprocessor = new Typography_Preprocessor( $settings_mock );
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
	 * Test it copies columns typography
	 */
	public function testItCopiesColumnsTypography(): void {
		$blocks               = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(
					'fontFamily' => 'arial-slug',
					'style'      => array(
						'color'      => array(
							'text' => '#aa00dd',
						),
						'typography' => array(
							'fontSize'       => '12px',
							'textDecoration' => 'underline',
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
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
		$expected_email_attrs = array(
			'color'           => '#aa00dd',
			'font-size'       => '12px',
			'text-decoration' => 'underline',
		);
		$result               = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$result               = $result[0];
		$this->assertCount( 2, $result['innerBlocks'] );
		$this->assertEquals( $expected_email_attrs, $result['email_attrs'] );
		$this->assertEquals( $expected_email_attrs, $result['innerBlocks'][0]['email_attrs'] );
		$this->assertEquals( $expected_email_attrs, $result['innerBlocks'][1]['email_attrs'] );
		$this->assertEquals( $expected_email_attrs, $result['innerBlocks'][1]['innerBlocks'][0]['email_attrs'] );
	}

	/**
	 * Test it replaces font family slugs with values
	 */
	public function testItReplacesFontSizeSlugsWithValues(): void {
		$blocks               = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(
					'fontSize' => 'slug-20px',
					'style'    => array(),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
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
		$expected_email_attrs = array(
			'color'     => '#000000',
			'font-size' => '20px',
		);
		$result               = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$result               = $result[0];
		$this->assertCount( 2, $result['innerBlocks'] );
		$this->assertEquals( $expected_email_attrs, $result['email_attrs'] );
		$this->assertEquals( $expected_email_attrs, $result['innerBlocks'][0]['email_attrs'] );
		$this->assertEquals( $expected_email_attrs, $result['innerBlocks'][1]['email_attrs'] );
		$this->assertEquals( $expected_email_attrs, $result['innerBlocks'][1]['innerBlocks'][0]['email_attrs'] );
	}

	/**
	 * Test it does not copy columns width
	 */
	public function testItDoesNotCopyColumnsWidth(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'email_attrs' => array(
					'width' => '640px',
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'innerBlocks' => array(),
					),
					array(
						'blockName'   => 'core/column',
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
		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$result = $result[0];
		$this->assertCount( 2, $result['innerBlocks'] );
		$this->assertEquals(
			array(
				'width'     => '640px',
				'color'     => '#000000',
				'font-size' => '13px',
			),
			$result['email_attrs']
		);
		$default_font_styles = array(
			'color'     => '#000000',
			'font-size' => '13px',
		);
		$this->assertEquals( $default_font_styles, $result['innerBlocks'][0]['email_attrs'] );
		$this->assertEquals( $default_font_styles, $result['innerBlocks'][1]['email_attrs'] );
		$this->assertEquals( $default_font_styles, $result['innerBlocks'][1]['innerBlocks'][0]['email_attrs'] );
	}

	/**
	 * Test it overrides columns typography
	 */
	public function testItOverridesColumnsTypography(): void {
		$blocks                = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(
					'fontFamily' => 'arial-slug',
					'style'      => array(
						'color'      => array(
							'text' => '#aa00dd',
						),
						'typography' => array(
							'fontSize' => '12px',
						),
					),
				),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'fontFamily' => 'georgia-slug',
							'style'      => array(
								'color'      => array(
									'text' => '#cc22aa',
								),
								'typography' => array(
									'fontSize' => '18px',
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
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(
							'fontFamily' => 'georgia-slug',
							'style'      => array(
								'color'      => array(
									'text' => '#cc22aa',
								),
								'typography' => array(
									'fontSize' => '18px',
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
		$expected_email_attrs1 = array(
			'color'     => '#aa00dd',
			'font-size' => '12px',
		);
		$expected_email_attrs2 = array(
			'color'     => '#cc22aa',
			'font-size' => '18px',
		);
		$result                = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$child1                = $result[0];
		$child2                = $result[1];
		$this->assertCount( 2, $child1['innerBlocks'] );
		$this->assertEquals( $expected_email_attrs1, $child1['email_attrs'] );
		$this->assertEquals( $expected_email_attrs2, $child1['innerBlocks'][0]['email_attrs'] );
		$this->assertEquals( $expected_email_attrs2, $child1['innerBlocks'][0]['innerBlocks'][0]['email_attrs'] );
		$this->assertEquals( $expected_email_attrs1, $child1['innerBlocks'][1]['email_attrs'] );
		$this->assertEquals( $expected_email_attrs1, $child1['innerBlocks'][1]['innerBlocks'][0]['email_attrs'] );
		$this->assertCount( 1, $child2['innerBlocks'] );
		$this->assertEquals(
			array(
				'color'     => '#000000',
				'font-size' => '13px',
			),
			$child2['email_attrs']
		);
		$this->assertEquals( $expected_email_attrs2, $child2['innerBlocks'][0]['email_attrs'] );
		$this->assertEquals( $expected_email_attrs2, $child2['innerBlocks'][0]['innerBlocks'][0]['email_attrs'] );
	}
}
