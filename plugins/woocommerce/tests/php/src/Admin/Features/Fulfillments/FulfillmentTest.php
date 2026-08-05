<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\FulfillmentsHelper;
use WC_Order;

/**
 * Tests for Fulfillment object.
 */
class FulfillmentTest extends \WC_Unit_Test_Case {

	/**
	 * Original value of the fulfillments feature flag.
	 *
	 * @var mixed
	 */
	private static $original_fulfillments_flag;

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$original_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		if ( false === self::$original_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', self::$original_fulfillments_flag );
		}
		parent::tearDownAfterClass();
	}

	/**
	 * Test that the Fulfillment object can be created.
	 */
	public function test_fulfillment_object() {
		$fulfillment = new Fulfillment();
		$this->assertInstanceOf( Fulfillment::class, $fulfillment );
	}

	/**
	 * Test that the Fulfillment object can be created with an ID.
	 */
	public function test_fulfillment_object_with_id_fetches_data_and_metadata() {
		$order          = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$db_fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id' => $order->get_id(),
			)
		);
		$fulfillment    = new Fulfillment( $db_fulfillment->get_id() );

		$this->assertInstanceOf( Fulfillment::class, $fulfillment );
		$this->assertEquals( $db_fulfillment->get_id(), $fulfillment->get_id() );
		$this->assertEquals( $db_fulfillment->get_entity_type(), $fulfillment->get_entity_type() );
		$this->assertEquals( $db_fulfillment->get_entity_id(), $fulfillment->get_entity_id() );
		$this->assertEquals( $db_fulfillment->get_date_updated(), $fulfillment->get_date_updated() );
		$this->assertEquals( $db_fulfillment->get_date_deleted(), $fulfillment->get_date_deleted() );
		$this->assertEquals( $db_fulfillment->get_items(), $fulfillment->get_items() );
		$this->assertEquals( $db_fulfillment->get_meta_data(), $fulfillment->get_meta_data() );
	}

	/**
	 * Test that Fulfillment object can be updated.
	 */
	public function test_fulfillment_object_update() {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type' => 'order-fulfillment',
				'entity_id'   => 123,
			)
		);

		$fulfillment->set_entity_type( 'updated-entity-type' );
		$fulfillment->set_entity_id( '456' );
		$fulfillment->save();

		$this->assertEquals( 'updated-entity-type', $fulfillment->get_entity_type() );
		$this->assertEquals( 456, $fulfillment->get_entity_id() );
	}

	/**
	 * Test that Fulfillment object can be soft deleted.
	 */
	public function test_fulfillment_object_soft_delete() {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type' => 'order-fulfillment',
				'entity_id'   => 123,
			)
		);

		$fulfillment_id = $fulfillment->get_id();
		$this->assertNotEquals( 0, $fulfillment_id );

		$fulfillment->delete();

		// Verify the fulfillment can still be read but is marked as deleted.
		$deleted_fulfillment = new Fulfillment( $fulfillment_id );
		$this->assertNotNull( $deleted_fulfillment->get_date_deleted(), 'Fulfillment should be marked as deleted.' );
	}

	/**
	 * Test that Fulfillment object can be created with items.
	 */
	public function test_fulfillment_object_with_items() {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type' => 'order-fulfillment',
				'entity_id'   => 123,
			)
		);

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

		$fulfillment->set_items( $items );
		$fulfillment->save();

		$fresh_fulfillment = new Fulfillment( $fulfillment->get_id() );
		$this->assertInstanceOf( Fulfillment::class, $fresh_fulfillment );
		$this->assertEquals( $fulfillment->get_id(), $fresh_fulfillment->get_id() );

		$this->assertEquals( $items, $fresh_fulfillment->get_items() );
	}

	/**
	 * Test that Fulfillment object can be created with metadata.
	 */
	public function test_fulfillment_object_with_metadata() {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type' => 'order-fulfillment',
				'entity_id'   => 123,
			)
		);

		$fulfillment->add_meta_data( 'test_meta_key', 'test_meta_value', true );
		$fulfillment->save();

		$this->assertEquals( 'test_meta_value', $fulfillment->get_meta( 'test_meta_key' ) );
	}

	/**
	 * Test that metadata can be updated.
	 */
	public function test_fulfillment_object_update_metadata() {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type' => 'order-fulfillment',
				'entity_id'   => 123,
			)
		);

		$fulfillment->add_meta_data( 'test_meta_key', 'test_meta_value', true );
		$fulfillment->save();

		$fulfillment->update_meta_data( 'test_meta_key', 'updated_meta_value' );
		$fulfillment->save();

		$this->assertEquals( 'updated_meta_value', $fulfillment->get_meta( 'test_meta_key' ) );
	}

	/**
	 * Test that metadata can be deleted.
	 */
	public function test_fulfillment_object_delete_metadata() {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type' => 'order-fulfillment',
				'entity_id'   => 123,
			)
		);

		$fulfillment->add_meta_data( 'test_meta_key', 'test_meta_value', true );
		$fulfillment->save();

		$fulfillment->delete_meta_data( 'test_meta_key' );
		$fulfillment->save();

		$this->assertEquals( '', $fulfillment->get_meta( 'test_meta_key' ) );
	}

	/**
	 * Test getting order from the Fulfillment object.
	 */
	public function test_get_order() {
		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type' => WC_Order::class,
				'entity_id'   => $order->get_id(),
			)
		);

		$this->assertInstanceOf( \WC_Order::class, $fulfillment->get_order() );
		$this->assertEquals( $order->get_id(), $fulfillment->get_order()->get_id() );
	}

	/**
	 * Test fulfillment locking functionality.
	 */
	public function test_fulfillment_locking() {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type' => 'order-fulfillment',
				'entity_id'   => 123,
			)
		);

		$this->assertFalse( $fulfillment->is_locked() );

		$fulfillment->set_locked( true, 'Test lock message' );
		$this->assertTrue( $fulfillment->is_locked() );
		$this->assertEquals( 'Test lock message', $fulfillment->get_meta( '_lock_message' ) );

		$fulfillment->set_locked( false );
		$this->assertFalse( $fulfillment->is_locked() );
		$this->assertEquals( '', $fulfillment->get_meta( '_lock_message' ) );
	}

	/**
	 * @testdox get_changes returns empty array when nothing is modified on a persisted fulfillment.
	 */
	public function test_get_changes_returns_empty_when_nothing_changed(): void {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id' => 123,
			)
		);

		$reloaded = new Fulfillment( $fulfillment->get_id() );

		$this->assertEmpty( $reloaded->get_changes(), 'A freshly loaded fulfillment should have no changes' );
	}

	/**
	 * @testdox get_changes detects core data property changes via set_prop.
	 */
	public function test_get_changes_detects_core_data_changes(): void {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id' => 123,
				'status'    => 'unfulfilled',
			)
		);

		$reloaded = new Fulfillment( $fulfillment->get_id() );
		$reloaded->set_status( 'fulfilled' );

		$changes = $reloaded->get_changes();

		$this->assertArrayHasKey( 'status', $changes );
		$this->assertSame( 'fulfilled', $changes['status'] );
		$this->assertArrayHasKey( 'is_fulfilled', $changes );
		$this->assertTrue( $changes['is_fulfilled'] );
	}

	/**
	 * @testdox get_changes detects meta-based field changes under the meta_data key.
	 */
	public function test_get_changes_detects_meta_changes(): void {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id' => 123,
			)
		);

		$reloaded = new Fulfillment( $fulfillment->get_id() );
		$reloaded->set_tracking_number( '1Z999AA10123456784' );
		$reloaded->set_shipment_provider( 'ups' );

		$changes = $reloaded->get_changes();

		$this->assertArrayHasKey( 'meta_data', $changes );
		$this->assertArrayHasKey( '_tracking_number', $changes['meta_data'] );
		$this->assertSame( '1Z999AA10123456784', $changes['meta_data']['_tracking_number'] );
		$this->assertArrayHasKey( '_shipment_provider', $changes['meta_data'] );
		$this->assertSame( 'ups', $changes['meta_data']['_shipment_provider'] );
	}

	/**
	 * @testdox get_changes detects both core data and meta changes together.
	 */
	public function test_get_changes_detects_core_and_meta_changes_together(): void {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id' => 123,
				'status'    => 'unfulfilled',
			)
		);

		$reloaded = new Fulfillment( $fulfillment->get_id() );
		$reloaded->set_status( 'fulfilled' );
		$reloaded->set_tracking_number( 'TRACK123' );

		$changes = $reloaded->get_changes();

		$this->assertArrayHasKey( 'status', $changes, 'Core data change should be at top level' );
		$this->assertArrayHasKey( 'meta_data', $changes, 'Meta changes should be under meta_data key' );
		$this->assertArrayHasKey( '_tracking_number', $changes['meta_data'] );
	}

	/**
	 * @testdox get_changes detects custom metadata changes added via update_meta_data.
	 */
	public function test_get_changes_detects_custom_meta_changes(): void {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id' => 123,
			)
		);

		$reloaded = new Fulfillment( $fulfillment->get_id() );
		$reloaded->update_meta_data( '_custom_field', 'custom_value' );

		$changes = $reloaded->get_changes();

		$this->assertArrayHasKey( 'meta_data', $changes );
		$this->assertArrayHasKey( '_custom_field', $changes['meta_data'] );
		$this->assertSame( 'custom_value', $changes['meta_data']['_custom_field'] );
	}

	/**
	 * @testdox get_changes detects deleted metadata.
	 */
	public function test_get_changes_detects_deleted_meta(): void {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id' => 123,
			),
			array(
				'_custom_key' => 'some_value',
				'_items'      => array(
					array(
						'item_id' => 1,
						'qty'     => 1,
					),
				),
			)
		);

		$reloaded = new Fulfillment( $fulfillment->get_id() );
		$reloaded->delete_meta_data( '_custom_key' );

		$changes = $reloaded->get_changes();

		$this->assertArrayHasKey( 'meta_data', $changes );
		$this->assertArrayHasKey( '_custom_key', $changes['meta_data'] );
		$this->assertNull( $changes['meta_data']['_custom_key'] );
	}

	/**
	 * @testdox apply_changes resets change tracking so get_changes returns empty.
	 */
	public function test_apply_changes_resets_change_tracking(): void {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id' => 123,
				'status'    => 'unfulfilled',
			)
		);

		$reloaded = new Fulfillment( $fulfillment->get_id() );
		$reloaded->set_status( 'fulfilled' );
		$reloaded->set_tracking_number( 'TRACK123' );

		$this->assertNotEmpty( $reloaded->get_changes(), 'Should have changes before apply_changes' );

		$reloaded->save();

		$this->assertEmpty( $reloaded->get_changes(), 'Should have no changes after save' );
	}

	/**
	 * Test that the fulfillment status is validated correctly, and the fallback doesn't change is_fulfilled flag.
	 */
	public function test_fulfillment_status_validation() {
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type' => 'order-fulfillment',
				'entity_id'   => 123,
			)
		);
		$fulfillment->set_status( 'unfulfilled' );
		$this->assertEquals( 'unfulfilled', $fulfillment->get_status() );
		$this->assertEquals( false, $fulfillment->get_is_fulfilled() );

		// Fallback to unfulfilled if an invalid status is set (is_fulfilled is false).
		$fulfillment->set_status( 'invalid_status' );
		$this->assertEquals( 'unfulfilled', $fulfillment->get_status() );
		$this->assertEquals( false, $fulfillment->get_is_fulfilled() );

		$fulfillment->set_status( 'fulfilled' );
		$this->assertEquals( 'fulfilled', $fulfillment->get_status() );
		$this->assertEquals( true, $fulfillment->get_is_fulfilled() );

		// Fallback to fulfilled if an invalid status is set (is_fulfilled is true).
		$fulfillment->set_status( 'invalid_status' );
		$this->assertEquals( 'fulfilled', $fulfillment->get_status() );
		$this->assertEquals( true, $fulfillment->get_is_fulfilled() );
	}
}
