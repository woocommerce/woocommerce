<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentOrderNotes;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsManager;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\FulfillmentsHelper;
use WC_Order;

/**
 * Tests for FulfillmentOrderNotes.
 *
 * @testdox FulfillmentOrderNotes
 * @since 10.7.0
 */
class FulfillmentOrderNotesTest extends \WC_Unit_Test_Case {

	/**
	 * @var FulfillmentsManager
	 */
	private FulfillmentsManager $manager;

	/**
	 * The original value of the fulfillments feature flag before tests.
	 *
	 * @var string|false
	 */
	private static $original_fulfillments_enabled;

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$original_fulfillments_enabled = get_option( 'woocommerce_feature_fulfillments_enabled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		if ( false === self::$original_fulfillments_enabled ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', self::$original_fulfillments_enabled );
		}
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
			if ( str_contains( $note->content, 'created' ) && str_contains( $note->content, 'Unfulfilled' ) ) {
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
	 * Test that the created note includes shipping provider when present.
	 */
	public function test_created_note_includes_shipping_provider(): void {
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
				'_items'             => array(
					array(
						'item_id' => $first_item->get_id(),
						'qty'     => 1,
					),
				),
				'_tracking_number'   => 'TRACK123456',
				'_shipping_provider' => 'fedex',
			)
		);

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$found = false;
		foreach ( $notes as $note ) {
			if ( str_contains( $note->content, 'TRACK123456' ) && str_contains( $note->content, 'fedex' ) ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected the created note to include the shipping provider.' );
	}

	/**
	 * Test that the created note includes tracking URL when present.
	 */
	public function test_created_note_includes_tracking_url(): void {
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
				'_tracking_url'    => 'https://example.com/track/TRACK123456',
			)
		);

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$found = false;
		foreach ( $notes as $note ) {
			if ( str_contains( $note->content, 'TRACK123456' ) && str_contains( $note->content, 'https://example.com/track/TRACK123456' ) ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected the created note to include the tracking URL.' );
	}

	/**
	 * Test that the updated note includes shipping provider and tracking URL.
	 */
	public function test_updated_note_includes_provider_and_url(): void {
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

		// Update with tracking info (non-status change).
		$fulfillment->update_meta_data( '_tracking_number', 'UPS999' );
		$fulfillment->update_meta_data( '_shipping_provider', 'ups' );
		$fulfillment->update_meta_data( '_tracking_url', 'https://ups.com/track/UPS999' );
		$fulfillment->save();

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		$found = false;
		foreach ( $notes as $note ) {
			if (
				str_contains( $note->content, 'updated' )
				&& str_contains( $note->content, 'UPS999' )
				&& str_contains( $note->content, 'ups' )
				&& str_contains( $note->content, 'https://ups.com/track/UPS999' )
			) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected the updated note to include tracking number, provider, and URL.' );
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
			if ( str_contains( $note->content, 'status changed' ) && str_contains( $note->content, 'Unfulfilled' ) && str_contains( $note->content, 'Fulfilled' ) ) {
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
			if ( str_contains( $note->content, 'Order fulfillment status changed' )
				&& str_contains( $note->content, 'No fulfillments' )
				&& str_contains( $note->content, 'Unfulfilled' )
			) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Expected an order fulfillment status changed note with status labels.' );
	}

	/**
	 * Test that disallowed HTML tags are stripped from note messages.
	 */
	public function test_normalize_strips_disallowed_html(): void {
		$malicious_filter = function () {
			return 'Fulfillment created <script>alert("xss")</script> successfully.';
		};
		add_filter( 'woocommerce_fulfillment_created_order_note', $malicious_filter );

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
			if ( str_contains( $note->content, 'Fulfillment created' ) ) {
				$found = true;
				$this->assertStringNotContainsString( '<script>', $note->content );
				$this->assertStringNotContainsString( '</script>', $note->content );
				$this->assertStringContainsString( 'successfully.', $note->content );
				break;
			}
		}
		$this->assertTrue( $found, 'Expected a sanitized fulfillment note.' );

		remove_filter( 'woocommerce_fulfillment_created_order_note', $malicious_filter );
	}

	/**
	 * Test that allowed HTML tags are preserved in note messages.
	 */
	public function test_normalize_preserves_allowed_html(): void {
		$filter_with_html = function () {
			return 'Fulfillment <strong>created</strong> with <a href="https://example.com">link</a>.';
		};
		add_filter( 'woocommerce_fulfillment_created_order_note', $filter_with_html );

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
			if ( str_contains( $note->content, 'Fulfillment' ) && str_contains( $note->content, 'created' ) ) {
				$found = true;
				$this->assertStringContainsString( '<strong>created</strong>', $note->content );
				$this->assertStringContainsString( '<a href="https://example.com">link</a>', $note->content );
				break;
			}
		}
		$this->assertTrue( $found, 'Expected allowed HTML to be preserved in note.' );

		remove_filter( 'woocommerce_fulfillment_created_order_note', $filter_with_html );
	}

	/**
	 * Test that returning a non-string value from a filter cancels the note.
	 */
	public function test_filter_returning_non_string_cancels_note(): void {
		$non_string_filter = function () {
			return 12345;
		};
		add_filter( 'woocommerce_fulfillment_created_order_note', $non_string_filter );

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
			if ( str_contains( $note->content, 'created' ) || str_contains( $note->content, '12345' ) ) {
				$found_created_note = true;
				break;
			}
		}
		$this->assertFalse( $found_created_note, 'Expected no fulfillment note when filter returns a non-string value.' );

		remove_filter( 'woocommerce_fulfillment_created_order_note', $non_string_filter );
	}

	/**
	 * Test that returning an empty string from a filter cancels the note.
	 */
	public function test_filter_returning_empty_string_cancels_note(): void {
		$empty_filter = function () {
			return '';
		};
		add_filter( 'woocommerce_fulfillment_created_order_note', $empty_filter );

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
		$this->assertFalse( $found_created_note, 'Expected no fulfillment note when filter returns empty string.' );

		remove_filter( 'woocommerce_fulfillment_created_order_note', $empty_filter );
	}

	/**
	 * Test that returning whitespace-only string from a filter cancels the note.
	 */
	public function test_filter_returning_whitespace_only_cancels_note(): void {
		$whitespace_filter = function () {
			return '   ';
		};
		add_filter( 'woocommerce_fulfillment_created_order_note', $whitespace_filter );

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
		$this->assertFalse( $found_created_note, 'Expected no fulfillment note when filter returns whitespace-only string.' );

		remove_filter( 'woocommerce_fulfillment_created_order_note', $whitespace_filter );
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
