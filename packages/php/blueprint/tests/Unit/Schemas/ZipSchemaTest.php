<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Schemas;

use Automattic\WooCommerce\Blueprint\Schemas\ZipSchema;
use Automattic\WooCommerce\Blueprint\Tests\TestCase;

/**
 * Class ZipSchemaTest
 */
class ZipSchemaTest extends TestCase {
	/**
	 * Test it throws exception on invalid zip file.
	 *
	 * @return void
	 * @throws \Exception If the zip file is invalid.
	 */
	public function test_it_throws_exception_on_unzip_failure() {
		$this->expectException( \Exception::class );
		new ZipSchema( $this->get_fixture_path( 'invalid-zip.zip' ) );
	}

	/**
	 * Test unzipping a zip file.
	 *
	 * @return void
	 * @throws \Exception If the zip file is invalid.
	 */
	public function test_unzip() {
		$schema        = new ZipSchema( $this->get_fixture_path( 'zipped-schema.zip' ) );
		$unzipped_path = $schema->get_unzipped_path();
		$this->assertEquals( wp_upload_dir()['path'], $unzipped_path );
	}
}
