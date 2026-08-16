<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\ResourceStorages;

use Automattic\WooCommerce\Blueprint\ResourceStorages\OrgPluginResourceStorage;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Unit tests for OrgPluginResourceStorage class.
 */
class OrgPluginResourceStorageTest extends TestCase {
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
	 * Test the get_supported_resource method returns the correct value.
	 *
	 * @return void
	 */
	public function test_get_supported_resource_returns_correct_value() {
		$storage = new OrgPluginResourceStorage();
		$this->assertEquals( 'wordpress.org/plugins', $storage->get_supported_resource() );
	}

	/**
	 * Test the download method returns the path for a valid plugin.
	 *
	 * @return void
	 */
	public function test_download_returns_path_for_valid_plugin() {
		$slug          = 'sample-plugin';
		$download_link = "https://downloads.wordpress.org/plugin/{$slug}.zip";
		$local_path    = "/tmp/{$slug}.zip";

		$mock_storage = Mockery::mock( OrgPluginResourceStorage::class )
								->makePartial()
								->shouldAllowMockingProtectedMethods();

		$mock_storage->shouldReceive( 'get_download_link' )
					->with( $slug )
					->andReturn( $download_link );

		$mock_storage->shouldReceive( 'wp_download_url' )
					->with( $download_link )
					->andReturn( $local_path );

		$result = $mock_storage->download( $slug );

		$this->assertEquals( $local_path, $result );
	}

	/**
	 * Test the download method returns null for an invalid plugin.
	 *
	 * @return void
	 */
	public function test_download_returns_null_for_invalid_plugin() {
		$slug = 'nonexistent-plugin';

		$mock_storage = Mockery::mock( OrgPluginResourceStorage::class )
								->makePartial()
								->shouldAllowMockingProtectedMethods();

		$mock_storage->shouldReceive( 'get_download_link' )
					->with( $slug )
					->andReturn( null );

		$result = $mock_storage->download( $slug );

		$this->assertEmpty( $result );
	}
}
