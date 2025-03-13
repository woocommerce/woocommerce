<?php

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\Blueprint\Steps\DeactivatePlugin;

/**
 * Unit tests for DeactivatePlugin class.
 */
class DeactivatePluginTest extends TestCase {
	/**
	 * Test the constructor and JSON preparation.
	 */
	public function testConstructorAndPrepareJsonArray() {
		$plugin_name = 'sample-plugin/sample-plugin.php';
		$deactivatePlugin = new DeactivatePlugin( $plugin_name );

		$expected_array = array(
			'step'       => 'deactivatePlugin',
			'pluginName' => $plugin_name,
		);

		$this->assertEquals( $expected_array, $deactivatePlugin->prepare_json_array() );
	}

	/**
	 * Test the static get_step_name method.
	 */
	public function testGetStepName() {
		$this->assertEquals( 'deactivatePlugin', DeactivatePlugin::get_step_name() );
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
					'enum' => array( 'deactivatePlugin' ),
				),
				'pluginName' => array(
					'type' => 'string',
				),
			),
			'required'   => array( 'step', 'pluginName' ),
		);

		$this->assertEquals( $expected_schema, DeactivatePlugin::get_schema() );
	}
}
