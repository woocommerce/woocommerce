<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\DataStores\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

/**
 * Class StockNotificationsDataStoreTests.
 */
class StockNotificationsDataStoreTests extends \WC_Unit_Test_Case {

	/**
	 * The data store instance.
	 *
	 * @var StockNotificationsDataStore
	 */
	private $data_store;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->data_store = wc_get_container()->get( StockNotificationsDataStore::class );
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		// Clean up all notifications.
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notifications" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notificationmeta" );
	}

	/**
	 * Test that the stock notification data store is registered.
	 */
	public function test_stock_notification_data_store_is_registered() {
		$store = new \WC_Data_Store( 'stock_notification' );
		$this->assertTrue( is_callable( array( $store, 'read' ) ) );
		$this->assertEquals( StockNotificationsDataStore::class, $store->get_current_class_name() );
	}

	/**
	 * Test creating a notification with all properties.
	 */
	public function test_create_notification_with_all_properties() {
		$notification = new Notification();

		// Set all properties.
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->set_user_email( 'test@test.com' );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_date_created( '2024-01-01 00:00:00' );
		$notification->set_date_modified( '2024-02-01 00:00:00' );
		$notification->set_date_confirmed( '2024-04-01 00:00:00' );
		$notification->set_date_notified( '2024-05-01 00:00:00' );
		$notification->set_date_last_attempt( '2024-06-01 00:00:00' );
		$notification->set_date_cancelled( '2024-07-01 00:00:00' );
		$notification->set_cancellation_source( NotificationCancellationSource::USER );

		$notification->save();

		// Verify all properties were saved correctly.
		$this->assertEquals( 1, $notification->get_id() );
		$this->assertEquals( 1, $notification->get_product_id() );
		$this->assertEquals( 1, $notification->get_user_id() );
		$this->assertEquals( 'test@test.com', $notification->get_user_email() );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification->get_status() );
		$this->assertEquals( '2024-01-01 00:00:00', $notification->get_date_created()->format( 'Y-m-d H:i:s' ) );
		$this->assertEquals( '2024-02-01 00:00:00', $notification->get_date_modified()->format( 'Y-m-d H:i:s' ) );
		$this->assertEquals( '2024-04-01 00:00:00', $notification->get_date_confirmed()->format( 'Y-m-d H:i:s' ) );
		$this->assertEquals( '2024-05-01 00:00:00', $notification->get_date_notified()->format( 'Y-m-d H:i:s' ) );
		$this->assertEquals( '2024-06-01 00:00:00', $notification->get_date_last_attempt()->format( 'Y-m-d H:i:s' ) );
		$this->assertEquals( '2024-07-01 00:00:00', $notification->get_date_cancelled()->format( 'Y-m-d H:i:s' ) );
		$this->assertEquals( NotificationCancellationSource::USER, $notification->get_cancellation_source() );
	}

	/**
	 * Test validation requirements for creating a notification.
	 */
	public function test_create_notification_validation() {
		$notification = new Notification();

		// Test missing product_id.
		$result = $notification->save();
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'stock_notification_validation_error', $result->get_error_code() );

		// Test missing user_id and user_email.
		$notification->set_product_id( 1 );
		$result = $notification->save();
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'stock_notification_validation_error', $result->get_error_code() );
	}

	/**
	 * Test default data.
	 */
	public function test_default_notification_data() {

		$notification = new Notification();
		$this->assertEquals( 0, $notification->get_id() );
		$this->assertEquals( null, $notification->get_product_id() );
		$this->assertEquals( null, $notification->get_user_id() );
		$this->assertEquals( null, $notification->get_user_email() );
		$this->assertEquals( NotificationStatus::PENDING, $notification->get_status() );
		$this->assertEquals( null, $notification->get_date_created() );
		$this->assertEquals( null, $notification->get_date_modified() );
		$this->assertEquals( null, $notification->get_date_confirmed() );
		$this->assertEquals( null, $notification->get_date_notified() );
		$this->assertEquals( null, $notification->get_date_last_attempt() );
		$this->assertEquals( null, $notification->get_date_cancelled() );
		$this->assertEquals( null, $notification->get_cancellation_source() );
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->save();

		$this->assertEquals( 1, $notification->get_id() );
		$this->assertEquals( 1, $notification->get_product_id() );
		$this->assertEquals( 1, $notification->get_user_id() );
		$this->assertEquals( null, $notification->get_user_email() );
		$this->assertEquals( NotificationStatus::PENDING, $notification->get_status() );
		$this->assertEqualsWithDelta( 5, $notification->get_date_created()->getTimestamp(), time() );
		$this->assertEqualsWithDelta( 5, $notification->get_date_modified()->getTimestamp(), time() );
		$this->assertEquals( null, $notification->get_date_confirmed() );
		$this->assertEquals( null, $notification->get_date_notified() );
		$this->assertEquals( null, $notification->get_date_last_attempt() );
		$this->assertEquals( null, $notification->get_date_cancelled() );
		$this->assertEquals( null, $notification->get_cancellation_source() );
	}

	/**
	 * Test updating a notification with all properties.
	 */
	public function test_update_notification() {
		// Create initial notification.
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->set_user_email( 'test@test.com' );
		$notification->set_date_created( '2024-01-01 00:00:00' );
		$notification->set_date_modified( '2024-01-01 00:00:00' );
		$notification->save();

		// Verify dates.
		$this->assertEquals( '2024-01-01 00:00:00', $notification->get_date_created()->format( 'Y-m-d H:i:s' ) );
		$this->assertEquals( '2024-01-01 00:00:00', $notification->get_date_modified()->format( 'Y-m-d H:i:s' ) );

		// Update all properties.
		$notification->set_product_id( 2 );
		$notification->set_user_id( 2 );
		$notification->set_user_email( 'test2@test.com' );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->set_date_confirmed( '2024-01-02 00:00:00' );
		$notification->set_date_notified( '2024-01-03 00:00:00' );
		$notification->set_date_last_attempt( '2024-01-04 00:00:00' );
		$notification->set_date_cancelled( '2024-01-05 00:00:00' );
		$notification->set_cancellation_source( NotificationCancellationSource::ADMIN );
		$notification->save();

		// Verify all properties were updated correctly.
		$this->assertEquals( 2, $notification->get_product_id() );
		$this->assertEquals( 2, $notification->get_user_id() );
		$this->assertEquals( 'test2@test.com', $notification->get_user_email() );
		$this->assertEquals( NotificationStatus::ACTIVE, $notification->get_status() );
		$this->assertEquals( '2024-01-02 00:00:00', $notification->get_date_confirmed()->format( 'Y-m-d H:i:s' ) );
		$this->assertEquals( '2024-01-03 00:00:00', $notification->get_date_notified()->format( 'Y-m-d H:i:s' ) );
		$this->assertEquals( '2024-01-04 00:00:00', $notification->get_date_last_attempt()->format( 'Y-m-d H:i:s' ) );
		$this->assertEquals( '2024-01-05 00:00:00', $notification->get_date_cancelled()->format( 'Y-m-d H:i:s' ) );
		$this->assertEquals( NotificationCancellationSource::ADMIN, $notification->get_cancellation_source() );

		// Verify modified date is updated.
		$this->assertEquals( '2024-01-01 00:00:00', $notification->get_date_created()->format( 'Y-m-d H:i:s' ) );
		$this->assertNotEquals( '2024-01-01 00:00:00', $notification->get_date_modified()->format( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Test deleting a notification.
	 */
	public function test_delete_notification() {
		// Create a notification.
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->save();

		$notification_id = $notification->get_id();
		$this->assertGreaterThan( 0, $notification_id );

		// Delete the notification.
		$notification->delete();

		// Verify the notification was deleted.
		$this->assertEquals( 0, $notification->get_id() );

		// Try to read the deleted notification.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Stock notification not found' );
		new Notification( $notification_id );
	}

	/**
	 * Test reading a non-existent notification.
	 */
	public function test_read_nonexistent_notification() {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Stock notification not found' );
		new Notification( 999999 );
	}

	/**
	 * Test adding a meta to a notification.
	 */
	public function test_add_meta_to_notification() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->add_meta_data( 'test_meta', 'test_value' );
		$notification->save();

		$this->assertEquals( 'test_value', $notification->get_meta( 'test_meta' ) );

		// Refresh the notification.
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( 'test_value', $notification->get_meta( 'test_meta' ) );
	}

	/**
	 * Test updating a meta for a notification.
	 */
	public function test_update_meta_for_notification() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->save();

		// Refetch the notification.
		$notification = new Notification( $notification->get_id() );
		$notification->add_meta_data( 'test_meta', 'test_value' );
		$notification->save();

		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( 'test_value', $notification->get_meta( 'test_meta' ) );
		$notification->update_meta_data( 'test_meta', 'updated_value' );
		$notification->save();

		// Refetch the notification.
		$notification = new Notification( $notification->get_id() );

		$this->assertEquals( 'updated_value', $notification->get_meta( 'test_meta' ) );
	}

	/**
	 * Test deleting a meta for a notification.
	 */
	public function test_delete_meta_for_notification() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->add_meta_data( 'test_meta', 'test_value' );
		$notification->save();

		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( 'test_value', $notification->get_meta( 'test_meta' ) );
		$notification->delete_meta_data( 'test_meta' );
		$this->assertFalse( $notification->meta_exists( 'test_meta' ) );
		$notification->save();

		$notification = new Notification( $notification->get_id() );
		$this->assertFalse( $notification->meta_exists( 'test_meta' ) );
		$this->assertEquals( '', $notification->get_meta( 'test_meta' ) );
	}

	/**
	 * Test the modified date is updated when meta is changed.
	 */
	public function test_modified_date_is_updated_when_meta_is_added() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->set_date_created( '2024-01-01 00:00:00' );
		$notification->set_date_modified( '2024-01-01 00:00:00' );
		$notification->save();

		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( '2024-01-01 00:00:00', $notification->get_date_modified()->format( 'Y-m-d H:i:s' ) );
		$this->assertEquals( '', $notification->get_meta( 'test_meta' ) );
		$notification->add_meta_data( 'test_meta', 'test_value' );
		$notification->save();

		$notification = new Notification( $notification->get_id() );
		$this->assertNotEquals( '2024-01-01 00:00:00', $notification->get_date_modified()->format( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Test the modified date is updated when meta is changed.
	 */
	public function test_modified_date_is_updated_when_meta_is_changed() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->set_date_created( '2024-01-01 00:00:00' );
		$notification->set_date_modified( '2024-01-01 00:00:00' );
		$notification->add_meta_data( 'test_meta', 'test_value' );
		$notification->save();

		$this->assertEquals( '2024-01-01 00:00:00', $notification->get_date_modified()->format( 'Y-m-d H:i:s' ) );
		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( '2024-01-01 00:00:00', $notification->get_date_modified()->format( 'Y-m-d H:i:s' ) );
		$this->assertEquals( 'test_value', $notification->get_meta( 'test_meta' ) );
		$notification->update_meta_data( 'test_meta', 'updated_value' );
		$notification->save();

		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( 'updated_value', $notification->get_meta( 'test_meta' ) );
		$this->assertNotEquals( '2024-01-01 00:00:00', $notification->get_date_modified()->format( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Test the modified date is updated when meta is deleted.
	 */
	public function test_modified_date_is_updated_when_meta_is_deleted() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->set_date_created( '2024-01-01 00:00:00' );
		$notification->set_date_modified( '2024-01-01 00:00:00' );
		$notification->add_meta_data( 'test_meta', 'test_value' );
		$notification->save();

		$notification = new Notification( $notification->get_id() );
		$this->assertEquals( '2024-01-01 00:00:00', $notification->get_date_modified()->format( 'Y-m-d H:i:s' ) );
		$notification->delete_meta_data( 'test_meta' );
		$notification->save();

		$notification = new Notification( $notification->get_id() );
		$this->assertNotEquals( '2024-01-01 00:00:00', $notification->get_date_modified()->format( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Test querying notifications.
	 */
	public function test_query_notifications() {

		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->save();

		$notifications = $this->data_store->query(
			array(
				'product_id' => 1,
				'user_id'    => 1,
			)
		);
		$this->assertCount( 1, $notifications );
	}

	/**
	 * Test querying notifications with a status.
	 */
	public function test_query_notifications_with_status() {

		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->set_status( NotificationStatus::ACTIVE );
		$notification->save();

		$notification_2 = new Notification();
		$notification_2->set_product_id( 1 );
		$notification_2->set_user_id( 2 );
		$notification_2->save();

		$notifications = $this->data_store->query(
			array(
				'status' => NotificationStatus::ACTIVE,
			)
		);
		$this->assertCount( 1, $notifications );
	}

	/**
	 * Test querying notifications with a product ID.
	 */
	public function test_query_notifications_with_product_id() {

		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->save();

		$notification_2 = new Notification();
		$notification_2->set_product_id( 2 );
		$notification_2->set_user_id( 2 );
		$notification_2->save();

		$notifications = $this->data_store->query(
			array(
				'product_id' => 1,
			)
		);
		$this->assertCount( 1, $notifications );
	}

	/**
	 * Test querying notifications with a user ID.
	 */
	public function test_query_notifications_with_user_id() {

		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->save();

		$notification_2 = new Notification();
		$notification_2->set_product_id( 1 );
		$notification_2->set_user_id( 2 );
		$notification_2->save();

		$notifications = $this->data_store->query(
			array(
				'user_id' => 1,
			)
		);
		$this->assertCount( 1, $notifications );
	}

	/**
	 * Test querying notifications with a user email.
	 */
	public function test_query_notifications_with_user_email() {

		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->set_user_email( 'test@test.com' );
		$notification->save();

		$notification_2 = new Notification();
		$notification_2->set_product_id( 1 );
		$notification_2->set_user_id( 2 );
		$notification_2->set_user_email( 'test2@test.com' );
		$notification_2->save();

		$notifications = $this->data_store->query(
			array(
				'user_email' => 'test@test.com',
			)
		);
		$this->assertCount( 1, $notifications );
	}

	/**
	 * Test querying notifications with a last attempt limit.
	 */
	public function test_query_notifications_with_last_attempt_limit() {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->save();

		$notifications = $this->data_store->query(
			array(
				'last_attempt_limit' => time(),
			)
		);
		// First notification is returned because it has no last attempt date.
		$this->assertCount( 1, $notifications );

		$notification->set_date_last_attempt( time() );
		$notification->save();

		$notifications = $this->data_store->query(
			array(
				'last_attempt_limit' => time(),
			)
		);
		// Second notification is returned because we need to older than now.
		$this->assertCount( 0, $notifications );

		$notification->set_date_last_attempt( time() - 1000 );
		$notification->save();

		$notifications = $this->data_store->query(
			array(
				'last_attempt_limit' => time(),
			)
		);
		$this->assertCount( 1, $notifications );
	}

	/**
	 * Test querying notifications with a limit and offset.
	 */
	public function test_query_notifications_with_limit_and_offset() {

		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->save();

		$notification_2 = new Notification();
		$notification_2->set_product_id( 1 );
		$notification_2->set_user_id( 2 );
		$notification_2->save();

		$notification_3 = new Notification();
		$notification_3->set_product_id( 1 );
		$notification_3->set_user_id( 3 );
		$notification_3->save();

		$notification_4 = new Notification();
		$notification_4->set_product_id( 1 );
		$notification_4->set_user_id( 4 );
		$notification_4->save();

		$notifications = $this->data_store->query(
			array(
				'limit'  => 2,
				'offset' => 1,
			)
		);

		$this->assertCount( 2, $notifications );
		$this->assertEquals( 2, $notifications[0] );
		$this->assertEquals( 3, $notifications[1] );
	}

	/**
	 * Test querying notifications with a return type of count.
	 */
	public function test_query_notifications_with_return_type_count() {

		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->save();

		$notification_2 = new Notification();
		$notification_2->set_product_id( 1 );
		$notification_2->set_user_id( 2 );
		$notification_2->save();

		$count = $this->data_store->query(
			array(
				'return' => 'count',
			)
		);
		$this->assertEquals( 2, $count );
	}

	/**
	 * Test querying notifications with a return type of ids.
	 */
	public function test_query_notifications_with_return_type_ids() {

		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->save();

		// Check the default return type is ids.
		$notifications = $this->data_store->query(
			array(
				'product_id' => 1,
				'user_id'    => 1,
			)
		);

		$this->assertCount( 1, $notifications );
		$this->assertEquals( 1, $notifications[0] );

		// Check the return type is ids.
		$notifications = $this->data_store->query(
			array(
				'product_id' => 1,
				'user_id'    => 1,
				'return'     => 'ids',
			)
		);
		$this->assertCount( 1, $notifications );
		$this->assertEquals( 1, $notifications[0] );
	}

	/**
	 * Test querying notifications with a return type of objects.
	 */
	public function test_query_notifications_with_return_type_objects() {

		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_id( 1 );
		$notification->set_user_email( 'test@test.com' );
		$notification->save();

		$notifications = $this->data_store->query(
			array(
				'product_id' => 1,
				'user_id'    => 1,
				'user_email' => 'test@test.com',
				'return'     => 'objects',
			)
		);

		$this->assertCount( 1, $notifications );
		$this->assertInstanceOf( Notification::class, $notifications[0] );
		$this->assertEquals( 'test@test.com', $notifications[0]->get_user_email() );
	}
}
