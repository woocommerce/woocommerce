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
		$file            = preg_replace( '/^---(.*?)---/s', '', file_get_contents( __DIR__ . '/fixtures/test.md' ), 1 );

		$converted = $block_converter->convert( $file );

		// write to a file
		file_put_contents( __DIR__ . '/fixtures/2_expected.html', $converted );
		$this->assertEquals( file_get_contents( __DIR__ . '/fixtures/expected.html' ), $converted );

	}
}
