<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\Status as StatusBlock;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for order-confirmation status notices.
 */
final class StatusTest extends WC_Unit_Test_Case {
	/**
	 * @testdox A missing order directs the shopper to email and account confirmation paths.
	 */
	public function test_missing_order_notice(): void {
		$my_account_url = wc_get_page_permalink( 'myaccount' );
		$content        = $this->render_confirmation_notice( null );

		$this->assertNotSame( '', $my_account_url, 'The fixture must expose the My account login destination.' );
		$this->assertStringContainsString( 'If you&#039;ve just placed an order, give your email a quick check for the confirmation.', $content );
		$this->assertStringContainsString( 'Have an account with us?', $content );
		$this->assertStringContainsString( 'Log in here to view your order details', $content );
		$this->assertStringContainsString( 'href="' . esc_url( $my_account_url ) . '"', $content );
	}

	/**
	 * @testdox An existing customer order without a valid key directs the shopper to email and account confirmation paths.
	 * @dataProvider invalid_order_key_cases
	 *
	 * @param string $key_mode Missing or invalid key mode.
	 */
	public function test_invalid_key_notice_uses_real_permission_result( string $key_mode ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Request globals are restored fixture state.
		$original_get     = $_GET;
		$original_user_id = get_current_user_id();
		$customer_id      = 0;
		$order            = null;

		try {
			$customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
			$order       = wc_create_order( array( 'customer_id' => $customer_id ) );
			$order->set_billing_email( 'status-shopper@example.com' );
			$order->save();
			wp_set_current_user( $customer_id );
			$_GET = array();

			if ( 'wrong' === $key_mode ) {
				$_GET['key'] = 'wc_order_wrong';
			}

			$permission     = $this->get_view_order_permissions( $order );
			$content        = $this->render_confirmation_notice( $order );
			$my_account_url = wc_get_page_permalink( 'myaccount' );

			$this->assertFalse( $permission, 'The real permission decision must reject a missing or invalid order key.' );
			$this->assertNotSame( '', $my_account_url, 'The fixture must expose the My account login destination.' );
			$this->assertStringContainsString( 'Great news! Your order has been received, and a confirmation will be sent to your email address.', $content );
			$this->assertStringContainsString( 'Have an account with us?', $content );
			$this->assertStringContainsString( 'Log in here', $content );
			$this->assertStringContainsString( 'to view your order.', $content );
			$this->assertStringContainsString( 'href="' . esc_url( $my_account_url ) . '"', $content );
		} finally {
			if ( $order instanceof WC_Order ) {
				$order->delete( true );
			}
			wp_set_current_user( $original_user_id );
			$_GET = $original_get;
			if ( $customer_id ) {
				wp_delete_user( $customer_id );
			}
		}
	}

	/**
	 * Named invalid-key cases.
	 *
	 * @return array<string, array{string}>
	 */
	public static function invalid_order_key_cases(): array {
		return array(
			'missing order key' => array( 'missing' ),
			'wrong order key'   => array( 'wrong' ),
		);
	}

	/**
	 * Render a status notice through the real Status block owner.
	 *
	 * @param WC_Order|null $order Order object, or null when none exists.
	 * @return string
	 */
	private function render_confirmation_notice( ?WC_Order $order ): string {
		return $this->create_proxy()->render_confirmation_notice_proxy( $order );
	}

	/**
	 * Resolve view permission through the real Status block owner.
	 *
	 * @param WC_Order $order Order object.
	 * @return string|false
	 */
	private function get_view_order_permissions( WC_Order $order ) {
		return $this->create_proxy()->get_view_order_permissions_proxy( $order );
	}

	/**
	 * Create a public proxy over the Status block's protected owners.
	 *
	 * @return StatusBlock
	 */
	private function create_proxy(): StatusBlock {
		return new class() extends StatusBlock {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function render_confirmation_notice_proxy( ?WC_Order $order ): string {
				return $this->render_confirmation_notice( $order );
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function get_view_order_permissions_proxy( WC_Order $order ) {
				return $this->get_view_order_permissions( $order );
			}
		};
	}
}
