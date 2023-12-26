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
		$converted       = $block_converter->convert_markdown_to_gb_blocks( file_get_contents( __DIR__ . '/fixtures/test.md' ) );
		$this->assertEquals( file_get_contents( __DIR__ . '/fixtures/expected.html' ), $converted );
	}

	/**
	 * Test frontmatter is stripped correctly from sample markdown.
	 */
	public function test_frontmatter_stripped() {
		$block_converter = new BlockConverter();
		$stripped        = $block_converter->strip_frontmatter( file_get_contents( __DIR__ . '/fixtures/test.md' ) );
		$this->assertEquals( file_get_contents( __DIR__ . '/fixtures/no-frontmatter.md' ), $stripped );
	}
}
