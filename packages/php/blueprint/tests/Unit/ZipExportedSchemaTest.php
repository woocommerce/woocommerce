<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit;

use Automattic\WooCommerce\Blueprint\Tests\TestCase;
use Automattic\WooCommerce\Blueprint\ZipExportedSchema;

/**
 * Class ZipExportedSchemaTest
 */
class ZipExportedSchemaTest extends TestCase {
	/**
	 * Test it throws exception on invalid plugin slug.
	 *
	 * @return void
	 * @throws \Exception If the plugin slug is invalid.
	 */
	public function test_it_throws_invalid_argument_exception_with_invalid_slug() {
		$this->expectException( \InvalidArgumentException::class );
		// phpcs:ignore
		$json = json_decode( file_get_contents( $this->get_fixture_path( 'install-plugin-with-invalid-slug.json' ) ), true );
		$mock = Mock( ZipExportedSchema::class, array( $json ) );
		$mock->makePartial();
		$mock->shouldAllowMockingProtectedMethods();
		$mock->shouldReceive( 'maybe_create_dir' )->andReturn( null );
		$mock->shouldReceive( 'wp_filesystem_put_contents' )->andReturn( null );
		$mock->zip();
	}
}
