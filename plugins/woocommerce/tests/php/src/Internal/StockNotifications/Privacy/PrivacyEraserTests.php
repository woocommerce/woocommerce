<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Privacy;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Privacy\PrivacyEraser;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\StockNotificationsFeatureTrait;

/**
 * PrivacyEraser tests.
 */
class PrivacyEraserTests extends \WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		$this->restore_stock_notifications_feature_option();
		parent::tearDown();
	}

	/**
	 * Test that privacy eraser makes notification data anonymous.
	 */
	public function test_privacy_eraser_makes_data_anonymous() {
		$notification = new Notification();
		$notification->set_user_email( 'jon@doe.com' );
		$notification->set_product_id( 1 );
		$notification_id = $notification->save();

		$response = PrivacyEraser::erase_notification_data( 'jon@doe.com' );
		$this->assertTrue( $response['items_removed'] );
		$this->assertEquals( $response['messages'][0], 'Removed back-in-stock notification for product id: 1' );

		$anonymous_notification = new Notification( $notification_id );

		$this->assertEquals( $anonymous_notification->get_user_email(), wp_privacy_anonymize_data( 'email', '' ) );
		$this->assertEquals( NotificationStatus::CANCELLED, $anonymous_notification->get_status() );
		$this->assertEquals( NotificationCancellationSource::PRIVACY, $anonymous_notification->get_cancellation_source() );
		$this->assertNotNull( $anonymous_notification->get_date_cancelled() );
	}

	/**
	 * @testdox Should keep a sent notification sent, with its sent date, while still anonymizing it.
	 */
	public function test_privacy_eraser_keeps_sent_notification_sent(): void {
		$notification = new Notification();
		$notification->set_user_email( 'jon@doe.com' );
		$notification->set_product_id( 1 );
		$notification->set_status( NotificationStatus::SENT );
		$notification->set_date_notified( time() - DAY_IN_SECONDS );
		$notification_id = $notification->save();
		$date_notified   = ( new Notification( $notification_id ) )->get_date_notified();

		$response = PrivacyEraser::erase_notification_data( 'jon@doe.com' );
		$this->assertTrue( $response['items_removed'] );

		$updated = new Notification( $notification_id );
		$this->assertEquals( NotificationStatus::SENT, $updated->get_status() );
		$this->assertNotNull( $date_notified );
		$this->assertEquals( $date_notified->getTimestamp(), $updated->get_date_notified()->getTimestamp() );
		$this->assertNull( $updated->get_cancellation_source() );
		$this->assertNull( $updated->get_date_cancelled() );
		$this->assertEquals( wp_privacy_anonymize_data( 'email', '' ), $updated->get_user_email() );
		$this->assertSame( 0, $updated->get_user_id() );
		$this->assertEquals( 'yes', $updated->get_meta( '_anonymized' ) );
	}

	/**
	 * @testdox Should keep the cancellation source of a notification the user already cancelled.
	 */
	public function test_privacy_eraser_keeps_existing_cancellation_source(): void {
		$notification = new Notification();
		$notification->set_user_email( 'jon@doe.com' );
		$notification->set_product_id( 1 );
		$notification->set_status( NotificationStatus::CANCELLED );
		$notification->set_cancellation_source( NotificationCancellationSource::USER );
		$notification_id = $notification->save();

		PrivacyEraser::erase_notification_data( 'jon@doe.com' );

		$updated = new Notification( $notification_id );
		$this->assertEquals( NotificationStatus::CANCELLED, $updated->get_status() );
		$this->assertEquals( NotificationCancellationSource::USER, $updated->get_cancellation_source() );
		$this->assertEquals( wp_privacy_anonymize_data( 'email', '' ), $updated->get_user_email() );
	}

	/**
	 * @testdox Should erase a lowercase row when given a mixed-case email.
	 */
	public function test_privacy_eraser_matches_case_variants(): void {
		$notification = new Notification();
		$notification->set_user_email( 'jon@doe.com' );
		$notification->set_product_id( 1 );
		$notification_id = $notification->save();

		$response = PrivacyEraser::erase_notification_data( 'Jon@Doe.COM' );

		$this->assertTrue( $response['items_removed'] );
		$this->assertEquals( NotificationStatus::CANCELLED, ( new Notification( $notification_id ) )->get_status() );
	}
}
