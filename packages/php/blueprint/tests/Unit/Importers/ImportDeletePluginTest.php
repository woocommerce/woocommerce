<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportDeletePlugin;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\DeletePlugin;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test the ImportDeletePlugin class.
 *
 * @package Automattic\WooCommerce\Blueprint\Tests\Unit\Importers
 */
class ImportDeletePluginTest extends TestCase {
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
	 * Test successful plugin deletion process.
	 *
	 * @return void
	 */
	public function test_process_successful_deletion() {
		$plugin_name = 'sample-plugin';

		// Create a mock schema object.
		$schema = Mockery::mock();
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$schema->pluginName = $plugin_name;

		// Create a partial mock of ImportDeletePlugin.
		$import_delete_plugin = Mockery::mock( ImportDeletePlugin::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Mock the delete_plugin_by_slug method.
		$import_delete_plugin->shouldReceive( 'delete_plugin_by_slug' )
			->with( $plugin_name )
			->andReturn( true );

		// Execute the process method.
		$result = $import_delete_plugin->process( $schema );

		// Assert the result is an instance of StepProcessorResult.
		$this->assertInstanceOf( StepProcessorResult::class, $result );

		// Assert success.
		$this->assertTrue( $result->is_success() );
		$this->assertEquals( DeletePlugin::get_step_name(), $result->get_step_name() );

		// Assert the info message is added.
		$messages = $result->get_messages( 'info' );
		$this->assertCount( 1, $messages );
		$this->assertEquals( "Deleted {$plugin_name}.", $messages[0]['message'] );
	}

	/**
	 * Test plugin deletion process when deletion fails.
	 *
	 * @return void
	 */
	public function test_process_failed_deletion() {
		$plugin_name = 'invalid-plugin';

		// Create a mock schema object.
		$schema = Mockery::mock();
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$schema->pluginName = $plugin_name;

		// Create a partial mock of ImportDeletePlugin.
		$import_delete_plugin = Mockery::mock( ImportDeletePlugin::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Mock the delete_plugin_by_slug method.
		$import_delete_plugin->shouldReceive( 'delete_plugin_by_slug' )
			->with( $plugin_name )
			->andReturn( false );

		// Execute the process method.
		$result = $import_delete_plugin->process( $schema );

		// Assert the result is an instance of StepProcessorResult.
		$this->assertInstanceOf( StepProcessorResult::class, $result );

		// Assert failure.
		$this->assertFalse( $result->is_success() );
		$this->assertEquals( DeletePlugin::get_step_name(), $result->get_step_name() );

		// Assert the error message is added.
		$messages = $result->get_messages( 'error' );
		$this->assertCount( 1, $messages );
		$this->assertEquals( "Unable to delete {$plugin_name}.", $messages[0]['message'] );
	}

	/**
	 * Test getting the step class.
	 *
	 * @return void
	 */
	public function test_get_step_class() {
		$import_delete_plugin = new ImportDeletePlugin();

		// Assert the correct step class is returned.
		$this->assertEquals( DeletePlugin::class, $import_delete_plugin->get_step_class() );
	}
}
