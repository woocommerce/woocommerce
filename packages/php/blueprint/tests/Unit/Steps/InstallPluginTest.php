<?php

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\Blueprint\Steps\InstallPlugin;

/**
 * Unit tests for InstallPlugin class.
 */
class InstallPluginTest extends TestCase {
	/**
	 * Test the constructor and JSON preparation.
	 */
	public function testConstructorAndPrepareJsonArray() {
		$slug     = 'sample-plugin';
		$resource = 'https://example.com/sample-plugin.zip';
		$options  = array( 'activate' => true );

		$install_plugin = new InstallPlugin( $slug, $resource, $options );

		$expected_array = array(
			'step'       => 'installPlugin',
			'pluginData' => array(
				'resource' => $resource,
				'slug'     => $slug,
			),
			'options'    => $options,
		);

		$this->assertEquals( $expected_array, $install_plugin->prepare_json_array() );
	}

	/**
	 * Test the static get_step_name method.
	 */
	public function testGetStepName() {
		$this->assertEquals( 'installPlugin', InstallPlugin::get_step_name() );
	}

	/**
	 * Test the static get_schema method.
	 */
	public function testGetSchema() {
		$expected_schema = array(
			'type'       => 'object',
			'properties' => array(
				'step'       => array(
					'type' => 'string',
					'enum' => array( 'installPlugin' ),
				),
				'pluginData' => array(
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
				'options'    => array(
					'type'       => 'object',
					'properties' => array(
						'activate' => array(
							'type' => 'boolean',
						),
					),
				),
			),
			'required'   => array( 'step', 'pluginData' ),
		);

		$this->assertEquals( $expected_schema, InstallPlugin::get_schema() );
	}
}
