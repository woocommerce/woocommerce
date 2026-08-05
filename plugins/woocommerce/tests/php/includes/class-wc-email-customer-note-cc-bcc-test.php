<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Class WC_Email_Customer_Note_Cc_Bcc_Test.
 *
 * Tests that the customer note email includes Cc/Bcc headers when
 * the woocommerce_new_customer_note action provides cc/bcc args.
 */
class WC_Email_Customer_Note_Cc_Bcc_Test extends \WC_Unit_Test_Case {

	/**
	 * Test that cc and bcc headers are added when provided.
	 */
	public function test_customer_note_email_includes_cc_bcc_headers() {
		$order = OrderHelper::create_dummy_order();
		$order->save();

		$email = new WC_Email_Customer_Note();

		$cc  = 'cc1@example.com, cc2@example.com';
		$bcc = 'bcc@example.com';

		$email->trigger(
			array(
				'order_id'      => $order->get_id(),
				'customer_note' => 'Test note',
				'cc'            => $cc,
				'bcc'           => $bcc,
			)
		);

		$headers = $email->get_headers();

		$this->assertStringContainsString( 'Cc: ' . $cc, $headers );
		$this->assertStringContainsString( 'Bcc: ' . $bcc, $headers );
	}

	/**
	 * Test that no cc/bcc headers added when not provided.
	 */
	public function test_customer_note_email_no_cc_bcc_when_empty() {
		$order = OrderHelper::create_dummy_order();
		$order->save();

		$email = new WC_Email_Customer_Note();

		$email->trigger(
			array(
				'order_id'      => $order->get_id(),
				'customer_note' => 'Test note without cc/bcc',
			)
		);

		$headers = $email->get_headers();

		$this->assertStringNotContainsString( 'Cc:', $headers );
		$this->assertNotContains( 'Bcc:', $headers );
	}
}
