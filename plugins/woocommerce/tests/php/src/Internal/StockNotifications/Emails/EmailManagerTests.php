<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\StockNotificationsFeatureTrait;
use WC_Helper_Product;

/**
 * Tests for EmailManager wrapper methods.
 */
class EmailManagerTests extends \WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

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
		$this->enable_stock_notifications_feature();

		// Short-circuit `wp_mail()` so the tests never attempt a real SMTP handoff.
		// Returning a non-null value from `pre_wp_mail` signals WP core to skip the actual send.
		add_filter( 'pre_wp_mail', array( $this, 'capture_pre_wp_mail' ), 10, 2 );

		$this->sut = new EmailManager();
		$this->sut->init();

		// `WC_Emails` is a singleton that keeps whichever email list it built on its first
		// construction for the rest of the process. Reset it so this test's registration
		// via `woocommerce_email_classes` is actually picked up rather than an earlier
		// test's (feature-disabled) build of the list.
		$this->reset_email_singleton();
		WC()->mailer();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_wp_mail', array( $this, 'capture_pre_wp_mail' ), 10 );
		$this->sent_to = array();
		$this->restore_stock_notifications_feature_option();
		// Discard this test's `WC_Emails` build so later tests rebuild their own from
		// whatever filters are active for them, rather than inheriting this test's list.
		$this->reset_email_singleton();
		parent::tearDown();
	}

	/**
	 * Reset the `WC_Emails` singleton so the next `WC()->mailer()` call rebuilds it.
	 */
	private function reset_email_singleton(): void {
		$instance_property = ( new \ReflectionClass( \WC_Emails::class ) )->getProperty( 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, null );
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
