<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Tests\Integration\Engine\Renderer\ContentRenderer\Postprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Variables_Postprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit test for Variables_Postprocessor
 */
class Variables_Postprocessor_Test extends \Email_Editor_Unit_Test {
	/**
	 * Instance of Variables_Postprocessor
	 *
	 * @var Variables_Postprocessor
	 */
	private Variables_Postprocessor $postprocessor;

	/**
	 * Theme_Controller mock
	 *
	 * @var Theme_Controller & MockObject
	 */
	private $theme_controller_mock;

	/**
	 * Set up the test
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->theme_controller_mock = $this->createMock( Theme_Controller::class );
		$this->postprocessor         = new Variables_Postprocessor( $this->theme_controller_mock );
	}

	/**
	 * Test it replaces variables in the content
	 */
	public function testItReplacesVariablesInStyleAttributes(): void {
		$variables_map = array(
			'--wp--preset--spacing--10' => '10px',
			'--wp--preset--spacing--20' => '20px',
			'--wp--preset--spacing--30' => '30px',
		);
		$this->theme_controller_mock->method( 'get_variables_values_map' )->willReturn( $variables_map );
		$html   = '<div style="padding:var(--wp--preset--spacing--10);margin:var(--wp--preset--spacing--20)"><p style="color:white;padding-left:var(--wp--preset--spacing--10);">Helloo I have padding var(--wp--preset--spacing--10); </p></div>';
		$result = $this->postprocessor->postprocess( $html );
		$this->assertEquals( '<div style="padding:10px;margin:20px"><p style="color:white;padding-left:10px;">Helloo I have padding var(--wp--preset--spacing--10); </p></div>', $result );
	}

	/**
	 * Test it leaves out a declaration whose preset the theme does not define.
	 *
	 * No email client can resolve such a variable, so the declaration is one the client would drop.
	 */
	public function testItLeavesOutDeclarationsWithUndefinedPresets(): void {
		$this->theme_controller_mock->method( 'get_variables_values_map' )->willReturn(
			array( '--wp--preset--spacing--10' => '10px' )
		);
		$html   = '<a style="text-decoration: none; color: var(--wp--preset--color--accent-6); padding: var(--wp--preset--spacing--10)">Link</a><p style="color:var(--wp--preset--color--accent-6)">I mention var(--wp--preset--color--accent-6);</p>';
		$result = $this->postprocessor->postprocess( $html );
		$this->assertSame( '<a style="text-decoration: none; padding: 10px">Link</a><p style="">I mention var(--wp--preset--color--accent-6);</p>', $result );
	}

	/**
	 * Test it resolves a preset whose value points at another preset, whatever their order.
	 *
	 * A palette color can name another palette color. The target is listed first here, so one
	 * pass over the variables leaves the reference behind.
	 */
	public function testItResolvesAPresetThatPointsAtAnotherPreset(): void {
		$this->theme_controller_mock->method( 'get_variables_values_map' )->willReturn(
			array(
				'--wp--preset--color--contrast' => '#111111',
				'--wp--preset--color--primary'  => 'var(--wp--preset--color--contrast)',
				'--wp--preset--color--brand'    => 'var(--wp--preset--color--accent-6)',
			)
		);
		$html   = '<a style="color: var(--wp--preset--color--primary)">Resolved</a><a style="margin: 0; color: var(--wp--preset--color--brand)">Points at nothing</a>';
		$result = $this->postprocessor->postprocess( $html );
		$this->assertSame( '<a style="color: #111111">Resolved</a><a style="margin: 0;">Points at nothing</a>', $result );
	}
}
