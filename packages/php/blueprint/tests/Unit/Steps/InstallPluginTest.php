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
		$slug = 'sample-plugin';
		$resource = 'https://example.com/sample-plugin.zip';
		$options = array( 'activate' => true );

		$installPlugin = new InstallPlugin( $slug, $resource, $options );

		$expected_array = array(
			'step'          => 'installPlugin',
			'pluginZipFile' => array(
				'resource' => $resource,
				'slug'     => $slug,
			),
			'options'       => $options,
		);

		$this->assertEquals( $expected_array, $installPlugin->prepare_json_array() );
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
				'step'          => array(
					'type' => 'string',
					'enum' => array( 'installPlugin' ),
				),
				'pluginZipFile' => array(
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
				'options'       => array(
					'type'       => 'object',
					'properties' => array(
						'activate' => array(
							'type' => 'boolean',
						),
					),
				),
			),
			'required'   => array( 'step', 'pluginZipFile' ),
		);

		$this->assertEquals( $expected_schema, InstallPlugin::get_schema() );
	}
}
