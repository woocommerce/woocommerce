<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Templates;

use Automattic\WooCommerce\Tests\Blocks\Mocks\BlockHooksTestBlock;
use WP_UnitTestCase;

/**
 * Tests Block Hooks logic.
 *
 */
class BlockHooksTests extends WP_UnitTestCase {
	/**
	 * This variable holds our Product Query object.
	 *
	 * @var TestBlock
	 */
	protected static $block_instance;

	/**
	 * Option name for storing the block hooks version.
	 *
	 * @var string
	 */
	protected static $option_name = 'woocommerce_hooked_blocks_version';

	/**
	 * Initiate the mock object.
	 */
	public static function setUpBeforeClass(): void {
		delete_option( self::$option_name );
		self::$block_instance = new BlockHooksTestBlock();
	}

	/**
	 * Test block gets hooked with correct version
	 *
	 * @return void
	 */
	public function test_mocked_block_gets_hooked_with_correct_version() {
		update_option( self::$option_name, '8.4.0', false );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- test code.
		$hooked_block_types = apply_filters( 'hooked_block_types', array(), 'after', 'core/navigation', array( 'mock-context' ) );
		$this->assertContains(
			'woocommerce/test-block',
			$hooked_block_types,
			'Test block should be included in hooked blocks with correct version'
		);
		delete_option( self::$option_name );
	}

	/**
	 * Test block does not get hooked because no version is set.
	 *
	 * @return void
	 */
	public function test_mocked_block_does_not_get_hooked() {
		delete_option( self::$option_name );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- test code.
		$hooked_block_types = apply_filters( 'hooked_block_types', array(), 'after', 'core/navigation', array( 'mock-context' ) );
		$this->assertNotContains(
			'woocommerce/test-block',
			$hooked_block_types,
			"Hooked block shouldn't be added unless a version is set"
		);
	}

	/**
	 * Test block does not get hooked with lower version
	 *
	 * @return void
	 */
	public function test_mocked_block_does_not_get_hooked_with_lower_version() {
		update_option( self::$option_name, '8.3.0', false );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- test code.
		$hooked_block_types = apply_filters( 'hooked_block_types', array(), 'after', 'core/navigation', array( 'mock-context' ) );
		$this->assertNotContains(
			'woocommerce/test-block',
			$hooked_block_types,
			'Test block should not be included in hooked blocks with lower version'
		);
		delete_option( self::$option_name );
	}
}
