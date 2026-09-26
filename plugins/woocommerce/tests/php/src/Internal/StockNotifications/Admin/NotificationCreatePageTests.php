<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationCreatePage;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationsPage;

/**
 * Tests for the admin notification create form handler.
 */
class NotificationCreatePageTests extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NotificationCreatePage
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new NotificationCreatePage();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$_POST    = array();
		$_REQUEST = array();
		delete_option( NotificationsPage::ADMIN_NOTICE_OPTION_NAME );
		parent::tearDown();
	}

	/**
	 * @testdox Should reject a posted email that is not a string instead of passing it to the sanitizers.
	 *
	 * @testWith [["guest@example.com"]]
	 *           ["not an email"]
	 *
	 * @param mixed $posted_email The submitted email value.
	 */
	public function test_create_form_rejects_invalid_email( $posted_email ): void {
		$_POST = array(
			'save'       => '1',
			'product_id' => '1',
			'user_email' => $posted_email,
		);

		$_POST['customer_stock_notification_create_security'] = wp_create_nonce( 'woocommerce-customer-stock-notification-create' );

		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Simulates the request the handler verifies.

		$this->sut->process_create_form();

		$notice = get_option( NotificationsPage::ADMIN_NOTICE_OPTION_NAME );
		$this->assertIsArray( $notice, 'An invalid email should produce an admin notice' );
		$this->assertSame( 'error', $notice['type'] );
		$this->assertSame( 'Please enter a valid email address.', $notice['message'] );
	}
}
