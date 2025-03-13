<?php

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\Blueprint\Steps\InstallTheme;

/**
 * Unit tests for InstallTheme class.
 */
class InstallThemeTest extends TestCase {
	/**
	 * Test the constructor and JSON preparation.
	 */
	public function testConstructorAndPrepareJsonArray() {
		$slug = 'my-theme';
		$resource = 'https://example.com/my-theme.zip';
		$options = array( 'activate' => true );

		$installTheme = new InstallTheme( $slug, $resource, $options );

		$expected_array = array(
			'step'         => 'installTheme',
			'themeZipFile' => array(
				'resource' => $resource,
				'slug'     => $slug,
			),
			'options'      => $options,
		);

		$this->assertEquals( $expected_array, $installTheme->prepare_json_array() );
	}

	/**
	 * Test the static get_step_name method.
	 */
	public function testGetStepName() {
		$this->assertEquals( 'installTheme', InstallTheme::get_step_name() );
	}

	/**
	 * Test the static get_schema method.
	 */
	public function testGetSchema() {
		$expected_schema = array(
			'type'       => 'object',
			'properties' => array(
				'step'         => array(
					'type' => 'string',
					'enum' => array( 'installTheme' ),
				),
				'themeZipFile' => array(
					'type'       => 'object',
					'properties' => array(
						'resource' => array(
							'type' => 'string',
						),
						'slug'     => array(
							'type' => 'string',
						),
					),
					'required'   => array( 'resource', 'slug' ),
				),
				'options'      => array(
					'type'       => 'object',
					'properties' => array(
						'activate' => array(
							'type' => 'boolean',
						),
					),
				),
			),
			'required'   => array( 'step', 'themeZipFile' ),
		);

		$this->assertEquals( $expected_schema, InstallTheme::get_schema() );
	}
}
