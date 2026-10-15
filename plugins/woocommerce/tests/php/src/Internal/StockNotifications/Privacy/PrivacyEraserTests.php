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

	/**
	 * @testdox Should export a user's sign-ups under their previous and current email once each, plus guest rows for the current email, and leave other users out.
	 */
	public function test_privacy_exporter_matches_email_and_user_id(): void {
		$user_id = self::factory()->user->create( array( 'user_email' => 'old@doe.com' ) );
		$other   = self::factory()->user->create( array( 'user_email' => 'other@doe.com' ) );
		$product = \WC_Helper_Product::create_simple_product();

		$user_notification = new Notification();
		$user_notification->set_user_id( $user_id );
		$user_notification->set_user_email( 'old@doe.com' );
		$user_notification->set_product_id( $product->get_id() );
		$user_notification_id = $user_notification->save();

		$guest_notification = new Notification();
		$guest_notification->set_user_email( 'new@doe.com' );
		$guest_notification->set_product_id( $product->get_id() );
		$guest_notification_id = $guest_notification->save();

		$other_notification = new Notification();
		$other_notification->set_user_id( $other );
		$other_notification->set_user_email( 'other@doe.com' );
		$other_notification->set_product_id( $product->get_id() );
		$other_notification->save();

		wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => 'new@doe.com',
			)
		);

		$current_notification = new Notification();
		$current_notification->set_user_id( $user_id );
		$current_notification->set_user_email( 'new@doe.com' );
		$current_notification->set_product_id( $product->get_id() );
		$current_notification_id = $current_notification->save();

		$response = PrivacyEraser::export_notification_data( 'New@Doe.COM' );

		$this->assertTrue( $response['done'] );
		$this->assertSame(
			array(
				'stock-notification-' . $user_notification_id,
				'stock-notification-' . $guest_notification_id,
				'stock-notification-' . $current_notification_id,
			),
			array_column( $response['data'], 'item_id' )
		);

		$item = $response['data'][0];
		$this->assertSame( 'woocommerce_stock_notifications', $item['group_id'] );

		$values = array_column( $item['data'], 'value', 'name' );
		$this->assertSame( $product->get_name(), $values['Product'] );
		$this->assertSame( $product->get_permalink(), $values['Product URL'] );
		$this->assertSame( 'old@doe.com', $values['Email'] );
		$this->assertSame( 'Pending', $values['Status'] );
		$this->assertNotSame( '', $values['Date signed up'] );
		$this->assertSame( '', $values['Date notified'] );
	}

	/**
	 * @testdox Should export sign-ups ten per page and report done on the last page.
	 */
	public function test_privacy_exporter_paginates(): void {
		for ( $i = 1; $i <= 11; $i++ ) {
			$notification = new Notification();
			$notification->set_user_email( 'jon@doe.com' );
			$notification->set_product_id( $i );
			$notification->save();
		}

		$first_page = PrivacyEraser::export_notification_data( 'jon@doe.com', 1 );
		$this->assertCount( 10, $first_page['data'] );
		$this->assertFalse( $first_page['done'] );

		$second_page = PrivacyEraser::export_notification_data( 'jon@doe.com', 2 );
		$this->assertCount( 1, $second_page['data'] );
		$this->assertTrue( $second_page['done'] );

		$this->assertEmpty( array_intersect( array_column( $first_page['data'], 'item_id' ), array_column( $second_page['data'], 'item_id' ) ) );
	}

	/**
	 * @testdox Should report done with no data when the email has no sign-ups.
	 */
	public function test_privacy_exporter_returns_done_when_empty(): void {
		$response = PrivacyEraser::export_notification_data( 'nobody@doe.com' );

		$this->assertSame( array(), $response['data'] );
		$this->assertTrue( $response['done'] );
	}
}
