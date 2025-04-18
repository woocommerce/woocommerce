<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportActivatePlugin;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\ActivatePlugin;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test the ImportActivatePlugin class.
 *
 * @package Automattic\WooCommerce\Blueprint\Tests\Unit\Importers
 */
class ImportActivatePluginTest extends TestCase {

	/**
	 * Tear down the test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test the process method with a successful activation.
	 *
	 * @return void
	 */
	public function test_process_successful_activation() {
		$plugin_path = 'sample-plugin';

		// Create a mock schema object.
		$schema = Mockery::mock();
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$schema->pluginPath = $plugin_path;

		// Create a partial mock of ImportActivatePlugin.
		$import_activate_plugin = Mockery::mock( ImportActivatePlugin::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Mock the activate_plugin_by_slug method.
		$import_activate_plugin->shouldReceive( 'wp_activate_plugin' )
			->with( $plugin_path )
			->andReturn( true );

		// Execute the process method.
		$result = $import_activate_plugin->process( $schema );

		// Assert the result is an instance of StepProcessorResult.
		$this->assertInstanceOf( StepProcessorResult::class, $result );

		// Assert success.
		$this->assertTrue( $result->is_success() );
		$this->assertEquals( ActivatePlugin::get_step_name(), $result->get_step_name() );

		// Assert the success message is added.
		$messages = $result->get_messages( 'info' );
		$this->assertCount( 1, $messages );
		$this->assertEquals( "Activated {$plugin_path}.", $messages[0]['message'] );
	}

	/**
	 * Test the process method with a failed activation.
	 *
	 * @return void
	 */
	public function test_process_failed_activation() {
		$plugin_path = 'invalid-plugin';

		// Create a mock schema object.
		$schema = Mockery::mock();
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$schema->pluginPath = $plugin_path;

		// Create a partial mock of ImportActivatePlugin.
		$import_activate_plugin = Mockery::mock( ImportActivatePlugin::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Mock the activate_plugin_by_slug method.
		$import_activate_plugin->shouldReceive( 'wp_activate_plugin' )
			->with( $plugin_path )
			->andReturn( new \WP_Error( 'error', 'Error message' ) );

		// Execute the process method.
		$result = $import_activate_plugin->process( $schema );

		// Assert the result is an instance of StepProcessorResult.
		$this->assertInstanceOf( StepProcessorResult::class, $result );

		// Assert failure.
		$this->assertFalse( $result->is_success() );
		$this->assertEquals( ActivatePlugin::get_step_name(), $result->get_step_name() );

		// Assert the error message is added.
		$messages = $result->get_messages( 'error' );
		$this->assertCount( 1, $messages );
		$this->assertEquals( "Unable to activate {$plugin_path}.", $messages[0]['message'] );
	}
}
