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
		$slug     = 'my-theme';
		$resource = 'https://example.com/my-theme.zip';
		$options  = array( 'activate' => true );

		$install_theme = new InstallTheme( $slug, $resource, $options );

		$expected_array = array(
			'step'      => 'installTheme',
			'themeData' => array(
				'resource' => $resource,
				'slug'     => $slug,
			),
			'options'   => $options,
		);

		$this->assertEquals( $expected_array, $install_theme->prepare_json_array() );
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
				'step'      => array(
					'type' => 'string',
					'enum' => array( 'installTheme' ),
				),
				'themeData' => array(
					'anyOf' => array(
						require __DIR__ . '/../../../src/Steps/schemas/definitions/VFSReference.php',
						require __DIR__ . '/../../../src/Steps/schemas/definitions/LiteralReference.php',
						require __DIR__ . '/../../../src/Steps/schemas/definitions/CorePluginReference.php',
						require __DIR__ . '/../../../src/Steps/schemas/definitions/CoreThemeReference.php',
						require __DIR__ . '/../../../src/Steps/schemas/definitions/UrlReference.php',
						require __DIR__ . '/../../../src/Steps/schemas/definitions/GitDirectoryReference.php',
						require __DIR__ . '/../../../src/Steps/schemas/definitions/DirectoryLiteralReference.php',
					),
				),
				'options'   => array(
					'type'       => 'object',
					'properties' => array(
						'activate' => array(
							'type' => 'boolean',
						),
					),
				),
			),
			'required'   => array( 'step', 'themeData' ),
		);

		$this->assertEquals( $expected_schema, InstallTheme::get_schema() );
	}
}
