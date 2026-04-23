<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use WC_Helper_Product;

/**
 * Tests for EmailManager wrapper methods.
 */
class EmailManagerTests extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var EmailManager
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new EmailManager();
		$this->sut->init();

		// Boot the mailer so email classes are registered.
		WC()->mailer();
	}

	/**
	 * @testdox Should register the three BIS email classes via woocommerce_email_classes filter.
	 */
	public function test_registers_all_three_bis_email_classes() {
		$emails = WC()->mailer()->get_emails();

		$this->assertArrayHasKey( 'WC_Email_Customer_Stock_Notification', $emails );
		$this->assertArrayHasKey( 'WC_Email_Customer_Stock_Notification_Verify', $emails );
		$this->assertArrayHasKey( 'WC_Email_Customer_Stock_Notification_Verified', $emails );
	}

	/**
	 * @testdox Should set the verify email object to the given notification when send_verify_email is called.
	 */
	public function test_send_verify_email_prepares_verify_email_for_notification() {
		$notification = $this->build_notification();

		$this->sut->send_verify_email( $notification );

		$emails = WC()->mailer()->get_emails();
		$verify = $emails['WC_Email_Customer_Stock_Notification_Verify'];
		$this->assertSame( $notification->get_user_email(), $verify->get_recipient() );
	}

	/**
	 * @testdox Should set the verified email object to the given notification when send_verified_email is called.
	 */
	public function test_send_verified_email_prepares_verified_email_for_notification() {
		$notification = $this->build_notification();

		$this->sut->send_verified_email( $notification );

		$emails   = WC()->mailer()->get_emails();
		$verified = $emails['WC_Email_Customer_Stock_Notification_Verified'];
		$this->assertSame( $notification->get_user_email(), $verified->get_recipient() );
	}

	/**
	 * Build a persisted notification for an in-stock simple product.
	 *
	 * @return Notification
	 */
	private function build_notification(): Notification {
		$product = WC_Helper_Product::create_simple_product();

		$notification = new Notification();
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::PENDING );
		$notification->set_user_email( 'customer@example.com' );
		$notification->save();

		return $notification;
	}
}
