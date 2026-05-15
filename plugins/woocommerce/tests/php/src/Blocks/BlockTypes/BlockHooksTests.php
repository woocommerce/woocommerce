<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

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

	/**
	 * Test that `register_block_hooks_metadata` exposes the block's hook placement
	 * via the `block_hooks` property on the registered `WP_Block_Type`. This is what
	 * lets the block editor render a toggle for the block in the anchor block's
	 * inspector even when the block is hooked exclusively via a PHP filter.
	 *
	 * @return void
	 */
	public function test_register_block_hooks_metadata_sets_block_hooks_on_registered_type() {
		$registry   = \WP_Block_Type_Registry::get_instance();
		$block_name = 'woocommerce/test-block';

		$was_registered = $registry->is_registered( $block_name );
		if ( ! $was_registered ) {
			register_block_type( $block_name );
		}

		try {
			self::$block_instance->register_block_hooks_metadata();

			$block_type = $registry->get_registered( $block_name );
			$this->assertInstanceOf( \WP_Block_Type::class, $block_type );
			$this->assertIsArray( $block_type->block_hooks );
			$this->assertArrayHasKey( 'core/navigation', $block_type->block_hooks );
			$this->assertSame( 'after', $block_type->block_hooks['core/navigation'] );
		} finally {
			if ( ! $was_registered ) {
				$registry->unregister( $block_name );
			}
		}
	}

	/**
	 * Test that the hooked block list does not contain the block when no placement
	 * matches the requested anchor/position pair — even when something else (e.g.
	 * the `block_hooks` metadata registered via `register_block_hooks_metadata`)
	 * already added it.
	 *
	 * @return void
	 */
	public function test_register_hooked_block_strips_block_when_anchor_does_not_match() {
		update_option( self::$option_name, '8.4.0', false );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- test code.
		$hooked_block_types = apply_filters(
			'hooked_block_types',
			array( 'woocommerce/test-block' ),
			'after',
			'core/paragraph',
			array( 'mock-context' )
		);
		$this->assertNotContains(
			'woocommerce/test-block',
			$hooked_block_types,
			'Test block should be removed when the anchor does not match any placement'
		);
		delete_option( self::$option_name );
	}
}
