<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Tests\Blocks\Mocks\BlockHooksTestBlock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\BlockHooksLowerVersionTestBlock;
use Automattic\WooCommerce\Tests\Blocks\Mocks\BlockHooksNoVersionTestBlock;
use WP_UnitTestCase;

/**
 * Tests Block Hooks logic.
 */
class BlockHooksTests extends WP_UnitTestCase {
	/**
	 * Option name for storing the block hooks version.
	 *
	 * @var string
	 */
	protected static $option_name = 'woocommerce_hooked_blocks_version';

	/**
	 * Clean up the mock block registration.
	 */
	public function tearDown(): void {
		$registry = \WP_Block_Type_Registry::get_instance();
		if ( $registry->is_registered( 'woocommerce/test-block' ) ) {
			unregister_block_type( 'woocommerce/test-block' );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should hook the mock block when the configured version meets the placement requirement.
	 */
	public function test_mocked_block_gets_hooked_with_correct_version(): void {
		new BlockHooksTestBlock();
		update_option( self::$option_name, '8.4.0', false );
		$hooked_block_types = apply_filters( 'hooked_block_types', array(), 'after', 'core/navigation', array( 'mock-context' ) );
		$this->assertContains(
			'woocommerce/test-block',
			$hooked_block_types,
			'Test block should be included in hooked blocks with correct version'
		);
		delete_option( self::$option_name );
	}

	/**
	 * @testdox Should not hook the mock block when no version is configured.
	 */
	public function test_mocked_block_does_not_get_hooked(): void {
		new BlockHooksNoVersionTestBlock();
		delete_option( self::$option_name );
		$hooked_block_types = apply_filters( 'hooked_block_types', array(), 'after', 'core/navigation', array( 'mock-context' ) );
		$this->assertNotContains(
			'woocommerce/test-block',
			$hooked_block_types,
			"Hooked block shouldn't be added unless a version is set"
		);
	}

	/**
	 * @testdox Should not hook the mock block when the configured version is lower than required.
	 */
	public function test_mocked_block_does_not_get_hooked_with_lower_version(): void {
		new BlockHooksLowerVersionTestBlock();
		update_option( self::$option_name, '8.3.0', false );
		$hooked_block_types = apply_filters( 'hooked_block_types', array(), 'after', 'core/navigation', array( 'mock-context' ) );
		$this->assertNotContains(
			'woocommerce/test-block',
			$hooked_block_types,
			'Test block should not be included in hooked blocks with lower version'
		);
		delete_option( self::$option_name );
	}
}
