<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Privacy;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsMetaDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Privacy\PrivacyEraser;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\StockNotificationsFeatureTrait;

/**
 * PrivacyEraser tests.
 */
class PrivacyEraserTests extends \WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	/**
	 * Pattern of the random placeholder email an erasure gives anonymized sign-ups.
	 */
	private const ANONYMOUS_EMAIL_PATTERN = '/^deleted-[a-z0-9]{12}@site\.invalid$/';

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
		$notification->set_user_id( 5 );
		$notification->set_product_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->update_meta_data( 'unsubscribe_action_key', 'abc' );
		$notification_id = $notification->save();

		$response = PrivacyEraser::erase_notification_data( 'jon@doe.com' );
		$this->assertTrue( $response['items_removed'] );
		$this->assertEquals( $response['messages'][0], 'Removed back-in-stock notification for product id: 1' );

		$anonymous_notification = new Notification( $notification_id );

		$this->assertMatchesRegularExpression( self::ANONYMOUS_EMAIL_PATTERN, $anonymous_notification->get_user_email() );
		$this->assertStringNotContainsString( 'jon', $anonymous_notification->get_user_email() );
		$this->assertSame( 0, $anonymous_notification->get_user_id() );
		$this->assertSame( 'yes', $anonymous_notification->get_meta( '_anonymized' ) );
		$this->assertSame( '', $anonymous_notification->get_meta( 'unsubscribe_action_key' ) );
		$this->assertEquals( NotificationStatus::CANCELLED, $anonymous_notification->get_status() );
		$this->assertEquals( NotificationCancellationSource::PRIVACY, $anonymous_notification->get_cancellation_source() );
		$this->assertNotNull( $anonymous_notification->get_date_cancelled() );
	}

	/**
	 * @testdox Should fire woocommerce_privacy_before_remove_stock_notification_personal_data with the original email before anonymizing.
	 */
	public function test_privacy_eraser_fires_before_remove_action_before_anonymizing(): void {
		$notification = new Notification();
		$notification->set_user_email( 'jon@doe.com' );
		$notification->set_user_id( 5 );
		$notification->set_product_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification_id = $notification->save();

		$erased = array();
		add_action(
			'woocommerce_privacy_before_remove_stock_notification_personal_data',
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
		$this->assertCount( 1, $status_changes );
		$this->assertSame( array( $notification_id, NotificationStatus::ACTIVE, NotificationStatus::CANCELLED ), array_slice( $status_changes[0], 0, 3 ) );
		$this->assertMatchesRegularExpression( self::ANONYMOUS_EMAIL_PATTERN, $status_changes[0][3] );
	}

	/**
	 * @testdox Should delete a pending notification instead of anonymizing and cancelling it.
	 */
	public function test_privacy_eraser_deletes_pending_notification(): void {
		$notification = new Notification();
		$notification->set_user_email( 'jon@doe.com' );
		$notification->set_product_id( 1 );
		$notification->set_status( NotificationStatus::PENDING );
		$notification_id = $notification->save();

		$before_remove = array();
		add_action(
			'woocommerce_privacy_before_remove_stock_notification_personal_data',
			function ( $id, $original_email ) use ( &$before_remove ) {
				$before_remove[] = array( $id, $original_email );
			},
			10,
			2
		);
		$deleted = array();
		add_action(
			'woocommerce_delete_stock_notification',
			function ( $id ) use ( &$deleted ) {
				$deleted[] = $id;
			}
		);
		$status_changes = 0;
		add_action(
			'woocommerce_stock_notification_status_changed',
			function () use ( &$status_changes ) {
				++$status_changes;
			}
		);

		$response = PrivacyEraser::erase_notification_data( 'jon@doe.com' );

		$this->assertTrue( $response['items_removed'] );
		$this->assertSame( array( 'Removed back-in-stock notification for product id: 1' ), $response['messages'] );
		$this->assertSame( array( array( $notification_id, 'jon@doe.com' ) ), $before_remove );
		$this->assertSame( array( $notification_id ), $deleted );
		$this->assertSame( 0, $status_changes, 'A deleted pending sign-up should not be reported as cancelled.' );
		$this->assertSame( 0, NotificationQuery::count_notifications( array( 'customer' => array( 'jon@doe.com' ) ) ), 'The pending sign-up should no longer exist.' );
	}

	/**
	 * @testdox Should give each erased customer a different placeholder email.
	 */
	public function test_privacy_eraser_uses_distinct_placeholder_per_erasure(): void {
		$ids = array();
		foreach ( array( 'jon@doe.com', 'jane@doe.com' ) as $email ) {
			$notification = new Notification();
			$notification->set_user_email( $email );
			$notification->set_product_id( 1 );
			$notification->set_status( NotificationStatus::SENT );
			$ids[] = $notification->save();

			PrivacyEraser::erase_notification_data( $email );
		}

		$first  = ( new Notification( $ids[0] ) )->get_user_email();
		$second = ( new Notification( $ids[1] ) )->get_user_email();
		$this->assertMatchesRegularExpression( self::ANONYMOUS_EMAIL_PATTERN, $first );
		$this->assertMatchesRegularExpression( self::ANONYMOUS_EMAIL_PATTERN, $second );
		$this->assertNotSame( $first, $second );
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
		$this->assertMatchesRegularExpression( self::ANONYMOUS_EMAIL_PATTERN, $updated->get_user_email() );
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
		$this->assertMatchesRegularExpression( self::ANONYMOUS_EMAIL_PATTERN, $updated->get_user_email() );
	}

	/**
	 * @testdox Should erase a lowercase row when given a mixed-case email.
	 */
	public function test_privacy_eraser_matches_case_variants(): void {
		$notification = new Notification();
		$notification->set_user_email( 'jon@doe.com' );
		$notification->set_product_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
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
		$user_notification->set_status( NotificationStatus::ACTIVE );
		$user_notification_id = $user_notification->save();

		$guest_notification = new Notification();
		$guest_notification->set_user_email( 'new@doe.com' );
		$guest_notification->set_product_id( 2 );
		$guest_notification->set_status( NotificationStatus::ACTIVE );
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
		$current_notification->set_status( NotificationStatus::ACTIVE );
		$current_notification_id = $current_notification->save();

		$response = PrivacyEraser::erase_notification_data( 'new@doe.com' );

		$this->assertTrue( $response['items_removed'] );
		$this->assertCount( 3, $response['messages'] );

		$erased_user_notification = new Notification( $user_notification_id );
		$this->assertSame( NotificationStatus::CANCELLED, $erased_user_notification->get_status() );
		$this->assertSame( 0, $erased_user_notification->get_user_id() );
		$this->assertMatchesRegularExpression( self::ANONYMOUS_EMAIL_PATTERN, $erased_user_notification->get_user_email() );

		$erased_guest_notification   = new Notification( $guest_notification_id );
		$erased_current_notification = new Notification( $current_notification_id );
		$this->assertSame( NotificationStatus::CANCELLED, $erased_guest_notification->get_status() );
		$this->assertSame( NotificationStatus::CANCELLED, $erased_current_notification->get_status() );
		$this->assertSame( $erased_user_notification->get_user_email(), $erased_guest_notification->get_user_email(), 'All of a customer\'s rows should share one placeholder.' );
		$this->assertSame( $erased_user_notification->get_user_email(), $erased_current_notification->get_user_email(), 'All of a customer\'s rows should share one placeholder.' );

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
		$user_notification->set_date_created( 1690000000 );
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
		$current_notification->set_status( NotificationStatus::SENT );
		$current_notification->set_date_created( 1700000000 );
		$current_notification->set_date_notified( 1700003600 );
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
		$date_format = wc_date_format() . ', ' . wc_time_format();
		$this->assertSame( wc_format_datetime( new \WC_DateTime( '@1690000000' ), $date_format ), $values['Date signed up'] );
		$this->assertSame( '', $values['Date notified'] );

		$sent_values = array_column( $response['data'][2]['data'], 'value', 'name' );
		$this->assertSame( 'Sent', $sent_values['Status'] );
		$this->assertSame( wc_format_datetime( new \WC_DateTime( '@1700000000' ), $date_format ), $sent_values['Date signed up'] );
		$this->assertSame( wc_format_datetime( new \WC_DateTime( '@1700003600' ), $date_format ), $sent_values['Date notified'] );
	}

	/**
	 * @testdox Should export nothing for an empty or invalid email instead of every sign-up.
	 */
	public function test_privacy_exporter_ignores_empty_and_invalid_email(): void {
		$notification = new Notification();
		$notification->set_user_email( 'jon@doe.com' );
		$notification->set_product_id( 1 );
		$notification->save();

		foreach ( array( '', '   ', '0', 'not-an-email' ) as $email ) {
			$response = PrivacyEraser::export_notification_data( $email );
			$this->assertSame( array(), $response['data'], "Email '{$email}' should export nothing." );
			$this->assertTrue( $response['done'] );
		}

		$erased = PrivacyEraser::erase_notification_data( '' );
		$this->assertFalse( $erased['items_removed'] );
		$this->assertSame( 'jon@doe.com', ( new Notification( $notification->get_id() ) )->get_user_email() );
	}

	/**
	 * @testdox Should export the product ID when the product has been deleted.
	 */
	public function test_privacy_exporter_falls_back_to_product_id(): void {
		$notification = new Notification();
		$notification->set_user_email( 'jon@doe.com' );
		$notification->set_product_id( 987654 );
		$notification->save();

		$response = PrivacyEraser::export_notification_data( 'jon@doe.com' );
		$values   = array_column( $response['data'][0]['data'], 'value', 'name' );

		$this->assertSame( 'Product #987654', $values['Product'] );
		$this->assertSame( '', $values['Product URL'] );
	}

	/**
	 * @testdox Should register the exporter alongside the eraser.
	 */
	public function test_privacy_exporter_is_registered(): void {
		$privacy = new PrivacyEraser();
		$privacy->register_erasers_exporters();

		$exporters = apply_filters( 'wp_privacy_personal_data_exporters', array() );

		$this->assertArrayHasKey( 'woocommerce-customer-stock-notifications', $exporters );
		$callback = $exporters['woocommerce-customer-stock-notifications']['callback'];
		$this->assertIsCallable( $callback );
		$this->assertSame( array( $privacy, 'export_notification_data' ), $callback );
	}

	/**
	 * @testdox Should export a variation as its parent title followed by the attributes once.
	 */
	public function test_privacy_exporter_formats_variation_name(): void {
		$product   = \WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $product->get_children()[0] );

		$notification = new Notification();
		$notification->set_user_email( 'jon@doe.com' );
		$notification->set_product_id( $variation->get_id() );
		$notification->save();

		$response = PrivacyEraser::export_notification_data( 'jon@doe.com' );
		$values   = array_column( $response['data'][0]['data'], 'value', 'name' );

		$this->assertSame( $product->get_name() . ' size: small', $values['Product'] );
		$this->assertSame( $variation->get_permalink(), $values['Product URL'] );
	}

	/**
	 * @testdox Should export sign-ups ten per page and report done on the last page.
	 */
	public function test_privacy_exporter_paginates(): void {
		for ( $i = 1; $i <= 10; $i++ ) {
			$notification = new Notification();
			$notification->set_user_email( 'jon@doe.com' );
			$notification->set_product_id( $i );
			$notification->save();
		}

		$full_page = PrivacyEraser::export_notification_data( 'jon@doe.com', 1 );
		$this->assertCount( 10, $full_page['data'] );
		$this->assertTrue( $full_page['done'] );

		$notification = new Notification();
		$notification->set_user_email( 'jon@doe.com' );
		$notification->set_product_id( 11 );
		$notification->save();

		$first_page = PrivacyEraser::export_notification_data( 'jon@doe.com', 1 );
		$this->assertCount( 10, $first_page['data'] );
		$this->assertFalse( $first_page['done'] );

		$second_page = PrivacyEraser::export_notification_data( 'jon@doe.com', 2 );
		$this->assertCount( 1, $second_page['data'] );
		$this->assertTrue( $second_page['done'] );

		$this->assertEmpty( array_intersect( array_column( $first_page['data'], 'item_id' ), array_column( $second_page['data'], 'item_id' ) ) );

		$notification_ids = array( (int) $notification->get_id() );
		for ( $i = 12; $i <= 20; $i++ ) {
			$notification = new Notification();
			$notification->set_user_email( 'jon@doe.com' );
			$notification->set_product_id( $i );
			$notification_ids[] = $notification->save();
		}

		$first_page = PrivacyEraser::export_notification_data( 'jon@doe.com', 1 );
		$this->assertCount( 10, $first_page['data'] );
		$this->assertFalse( $first_page['done'] );

		$second_page = PrivacyEraser::export_notification_data( 'jon@doe.com', 2 );
		$this->assertTrue( $second_page['done'] );
		$this->assertSame(
			array_map(
				static fn( $id ) => 'stock-notification-' . $id,
				$notification_ids
			),
			array_column( $second_page['data'], 'item_id' )
		);
	}

	/**
	 * @testdox Should report done with no data when the email has no sign-ups.
	 */
	public function test_privacy_exporter_returns_done_when_empty(): void {
		$response = PrivacyEraser::export_notification_data( 'nobody@doe.com' );

		$this->assertSame( array(), $response['data'] );
		$this->assertTrue( $response['done'] );
	}

	/**
	 * @testdox Should skip sign-ups of other customers when the data store ignores the `customer` query argument.
	 */
	public function test_privacy_export_and_erase_skip_other_customers_rows(): void {
		$mine = new Notification();
		$mine->set_user_email( 'jon@doe.com' );
		$mine->set_product_id( 1 );
		$mine->set_status( NotificationStatus::ACTIVE );
		$mine_id = $mine->save();

		$other = new Notification();
		$other->set_user_email( 'other@doe.com' );
		$other->set_product_id( 1 );
		$other_id = $other->save();

		$store = new class() extends StockNotificationsDataStore {
			/**
			 * Query like a store written before the `customer` argument existed.
			 *
			 * @param array $args The arguments.
			 * @return array<int>|array<Notification>|int
			 */
			public function query( array $args ) {
				unset( $args['customer'] );
				return parent::query( $args );
			}
		};
		$store->init( wc_get_container()->get( StockNotificationsMetaDataStore::class ), wc_get_container()->get( DatabaseUtil::class ) );
		add_filter( 'woocommerce_stock_notification_data_store', fn() => $store );

		$response = PrivacyEraser::export_notification_data( 'jon@doe.com' );
		$this->assertSame( array( 'stock-notification-' . $mine_id ), array_column( $response['data'], 'item_id' ) );

		$erased = PrivacyEraser::erase_notification_data( 'jon@doe.com' );
		$this->assertTrue( $erased['items_removed'] );
		$this->assertSame( NotificationStatus::CANCELLED, ( new Notification( $mine_id ) )->get_status() );
		$this->assertSame( 'other@doe.com', ( new Notification( $other_id ) )->get_user_email() );
	}
}
