<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\ResourceStorages;

use Automattic\WooCommerce\Blueprint\ResourceStorages\OrgPluginResourceStorage;
use PHPUnit\Framework\TestCase;
use Mockery;

class OrgPluginResourceStorageTest extends TestCase {
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_get_supported_resource_returns_correct_value() {
		$storage = new OrgPluginResourceStorage();
		$this->assertEquals('wordpress.org/plugins', $storage->get_supported_resource());
	}

	public function test_download_returns_path_for_valid_plugin() {
		$slug = 'sample-plugin';
		$downloadLink = "https://downloads.wordpress.org/plugin/{$slug}.zip";
		$localPath = "/tmp/{$slug}.zip";

		$mockStorage = Mockery::mock(OrgPluginResourceStorage::class)
		                      ->makePartial()
		                      ->shouldAllowMockingProtectedMethods();

		$mockStorage->shouldReceive('get_download_link')
		            ->with($slug)
		            ->andReturn($downloadLink);

		$mockStorage->shouldReceive('wp_download_url')
		            ->with($downloadLink)
		            ->andReturn($localPath);

		$result = $mockStorage->download($slug);

		$this->assertEquals($localPath, $result);
	}

	public function test_download_returns_null_for_invalid_plugin() {
		$slug = 'nonexistent-plugin';

		$mockStorage = Mockery::mock(OrgPluginResourceStorage::class)
		                      ->makePartial()
		                      ->shouldAllowMockingProtectedMethods();

		$mockStorage->shouldReceive('get_download_link')
		            ->with($slug)
		            ->andReturn(null);

		$result = $mockStorage->download($slug);

		$this->assertEmpty($result);
	}
}
