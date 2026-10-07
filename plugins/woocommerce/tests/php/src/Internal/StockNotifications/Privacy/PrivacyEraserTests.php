<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Privacy;

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
	 * @testdox Should erase a user's sign-up stored under their previous email, plus guest rows for the current email only.
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

		$response = PrivacyEraser::erase_notification_data( 'new@doe.com' );

		$this->assertTrue( $response['items_removed'] );
		$this->assertCount( 2, $response['messages'] );

		$erased_user_notification = new Notification( $user_notification_id );
		$this->assertSame( NotificationStatus::CANCELLED, $erased_user_notification->get_status() );
		$this->assertSame( 0, $erased_user_notification->get_user_id() );
		$this->assertSame( wp_privacy_anonymize_data( 'email', '' ), $erased_user_notification->get_user_email() );

		$this->assertSame( NotificationStatus::CANCELLED, ( new Notification( $guest_notification_id ) )->get_status() );

		$untouched_notification = new Notification( $other_notification_id );
		$this->assertNotSame( NotificationStatus::CANCELLED, $untouched_notification->get_status() );
		$this->assertSame( $other, $untouched_notification->get_user_id() );
		$this->assertSame( 'other@doe.com', $untouched_notification->get_user_email() );
	}
}
