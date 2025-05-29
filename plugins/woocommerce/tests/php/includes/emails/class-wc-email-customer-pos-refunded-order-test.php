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
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-refunded-order.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-pos-refunded-order.php';
		require_once $bootstrap->plugin_dir . '/includes/class-wc-emails.php';
	}

	/**
	 * @testdox order_item_totals does not add custom rows for cash change even when cash change is set to avoid confusion in UX.
	 */
	public function test_order_item_totals_adds_formatted_cash_change_due_amount_to_order_totals() {
		// Given order with cash payment change amount.
		$order = OrderHelper::create_order();
		$order->add_meta_data( '_cash_change_amount', '5.00' );
		$order->save();
		$email = new WC_Email_Customer_POS_Refunded_Order();

		// When overriding order item totals.
		$totals = $email->order_item_totals( array(), $order, 'incl' );

		// Then cash payment change due amount is not set.
		$this->assertArrayNotHasKey( 'cash_payment_change_due_amount', $totals );
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

	/**
	 * @testdox POS email includes additional rows in order totals that regular refunded order email does not.
	 */
	public function test_pos_email_includes_additional_rows_in_order_totals_while_regular_email_does_not() {
		// Initialize WC_Emails to set up actions and filters for order totals where the POS email is different.
		$emails = new WC_Emails();

		// Given an order with POS-specific data.
		$order = OrderHelper::create_order();
		$order->add_meta_data( '_charge_id', 'AUTH134' );
		$order->set_date_paid( '2023-06-01 12:00:00' );
		$order->save();

		// When getting content from both email types.
		$pos_email     = new WC_Email_Customer_POS_Refunded_Order();
		$regular_email = new WC_Email_Customer_Refunded_Order();

		// Set the order on both email objects.
		$pos_email->object     = $order;
		$regular_email->object = $order;

		$pos_content     = $pos_email->get_content_html();
		$regular_content = $regular_email->get_content_html();

		// Then POS email should include additional rows.
		$this->assertStringContainsString( 'payment_auth_code', $pos_content );
		$this->assertStringContainsString( 'date_paid', $pos_content );

		// And regular email should not include these rows.
		$this->assertStringNotContainsString( 'payment_auth_code', $regular_content );
		$this->assertStringNotContainsString( 'date_paid', $regular_content );

		// When generating plain text emails.
		$pos_plain_text     = $pos_email->get_content_plain();
		$regular_plain_text = $regular_email->get_content_plain();

		// Then POS email should include additional rows.
		$this->assertStringContainsString( __( 'Auth code:', 'woocommerce' ), $pos_plain_text );
		$this->assertStringContainsString( __( 'Time of payment:', 'woocommerce' ), $pos_plain_text );

		// And regular email should not include these rows.
		$this->assertStringNotContainsString( __( 'Auth code:', 'woocommerce' ), $regular_plain_text );
		$this->assertStringNotContainsString( __( 'Time of payment:', 'woocommerce' ), $regular_plain_text );
	}

	/**
	 * @testdox POS email includes POS store name in email header HTML while regular email includes blog name.
	 */
	public function test_pos_email_includes_pos_store_name_in_email_header_html_while_regular_email_includes_blog_name() {
		// Initialize WC_Emails to set up actions and filters for email header in regular emails.
		$emails = new WC_Emails();

		// Given POS store name and blog name.
		update_option( 'woocommerce_pos_store_name', 'Physical Store' );
		update_option( 'blogname', 'Online Store' );

		// When getting content from both email classes.
		$pos_email     = new WC_Email_Customer_POS_Refunded_Order();
		$regular_email = new WC_Email_Customer_Refunded_Order();

		// Set the order on both email classes.
		$pos_email->object     = OrderHelper::create_order();
		$regular_email->object = OrderHelper::create_order();

		$pos_content     = $pos_email->get_content_html();
		$regular_content = $regular_email->get_content_html();

		// Then POS email should include POS store name.
		$this->assertStringContainsString( '<title>Physical Store</title>', $pos_content );
		$this->assertStringNotContainsString( '<title>Online Store</title>', $pos_content );

		// And regular email should include blog name.
		$this->assertStringNotContainsString( '<title>Physical Store</title>', $regular_content );
		$this->assertStringContainsString( '<title>Online Store</title>', $regular_content );
	}

	/**
	 * @testdox POS email includes blog name in email header HTML when POS store name is not set.
	 */
	public function test_pos_email_header_html_includes_blog_name_when_pos_store_name_is_not_set() {
		// Given POS store name is not set.
		delete_option( 'woocommerce_pos_store_name' );
		update_option( 'blogname', 'Online Store' );

		$emails = new WC_Emails();

		// When getting content from POS email.
		$email = new WC_Email_Customer_POS_Refunded_Order( $emails );

		// Set the order on the email.
		$email->object = OrderHelper::create_order();

		$content = $email->get_content_html();

		// Then POS email should include blog name.
		$this->assertStringContainsString( '<title>Online Store</title>', $content );
	}

	/**
	 * @testdox get_default_subject includes blog name when POS store name is not set.
	 */
	public function test_get_default_subject_includes_blog_name_when_pos_store_name_not_set() {
		// Given POS store name is not set.
		delete_option( 'woocommerce_pos_store_name' );
		$blog_name = 'Test Blog Name';
		update_option( 'blogname', $blog_name );

		// When getting default subject.
		$email   = new WC_Email_Customer_POS_Refunded_Order();
		$subject = $email->get_default_subject();

		// Then blog name is included in the subject.
		$this->assertStringContainsString( $blog_name, $subject );
	}

	/**
	 * @testdox get_default_subject includes POS store name when option is set.
	 */
	public function test_get_default_subject_includes_pos_store_name_when_option_is_set() {
		// Given POS store name is set.
		$store_name = 'POS Store';
		update_option( 'woocommerce_pos_store_name', $store_name );

		// When getting default subject.
		$email   = new WC_Email_Customer_POS_Refunded_Order();
		$subject = $email->get_default_subject();

		// Then POS store name is included in the subject.
		$this->assertStringContainsString( $store_name, $subject );
	}

	/**
	 * @testdox POS email content includes store details when options are set.
	 */
	public function test_email_content_includes_store_details_when_options_are_set() {
		// Given POS store details are set.
		update_option( 'woocommerce_pos_store_name', 'POS Store' );
		update_option( 'woocommerce_pos_store_email', 'pos@example.com' );
		update_option( 'woocommerce_pos_store_phone', '1234567890' );
		update_option( 'woocommerce_pos_store_address', '134 Main St, Anytown, USA' );

		// When getting content from both email types.
		$email              = new WC_Email_Customer_POS_Refunded_Order();
		$email->object      = OrderHelper::create_order();
		$html_content       = $email->get_content_html();
		$plain_text_content = $email->get_content_plain();

		// Then POS email should include store details.
		$this->assertStringContainsString( '<h2>POS Store</h2>', $html_content );
		$this->assertStringContainsString( 'pos@example.com', $html_content );
		$this->assertStringContainsString( '1234567890', $html_content );
		$this->assertStringContainsString( '134 Main St, Anytown, USA', $html_content );

		// And plain text email should include store details.
		$this->assertStringContainsString( 'POS Store', $plain_text_content );
		$this->assertStringContainsString( 'pos@example.com', $plain_text_content );
		$this->assertStringContainsString( '1234567890', $plain_text_content );
		$this->assertStringContainsString( '134 Main St, Anytown, USA', $plain_text_content );
	}

	/**
	 * @testdox POS email content includes store details when options are set.
	 */
	public function test_email_content_does_not_include_store_details_when_options_are_not_set() {
		// Given POS store details are not set.
		delete_option( 'woocommerce_pos_store_name' );
		delete_option( 'woocommerce_pos_store_email' );
		delete_option( 'woocommerce_pos_store_phone' );
		delete_option( 'woocommerce_pos_store_address' );

		// When getting content from both email types.
		$email              = new WC_Email_Customer_POS_Refunded_Order();
		$email->object      = OrderHelper::create_order();
		$html_content       = $email->get_content_html();
		$plain_text_content = $email->get_content_plain();

		// Then POS email should not include store details.
		$this->assertStringNotContainsString( 'POS Store', $html_content );
		$this->assertStringNotContainsString( 'pos@example.com', $html_content );
		$this->assertStringNotContainsString( '1234567890', $html_content );
		$this->assertStringNotContainsString( '134 Main St, Anytown, USA', $html_content );

		// And plain text email should not include store details.
		$this->assertStringNotContainsString( 'POS Store', $plain_text_content );
		$this->assertStringNotContainsString( 'pos@example.com', $plain_text_content );
		$this->assertStringNotContainsString( '1234567890', $plain_text_content );
		$this->assertStringNotContainsString( '134 Main St, Anytown, USA', $plain_text_content );
	}

	/**
	 * @testdox POS email content includes refund & returns policy when option is set.
	 */
	public function test_email_content_includes_refund_returns_policy_when_option_is_set() {
		// Given option is set.
		update_option( 'woocommerce_pos_refund_returns_policy', 'Accepted within 30 days of purchase.' );

		// When getting content from both email types.
		$email              = new WC_Email_Customer_POS_Refunded_Order();
		$email->object      = OrderHelper::create_order();
		$html_content       = $email->get_content_html();
		$plain_text_content = $email->get_content_plain();

		// Then POS email should include store details.
		$this->assertStringContainsString( esc_html__( 'Refund & Returns Policy', 'woocommerce' ), $html_content );
		$this->assertStringContainsString( 'Accepted within 30 days of purchase.', $html_content );

		// And plain text email should include store details.
		$this->assertStringContainsString( esc_html__( 'Refund & Returns Policy', 'woocommerce' ), $plain_text_content );
		$this->assertStringContainsString( 'Accepted within 30 days of purchase.', $plain_text_content );
	}

	/**
	 * @testdox POS email content does not include refund & returns policy when option is not set.
	 */
	public function test_email_content_does_not_include_refund_returns_policy_when_option_is_not_set() {
		// Given option is not set.
		delete_option( 'woocommerce_pos_refund_returns_policy' );

		// When getting content from both email types.
		$email              = new WC_Email_Customer_POS_Refunded_Order();
		$email->object      = OrderHelper::create_order();
		$html_content       = $email->get_content_html();
		$plain_text_content = $email->get_content_plain();

		// Then POS email should not include refund & returns policy.
		$this->assertStringNotContainsString( esc_html__( 'Refund & Returns Policy', 'woocommerce' ), $html_content );

		// And plain text email should not include refund & returns policy.
		$this->assertStringNotContainsString( esc_html__( 'Refund & Returns Policy', 'woocommerce' ), $plain_text_content );
	}
}
