<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Schemas;

use Automattic\WooCommerce\Blueprint\Schemas\ZipSchema;
use Automattic\WooCommerce\Blueprint\Tests\TestCase;

class ZipSchemaTest extends TestCase {
	public function test_it_throws_exception_on_unzip_failure() {
		$this->expectException( \Exception::class );
		new ZipSchema( $this->get_fixture_path('invalid-zip.zip') );
	}

	public function test_unzip() {
	    $schema = new ZipSchema( $this->get_fixture_path('zipped-schema.zip') );
		$unzipped_path = $schema->get_unzipped_path();
		$this->assertEquals(wp_upload_dir()['path'], $unzipped_path);
	}
}
