<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores\Fulfillments;

use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;
use WC_Helper_Order;
use WC_Order;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Class OrderFulfillmentsRestControllerHookTest
 *
 * @package Automattic\WooCommerce\Tests\Internal\Fulfillments
 */
class FulfillmentsDataStoreHookTest extends WC_Unit_Test_Case {
	/**
	 * @var FulfillmentsDataStore
	 */
	private FulfillmentsDataStore $store;

	/**
	 * @var WC_Order
	 */
	private WC_Order $order;

	/**
	 * Runs before all the tests of the class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		wc_get_container()->get( \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsController::class )->register();
	}

	/**
	 * Runs after all the tests of the class.
	 */
	public static function tearDownAfterClass(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		parent::tearDownAfterClass();
	}

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->store = new FulfillmentsDataStore();
		$this->order = WC_Helper_Order::create_order( get_current_user_id() );
	}

	/**
	 * Tear down test case.
	 *
	 * This method is called after each test method is executed.
	 * It is used to clean up any data created during the tests.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Remove any fulfillments added during the tests.
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_order_fulfillments;" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_order_fulfillment_meta;" );
	}

	/**
	 * Test that the fulfillment before create hook is called when creating a fulfillment.
	 */
	public function test_fulfillment_before_create_hook_is_called() {
		$hook_called = false;
		add_filter(
			'woocommerce_fulfillment_before_create',
			function ( $fulfillment ) use ( &$hook_called ) {
				$hook_called = true;
				return $fulfillment;
			}
		);

		$this->store->create( $this->get_test_fulfillment( $this->order->get_id() ) );
		$this->assertTrue( $hook_called, 'The fulfillment before create hook was not called.' );
		$fulfillments = $this->store->read_fulfillments( WC_Order::class, (string) $this->order->get_id() );
		$this->assertCount( 1, $fulfillments, 'Fulfillment was not created.' );
	}

	/**
	 * Test that the fulfillment before create hook can prevent creating a fulfillment.
	 */
	public function test_fulfillment_before_create_hook_can_interrupt() {
		$hook_called = false;

		add_filter(
			'woocommerce_fulfillment_before_create',
			function () use ( &$hook_called ) {
				$hook_called = true;
				throw new \Exception( 'Fulfillment creation prevented by hook.' );
			}
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Fulfillment creation prevented by hook.' );
		$this->store->create( $this->get_test_fulfillment( $this->order->get_id() ) );

		$this->assertTrue( $hook_called, 'The fulfillment before create hook was not called.' );

		// Check that no fulfillment was created.
		$fulfillments = $this->store->read_fulfillments( WC_Order::class, (string) $this->order->get_id() );
		$this->assertCount( 0, $fulfillments, 'Fulfillment was created.' );
	}

	/**
	 * Test that the fulfillment after create hook is called when creating a fulfillment.
	 */
	public function test_fulfillment_after_create_hook_is_called() {
		$hook_called = false;

		add_action(
			'woocommerce_fulfillment_after_create',
			function ( $fulfillment ) use ( &$hook_called, &$received_fulfillment ) {
				$received_fulfillment = $fulfillment;
				$hook_called          = true;
				return $fulfillment;
			},
			10,
			2
		);

		$this->store->create( $this->get_test_fulfillment( $this->order->get_id() ) );
		$this->assertTrue( $hook_called, 'The fulfillment after create hook was not called.' );

		// Compare the received fulfillment with the expected data.
		$sent_data = $this->get_test_fulfillment_data( $this->order->get_id() );
		$this->assertEquals( $received_fulfillment->get_entity_type(), $sent_data['entity_type'], );
		$this->assertEquals( $received_fulfillment->get_entity_id(), $sent_data['entity_id'], );
		$this->assertEquals( $received_fulfillment->get_status(), $sent_data['status'], );
		$this->assertEquals( $received_fulfillment->get_is_fulfilled(), $sent_data['is_fulfilled'], );
		$this->assertEquals( $received_fulfillment->get_meta( 'test_meta_key' ), $sent_data['meta_data'][0]['value'] );
		$this->assertEquals( $received_fulfillment->get_meta( 'test_meta_key_2' ), $sent_data['meta_data'][1]['value'] );
		$this->assertEquals( $received_fulfillment->get_items(), $sent_data['meta_data'][2]['value'] );
		$this->assertNotNull( $received_fulfillment->get_id(), 'Fulfillment ID should not be null.' );
		$this->assertGreaterThan( 0, $received_fulfillment->get_id(), 'Fulfillment ID should be greater than 0.' );

		$fulfillments = $this->store->read_fulfillments( WC_Order::class, (string) $this->order->get_id() );
		$this->assertCount( 1, $fulfillments, 'Fulfillment was not created.' );
	}

	/**
	 * Test that the fulfillment before update hook is called when updating a fulfillment.
	 */
	public function test_fulfillment_before_update_hook_is_called() {
		// Create a fulfillment for the order.
		$fulfillment = $this->get_test_fulfillment( $this->order->get_id() );
		$fulfillment->save();
		$this->assertNotNull( $fulfillment->get_id(), 'Fulfillment ID should not be null.' );

		$hook_called = false;
		add_filter(
			'woocommerce_fulfillment_before_update',
			function ( $fulfillment ) use ( &$hook_called ) {
				$hook_called = true;
				return $fulfillment;
			}
		);

		// Add a modification to the saved fulfillment, so we can see the difference.
		$fulfillment->add_meta_data( 'test_meta_update', 'test_meta_value' );

		$this->store->update( $fulfillment );
		$this->assertTrue( $hook_called, 'The fulfillment before update hook was not called.' );

		$db_fulfillment = new Fulfillment( $fulfillment->get_id() );
		$this->assertTrue( $db_fulfillment->meta_exists( 'test_meta_update' ), 'Fulfillment was not updated.' );
	}

	/**
	 * Test that the fulfillment before update hook can prevent updating a fulfillment.
	 */
	public function test_fulfillment_before_update_hook_can_interrupt() {
		// Create a fulfillment for the order.
		$fulfillment = $this->get_test_fulfillment( $this->order->get_id() );
		$fulfillment->save();
		$this->assertNotNull( $fulfillment->get_id(), 'Fulfillment ID should not be null.' );

		$hook_called = false;
		add_filter(
			'woocommerce_fulfillment_before_update',
			function () use ( &$hook_called ) {
				$hook_called = true;
				throw new \Exception( 'Fulfillment update prevented by hook.' );
			}
		);

		// Add a modification to the saved fulfillment, so we can see the difference.
		$fulfillment->add_meta_data( 'test_meta_update', 'test_meta_value' );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Fulfillment update prevented by hook.' );
		$this->store->update( $fulfillment );

		$this->assertTrue( $hook_called, 'The fulfillment before update hook was not called.' );
		$db_fulfillment = new Fulfillment( $fulfillment->get_id() );
		$this->assertFalse( $db_fulfillment->meta_exists( 'test_meta_update' ), 'Fulfillment was updated.' );
	}

	/**
	 * Test that the fulfillment after update hook is called after updating a fulfillment.
	 */
	public function test_fulfillment_after_update_hook_is_called() {
		// Create a fulfillment for the order.
		$fulfillment = $this->get_test_fulfillment( $this->order->get_id() );
		$fulfillment->save();
		$this->assertNotNull( $fulfillment->get_id(), 'Fulfillment ID should not be null.' );

		$hook_called = false;
		add_action(
			'woocommerce_fulfillment_after_update',
			function ( $fulfillment ) use ( &$hook_called, &$received_fulfillment ) {
				$received_fulfillment = $fulfillment;
				$hook_called          = true;
				return $fulfillment;
			},
			10,
			2
		);

		// Add a modification to the saved fulfillment, so we can see the difference.
		$fulfillment->add_meta_data( 'test_meta_update', 'test_meta_value' );

		$this->store->update( $fulfillment );
		$this->assertTrue( $hook_called, 'The fulfillment after update hook was not called.' );

		// Compare the received fulfillment with the expected data.
		$this->assertEquals( $received_fulfillment->get_id(), $fulfillment->get_id() );
		$this->assertEquals( $received_fulfillment->get_entity_type(), $fulfillment->get_entity_type() );
		$this->assertEquals( $received_fulfillment->get_entity_id(), $fulfillment->get_entity_id() );
		$this->assertEquals( $received_fulfillment->get_status(), $fulfillment->get_status() );
		$this->assertEquals( $received_fulfillment->get_is_fulfilled(), $fulfillment->get_is_fulfilled() );
		$this->assertEquals( $received_fulfillment->get_meta( 'test_meta_key' ), $fulfillment->get_meta( 'test_meta_key' ) );
		$this->assertEquals( $received_fulfillment->get_meta( 'test_meta_key_2' ), $fulfillment->get_meta( 'test_meta_key_2' ) );
		$this->assertEquals( $received_fulfillment->get_meta( 'test_meta_update' ), $fulfillment->get_meta( 'test_meta_update' ) );
		$this->assertEquals( $received_fulfillment->get_items(), $fulfillment->get_items() );
	}

	/**
	 * Data provider for fulfillment types.
	 *
	 * @return array
	 */
	public function fulfillment_types() {
		return array( array( 'create' ), array( 'update' ) );
	}

	/**
	 * Test that the fulfillment before fulfill hook is not called when is_fulfilled is false.
	 *
	 * @param string $type The type of fulfillment operation ('create' or 'update').
	 *
	 * @dataProvider fulfillment_types
	 */
	public function test_fulfillment_before_fulfill_hook_is_not_called( $type ) {
		// Create a fulfillment for the order.
		$fulfillment = $this->get_test_fulfillment( $this->order->get_id() );
		if ( 'update' === $type ) {
			$fulfillment->save();
			$this->assertNotNull( $fulfillment->get_id(), 'Fulfillment ID should not be null.' );
		}

		$hook_called = false;
		add_filter(
			'woocommerce_fulfillment_before_fulfill',
			function ( $fulfillment ) use ( &$hook_called ) {
				$hook_called = true;

				return $fulfillment;
			}
		);

		if ( 'create' === $type ) {
			// Create the fulfillment.
			$this->store->create( $fulfillment );
		} else {
			// Update the fulfillment.
			$this->store->update( $fulfillment );
		}

		$this->assertFalse( $hook_called, 'The fulfillment before fulfill hook was called.' );

		$db_fulfillment = new Fulfillment( $fulfillment->get_id() );
		$this->assertFalse( $db_fulfillment->get_is_fulfilled() );
		remove_all_filters( 'woocommerce_fulfillment_before_fulfill' );
	}

	/**
	 * Test that the fulfillment before fulfill hook is called when updating a fulfillment.
	 *
	 * @param string $type The type of fulfillment operation ('create' or 'update').
	 *
	 * @dataProvider fulfillment_types
	 */
	public function test_fulfillment_before_fulfill_hook_is_called( $type ) {
		// Create a fulfillment for the order.
		$fulfillment = $this->get_test_fulfillment( $this->order->get_id() );
		if ( 'update' === $type ) {
			$fulfillment->save();
			$this->assertNotNull( $fulfillment->get_id(), 'Fulfillment ID should not be null.' );
		}

		$hook_called = false;
		add_filter(
			'woocommerce_fulfillment_before_fulfill',
			function ( $fulfillment ) use ( &$hook_called ) {
				$hook_called = true;

				return $fulfillment;
			}
		);

		// Fulfill the fulfillment.
		$fulfillment->set_status( 'fulfilled' );

		if ( 'create' === $type ) {
			// Create the fulfillment.
			$this->store->create( $fulfillment );
		} else {
			// Update the fulfillment.
			$this->store->update( $fulfillment );
		}

		$this->assertTrue( $hook_called, 'The fulfillment before fulfill hook was not called.' );

		$db_fulfillment = new Fulfillment( $fulfillment->get_id() );
		$this->assertTrue( $db_fulfillment->get_is_fulfilled() );
		remove_all_filters( 'woocommerce_fulfillment_before_fulfill' );
	}

	/**
	 * Test that the fulfillment before fulfill hook can prevent updating a fulfillment.
	 *
	 * @param string $type The type of fulfillment operation ('create' or 'update').
	 *
	 * @dataProvider fulfillment_types
	 */
	public function test_fulfillment_before_fulfill_hook_can_interrupt( $type ) {
		// Create a fulfillment for the order.
		$fulfillment = $this->get_test_fulfillment( $this->order->get_id() );
		if ( 'update' === $type ) {
			$fulfillment->save();
			$this->assertNotNull( $fulfillment->get_id(), 'Fulfillment ID should not be null.' );
		}

		$hook_called = false;
		add_filter(
			'woocommerce_fulfillment_before_fulfill',
			function () use ( &$hook_called ) {
				$hook_called = true;
				throw new \Exception( 'Fulfillment fulfill prevented by hook.' );
			}
		);

		// Fulfill the fulfillment.
		$fulfillment->set_status( 'fulfilled' );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Fulfillment fulfill prevented by hook.' );

		if ( 'create' === $type ) {
			// Create the fulfillment.
			$this->store->create( $fulfillment );
		} else {
			// Update the fulfillment.
			$this->store->update( $fulfillment );
		}

		$this->assertTrue( $hook_called, 'The fulfillment before fulfill hook was not called.' );
		if ( 'update' === $type ) {
			// If it's an update, we should still have the unfulfilled fulfillment in the database.
			$db_fulfillment = new Fulfillment( $fulfillment->get_id() );
			$this->assertFalse( $db_fulfillment->get_is_fulfilled(), 'Fulfillment was fulfilled.' );
		} else {
			// If it's a create, we should not have the fulfillment in the database.
			$this->expectException( \Exception::class );
			$this->expectExceptionMessage( 'Fulfillment not found.' );
			new Fulfillment( $fulfillment->get_id() );
		}
		remove_all_filters( 'woocommerce_fulfillment_before_fulfill' );
	}

	/**
	 * Test that the fulfillment after update hook is called after updating a fulfillment.
	 *
	 * @param string $type The type of fulfillment operation ('create' or 'update').
	 *
	 * @dataProvider fulfillment_types
	 */
	public function test_fulfillment_after_fulfill_hook_is_called( $type ) {
		// Create a fulfillment for the order.
		$fulfillment = $this->get_test_fulfillment( $this->order->get_id() );
		if ( 'update' === $type ) {
			$fulfillment->save();
			$this->assertNotNull( $fulfillment->get_id(), 'Fulfillment ID should not be null.' );
		}

		$hook_called = false;
		add_action(
			'woocommerce_fulfillment_after_fulfill',
			function ( $fulfillment ) use ( &$hook_called, &$received_fulfillment ) {
				$received_fulfillment = $fulfillment;
				$hook_called          = true;
				return $fulfillment;
			},
			10,
			2
		);

		// Fulfill the fulfillment.
		$fulfillment->set_status( 'fulfilled' );

		if ( 'create' === $type ) {
			// Create the fulfillment.
			$this->store->create( $fulfillment );
		} else {
			// Update the fulfillment.
			$this->store->update( $fulfillment );
		}
		$this->assertTrue( $hook_called, 'The fulfillment after fulfill hook was not called.' );

		// Compare the received fulfillment with the expected data.
		$this->assertEquals( $received_fulfillment->get_id(), $fulfillment->get_id() );
		$this->assertEquals( $received_fulfillment->get_entity_type(), $fulfillment->get_entity_type() );
		$this->assertEquals( $received_fulfillment->get_entity_id(), $fulfillment->get_entity_id() );
		$this->assertEquals( $received_fulfillment->get_status(), $fulfillment->get_status() );
		$this->assertEquals( $received_fulfillment->get_is_fulfilled(), $fulfillment->get_is_fulfilled() );
		$this->assertEquals( $received_fulfillment->get_meta( 'test_meta_key' ), $fulfillment->get_meta( 'test_meta_key' ) );
		$this->assertEquals( $received_fulfillment->get_meta( 'test_meta_key_2' ), $fulfillment->get_meta( 'test_meta_key_2' ) );
		$this->assertEquals( $received_fulfillment->get_meta( 'test_meta_update' ), $fulfillment->get_meta( 'test_meta_update' ) );
		$this->assertEquals( $received_fulfillment->get_items(), $fulfillment->get_items() );
		remove_all_actions( 'woocommerce_fulfillment_after_fulfill' );
	}

	/**
	 * Test that the fulfillment before delete hook is called when deleting a fulfillment.
	 */
	public function test_fulfillment_before_delete_hook_is_called() {
		// Create a fulfillment for the order.
		$fulfillment = $this->get_test_fulfillment( $this->order->get_id() );
		$fulfillment->save();
		$fulfillment_id = $fulfillment->get_id();
		$this->assertNotNull( $fulfillment_id, 'Fulfillment ID should not be null.' );

		$hook_called = false;
		add_filter(
			'woocommerce_fulfillment_before_delete',
			function ( $fulfillment ) use ( &$hook_called ) {
				$hook_called = true;
				return $fulfillment;
			}
		);

		$this->store->delete( $fulfillment );
		$this->assertTrue( $hook_called, 'The fulfillment before delete hook was not called.' );

		// Verify the fulfillment can still be read but is marked as deleted.
		$deleted_fulfillment = new Fulfillment( $fulfillment_id );
		$this->assertNotNull( $deleted_fulfillment->get_date_deleted(), 'Fulfillment should be marked as deleted.' );
	}

	/**
	 * Test that the fulfillment before delete hook can prevent deleting a fulfillment.
	 */
	public function test_fulfillment_before_delete_hook_can_interrupt() {
		// Create a fulfillment for the order.
		$fulfillment = $this->get_test_fulfillment( $this->order->get_id() );
		$fulfillment->save();
		$fulfillment_id = $fulfillment->get_id();
		$this->assertNotNull( $fulfillment_id, 'Fulfillment ID should not be null.' );

		$hook_called = false;
		add_filter(
			'woocommerce_fulfillment_before_delete',
			function () use ( &$hook_called ) {
				$hook_called = true;
				throw new \Exception( 'Fulfillment delete prevented by hook.' );
			}
		);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Fulfillment delete prevented by hook.' );
		$this->store->delete( $fulfillment );

		$this->assertTrue( $hook_called, 'The fulfillment before delete hook was not called.' );
		$db_fulfillment = new Fulfillment( $fulfillment_id );
		$this->assertNotNull( $db_fulfillment, 'Fulfillment was deleted.' );
	}

	/**
	 * Test that the fulfillment after delete hook is called after deleting a fulfillment.
	 */
	public function test_fulfillment_after_delete_hook_is_called() {
		// Create a fulfillment for the order.
		$fulfillment = $this->get_test_fulfillment( $this->order->get_id() );
		$fulfillment->save();
		$fulfillment_id = $fulfillment->get_id();
		$this->assertNotNull( $fulfillment_id, 'Fulfillment ID should not be null.' );
		$fulfillment_clone = clone $fulfillment;

		$hook_called = false;
		add_action(
			'woocommerce_fulfillment_after_delete',
			function ( $fulfillment ) use ( &$hook_called, &$received_fulfillment ) {
				$received_fulfillment = $fulfillment;
				$hook_called          = true;
				return $fulfillment;
			},
			10,
			2
		);

		$this->store->delete( $fulfillment );
		$this->assertTrue( $hook_called, 'The fulfillment after delete hook was not called.' );

		// Compare the received fulfillment with the expected data.
		$this->assertEquals( $fulfillment, new Fulfillment(), 'Fulfillment should be reset after deletion.' );
		$this->assertNotNull( $received_fulfillment, 'Received fulfillment should not be null.' );
		$this->assertInstanceOf( Fulfillment::class, $received_fulfillment, 'Received fulfillment should be an instance of Fulfillment.' );
		$this->assertEquals( $received_fulfillment->get_id(), $fulfillment_clone->get_id() );
		$this->assertEquals( $received_fulfillment->get_entity_type(), $fulfillment_clone->get_entity_type() );
		$this->assertEquals( $received_fulfillment->get_entity_id(), $fulfillment_clone->get_entity_id() );
		$this->assertEquals( $received_fulfillment->get_status(), $fulfillment_clone->get_status() );
		$this->assertEquals( $received_fulfillment->get_is_fulfilled(), $fulfillment_clone->get_is_fulfilled() );
		$this->assertEquals( $received_fulfillment->get_meta( 'test_meta_key' ), $fulfillment_clone->get_meta( 'test_meta_key' ) );
		$this->assertEquals( $received_fulfillment->get_meta( 'test_meta_key_2' ), $fulfillment_clone->get_meta( 'test_meta_key_2' ) );
		$this->assertEquals( $received_fulfillment->get_items(), $fulfillment_clone->get_items() );
		$this->assertNotNull( $received_fulfillment->get_date_deleted(), 'Fulfillment deletion date should not be null.' );
	}

	/**
	 * Create a test fulfillment to use in the tests.
	 *
	 * @param int $order_id The ID of the order to create a fulfillment for.
	 *
	 * @return Fulfillment
	 */
	private function get_test_fulfillment( $order_id ): Fulfillment {
		// Create a fulfillment for the order.
		$test_data   = $this->get_test_fulfillment_data( $order_id );
		$fulfillment = new Fulfillment();
		$fulfillment->set_props( $test_data );
		$fulfillment->set_meta_data( $test_data['meta_data'] );
		return $fulfillment;
	}

	/**
	 * Get test fulfillment data.
	 *
	 * @param int $order_id The ID of the order to create a fulfillment for.
	 * @return array
	 */
	private function get_test_fulfillment_data( $order_id ): array {
		return array(
			'entity_type'  => WC_Order::class,
			'entity_id'    => $order_id,
			'status'       => 'unfulfilled',
			'is_fulfilled' => false,
			'meta_data'    => array(
				array(
					'id'    => 0,
					'key'   => 'test_meta_key',
					'value' => 'test_meta_value',
				),
				array(
					'id'    => 0,
					'key'   => 'test_meta_key_2',
					'value' => 'test_meta_value_2',
				),
				array(
					'id'    => 0,
					'key'   => '_items',
					'value' => array(
						array(
							'item_id' => 1,
							'qty'     => 2,
						),
						array(
							'item_id' => 2,
							'qty'     => 3,
						),
					),
				),
			),
		);
	}
}
