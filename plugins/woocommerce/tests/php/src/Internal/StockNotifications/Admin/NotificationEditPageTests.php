<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationEditPage;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationsPage;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\NotificationManagementService;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\StockNotificationsFeatureTrait;

/**
 * Tests for the admin notification edit form handler.
 */
class NotificationEditPageTests extends \WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	/**
	 * The System Under Test.
	 *
	 * @var NotificationEditPage
	 */
	private $sut;

	/**
	 * Mocked email manager injected into the SUT.
	 *
	 * @var EmailManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $email_manager;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();
		$this->email_manager = $this->createMock( EmailManager::class );
		$this->sut           = new NotificationEditPage();
		$this->sut->init( $this->email_manager );
		add_filter( 'wp_redirect', array( $this, 'stop_redirect' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'wp_redirect', array( $this, 'stop_redirect' ) );
		$_POST    = array();
		$_REQUEST = array();
		delete_option( NotificationsPage::ADMIN_NOTICE_OPTION_NAME );
		$this->restore_stock_notifications_feature_option();
		parent::tearDown();
	}

	/**
	 * Throws instead of redirecting so the handler's exit is never reached.
	 *
	 * @throws \Exception Always.
	 */
	public function stop_redirect(): void {
		throw new \Exception( 'redirect' );
	}

	/**
	 * @testdox Sending a verification email from the edit page records the send time.
	 */
	public function test_send_verification_email_records_send_time(): void {
		$notification = new Notification();
		$notification->set_user_email( 'pending@test.com' );
		$notification->set_product_id( 1 );
		$notification->set_status( NotificationStatus::PENDING );
		$notification_id = $notification->save();

		$_POST = array(
			'wc_customer_stock_notification_action'     => 'send_verification_email',
			'customer_stock_notification_edit_security' => wp_create_nonce( 'woocommerce-customer-stock-notification-edit' ),
		);

		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Simulates the request the handler verifies.

		$before     = time();
		$redirected = false;
		try {
			$this->sut->process_edit_form( $notification );
		} catch ( \Exception $e ) {
			$redirected = 'redirect' === $e->getMessage();
		}
		$this->assertTrue( $redirected, 'Expected the handler to redirect.' );

		$sent_at = (int) ( new Notification( $notification_id ) )->get_meta( NotificationManagementService::LAST_VERIFY_EMAIL_SENT_META );
		$this->assertGreaterThanOrEqual( $before, $sent_at );
	}

	/**
	 * @testdox Sending a notification from the edit page marks it sent when the email goes out.
	 */
	public function test_send_notification_marks_sent_when_email_is_sent(): void {
		$this->email_manager->method( 'send_stock_notification_email' )->willReturn( true );
		$notification_id = $this->submit_send_notification();

		$notification = new Notification( $notification_id );
		$this->assertSame( NotificationStatus::SENT, $notification->get_status() );
		$this->assertNotEmpty( $notification->get_date_notified() );
		$this->assertSame( 'success', get_option( NotificationsPage::ADMIN_NOTICE_OPTION_NAME )['type'] );
	}

	/**
	 * @testdox Sending a notification from the edit page leaves it active when the email fails.
	 */
	public function test_send_notification_keeps_status_when_email_fails(): void {
		$this->email_manager->method( 'send_stock_notification_email' )->willReturn( false );
		$notification_id = $this->submit_send_notification();

		$notification = new Notification( $notification_id );
		$this->assertSame( NotificationStatus::ACTIVE, $notification->get_status() );
		$this->assertEmpty( $notification->get_date_notified() );
		$this->assertSame( 'error', get_option( NotificationsPage::ADMIN_NOTICE_OPTION_NAME )['type'] );
	}

	/**
	 * Submit the "send_notification" action for a new active sign-up on an in-stock product.
	 *
	 * @return int The notification ID.
	 */
	private function submit_send_notification(): int {
		$product = \WC_Helper_Product::create_simple_product();

		$notification = new Notification();
		$notification->set_user_email( 'active@test.com' );
		$notification->set_product_id( $product->get_id() );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification_id = $notification->save();

		$_POST = array(
			'wc_customer_stock_notification_action'     => 'send_notification',
			'customer_stock_notification_edit_security' => wp_create_nonce( 'woocommerce-customer-stock-notification-edit' ),
		);

		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Simulates the request the handler verifies.

		$redirected = false;
		try {
			$this->sut->process_edit_form( $notification );
		} catch ( \Exception $e ) {
			$redirected = 'redirect' === $e->getMessage();
		}
		$this->assertTrue( $redirected, 'Expected the handler to redirect.' );

		return $notification_id;
	}
}
