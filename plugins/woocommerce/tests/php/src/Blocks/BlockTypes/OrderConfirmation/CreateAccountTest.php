<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\CreateAccount;
use Automattic\WooCommerce\Internal\Customers\NewCustomerSession;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the order confirmation Create account block.
 */
final class CreateAccountTest extends WC_Unit_Test_Case {
	/**
	 * @testdox An account created on the order confirmation page is linked to its order, not to the session.
	 */
	public function test_account_is_linked_to_the_order_not_the_session(): void {
		wp_set_current_user( 0 );
		add_filter( 'pre_wp_mail', '__return_true' );
		$order = \WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( 'confirmation-create@example.com' );
		$order->save();

		$sut = new class() extends CreateAccount {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function __construct() {
			}
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function process_form_post_proxy( WC_Order $order ) {
				return $this->process_form_post( $order );
			}
		};

		$_POST = array(
			'create-account' => '1',
			'email'          => 'confirmation-create@example.com',
			'_wpnonce'       => wp_create_nonce( 'wc_create_account' ),
		);

		try {
			$customer_id = $sut->process_form_post_proxy( $order );
		} finally {
			$_POST = array();
		}

		$new_customer_session = wc_get_container()->get( NewCustomerSession::class );
		$this->assertIsInt( $customer_id, 'The account should be created.' );
		$this->assertTrue( $new_customer_session->is_unclaimed_customer_order( wc_get_order( $order->get_id() ) ), 'The shopper has no password yet, so the order should stay open to them.' );
		$this->assertFalse( $new_customer_session->has_unclaimed_customer(), 'A later checkout in this session must not reuse the account or give it a saved card.' );
	}
}
