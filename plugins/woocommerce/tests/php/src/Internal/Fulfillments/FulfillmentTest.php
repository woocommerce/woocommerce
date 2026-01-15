<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Tests\Internal\Fulfillments\Helpers\FulfillmentsHelper;
use WC_Order;

/**
 * Tests for Fulfillment object.
 */
class FulfillmentTest extends \WC_Unit_Test_Case {

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
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
