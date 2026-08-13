<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\BlocksUtil;

/**
 * Class BlocksUtilTest.
 *
 * @package Automattic\WooCommerce\Tests\Internal\Utilities
 */
class BlocksUtilTest extends \WC_Unit_Test_Case {

	/**
	 * Test get_blocks_from_widget_area with empty widget option.
	 */
	public function test_get_blocks_from_widget_area_with_empty_widgets() {
		// Test with no widgets.
		update_option( 'widget_block', array() );
		$result = BlocksUtil::get_blocks_from_widget_area( 'test/block' );
		$this->assertEmpty( $result );

		// Test with non-array widget option.
		update_option( 'widget_block', null );
		$result = BlocksUtil::get_blocks_from_widget_area( 'test/block' );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_blocks_from_widget_area with widgets but no matching blocks.
	 */
	public function test_get_blocks_from_widget_area_with_no_matching_blocks() {
		$widgets = array(
			'2'            => array(
				'content' => '<!-- wp:test/other-block --><div>Test</div><!-- /wp:test/other-block -->',
			),
			'_multiwidget' => 1,
		);

		update_option( 'widget_block', $widgets );
		$result = BlocksUtil::get_blocks_from_widget_area( 'test/block' );
		$this->assertEmpty( $result );
	}

	/**
	 * Test get_blocks_from_widget_area with matching blocks.
	 */
	public function test_get_blocks_from_widget_area_with_matching_blocks() {
		$widgets = array(
			'2'            => array(
				'content' => '<!-- wp:test/block --><div>Test 1</div><!-- /wp:test/block -->',
			),
			'3'            => array(
				'content' => '<!-- wp:test/other-block --><div>Test 2</div><!-- /wp:test/other-block -->',
			),
			'4'            => array(
				'content' => '<!-- wp:test/block --><div>Test 3</div><!-- /wp:test/block -->',
			),
			'_multiwidget' => 1,
		);

		update_option( 'widget_block', $widgets );
		$result = BlocksUtil::get_blocks_from_widget_area( 'test/block' );

		$this->assertCount( 2, $result );
		$this->assertEquals( 'test/block', $result[0]['blockName'] );
		$this->assertEquals( 'test/block', $result[1]['blockName'] );
		$this->assertStringContainsString( 'Test 1', $result[0]['innerHTML'] );
		$this->assertStringContainsString( 'Test 3', $result[1]['innerHTML'] );
	}

	/**
	 * Test get_blocks_from_widget_area with invalid widget data.
	 */
	public function test_get_blocks_from_widget_area_with_invalid_data() {
		$test_cases = array(
			// Non-array widget item.
			array( 'invalid' ),
			// Empty array.
			array( array() ),
			// Missing content.
			array( array( 'something' => 'else' ) ),
			// Empty content.
			array( array( 'content' => '' ) ),
		);

		foreach ( $test_cases as $test_case ) {
			update_option( 'widget_block', $test_case );
			$result = BlocksUtil::get_blocks_from_widget_area( 'test/block' );
			$this->assertEmpty( $result );
		}
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		delete_option( 'widget_block' );
		parent::tearDown();
	}
}
