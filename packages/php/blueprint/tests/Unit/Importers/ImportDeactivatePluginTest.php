<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportDeactivatePlugin;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\DeactivatePlugin;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test the ImportDeactivatePlugin class.
 *
 * @package Automattic\WooCommerce\Blueprint\Tests\Unit\Importers
 */
class ImportDeactivatePluginTest extends TestCase {
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
	 * Test successful plugin deactivation process.
	 *
	 * @return void
	 */
	public function test_process_successful_deactivation() {
		$plugin_name = 'sample-plugin';

		// Create a mock schema object.
		$schema = Mockery::mock();
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$schema->pluginName = $plugin_name;

		// Create a partial mock of ImportDeactivatePlugin.
		$import_deactivate_plugin = Mockery::mock( ImportDeactivatePlugin::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Mock the deactivate_plugin_by_slug method.
		$import_deactivate_plugin->shouldReceive( 'deactivate_plugin_by_slug' )
			->with( $plugin_name )
			->andReturnNull();

		// Execute the process method.
		$result = $import_deactivate_plugin->process( $schema );

		// Assert the result is an instance of StepProcessorResult.
		$this->assertInstanceOf( StepProcessorResult::class, $result );

		// Assert success.
		$this->assertTrue( $result->is_success() );
		$this->assertEquals( DeactivatePlugin::get_step_name(), $result->get_step_name() );

		// Assert the info message is added.
		$messages = $result->get_messages( 'info' );
		$this->assertCount( 1, $messages );
		$this->assertEquals( "Deactivated {$plugin_name}.", $messages[0]['message'] );
	}

	/**
	 * Test getting the step class.
	 *
	 * @return void
	 */
	public function test_get_step_class() {
		$import_deactivate_plugin = new ImportDeactivatePlugin();

		// Assert the correct step class is returned.
		$this->assertEquals( DeactivatePlugin::class, $import_deactivate_plugin->get_step_class() );
	}
}
