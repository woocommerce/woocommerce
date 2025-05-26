<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\Block_Delimiter;
use WP_UnitTestCase;

/**
 * Tests for the Block_Delimiter class.
 */
class BlockDelimiterTest extends WP_UnitTestCase {

	/**
	 * Test finding a simple block opener.
	 */
	public function test_find_simple_block_opener() {
		$html      = '<!-- wp:paragraph -->';
		$delimiter = Block_Delimiter::next_delimiter( $html, 0 );

		$this->assertNotNull( $delimiter );
		$this->assertEquals( Block_Delimiter::OPENER, $delimiter->get_delimiter_type() );
		$this->assertTrue( $delimiter->is_block_type( 'core/paragraph' ) );
		$this->assertFalse( $delimiter->has_void_flag() );
	}

	/**
	 * Test finding a block opener with attributes.
	 */
	public function test_find_block_opener_with_attributes() {
		$html      = '<!-- wp:paragraph {"test": true} -->';
		$delimiter = Block_Delimiter::next_delimiter( $html, 0 );

		$this->assertNotNull( $delimiter );
		$this->assertEquals( Block_Delimiter::OPENER, $delimiter->get_delimiter_type() );
		$this->assertTrue( $delimiter->is_block_type( 'core/paragraph' ) );

		$attributes = $delimiter->allocate_and_return_parsed_attributes();
		$this->assertIsArray( $attributes );
		$this->assertTrue( $attributes['test'] );
	}

	/**
	 * Test finding a block closer.
	 */
	public function test_find_block_closer() {
		$html      = '<!-- /wp:paragraph -->';
		$delimiter = Block_Delimiter::next_delimiter( $html, 0 );

		$this->assertNotNull( $delimiter );
		$this->assertEquals( Block_Delimiter::CLOSER, $delimiter->get_delimiter_type() );
		$this->assertTrue( $delimiter->is_block_type( 'core/paragraph' ) );
		$this->assertFalse( $delimiter->has_void_flag() );
	}

	/**
	 * Test finding a block with custom namespace.
	 */
	public function test_find_block_with_custom_namespace() {
		$html      = '<!-- wp:custom-block/example -->';
		$delimiter = Block_Delimiter::next_delimiter( $html, 0 );

		$this->assertNotNull( $delimiter );
		$this->assertEquals( Block_Delimiter::OPENER, $delimiter->get_delimiter_type() );
		$this->assertTrue( $delimiter->is_block_type( 'custom-block/example' ) );
	}

	/**
	 * Test scanning delimiters in a text document.
	 */
	public function test_scan_delimiters() {
		$html = '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' .
				'<!-- wp:image {"url":"test.jpg"} /-->' .
				'<!-- wp:custom-block/example {"setting":true} -->Content<!-- /wp:custom-block/example -->';

		$delimiters = [];

		foreach ( Block_Delimiter::scan_delimiters( $html ) as $delimiter ) {
			$delimiters[] = $delimiter;
		}

		// We should find 5 delimiters. One paragraph, one image void, one custom block pair.
		$this->assertCount( 5, $delimiters );

		// Test paragraph block.
		$this->assertEquals( Block_Delimiter::OPENER, $delimiters[0]->get_delimiter_type() );
		$this->assertTrue( $delimiters[0]->is_block_type( 'core/paragraph' ) );
		$this->assertEquals( Block_Delimiter::CLOSER, $delimiters[1]->get_delimiter_type() );
		$this->assertTrue( $delimiters[1]->is_block_type( 'core/paragraph' ) );

		// Test void image block.
		$this->assertEquals( Block_Delimiter::VOID, $delimiters[2]->get_delimiter_type() );
		$this->assertTrue( $delimiters[2]->is_block_type( 'core/image' ) );
		$attributes = $delimiters[2]->allocate_and_return_parsed_attributes();
		$this->assertIsArray( $attributes );
		$this->assertEquals( 'test.jpg', $attributes['url'] );

		// Test custom block.
		$this->assertEquals( Block_Delimiter::OPENER, $delimiters[3]->get_delimiter_type() );
		$this->assertTrue( $delimiters[3]->is_block_type( 'custom-block/example' ) );
		$this->assertEquals( Block_Delimiter::CLOSER, $delimiters[4]->get_delimiter_type() );
		$this->assertTrue( $delimiters[4]->is_block_type( 'custom-block/example' ) );
	}
}
