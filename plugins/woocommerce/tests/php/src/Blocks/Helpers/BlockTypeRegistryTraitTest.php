<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Helpers;

use Automattic\WooCommerce\Tests\Blocks\Mocks\CouponCodeMock;
use WC_Unit_Test_Case;

/**
 * Tests for BlockTypeRegistryTrait.
 */
class BlockTypeRegistryTraitTest extends WC_Unit_Test_Case {
	use BlockTypeRegistryTrait;

	/**
	 * Block names these tests register, removed whatever happens in a test.
	 */
	private const TEST_BLOCK_NAMES = array( 'woocommerce-test/swapped', 'woocommerce-test/other' );

	/**
	 * Remove anything a test left registered.
	 */
	public function tearDown(): void {
		try {
			$this->restore_block_types();
			$registry = \WP_Block_Type_Registry::get_instance();
			foreach ( self::TEST_BLOCK_NAMES as $block_name ) {
				if ( $registry->is_registered( $block_name ) ) {
					$registry->unregister( $block_name );
				}
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Replacing a registered block type puts the original back on restore.
	 */
	public function test_replacing_a_registered_block_type_puts_the_original_back(): void {
		$original = register_block_type( 'woocommerce-test/swapped', array( 'title' => 'Original' ) );

		$replacement = $this->replace_block_type(
			'woocommerce-test/swapped',
			static function () {
				return register_block_type( 'woocommerce-test/swapped', array( 'title' => 'Replacement' ) );
			}
		);

		$registry = \WP_Block_Type_Registry::get_instance();
		$this->assertSame( $replacement, $registry->get_registered( 'woocommerce-test/swapped' ), 'The replacement should be registered during the test.' );

		$this->restore_block_types();

		$this->assertSame( $original, $registry->get_registered( 'woocommerce-test/swapped' ), 'The original block type should be registered again.' );
	}

	/**
	 * @testdox A block type that was not registered before the test is removed on restore.
	 */
	public function test_a_block_type_that_was_not_registered_is_removed(): void {
		$this->replace_block_type(
			'woocommerce-test/swapped',
			static function () {
				return register_block_type( 'woocommerce-test/swapped' );
			}
		);

		$this->restore_block_types();

		$this->assertFalse( \WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce-test/swapped' ), 'A block type the test added should not stay registered.' );
	}

	/**
	 * @testdox Replacing the same name twice in a test still restores the block type from before the test.
	 */
	public function test_replacing_twice_restores_the_block_type_from_before_the_test(): void {
		$original = register_block_type( 'woocommerce-test/swapped', array( 'title' => 'Original' ) );

		foreach ( array( 'First', 'Second' ) as $title ) {
			$this->replace_block_type(
				'woocommerce-test/swapped',
				static function () use ( $title ) {
					return register_block_type( 'woocommerce-test/swapped', array( 'title' => $title ) );
				}
			);
		}

		$this->restore_block_types();

		$this->assertSame( $original, \WP_Block_Type_Registry::get_instance()->get_registered( 'woocommerce-test/swapped' ), 'The block type from before the first replacement should come back.' );
	}

	/**
	 * @testdox replace_block_types() constructs each class under its block name.
	 */
	public function test_replace_block_types_constructs_each_class(): void {
		$this->replace_block_types( array( 'woocommerce/coupon-code' => CouponCodeMock::class ) );

		$this->assertTrue( \WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/coupon-code' ), 'Constructing the mock should register its block type.' );
	}

	/**
	 * @testdox A callback that registers a different name than the one replaced is rejected.
	 */
	public function test_a_callback_that_registers_another_name_is_rejected(): void {
		$this->expectException( \LogicException::class );

		$this->replace_block_type(
			'woocommerce-test/swapped',
			static function () {
				return register_block_type( 'woocommerce-test/other' );
			}
		);
	}
}
