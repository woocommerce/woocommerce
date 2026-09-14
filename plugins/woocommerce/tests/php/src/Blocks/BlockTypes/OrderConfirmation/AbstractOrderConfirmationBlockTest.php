<?php declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\Status as StatusBlock;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the order key guard shared by order confirmation blocks.
 */
final class AbstractOrderConfirmationBlockTest extends WC_Unit_Test_Case {
	/**
	 * Restore the request state touched by this test.
	 */
	public function tearDown(): void {
		unset( $_GET['key'] );

		parent::tearDown();
	}

	/**
	 * @testdox An array order key is treated as invalid instead of reaching hash_equals().
	 */
	public function test_array_order_key_is_treated_as_invalid(): void {
		$order = \WC_Helper_Order::create_order();

		$_GET['key'] = array( $order->get_order_key() );

		$this->assertFalse( $this->check_key( $order ), 'An array key must be rejected without reaching hash_equals().' );
	}

	/**
	 * @testdox A string order key still validates.
	 */
	public function test_string_order_key_still_validates(): void {
		$order = \WC_Helper_Order::create_order();

		$_GET['key'] = $order->get_order_key();

		$this->assertTrue( $this->check_key( $order ), 'A valid string key must keep validating.' );
	}

	/**
	 * Check an order key through the block guard.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	private function check_key( WC_Order $order ): bool {
		$sut = new class() extends StatusBlock {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}

			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function has_valid_order_key_proxy( $order ) {
				return $this->has_valid_order_key( $order );
			}
		};

		return $sut->has_valid_order_key_proxy( $order );
	}
}
