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
	 * The mock block providing the `register_hooked_block` callback under test.
	 *
	 * @var BlockHooksTestBlock
	 */
	protected static $block_instance;

	/**
	 * Option name for storing the block hooks version.
	 *
	 * @var string
	 */
	protected static $option_name = 'woocommerce_hooked_blocks_version';

	/**
	 * Instantiate the mock block once for the whole class.
	 *
	 * Constructing it registers the `woocommerce/test-block` block type, and the block
	 * registry is not reset between tests — constructing it per test makes WordPress report
	 * "Block type is already registered", which the incorrect-usage catcher turns into a
	 * failure. The filter the tests actually exercise is (re)registered per test in `setUp()`.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		self::$block_instance = new BlockHooksTestBlock();
	}

	/**
	 * Register the hooked-block filter for every test.
	 *
	 * WordPress snapshots the hook globals once per process and restores that snapshot after
	 * every test, so a filter registered before the snapshot is taken — from
	 * `setUpBeforeClass`, where the block's constructor adds it — is dropped as soon as the
	 * first test finishes. Every later test then asserts against a filter that is no longer
	 * there and passes for the wrong reason. Re-adding it here, after `parent::setUp()`, keeps
	 * it in place for each test and lets the restore take it away again afterwards.
	 */
	public function setUp(): void {
		parent::setUp();

		delete_option( self::$option_name );
		add_filter( 'hooked_block_types', array( self::$block_instance, 'register_hooked_block' ), 9, 4 );
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
