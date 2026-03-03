<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentOrderNotes;
use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsManager;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Tests\Internal\Fulfillments\Helpers\FulfillmentsHelper;
use WC_Order;

/**
 * Tests for FulfillmentOrderNotes.
 *
 * @since 10.7.0
 */
class FulfillmentOrderNotesTest extends \WC_Unit_Test_Case {

	/**
	 * @var FulfillmentsManager
	 */
	private FulfillmentsManager $manager;

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
	 * Set up each test case.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->manager = wc_get_container()->get( FulfillmentsManager::class );
		$this->manager->register();
	}

	/**
	 * Test that order notes hooks are registered.
	 */
	public function test_hooks_registered(): void {
		$this->assertNotFalse( has_action( 'woocommerce_fulfillment_after_create' ) );
		$this->assertNotFalse( has_filter( 'woocommerce_fulfillment_before_update' ) );
		$this->assertNotFalse( has_action( 'woocommerce_fulfillment_after_update' ) );
		$this->assertNotFalse( has_action( 'woocommerce_fulfillment_after_delete' ) );
	}

	/**
	 * Test that an order note is added when a fulfillment is created.
	 */
	public function test_note_added_on_fulfillment_creation(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$order   = OrderHelper::create_order( get_current_user_id(), $product );

		$order_items = $order->get_items();
		$first_item  = reset( $order_items );

		FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $order->get_id(),
				'status'       => 'unfulfilled',
				'is_fulfilled' => false,
			),
			array(
				'_items' => array(
					array(
						'item_id' => $first_item->get_id(),
						'qty'     => 1,
					),
				),
			)
		);

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$this->assertNotEmpty( $notes );

		$found_created_note = false;
		foreach ( $notes as $note ) {
			if ( str_contains( $note->content, 'created' ) && str_contains( $note->content, 'unfulfilled' ) ) {
				$found_created_note = true;
				$note_group         = get_comment_meta( $note->id, 'note_group', true );
				$this->assertSame( OrderNoteGroup::FULFILLMENT, $note_group );
				break;
			}
		}
		$this->assertTrue( $found_created_note, 'Expected a fulfillment created order note.' );
	}

	/**
	 * Test that the created note includes item names and quantities.
	 */
	public function test_created_note_includes_items(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$order   = OrderHelper::create_order( get_current_user_id(), $product );

		$order_items = $order->get_items();
		$first_item  = reset( $order_items );

		FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $order->get_id(),
				'status'       => 'unfulfilled',
				'is_fulfilled' => false,
			),
			array(
				'_items' => array(
					array(
						'item_id' => $first_item->get_id(),
						'qty'     => 2,
					),
				),
			)
		);

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$found = false;
		foreach ( $notes as $note ) {
			if ( str_contains( $note->content, 'created' ) && str_contains( $note->content, 'x2' ) ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected the created note to include item list with quantities.' );
	}

	/**
	 * Test that the created note includes tracking number when present.
	 */
	public function test_created_note_includes_tracking_number(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$order   = OrderHelper::create_order( get_current_user_id(), $product );

		$order_items = $order->get_items();
		$first_item  = reset( $order_items );

		FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $order->get_id(),
				'status'       => 'unfulfilled',
				'is_fulfilled' => false,
			),
			array(
				'_items'           => array(
					array(
						'item_id' => $first_item->get_id(),
						'qty'     => 1,
					),
				),
				'_tracking_number' => 'TRACK123456',
			)
		);

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$found = false;
		foreach ( $notes as $note ) {
			if ( str_contains( $note->content, 'TRACK123456' ) ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected the created note to include the tracking number.' );
	}

	/**
	 * Test that an order note is added when a fulfillment status changes.
	 */
	public function test_note_added_on_fulfillment_status_change(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$order   = OrderHelper::create_order( get_current_user_id(), $product );

		$order_items = $order->get_items();
		$first_item  = reset( $order_items );

		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $order->get_id(),
				'status'       => 'unfulfilled',
				'is_fulfilled' => false,
			),
			array(
				'_items' => array(
					array(
						'item_id' => $first_item->get_id(),
						'qty'     => 1,
					),
				),
			)
		);

		// Change status to fulfilled.
		$fulfillment->set_status( 'fulfilled' );
		$fulfillment->save();

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$found = false;
		foreach ( $notes as $note ) {
			if ( str_contains( $note->content, 'status changed' ) && str_contains( $note->content, 'unfulfilled' ) && str_contains( $note->content, 'fulfilled' ) ) {
				$found      = true;
				$note_group = get_comment_meta( $note->id, 'note_group', true );
				$this->assertSame( OrderNoteGroup::FULFILLMENT, $note_group );
				break;
			}
		}
		$this->assertTrue( $found, 'Expected a fulfillment status changed order note.' );
	}

	/**
	 * Test that an order note is added when a fulfillment is updated (non-status change).
	 */
	public function test_note_added_on_fulfillment_update(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$order   = OrderHelper::create_order( get_current_user_id(), $product );

		$order_items = $order->get_items();
		$first_item  = reset( $order_items );

		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $order->get_id(),
				'status'       => 'unfulfilled',
				'is_fulfilled' => false,
			),
			array(
				'_items' => array(
					array(
						'item_id' => $first_item->get_id(),
						'qty'     => 1,
					),
				),
			)
		);

		// Update tracking number (non-status change).
		$fulfillment->update_meta_data( '_tracking_number', 'NEWTRACK789' );
		$fulfillment->save();

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$found = false;
		foreach ( $notes as $note ) {
			if ( str_contains( $note->content, 'updated' ) && str_contains( $note->content, 'NEWTRACK789' ) ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected a fulfillment updated order note with tracking number.' );
	}

	/**
	 * Test that an order note is added when a fulfillment is deleted.
	 */
	public function test_note_added_on_fulfillment_deletion(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$order   = OrderHelper::create_order( get_current_user_id(), $product );

		$order_items = $order->get_items();
		$first_item  = reset( $order_items );

		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $order->get_id(),
				'status'       => 'unfulfilled',
				'is_fulfilled' => false,
			),
			array(
				'_items' => array(
					array(
						'item_id' => $first_item->get_id(),
						'qty'     => 1,
					),
				),
			)
		);

		$fulfillment_id = $fulfillment->get_id();
		$fulfillment->delete();

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$found = false;
		foreach ( $notes as $note ) {
			if ( str_contains( $note->content, 'deleted' ) && str_contains( $note->content, (string) $fulfillment_id ) ) {
				$found      = true;
				$note_group = get_comment_meta( $note->id, 'note_group', true );
				$this->assertSame( OrderNoteGroup::FULFILLMENT, $note_group );
				break;
			}
		}
		$this->assertTrue( $found, 'Expected a fulfillment deleted order note.' );
	}

	/**
	 * Test that an order note is added when the order fulfillment status changes.
	 */
	public function test_note_added_on_order_fulfillment_status_change(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$order   = OrderHelper::create_order( get_current_user_id(), $product );

		$order_items = $order->get_items();
		$first_item  = reset( $order_items );

		// Creating a fulfillment triggers order status change from no_fulfillments to unfulfilled.
		FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $order->get_id(),
				'status'       => 'unfulfilled',
				'is_fulfilled' => false,
			),
			array(
				'_items' => array(
					array(
						'item_id' => $first_item->get_id(),
						'qty'     => 1,
					),
				),
			)
		);

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$found = false;
		foreach ( $notes as $note ) {
			if ( str_contains( $note->content, 'Order fulfillment status changed' ) ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected an order fulfillment status changed note.' );
	}

	/**
	 * Test that returning null from a filter cancels the note.
	 */
	public function test_filter_returning_null_cancels_note(): void {
		add_filter( 'woocommerce_fulfillment_created_order_note', '__return_null' );

		$product = \WC_Helper_Product::create_simple_product();
		$order   = OrderHelper::create_order( get_current_user_id(), $product );

		$order_items = $order->get_items();
		$first_item  = reset( $order_items );

		FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $order->get_id(),
				'status'       => 'unfulfilled',
				'is_fulfilled' => false,
			),
			array(
				'_items' => array(
					array(
						'item_id' => $first_item->get_id(),
						'qty'     => 1,
					),
				),
			)
		);

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$found_created_note = false;
		foreach ( $notes as $note ) {
			if ( str_contains( $note->content, 'created' ) ) {
				$found_created_note = true;
				break;
			}
		}
		$this->assertFalse( $found_created_note, 'Expected no fulfillment created note when filter returns null.' );

		remove_filter( 'woocommerce_fulfillment_created_order_note', '__return_null' );
	}

	/**
	 * Test that a filter can modify the note message.
	 */
	public function test_filter_modifies_note_message(): void {
		$custom_filter = function () {
			return 'Custom fulfillment note message';
		};
		add_filter( 'woocommerce_fulfillment_created_order_note', $custom_filter );

		$product = \WC_Helper_Product::create_simple_product();
		$order   = OrderHelper::create_order( get_current_user_id(), $product );

		$order_items = $order->get_items();
		$first_item  = reset( $order_items );

		FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $order->get_id(),
				'status'       => 'unfulfilled',
				'is_fulfilled' => false,
			),
			array(
				'_items' => array(
					array(
						'item_id' => $first_item->get_id(),
						'qty'     => 1,
					),
				),
			)
		);

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$found = false;
		foreach ( $notes as $note ) {
			if ( 'Custom fulfillment note message' === $note->content ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected the note to contain the custom filter message.' );

		remove_filter( 'woocommerce_fulfillment_created_order_note', $custom_filter );
	}

	/**
	 * Test the FULFILLMENT constant exists in OrderNoteGroup.
	 */
	public function test_fulfillment_order_note_group_constant(): void {
		$this->assertSame( 'fulfillment', OrderNoteGroup::FULFILLMENT );
		$this->assertSame( 'Fulfillment', OrderNoteGroup::get_default_group_title( OrderNoteGroup::FULFILLMENT ) );
	}
}
