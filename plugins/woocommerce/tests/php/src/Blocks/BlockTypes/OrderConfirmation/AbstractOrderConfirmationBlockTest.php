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
			public function get_view_order_permissions_proxy( WC_Order $order ) {
				return $this->get_view_order_permissions( $order );
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

	/**
	 * @testdox After the grace period, the billing email unlocks a guest order only while guest checkout is on.
	 */
	public function test_billing_email_unlocks_guest_order_with_guest_checkout(): void {
		$order = wc_create_order();
		$order->set_billing_email( 'guest-unlock@example.com' );
		$order->set_date_created( time() - HOUR_IN_SECONDS );
		$order->save();
		$_GET['key']       = $order->get_order_key();
		$_POST['_wpnonce'] = wp_create_nonce( 'wc_verify_email' );
		$_POST['email']    = strtoupper( $order->get_billing_email() );

		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		$with_guest_checkout = $this->sut->get_view_order_permissions_proxy( $order );
		update_option( 'woocommerce_enable_guest_checkout', 'no' );
		$without_guest_checkout = $this->sut->get_view_order_permissions_proxy( $order );

		unset( $_GET['key'], $_POST['_wpnonce'], $_POST['email'] );
		delete_option( 'woocommerce_enable_guest_checkout' );

		$this->assertSame( 'full', $with_guest_checkout );
		$this->assertFalse( $without_guest_checkout );
	}

	/**
	 * @testdox The billing email never unlocks another customer's order, even when a subclass permits email verification.
	 */
	public function test_billing_email_does_not_unlock_customer_order_for_permissive_subclass(): void {
		$sut   = new class() extends AbstractOrderConfirmationBlock {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function get_view_order_permissions_proxy( WC_Order $order ) {
				return $this->get_view_order_permissions( $order );
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			protected function email_verification_permitted( $order ) {
				return true;
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			protected function render_content( $order, $permission = false, $attributes = array(), $content = '' ) {
				return '';
			}
		};
		$order = wc_create_order( array( 'customer_id' => self::factory()->user->create( array( 'role' => 'customer' ) ) ) );
		$order->set_billing_email( 'customer-unlock@example.com' );
		$order->save();
		wp_set_current_user( 0 );
		$_GET['key']       = $order->get_order_key();
		$_POST['_wpnonce'] = wp_create_nonce( 'wc_verify_email' );
		$_POST['email']    = $order->get_billing_email();
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );

		$permission = $sut->get_view_order_permissions_proxy( $order );

		unset( $_GET['key'], $_POST['_wpnonce'], $_POST['email'] );
		delete_option( 'woocommerce_enable_guest_checkout' );

		$this->assertFalse( $permission );
	}
}
