<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\EmailEditor\Bootstrap;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Dependency_Check;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\Package;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmails;
use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalFormProcessor;

/**
 * Block email editor versions of the order withdrawal emails.
 *
 * @covers WC_Email_Customer_Order_Withdrawal_Requested
 * @covers WC_Email_Order_Withdrawal_Requested
 */
class WC_Email_Order_Withdrawal_Block_Email_Test extends \WC_Unit_Test_Case {

	/**
	 * Turn on the block email editor and the order withdrawal feature, and load the email classes.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		update_option( 'woocommerce_feature_order_withdrawal_enabled', 'yes' );

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-customer-order-withdrawal-requested.php';
		require_once $bootstrap->plugin_dir . '/includes/emails/class-wc-email-order-withdrawal-requested.php';

		wc_get_container()->get( Package::class )->init();
		wc_get_container()->get( Integration::class )->initialize();
		Email_Editor_Container::container()->get( Bootstrap::class )->initialize();
	}

	/**
	 * @testdox The withdrawal emails are listed in the block email editor only when the order withdrawal feature is on.
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $feature_enabled Value of the order withdrawal feature option.
	 * @param bool   $expected_listed Whether the withdrawal emails should be listed.
	 */
	public function test_withdrawal_emails_are_listed_with_the_feature( string $feature_enabled, bool $expected_listed ): void {
		update_option( 'woocommerce_feature_order_withdrawal_enabled', $feature_enabled );

		$emails = WCTransactionalEmails::get_core_transactional_emails();

		$this->assertSame( $expected_listed, in_array( 'customer_order_withdrawal_requested', $emails, true ) );
		$this->assertSame( $expected_listed, in_array( 'order_withdrawal_requested', $emails, true ) );
	}

	/**
	 * @testdox The customer email is sent in the block design with the request details and without an order table.
	 */
	public function test_customer_email_is_sent_in_block_design(): void {
		$this->skip_if_unsupported_environment();

		$email                  = new WC_Email_Customer_Order_Withdrawal_Requested();
		$email->object          = (object) $this->get_withdrawal_data();
		$email->withdrawal_data = $this->get_withdrawal_data();
		$email->submitted_at    = 1767225600;

		$content = $email->get_content();

		$this->assertStringContainsString( 'We received your withdrawal request', $content );
		$this->assertStringContainsString( 'Please take back the blue mug.', $content );
		$this->assertStringContainsString( 'Specific items only', $content );
		$this->assertStringNotContainsString( 'email-order-details', $content );
		$this->assertStringContainsString( '. All Rights Reserved.', $content, 'Only the block email template renders this footer.' );
	}

	/**
	 * @testdox The customer email block content shows the request details, and no order table in the preview, where the email object is an order.
	 */
	public function test_customer_block_content_has_no_order_table_for_an_order(): void {
		$order = wc_create_order();

		$email                  = new WC_Email_Customer_Order_Withdrawal_Requested();
		$email->object          = $order;
		$email->withdrawal_data = $this->get_withdrawal_data();
		$email->submitted_at    = 1767225600;

		$content = $email->get_block_editor_email_template_content();

		$this->assertStringContainsString( 'Please take back the blue mug.', $content );
		$this->assertStringNotContainsString( 'email-order-details', $content );
	}

	/**
	 * @testdox The admin email is sent in the block design and says whether the request was matched to an order.
	 * @testWith [true, "WooCommerce matched this request to an order and added an order note."]
	 *           [false, "WooCommerce could not match this request to an order automatically, so no order note was added."]
	 *
	 * @param bool   $matched       Whether the request was matched to an order.
	 * @param string $expected_text Sentence the email should contain.
	 */
	public function test_admin_email_is_sent_in_block_design( bool $matched, string $expected_text ): void {
		$this->skip_if_unsupported_environment();

		$order = $matched ? wc_create_order() : null;

		$email                  = new WC_Email_Order_Withdrawal_Requested();
		$email->object          = $order ? $order : (object) $this->get_withdrawal_data();
		$email->withdrawal_data = $this->get_withdrawal_data();
		$email->matched_order   = $order;
		$email->submitted_at    = 1767225600;

		$content = $email->get_content();

		$this->assertStringContainsString( 'Order withdrawal request received', $content );
		$this->assertStringContainsString( $expected_text, $content );
		$this->assertStringContainsString( 'Please take back the blue mug.', $content );
		$this->assertStringContainsString( '. All Rights Reserved.', $content, 'Only the block email template renders this footer.' );
		if ( $order ) {
			$this->assertStringContainsString( 'Matched order ID: ' . $order->get_id(), $content );
		}
	}

	/**
	 * Withdrawal form data as the form processor normalizes it.
	 *
	 * @return array<string,string>
	 */
	private function get_withdrawal_data(): array {
		return array(
			OrderWithdrawalFormProcessor::FIELD_FIRST_NAME => 'Jane',
			OrderWithdrawalFormProcessor::FIELD_LAST_NAME  => 'Doe',
			OrderWithdrawalFormProcessor::FIELD_EMAIL      => 'jane@example.com',
			OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => '1234',
			OrderWithdrawalFormProcessor::FIELD_WITHDRAWAL_TYPE => OrderWithdrawalFormProcessor::WITHDRAWAL_TYPE_SPECIFIC,
			OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS => 'Please take back the blue mug.',
		);
	}

	/**
	 * Skip when the test environment can't run the block email editor.
	 */
	private function skip_if_unsupported_environment(): void {
		if ( ! Email_Editor_Container::container()->get( Dependency_Check::class )->are_dependencies_met() ) {
			$this->markTestSkipped( 'The test environment does not meet the block email editor requirements.' );
		}
	}
}
