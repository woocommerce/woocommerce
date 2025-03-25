<?php

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\Blueprint\Steps\ActivateTheme;

/**
 * Unit tests for ActivateTheme class.
 */
class ActivateThemeTest extends TestCase {
	/**
	 * Test the constructor and JSON preparation.
	 */
	public function testConstructorAndPrepareJsonArray() {
		$theme_name = 'my-theme';
		$activateTheme = new ActivateTheme( $theme_name );

		$expected_array = array(
			'step'      => 'activateTheme',
			'themeName' => $theme_name,
		);

		$this->assertEquals( $expected_array, $activateTheme->prepare_json_array() );
	}

	/**
	 * Test the static get_step_name method.
	 */
	public function testGetStepName() {
		$this->assertEquals( 'activateTheme', ActivateTheme::get_step_name() );
	}

	/**
	 * Test the static get_schema method.
	 */
	public function testGetSchema() {
		$expected_schema = array(
			'type'       => 'object',
			'properties' => array(
				'step'      => array(
					'type' => 'string',
					'enum' => array( 'activateTheme' ),
				),
				'themeName' => array(
					'type' => 'string',
				),
			),
			'required'   => array( 'step', 'themeName' ),
		);

		$this->assertEquals( $expected_schema, ActivateTheme::get_schema() );
	}
}
