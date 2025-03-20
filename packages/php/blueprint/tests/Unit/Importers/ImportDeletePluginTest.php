<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportDeletePlugin;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\DeletePlugin;
use Mockery;
use PHPUnit\Framework\TestCase;

class ImportDeletePluginTest extends TestCase {
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_process_successful_deletion() {
		$pluginName = 'sample-plugin';

		// Create a mock schema object
		$schema = Mockery::mock();
		$schema->pluginName = $pluginName;

		// Create a partial mock of ImportDeletePlugin
		$importDeletePlugin = Mockery::mock(ImportDeletePlugin::class)
		                             ->makePartial()
		                             ->shouldAllowMockingProtectedMethods();

		// Mock the delete_plugin_by_slug method
		$importDeletePlugin->shouldReceive('delete_plugin_by_slug')
		                   ->with($pluginName)
		                   ->andReturn(true);

		// Execute the process method
		$result = $importDeletePlugin->process($schema);

		// Assert the result is an instance of StepProcessorResult
		$this->assertInstanceOf(StepProcessorResult::class, $result);

		// Assert success
		$this->assertTrue($result->is_success());
		$this->assertEquals(DeletePlugin::get_step_name(), $result->get_step_name());

		// Assert the info message is added
		$messages = $result->get_messages('info');
		$this->assertCount(1, $messages);
		$this->assertEquals("Deleted {$pluginName}.", $messages[0]['message']);
	}

	public function test_process_failed_deletion() {
		$pluginName = 'invalid-plugin';

		// Create a mock schema object
		$schema = Mockery::mock();
		$schema->pluginName = $pluginName;

		// Create a partial mock of ImportDeletePlugin
		$importDeletePlugin = Mockery::mock(ImportDeletePlugin::class)
		                             ->makePartial()
		                             ->shouldAllowMockingProtectedMethods();

		// Mock the delete_plugin_by_slug method
		$importDeletePlugin->shouldReceive('delete_plugin_by_slug')
		                   ->with($pluginName)
		                   ->andReturn(false);

		// Execute the process method
		$result = $importDeletePlugin->process($schema);

		// Assert the result is an instance of StepProcessorResult
		$this->assertInstanceOf(StepProcessorResult::class, $result);

		// Assert failure
		$this->assertFalse($result->is_success());
		$this->assertEquals(DeletePlugin::get_step_name(), $result->get_step_name());

		// Assert the error message is added
		$messages = $result->get_messages('error');
		$this->assertCount(1, $messages);
		$this->assertEquals("Unable to delete {$pluginName}.", $messages[0]['message']);
	}

	public function test_get_step_class() {
		$importDeletePlugin = new ImportDeletePlugin();

		// Assert the correct step class is returned
		$this->assertEquals(DeletePlugin::class, $importDeletePlugin->get_step_class());
	}
}
