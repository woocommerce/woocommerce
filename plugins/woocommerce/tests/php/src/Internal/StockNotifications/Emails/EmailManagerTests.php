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
	 * Captured `wp_mail()` recipients.
	 *
	 * @var array<string,bool>
	 */
	private $sent_to = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Short-circuit `wp_mail()` so the tests never attempt a real SMTP handoff.
		// Returning a non-null value from `pre_wp_mail` signals WP core to skip the actual send.
		add_filter( 'pre_wp_mail', array( $this, 'capture_pre_wp_mail' ), 10, 2 );

		$this->sut = new EmailManager();
		$this->sut->init();

		// Boot the mailer so email classes are registered.
		WC()->mailer();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_wp_mail', array( $this, 'capture_pre_wp_mail' ), 10 );
		$this->sent_to = array();
		parent::tearDown();
	}

	/**
	 * `pre_wp_mail` filter: record the recipient and short-circuit the actual send.
	 *
	 * @param bool|null $short_circuit Null means "keep going", non-null short-circuits.
	 * @param array     $atts          Mail arguments.
	 * @return bool
	 */
	public function capture_pre_wp_mail( $short_circuit, $atts ): bool {
		unset( $short_circuit );
		$recipients = is_array( $atts['to'] ?? null ) ? $atts['to'] : array( $atts['to'] ?? '' );
		foreach ( $recipients as $recipient ) {
			$this->sent_to[ $recipient ] = true;
		}
		return true;
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
	 * @testdox Should dispatch the verify email to the notification's user email when send_verify_email is called.
	 */
	public function test_send_verify_email_prepares_verify_email_for_notification() {
		$notification = $this->build_notification();

		$this->sut->send_verify_email( $notification );

		$emails = WC()->mailer()->get_emails();
		$verify = $emails['WC_Email_Customer_Stock_Notification_Verify'];
		$this->assertSame( $notification->get_user_email(), $verify->get_recipient() );
		// Behavior assertion: the trigger path actually dispatched mail to the expected recipient.
		$this->assertArrayHasKey( $notification->get_user_email(), $this->sent_to );
	}

	/**
	 * @testdox Should dispatch the verified email to the notification's user email when send_verified_email is called.
	 */
	public function test_send_verified_email_prepares_verified_email_for_notification() {
		$notification = $this->build_notification();

		$this->sut->send_verified_email( $notification );

		$emails   = WC()->mailer()->get_emails();
		$verified = $emails['WC_Email_Customer_Stock_Notification_Verified'];
		$this->assertSame( $notification->get_user_email(), $verified->get_recipient() );
		// Behavior assertion: the trigger path actually dispatched mail to the expected recipient.
		$this->assertArrayHasKey( $notification->get_user_email(), $this->sent_to );
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
