<?php

use WooCommerceDocs\Blocks\BlockConverter;

/**
 * Class BlockConverterTest
 */
class BlockConverterTest extends WP_UnitTestCase {

	/**
	 * Test blocks are converted correctly from sample markdown.
	 */
	public function test_blocks_converted() {
		$block_converter = new BlockConverter();
		$converted       = $block_converter->convert( file_get_contents( __DIR__ . '/fixtures/test.md' ) );
		$this->assertEquals( $converted, file_get_contents( __DIR__ . '/fixtures/expected.html' ) );
	}
}
