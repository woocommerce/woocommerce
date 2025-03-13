<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\ResourceStorages;

use Automattic\WooCommerce\Blueprint\ResourceStorages\LocalPluginResourceStorage;
use PHPUnit\Framework\TestCase;

class LocalPluginResourceStorageTest extends TestCase {
	protected string $testPath;

	protected function setUp(): void {
		parent::setUp();
		// Setup a temporary directory for testing
		$this->testPath = sys_get_temp_dir() . '/test_plugins';
		mkdir($this->testPath, 0777, true);
		mkdir("{$this->testPath}/plugins", 0777, true);

		// Create a sample plugin file
		file_put_contents("{$this->testPath}/plugins/sample-plugin.zip", 'dummy content');
	}

	protected function tearDown(): void {
		// Cleanup temporary directory after test
		if (is_dir($this->testPath)) {
			array_map('unlink', glob("{$this->testPath}/plugins/*"));
			rmdir("{$this->testPath}/plugins");
			rmdir($this->testPath);
		}

		parent::tearDown();
	}

	public function test_download_finds_plugin_file() {
		$storage = new LocalPluginResourceStorage($this->testPath);
		$result = $storage->download('sample-plugin');

		$this->assertNotNull($result);
		$this->assertEquals("{$this->testPath}/plugins/sample-plugin.zip", $result);
	}

	public function test_download_returns_null_for_missing_plugin() {
		$storage = new LocalPluginResourceStorage($this->testPath);
		$result = $storage->download('nonexistent-plugin');

		$this->assertNull($result);
	}

	public function test_get_supported_resource_returns_correct_value() {
		$storage = new LocalPluginResourceStorage($this->testPath);
		$this->assertEquals('self/plugins', $storage->get_supported_resource());
	}
}
