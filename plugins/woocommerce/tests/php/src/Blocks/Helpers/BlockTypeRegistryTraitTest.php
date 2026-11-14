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
	 * @testdox Replacing a registered block type registers the replacement under its name.
	 */
	public function test_replacing_a_registered_block_type_registers_the_replacement(): void {
		$original = register_block_type( 'woocommerce-test/swapped', array( 'title' => 'Original' ) );

		$replacement = $this->replace_block_type( 'woocommerce-test/swapped', static fn () => register_block_type( 'woocommerce-test/swapped', array( 'title' => 'Replacement' ) ) );

		$this->assertInstanceOf( \WP_Block_Type::class, $replacement, 'The replacement should register without a conflict.' );
		$this->assertNotSame( $original, $replacement, 'The replacement should be a new block type.' );
		$this->assertSame( $replacement, \WP_Block_Type_Registry::get_instance()->get_registered( 'woocommerce-test/swapped' ), 'The replacement should be registered during the test.' );
	}

	/**
	 * @testdox replace_block_types() replaces an existing registration with the requested mock class.
	 */
	public function test_replace_block_types_constructs_each_class(): void {
		$registry = \WP_Block_Type_Registry::get_instance();
		$original = $registry->get_registered( 'woocommerce/coupon-code' );
		$this->assertInstanceOf( \WP_Block_Type::class, $original, 'The coupon block should be registered before replacement.' );

		$this->replace_block_types( array( 'woocommerce/coupon-code' => CouponCodeMock::class ) );

		$replacement = $registry->get_registered( 'woocommerce/coupon-code' );
		$this->assertNotSame( $original, $replacement, 'The mock should replace the original block type.' );
		$this->assertInstanceOf( CouponCodeMock::class, $replacement->render_callback[0], 'The registered block should render through the requested mock.' );
	}

	/**
	 * @testdox A callback that registers a different name than the one replaced is rejected.
	 */
	public function test_a_callback_that_registers_another_name_is_rejected(): void {
		register_block_type( 'woocommerce-test/swapped', array( 'title' => 'Original' ) );

		$this->expectException( \LogicException::class );

		$this->replace_block_type( 'woocommerce-test/swapped', static fn () => register_block_type( 'woocommerce-test/other' ) );
	}
}
