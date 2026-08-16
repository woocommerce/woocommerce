<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;

/**
 * Unit test for Preset_Variable_Resolver
 */
class Preset_Variable_Resolver_Test extends \Email_Editor_Unit_Test {

	/**
	 * Variables map used across tests.
	 *
	 * @var array
	 */
	private array $variables_map;

	/**
	 * Set up the test
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->variables_map = array(
			'--wp--preset--spacing--10'  => '10px',
			'--wp--preset--spacing--20'  => '24px',
			'--wp--preset--spacing--30'  => '30px',
			'--wp--preset--color--black' => '#000000',
		);
	}

	/**
	 * Test resolve returns pixel value for a preset reference.
	 */
	public function testResolveConvertsPresetToPixelValue(): void {
		$this->assertSame( '24px', Preset_Variable_Resolver::resolve( 'var:preset|spacing|20', $this->variables_map ) );
	}

	/**
	 * Test resolve returns original value when not a preset reference.
	 */
	public function testResolveReturnsOriginalForNonPreset(): void {
		$this->assertSame( '20px', Preset_Variable_Resolver::resolve( '20px', $this->variables_map ) );
	}

	/**
	 * Test resolve returns original value when variables map is empty.
	 */
	public function testResolveReturnsOriginalWhenMapIsEmpty(): void {
		$this->assertSame( 'var:preset|spacing|20', Preset_Variable_Resolver::resolve( 'var:preset|spacing|20', array() ) );
	}

	/**
	 * Test resolve returns original preset when key is not in the map.
	 */
	public function testResolveReturnsOriginalWhenKeyNotFound(): void {
		$this->assertSame( 'var:preset|spacing|99', Preset_Variable_Resolver::resolve( 'var:preset|spacing|99', $this->variables_map ) );
	}

	/**
	 * Test resolve works with non-spacing presets (e.g. color).
	 */
	public function testResolveWorksWithColorPresets(): void {
		$this->assertSame( '#000000', Preset_Variable_Resolver::resolve( 'var:preset|color|black', $this->variables_map ) );
	}

	/**
	 * Test resolve passes through zero values.
	 */
	public function testResolvePassesThroughZeroValues(): void {
		$this->assertSame( '0px', Preset_Variable_Resolver::resolve( '0px', $this->variables_map ) );
		$this->assertSame( '0', Preset_Variable_Resolver::resolve( '0', $this->variables_map ) );
	}

	/**
	 * Test is_preset_reference returns true for preset references.
	 */
	public function testIsPresetReferenceReturnsTrueForPreset(): void {
		$this->assertTrue( Preset_Variable_Resolver::is_preset_reference( 'var:preset|spacing|20' ) );
		$this->assertTrue( Preset_Variable_Resolver::is_preset_reference( 'var:preset|color|black' ) );
	}

	/**
	 * Test is_preset_reference returns false for non-preset values.
	 */
	public function testIsPresetReferenceReturnsFalseForNonPreset(): void {
		$this->assertFalse( Preset_Variable_Resolver::is_preset_reference( '20px' ) );
		$this->assertFalse( Preset_Variable_Resolver::is_preset_reference( '0' ) );
		$this->assertFalse( Preset_Variable_Resolver::is_preset_reference( '' ) );
		$this->assertFalse( Preset_Variable_Resolver::is_preset_reference( 'var(--wp--preset--spacing--20)' ) );
	}

	/**
	 * Test to_css_var converts preset reference to CSS var() syntax.
	 */
	public function testToCssVarConvertsPresetToCssVar(): void {
		$this->assertSame( 'var(--wp--preset--spacing--20)', Preset_Variable_Resolver::to_css_var( 'var:preset|spacing|20' ) );
		$this->assertSame( 'var(--wp--preset--color--black)', Preset_Variable_Resolver::to_css_var( 'var:preset|color|black' ) );
	}

	/**
	 * Test to_css_var returns original value for non-preset.
	 */
	public function testToCssVarReturnsOriginalForNonPreset(): void {
		$this->assertSame( '20px', Preset_Variable_Resolver::to_css_var( '20px' ) );
	}
}
