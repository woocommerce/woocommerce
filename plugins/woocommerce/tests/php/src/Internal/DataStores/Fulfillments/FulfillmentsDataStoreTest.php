<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Fulfillments;

use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;
use WC_Meta_Data;

/**
 * Tests for the WC_Order_Fulfillment_Data_Store_Test class.
 *
 * @package WooCommerce\Tests\Order_Fulfillment
 */
class FulfillmentsDataStoreTest extends \WC_Unit_Test_Case {
	/**
	 * The instance of the order fulfillment data store to use.
	 *
	 * @var FulfillmentsDataStore
	 */
	private FulfillmentsDataStore $data_store;

	/**
	 * Runs before all the tests of the class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Runs after all the tests of the class.
	 */
	public static function tearDownAfterClass(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		parent::tearDownAfterClass();
	}

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		$this->data_store = wc_get_container()->get( FulfillmentsDataStore::class );
	}

	/**
	 * Tear down the test case.
	 */
	public function tearDown(): void {
		global $wpdb;
		// Clean up the fulfillment meta table.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_order_fulfillment_meta" );
		// Clean up the fulfillment table.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_order_fulfillments" );
	}

	/**
	 * Tests the create method of the order fulfillment data store.
	 */
	public function test_create_fulfillment() {
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
				array(
					'item_id' => 2,
					'qty'     => 3,
				),
			)
		);

		$this->data_store->create( $fulfillment );
		$this->assertFulfillmentRecordInDB( $fulfillment );
		$this->assertFulfillmentMetaInDB( $fulfillment );
	}

	/**
	 * Tests the create method of the order fulfillment data store with invalid entity type.
	 */
	public function test_create_fulfillment_throws_error_on_invalid_entity_type() {
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( '' );
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
			)
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid entity type.' );

		$this->data_store->create( $fulfillment );
	}

	/**
	 * Tests the create method of the order fulfillment data store with invalid entity ID.
	 */
	public function test_create_fulfillment_throws_error_on_invalid_entity_id() {
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_entity_id( '' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
			)
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid entity ID.' );

		$this->data_store->create( $fulfillment );
	}

	/**
	 * Tests the create method of the order fulfillment data store with invalid items.
	 */
	public function test_create_fulfillment_throws_error_on_invalid_items() {
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_props( array( 'meta_data' => array( '_items' => null ) ) );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'The fulfillment should contain at least one item.' );

		$this->data_store->create( $fulfillment );
	}

	/**
	 * Tests the create method of the order fulfillment data store with no items.
	 */
	public function test_create_fulfillment_throws_error_on_empty_items() {
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items( array() );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'The fulfillment should contain at least one item.' );

		$this->data_store->create( $fulfillment );
	}

	/**
	 * Tests the create method of the order fulfillment data store with invalid item.
	 */
	public function test_create_fulfillment_throws_error_on_invalid_item() {
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
				array(
					'item_id' => 2,
					// Missing qty.
				),
			)
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid item.' );

		$this->data_store->create( $fulfillment );
	}

	/**
	 * Tests the read method of the order fulfillment data store.
	 */
	public function test_read_fulfillment() {
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
				array(
					'item_id' => 2,
					'qty'     => 3,
				),
			)
		);
		$this->data_store->create( $fulfillment );

		$this->assertNotNull( $fulfillment->get_id() );

		$new_fulfillment = new Fulfillment();
		$new_fulfillment->set_id( $fulfillment->get_id() );

		$this->data_store->read( $new_fulfillment );

		$this->assertFulfillmentRecordInDB( $new_fulfillment );
		$this->assertFulfillmentMetaInDB( $new_fulfillment );
	}

	/**
	 * Tests the update method of the order fulfillment data store.
	 */
	public function test_update_fulfillment() {
		$fulfillment = new Fulfillment();
		$fulfillment->set_id( 1 );
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
				array(
					'item_id' => 2,
					'qty'     => 3,
				),
			)
		);
		$this->data_store->create( $fulfillment );

		$fulfillment->set_entity_id( '456' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 3,
					'qty'     => 4,
				),
				array(
					'item_id' => 4,
					'qty'     => 5,
				),
			)
		);

		$this->data_store->update( $fulfillment );

		$this->assertFulfillmentRecordInDB( $fulfillment );
		$this->assertFulfillmentMetaInDB( $fulfillment );
	}

	/**
	 * Tests the delete method of the order fulfillment data store.
	 */
	public function test_delete_fulfillment() {
		$fulfillment = new Fulfillment();
		$fulfillment->set_id( 1 );
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
				array(
					'item_id' => 2,
					'qty'     => 3,
				),
			)
		);
		$this->data_store->create( $fulfillment );

		$this->assertNotNull( $fulfillment->get_id() );
		$this->assertNull( $fulfillment->get_date_deleted() );

		// Cache the metadata before deletion.
		$metadata = $fulfillment->get_meta_data();

		// Cache the ID before deletion.
		$fulfillment_id = $fulfillment->get_id();

		$this->data_store->delete( $fulfillment );
		// The fulfillment should be reset to it's initial state.
		$this->assertEquals( 0, $fulfillment->get_id() );
		$this->assertEquals( null, $fulfillment->get_entity_type() );
		$this->assertEquals( null, $fulfillment->get_entity_id() );
		$this->assertEquals( array(), $fulfillment->get_items() );
		$this->assertEquals( array(), $fulfillment->get_meta_data() );
		$this->assertEquals( null, $fulfillment->get_date_updated() );
		$this->assertEquals( null, $fulfillment->get_date_deleted() );
		$this->assertFulfillmentRecordInDB( $fulfillment, $fulfillment_id, true );
		$this->assertFulfillmentMetaInDB( $fulfillment, $fulfillment_id, $metadata );
	}

	/**
	 * Tests the read_meta method of the order fulfillment data store.
	 */
	public function test_read_fulfillment_meta() {
		$items = array(
			array(
				'item_id' => 1,
				'qty'     => 2,
			),
			array(
				'item_id' => 2,
				'qty'     => 3,
			),
		);

		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items( $items );
		$fulfillment->save();

		$this->assertNotEquals( 0, $fulfillment->get_id() );

		$result = $this->data_store->read_meta( $fulfillment );

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertIsObject( $result[0] );
		$this->assertEquals( $items, $result[0]->meta_value );
		$this->assertEquals( '_items', $result[0]->meta_key );
		$this->assertEquals( $fulfillment->get_id(), $result[0]->fulfillment_id );
	}

	/**
	 * Tests the delete_meta method of the order fulfillment data store.
	 */
	public function test_delete_fulfillment_meta() {
		$items = array(
			array(
				'item_id' => 1,
				'qty'     => 2,
			),
			array(
				'item_id' => 2,
				'qty'     => 3,
			),
		);

		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items( $items );
		$fulfillment->save();

		$this->assertNotEquals( 0, $fulfillment->get_id() );

		$meta = $fulfillment->get_meta_data();
		$this->assertCount( 1, $meta );
		$this->assertEquals( '_items', $meta[0]->key );
		$this->assertEquals( $items, $meta[0]->value );
		$this->assertNotNull( $meta[0]->id );

		$this->data_store->delete_meta( $fulfillment, $meta[0] ); // phpcs:ignore

		global $wpdb;
		$db_metadata = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_order_fulfillment_meta WHERE fulfillment_id = %d",
				$fulfillment->get_id()
			)
		);
		$this->assertCount( 0, $db_metadata );
	}

	/**
	 * Tests the add_meta method of the order fulfillment data store.
	 */
	public function test_add_fulfillment_meta() {
		$items = array(
			array(
				'item_id' => 1,
				'qty'     => 2,
			),
			array(
				'item_id' => 2,
				'qty'     => 3,
			),
		);

		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items( $items );
		$fulfillment->save();

		$this->assertNotEquals( 0, $fulfillment->get_id() );

		$this->data_store->add_meta(
			$fulfillment,
			new WC_Meta_Data(
				array(
					'key'   => '_new_meta_key',
					'value' => 'new_meta_value',
				)
			)
		);

		global $wpdb;
		$db_metadata = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_order_fulfillment_meta WHERE fulfillment_id = %d",
				$fulfillment->get_id()
			)
		);
		foreach ( $db_metadata as $meta ) {
			if ( '_new_meta_key' === $meta->meta_key ) {
				break;
			}
		}

		if ( ! isset( $meta ) ) {
			self::fail( 'Meta not found in database.' );
			return;
		}

		self::assertEquals( 'new_meta_value', json_decode( $meta->meta_value ) );
	}

	/**
	 * Tests the update_meta method of the order fulfillment data store.
	 */
	public function test_update_fulfillment_meta() {
		$items = array(
			array(
				'item_id' => 1,
				'qty'     => 2,
			),
			array(
				'item_id' => 2,
				'qty'     => 3,
			),
		);

		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items( $items );
		$fulfillment->save();

		$this->assertNotEquals( 0, $fulfillment->get_id() );
		$new_items = array(
			array(
				'item_id' => 3,
				'qty'     => 4,
			),
			array(
				'item_id' => 4,
				'qty'     => 5,
			),
		);
		$fulfillment->set_items( $new_items );
		$new_metadata = $fulfillment->get_meta_data();
		$this->assertCount( 1, $new_metadata );
		$this->assertEquals( '_items', $new_metadata[0]->key );

		$result = $this->data_store->update_meta( $fulfillment, $new_metadata[0] );

		$this->assertEquals( 1, $result );
	}

	/**
	 * Tests reading multiple fulfillments.
	 */
	public function test_read_fulfillments() {
		$this->prepare_db_for_test();
		$fulfillments = $this->data_store->read_fulfillments( 'order-fulfillment', '123' );
		$this->assertCount( 2, $fulfillments );
		$this->assertEquals( '123', $fulfillments[0]->get_entity_id() );
		$this->assertEquals( 'order-fulfillment', $fulfillments[0]->get_entity_type() );
		$this->assertEquals(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),

			),
			$fulfillments[0]->get_items(),
		);
		$this->assertEquals( '123', $fulfillments[1]->get_entity_id() );
		$this->assertEquals( 'order-fulfillment', $fulfillments[1]->get_entity_type() );
		$this->assertEquals(
			array(
				array(
					'item_id' => 4,
					'qty'     => 5,
				),
			),
			$fulfillments[1]->get_items(),
		);
	}

	/**
	 * Tests that deleted fulfillments can be read by ID.
	 */
	public function test_read_deleted_fulfillment_by_id() {
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
			)
		);
		$this->data_store->create( $fulfillment );

		$fulfillment_id = $fulfillment->get_id();
		$this->assertNotNull( $fulfillment_id );

		// Delete the fulfillment.
		$this->data_store->delete( $fulfillment );

		// Create a new fulfillment object and try to read the deleted one.
		$deleted_fulfillment = new Fulfillment();
		$deleted_fulfillment->set_id( $fulfillment_id );

		// Should be able to read deleted fulfillment by ID.
		$this->data_store->read( $deleted_fulfillment );

		$this->assertEquals( $fulfillment_id, $deleted_fulfillment->get_id() );
		$this->assertEquals( 'order-fulfillment', $deleted_fulfillment->get_entity_type() );
		$this->assertEquals( '123', $deleted_fulfillment->get_entity_id() );
		$this->assertNotNull( $deleted_fulfillment->get_date_deleted() );
	}

	/**
	 * Tests that deleted fulfillments cannot be updated.
	 */
	public function test_update_deleted_fulfillment_fails() {
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
			)
		);
		$this->data_store->create( $fulfillment );

		$fulfillment_id = $fulfillment->get_id();
		$this->assertNotNull( $fulfillment_id );

		// Delete the fulfillment.
		$this->data_store->delete( $fulfillment );

		// Create a new fulfillment object and read the deleted one.
		$deleted_fulfillment = new Fulfillment();
		$deleted_fulfillment->set_id( $fulfillment_id );
		$this->data_store->read( $deleted_fulfillment );

		// Try to update the deleted fulfillment - should not affect any rows.
		$deleted_fulfillment->set_entity_id( '456' );
		$deleted_fulfillment->set_status( 'fulfilled' );

		// Update should not throw an error but should not affect any rows.
		$this->data_store->update( $deleted_fulfillment );

		// Verify the database record was not updated.
		global $wpdb;
		$record = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_order_fulfillments WHERE fulfillment_id = %d",
				$fulfillment_id
			)
		);

		$this->assertNotNull( $record );
		$this->assertEquals( '123', $record->entity_id ); // Should still be original value.
		$this->assertEquals( 'unfulfilled', $record->status ); // Should still be original value.
		$this->assertNotNull( $record->date_deleted ); // Should still be deleted.
	}

	/**
	 * Tests that deleting an already deleted fulfillment returns early without side effects.
	 */
	public function test_delete_already_deleted_fulfillment_returns_early() {
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( 'order-fulfillment' );
		$fulfillment->set_entity_id( '123' );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
			)
		);
		$this->data_store->create( $fulfillment );

		$fulfillment_id = $fulfillment->get_id();
		$this->assertNotNull( $fulfillment_id );

		// Delete the fulfillment the first time.
		$this->data_store->delete( $fulfillment );

		// At this point, the fulfillment object should be reset.
		$this->assertEquals( 0, $fulfillment->get_id() );

		// Read the deleted fulfillment back.
		$deleted_fulfillment = new Fulfillment();
		$deleted_fulfillment->set_id( $fulfillment_id );
		$this->data_store->read( $deleted_fulfillment );

		$first_deletion_time = $deleted_fulfillment->get_date_deleted();
		$this->assertNotNull( $first_deletion_time );

		// Track action calls to verify hooks are not called on second delete.
		$before_delete_called = false;
		$after_delete_called  = false;

		add_action(
			'woocommerce_fulfillment_before_delete',
			function () use ( &$before_delete_called ) {
				$before_delete_called = true;
			}
		);

		add_action(
			'woocommerce_fulfillment_after_delete',
			function () use ( &$after_delete_called ) {
				$after_delete_called = true;
			}
		);

		// Try to delete the already deleted fulfillment - should return early.
		$this->data_store->delete( $deleted_fulfillment );

		// Verify hooks were not called.
		$this->assertFalse( $before_delete_called );
		$this->assertFalse( $after_delete_called );

		// Verify the fulfillment object was not reset again.
		$this->assertEquals( $fulfillment_id, $deleted_fulfillment->get_id() );
		$this->assertEquals( 'order-fulfillment', $deleted_fulfillment->get_entity_type() );
		$this->assertEquals( '123', $deleted_fulfillment->get_entity_id() );
		$this->assertEquals( $first_deletion_time, $deleted_fulfillment->get_date_deleted() );

		// Verify the database record was not modified.
		global $wpdb;
		$record = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_order_fulfillments WHERE fulfillment_id = %d",
				$fulfillment_id
			)
		);

		$this->assertNotNull( $record );
		$this->assertEquals( $first_deletion_time, $record->date_deleted );
	}

	/**
	 * Create a test fulfillment and save it to the database.
	 *
	 * @param string $entity_type The entity type.
	 * @param string $entity_id The entity ID.
	 * @param array  $items The items to fulfill.
	 *
	 * @return Fulfillment The created fulfillment object.
	 */
	private function create_test_fulfillment( string $entity_type, string $entity_id, array $items ) {
		$fulfillment = new Fulfillment();
		$fulfillment->set_id( 0 );
		$fulfillment->set_entity_type( $entity_type );
		$fulfillment->set_entity_id( $entity_id );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items( $items );
		$fulfillment->save();
		$fulfillment->save_meta_data();

		$this->assertNotEquals( 0, $fulfillment->get_id() );

		$this->assertFulfillmentRecordInDB( $fulfillment );
		$this->assertFulfillmentMetaInDB( $fulfillment );

		return $fulfillment;
	}

	/**
	 * Creates fulfillment records in the database for testing.
	 */
	private function prepare_db_for_test() {
		$this->create_test_fulfillment(
			'order-fulfillment',
			'123',
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
			)
		);
		$this->create_test_fulfillment(
			'order-fulfillment',
			'456',
			array(
				array(
					'item_id' => 2,
					'qty'     => 3,
				),
			)
		);
		$this->create_test_fulfillment(
			'order-fulfillment',
			'789',
			array(
				array(
					'item_id' => 3,
					'qty'     => 4,
				),
			)
		);
		$this->create_test_fulfillment(
			'order-fulfillment',
			'123',
			array(
				array(
					'item_id' => 4,
					'qty'     => 5,
				),
			)
		);
		$this->create_test_fulfillment(
			'order-fulfillment',
			'456',
			array(
				array(
					'item_id' => 5,
					'qty'     => 6,
				),
			)
		);
	}

	/**
	 * Asserts that a fulfillment record exists in the database.
	 *
	 * @param Fulfillment $fulfillment The fulfillment object.
	 * @param int         $deleted_id  The ID of the deleted record.
	 * @param bool        $is_deleted  Whether the record is deleted.
	 */
	private function assertFulfillmentRecordInDB( Fulfillment $fulfillment, int $deleted_id = 0, bool $is_deleted = false ) {
		global $wpdb;

		$fulfillment_id = $is_deleted ? $deleted_id : $fulfillment->get_id();
		$record         = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_order_fulfillments WHERE fulfillment_id = %d",
				$fulfillment_id
			)
		);

		if ( ! $is_deleted ) {
			$this->assertNotNull( $record );
			$this->assertEquals( $fulfillment->get_entity_type(), $record->entity_type );
			$this->assertEquals( $fulfillment->get_entity_id(), $record->entity_id );
			$this->assertEquals( $fulfillment->get_date_updated(), $record->date_updated );
			$this->assertEquals( $fulfillment->get_date_deleted(), $record->date_deleted );
		} else {
			$this->assertNotNull( $record );
			$this->assertNotEquals( $fulfillment->get_id(), $record->fulfillment_id );
			$this->assertNotEquals( null, $record->date_deleted );
		}
	}

	/**
	 * Asserts that a fulfillment record metadata matches the expected value.
	 *
	 * @param Fulfillment $fulfillment The fulfillment object.
	 * @param int         $deleted_id  The ID of the deleted record, if deleted.
	 * @param array|null  $metadata    The metadata to check.
	 */
	private function assertFulfillmentMetaInDB( Fulfillment $fulfillment, int $deleted_id = 0, ?array $metadata = null ) {
		global $wpdb;

		$fulfillment_id = 0 === $deleted_id ? $fulfillment->get_id() : $deleted_id;

		if ( null === $metadata ) {
			$metadata = $fulfillment->get_meta_data();
		}

		$records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_order_fulfillment_meta WHERE fulfillment_id = %d",
				$fulfillment_id,
			),
			OBJECT
		);

		foreach ( $metadata as $meta ) {
			$meta_key   = $meta->key;
			$meta_value = $meta->value;
			$record     = array_filter(
				$records,
				function ( $record ) use ( $meta_key ) {
					return $record->meta_key === $meta_key;
				}
			);

			$this->assertNotEmpty( $record, "$meta_key is empty" );
			$this->assertEquals( $meta_value, json_decode( reset( $record )->meta_value, true ) );
		}
	}
}
