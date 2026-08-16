<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\ResourceStorages;

use Automattic\WooCommerce\Blueprint\ResourceStorages\LocalPluginResourceStorage;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LocalPluginResourceStorage class.
 */
class LocalPluginResourceStorageTest extends TestCase {
	/**
	 * Test path.
	 *
	 * @var string
	 */
	protected string $test_path;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		// Setup a temporary directory for testing.
		$this->test_path = sys_get_temp_dir() . '/test_plugins';
		mkdir( $this->test_path, 0777, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		mkdir( "{$this->test_path}/plugins", 0777, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir

		// Create a sample plugin file.
		file_put_contents( "{$this->test_path}/plugins/sample-plugin.zip", 'dummy content' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Tear down the test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Cleanup temporary directory after test.
		if ( is_dir( $this->test_path ) ) {
			array_map( 'unlink', glob( "{$this->test_path}/plugins/*" ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_glob
			rmdir( "{$this->test_path}/plugins" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			rmdir( $this->test_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}

		parent::tearDown();
	}

	/**
	 * Test download finds plugin file.
	 *
	 * @return void
	 */
	public function test_download_finds_plugin_file() {
		$storage = new LocalPluginResourceStorage( $this->test_path );
		$result  = $storage->download( 'sample-plugin' );

		$this->assertNotNull( $result );
		$this->assertEquals( "{$this->test_path}/plugins/sample-plugin.zip", $result );
	}

	/**
	 * Test download returns null for missing plugin.
	 *
	 * @return void
	 */
	public function test_download_returns_null_for_missing_plugin() {
		$storage = new LocalPluginResourceStorage( $this->test_path );
		$result  = $storage->download( 'nonexistent-plugin' );

		$this->assertNull( $result );
	}

	/**
	 * Test get supported resource returns correct value.
	 *
	 * @return void
	 */
	public function test_get_supported_resource_returns_correct_value() {
		$storage = new LocalPluginResourceStorage( $this->test_path );
		$this->assertEquals( 'self/plugins', $storage->get_supported_resource() );
	}
}
