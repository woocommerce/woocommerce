<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * WC_Email_Customer_POS_Refunded_Order test.
 *
 * @covers WC_Email_Customer_POS_Refunded_Order
 */
class WC_Email_Customer_POS_Refunded_Order_Test extends \WC_Unit_Test_Case {
	/**
	 * Load up the email classes since they aren't loaded by default.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-pos-refunded-order.php';
	}

	/**
	 * @testdox order_item_totals adds custom rows for cash change.
	 */
	public function test_order_item_totals_adds_formatted_cash_change_due_amount_to_order_totals() {
		// Given order with cash payment change amount.
		$order = OrderHelper::create_order();
		$order->add_meta_data( '_cash_change_amount', '5.00' );
		$order->save();
		$email = new WC_Email_Customer_POS_Refunded_Order();

		// When overriding order item totals.
		$totals = $email->order_item_totals( array(), $order, 'incl' );

		// Then cash payment change due amount is set and formatted correctly.
		$this->assertArrayHasKey( 'cash_payment_change_due_amount', $totals );
		$this->assertEquals( wc_price( '5.00', array( 'currency' => $order->get_currency() ) ), $totals['cash_payment_change_due_amount']['value'] );
	}

	/**
	 * @testdox order_item_totals adds payment_auth_code row to order totals.
	 */
	public function test_order_item_totals_adds_payment_auth_code_to_order_totals() {
		// Given order with charge id.
		$order = OrderHelper::create_order();
		$order->add_meta_data( '_charge_id', 'AUTH134' );
		$order->save();
		$email = new WC_Email_Customer_POS_Refunded_Order();

		// When overriding order item totals.
		$totals = $email->order_item_totals( array(), $order, 'incl' );

		// Then payment auth code is set correctly.
		$this->assertArrayHasKey( 'payment_auth_code', $totals );
		$this->assertEquals( 'AUTH134', $totals['payment_auth_code']['value'] );
	}

	/**
	 * @testdox order_item_totals adds date_paid row to order totals.
	 */
	public function test_order_item_totals_adds_date_paid_to_order_totals() {
		// Given order with date paid.
		$order = OrderHelper::create_order();
		$order->set_date_paid( '2023-06-01 12:00:00' );
		$order->save();
		$email = new WC_Email_Customer_POS_Refunded_Order();
		update_option( 'date_format', 'd.m.Y' );
		update_option( 'time_format', 'g:i A' );

		// When overriding order item totals.
		$totals = $email->order_item_totals( array(), $order, 'incl' );

		// Then date paid is set and formatted correctly.
		$this->assertArrayHasKey( 'date_paid', $totals );
		$this->assertEquals( '01.06.2023 12:00 PM', $totals['date_paid']['value'] );
	}

	/**
	 * @testdox order_item_totals does not add cash_payment_change_due_amount row if cash change is not set.
	 */
	public function test_order_item_totals_does_not_add_cash_change_due_amount_when_not_set() {
		// Given order without cash change due amount.
		$order = OrderHelper::create_order();
		$order->save();
		$email = new WC_Email_Customer_POS_Refunded_Order();

		// When overriding order item totals.
		$totals = $email->order_item_totals( array(), $order, 'incl' );

		// Then cash payment change due amount is not set.
		$this->assertArrayNotHasKey( 'cash_payment_change_due_amount', $totals );
	}

	/**
	 * @testdox order_item_totals does not add payment_auth_code row if charge id is not set.
	 */
	public function test_order_item_totals_does_not_add_payment_auth_code_when_not_set() {
		// Given order without charge id.
		$order = OrderHelper::create_order();
		$order->save();
		$email = new WC_Email_Customer_POS_Refunded_Order();

		// When overriding order item totals.
		$totals = $email->order_item_totals( array(), $order, 'incl' );

		// Then payment auth code is not set.
		$this->assertArrayNotHasKey( 'payment_auth_code', $totals );
	}

	/**
	 * @testdox order_item_totals does not add date_paid row if date paid is not set.
	 */
	public function test_order_item_totals_does_not_add_date_paid_when_not_set() {
		// Given order without date paid.
		$order = OrderHelper::create_order();
		$order->save();
		$email = new WC_Email_Customer_POS_Refunded_Order();

		// When overriding order item totals.
		$totals = $email->order_item_totals( array(), $order, 'incl' );

		// Then date paid is not set.
		$this->assertArrayNotHasKey( 'date_paid', $totals );
	}
}
