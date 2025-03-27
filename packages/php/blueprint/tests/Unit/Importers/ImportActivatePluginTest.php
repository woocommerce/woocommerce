<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportActivatePlugin;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\ActivatePlugin;
use Mockery;
use PHPUnit\Framework\TestCase;

class ImportActivatePluginTest extends TestCase {
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_process_successful_activation() {
		$pluginPath = 'sample-plugin';

		// Create a mock schema object
		$schema = Mockery::mock();
		$schema->pluginPath = $pluginPath;

		// Create a partial mock of ImportActivatePlugin
		$importActivatePlugin = Mockery::mock(ImportActivatePlugin::class)
		                               ->makePartial()
		                               ->shouldAllowMockingProtectedMethods();

		// Mock the activate_plugin_by_slug method
		$importActivatePlugin->shouldReceive('wp_activate_plugin')
		                     ->with($pluginPath)
		                     ->andReturn(true);

		// Execute the process method
		$result = $importActivatePlugin->process($schema);

		// Assert the result is an instance of StepProcessorResult
		$this->assertInstanceOf(StepProcessorResult::class, $result);

		// Assert success
		$this->assertTrue($result->is_success());
		$this->assertEquals(ActivatePlugin::get_step_name(), $result->get_step_name());

		// Assert the success message is added
		$messages = $result->get_messages('info');
		$this->assertCount(1, $messages);
		$this->assertEquals("Activated {$pluginPath}.", $messages[0]['message']);
	}

	public function test_process_failed_activation() {
		$pluginPath = 'invalid-plugin';

		// Create a mock schema object
		$schema = Mockery::mock();
		$schema->pluginPath = $pluginPath;

		// Create a partial mock of ImportActivatePlugin
		$importActivatePlugin = Mockery::mock(ImportActivatePlugin::class)
		                               ->makePartial()
		                               ->shouldAllowMockingProtectedMethods();

		// Mock the activate_plugin_by_slug method
		$importActivatePlugin->shouldReceive('wp_activate_plugin')
		                     ->with($pluginPath)
		                     ->andReturn(new \WP_Error('error', 'Error message'));

		// Execute the process method
		$result = $importActivatePlugin->process($schema);

		// Assert the result is an instance of StepProcessorResult
		$this->assertInstanceOf(StepProcessorResult::class, $result);

		// Assert failure
		$this->assertFalse($result->is_success());
		$this->assertEquals(ActivatePlugin::get_step_name(), $result->get_step_name());

		// Assert the error message is added
		$messages = $result->get_messages('error');
		$this->assertCount(1, $messages);
		$this->assertEquals("Unable to activate {$pluginPath}.", $messages[0]['message']);
	}
}
