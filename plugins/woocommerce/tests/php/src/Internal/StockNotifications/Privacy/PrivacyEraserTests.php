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
	 * @testdox Should fire woocommerce_stock_notification_erased with the original email before anonymizing.
	 */
	public function test_privacy_eraser_fires_erased_action_before_anonymizing(): void {
		$notification = new Notification();
		$notification->set_user_email( 'jon@doe.com' );
		$notification->set_user_id( 5 );
		$notification->set_product_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification_id = $notification->save();

		$erased = array();
		add_action(
			'woocommerce_stock_notification_erased',
			function ( $id, $original_email, $notification ) use ( &$erased ) {
				$erased[] = array( $id, $original_email, $notification->get_user_email(), $notification->get_user_id(), $notification->get_status() );
			},
			10,
			3
		);
		$status_changes = array();
		add_action(
			'woocommerce_stock_notification_status_changed',
			function ( $id, $from, $to, $notification ) use ( &$status_changes ) {
				$status_changes[] = array( $id, $from, $to, $notification->get_user_email() );
			},
			10,
			4
		);

		PrivacyEraser::erase_notification_data( 'jon@doe.com' );

		$this->assertSame( array( array( $notification_id, 'jon@doe.com', 'jon@doe.com', 5, NotificationStatus::ACTIVE ) ), $erased );
		$this->assertSame( array( array( $notification_id, NotificationStatus::ACTIVE, NotificationStatus::CANCELLED, wp_privacy_anonymize_data( 'email', '' ) ) ), $status_changes );
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

	/**
	 * @testdox Should erase a user's sign-ups under their previous and current email once each, plus guest rows for the current email only.
	 */
	public function test_privacy_eraser_matches_user_id_after_email_change(): void {
		$user_id = self::factory()->user->create( array( 'user_email' => 'old@doe.com' ) );
		$other   = self::factory()->user->create( array( 'user_email' => 'other@doe.com' ) );

		$user_notification = new Notification();
		$user_notification->set_user_id( $user_id );
		$user_notification->set_user_email( 'old@doe.com' );
		$user_notification->set_product_id( 1 );
		$user_notification_id = $user_notification->save();

		$guest_notification = new Notification();
		$guest_notification->set_user_email( 'new@doe.com' );
		$guest_notification->set_product_id( 2 );
		$guest_notification_id = $guest_notification->save();

		$other_notification = new Notification();
		$other_notification->set_user_id( $other );
		$other_notification->set_user_email( 'other@doe.com' );
		$other_notification->set_product_id( 3 );
		$other_notification_id = $other_notification->save();

		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'new@doe.com',
			)
		);

		// Signed up after the email change, so it matches both the email and the user ID query.
		$current_notification = new Notification();
		$current_notification->set_user_id( $user_id );
		$current_notification->set_user_email( 'new@doe.com' );
		$current_notification->set_product_id( 4 );
		$current_notification_id = $current_notification->save();

		$response = PrivacyEraser::erase_notification_data( 'new@doe.com' );

		$this->assertTrue( $response['items_removed'] );
		$this->assertCount( 3, $response['messages'] );

		$erased_user_notification = new Notification( $user_notification_id );
		$this->assertSame( NotificationStatus::CANCELLED, $erased_user_notification->get_status() );
		$this->assertSame( 0, $erased_user_notification->get_user_id() );
		$this->assertSame( wp_privacy_anonymize_data( 'email', '' ), $erased_user_notification->get_user_email() );

		$this->assertSame( NotificationStatus::CANCELLED, ( new Notification( $guest_notification_id ) )->get_status() );
		$this->assertSame( NotificationStatus::CANCELLED, ( new Notification( $current_notification_id ) )->get_status() );

		$untouched_notification = new Notification( $other_notification_id );
		$this->assertNotSame( NotificationStatus::CANCELLED, $untouched_notification->get_status() );
		$this->assertSame( $other, $untouched_notification->get_user_id() );
		$this->assertSame( 'other@doe.com', $untouched_notification->get_user_email() );
	}
}
