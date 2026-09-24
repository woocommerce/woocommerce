<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\AbstractOrderConfirmationBlock;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for order-confirmation order key validation.
 */
final class AbstractOrderConfirmationBlockTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var AbstractOrderConfirmationBlock
	 */
	private $sut;

	/**
	 * Set up the test proxy.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new class() extends AbstractOrderConfirmationBlock {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function has_valid_order_key_proxy( WC_Order $order ): bool {
				return $this->has_valid_order_key( $order );
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			protected function render_content( $order, $permission = false, $attributes = array(), $content = '' ) {
				return '';
			}
		};
	}

	/**
	 * @testdox An array order key is treated as invalid instead of reaching hash_equals().
	 */
	public function test_array_order_key_is_treated_as_invalid(): void {
		$order       = \WC_Helper_Order::create_order();
		$_GET['key'] = array( $order->get_order_key() );

		$this->assertFalse( $this->sut->has_valid_order_key_proxy( $order ), 'An array key must be rejected without reaching hash_equals().' );
	}

	/**
	 * @testdox A string order key still validates.
	 */
	public function test_string_order_key_still_validates(): void {
		$order       = \WC_Helper_Order::create_order();
		$_GET['key'] = $order->get_order_key();

		$this->assertTrue( $this->sut->has_valid_order_key_proxy( $order ), 'A valid string key must keep validating.' );
	}
}
