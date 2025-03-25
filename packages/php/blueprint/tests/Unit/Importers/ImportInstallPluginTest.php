<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportInstallPlugin;
use Automattic\WooCommerce\Blueprint\ResourceStorages;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\InstallPlugin;
use Mockery;
use PHPUnit\Framework\TestCase;

class ImportInstallPluginTest extends TestCase {
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_process_skipped_installation() {
		$pluginSlug = 'already-installed-plugin';

		$schema = Mockery::mock();
		$schema->pluginZipFile = (object)[
			'slug' => $pluginSlug,
			'resource' => 'valid-resource'
		];

		$resourceStorage = Mockery::mock(ResourceStorages::class);
		$importInstallPlugin = Mockery::mock(ImportInstallPlugin::class, [$resourceStorage])
		                              ->makePartial()
		                              ->shouldAllowMockingProtectedMethods();

		$importInstallPlugin->shouldReceive('get_installed_plugins_paths')
		                    ->andReturn([$pluginSlug => '/path/to/plugin']);

		$result = $importInstallPlugin->process($schema);

		$this->assertInstanceOf(StepProcessorResult::class, $result);
		$this->assertTrue($result->is_success());
		$this->assertEquals(InstallPlugin::get_step_name(), $result->get_step_name());
		$messages = $result->get_messages('info');
		$this->assertCount(1, $messages);
		$this->assertEquals("Skipped installing {$pluginSlug}. It is already installed.", $messages[0]['message']);
	}

	public function test_process_invalid_resource() {
		$pluginSlug = 'invalid-resource-plugin';

		$schema = Mockery::mock();
		$schema->pluginZipFile = (object)[
			'slug' => $pluginSlug,
			'resource' => 'invalid-resource'
		];

		$resourceStorage = Mockery::mock(ResourceStorages::class);
		$resourceStorage->shouldReceive('is_supported_resource')
		                ->with('invalid-resource')
		                ->andReturn(false);

		$importInstallPlugin = Mockery::mock(ImportInstallPlugin::class, [$resourceStorage])
		                              ->makePartial()
		                              ->shouldAllowMockingProtectedMethods();

		$result = $importInstallPlugin->process($schema);

		$this->assertInstanceOf(StepProcessorResult::class, $result);
		$this->assertFalse($result->is_success());
		$messages = $result->get_messages('error');
		$this->assertCount(1, $messages);
		$this->assertEquals("Invalid resource type for {$pluginSlug}.", $messages[0]['message']);
	}

	public function test_process_successful_installation_and_activation() {
		$pluginSlug = 'sample-plugin';

		$schema = Mockery::mock();
		$schema->pluginZipFile = (object)[
			'slug' => $pluginSlug,
			'resource' => 'valid-resource',
			'options' => (object)['activate' => true]
		];

		$resourceStorage = Mockery::mock(ResourceStorages::class);
		$resourceStorage->shouldReceive('is_supported_resource')
		                ->with('valid-resource')
		                ->andReturn(true);
		$resourceStorage->shouldReceive('download')
		                ->with($pluginSlug, 'valid-resource')
		                ->andReturn('/path/to/plugin.zip');

		$importInstallPlugin = Mockery::mock(ImportInstallPlugin::class, [$resourceStorage])
		                              ->makePartial()
		                              ->shouldAllowMockingProtectedMethods();

		$importInstallPlugin->shouldReceive('get_installed_plugins_paths')
		                    ->andReturn([]);
		$importInstallPlugin->shouldReceive('install')
		                    ->with('/path/to/plugin.zip')
		                    ->andReturn(true);
		$importInstallPlugin->shouldReceive('activate')
		                    ->with($pluginSlug)
		                    ->andReturnNull();

		$result = $importInstallPlugin->process($schema);

		$this->assertInstanceOf(StepProcessorResult::class, $result);
		$this->assertTrue($result->is_success());
		$messages = $result->get_messages('info');
		$this->assertCount(2, $messages);
		$this->assertEquals("Installed {$pluginSlug}.", $messages[0]['message']);
		$this->assertEquals("Activated {$pluginSlug}.", $messages[1]['message']);
	}

	public function test_get_step_class() {
		$resourceStorage = Mockery::mock(ResourceStorages::class);
		$importInstallPlugin = new ImportInstallPlugin($resourceStorage);

		$this->assertEquals(InstallPlugin::class, $importInstallPlugin->get_step_class());
	}
}
