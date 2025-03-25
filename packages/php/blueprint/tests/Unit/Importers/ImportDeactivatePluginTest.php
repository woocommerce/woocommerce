<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportDeactivatePlugin;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\DeactivatePlugin;
use Mockery;
use PHPUnit\Framework\TestCase;

class ImportDeactivatePluginTest extends TestCase {
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_process_successful_deactivation() {
		$pluginName = 'sample-plugin';

		// Create a mock schema object
		$schema = Mockery::mock();
		$schema->pluginName = $pluginName;

		// Create a partial mock of ImportDeactivatePlugin
		$importDeactivatePlugin = Mockery::mock(ImportDeactivatePlugin::class)
		                                 ->makePartial()
		                                 ->shouldAllowMockingProtectedMethods();

		// Mock the deactivate_plugin_by_slug method
		$importDeactivatePlugin->shouldReceive('deactivate_plugin_by_slug')
		                       ->with($pluginName)
		                       ->andReturnNull();

		// Execute the process method
		$result = $importDeactivatePlugin->process($schema);

		// Assert the result is an instance of StepProcessorResult
		$this->assertInstanceOf(StepProcessorResult::class, $result);

		// Assert success
		$this->assertTrue($result->is_success());
		$this->assertEquals(DeactivatePlugin::get_step_name(), $result->get_step_name());

		// Assert the info message is added
		$messages = $result->get_messages('info');
		$this->assertCount(1, $messages);
		$this->assertEquals("Deactivated {$pluginName}.", $messages[0]['message']);
	}

	public function test_get_step_class() {
		$importDeactivatePlugin = new ImportDeactivatePlugin();

		// Assert the correct step class is returned
		$this->assertEquals(DeactivatePlugin::class, $importDeactivatePlugin->get_step_class());
	}
}
