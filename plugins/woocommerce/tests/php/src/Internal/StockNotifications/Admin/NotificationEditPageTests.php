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
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();
		$this->sut = new NotificationEditPage();
		$this->sut->init( $this->createMock( EmailManager::class ) );
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

		$before = time();
		try {
			$this->sut->process_edit_form( $notification );
		} catch ( \Exception $e ) {
			$this->assertSame( 'redirect', $e->getMessage() );
		}

		$sent_at = (int) ( new Notification( $notification_id ) )->get_meta( NotificationManagementService::LAST_VERIFY_EMAIL_SENT_META );
		$this->assertGreaterThanOrEqual( $before, $sent_at );
	}
}
