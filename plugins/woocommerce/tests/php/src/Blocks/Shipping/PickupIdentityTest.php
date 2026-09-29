<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Shipping;

use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use WC_Unit_Test_Case;

/**
 * Tests that the rest of the store agrees an order is being collected.
 *
 * The settings screen promises that local pickup "will appear as an option on the block based
 * checkout". What follows from a shopper taking that option is that the order is treated as
 * collected everywhere else: no delivery address is asked for, and the order shows where to
 * collect from instead of where it is being sent.
 */
class PickupIdentityTest extends WC_Unit_Test_Case {

	/**
	 * The checkout page id to put back.
	 *
	 * @var int|string
	 */
	private $original_checkout_page_id;

	/**
	 * Local pickup is only registered while the checkout is the block one.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_checkout_page_id = get_option( 'woocommerce_checkout_page_id' );
		update_option(
			'woocommerce_checkout_page_id',
			$this->factory->post->create(
				array(
					'post_type'    => 'page',
					'post_title'   => 'Checkout',
					'post_content' => '<!-- wp:woocommerce/checkout /-->',
					'post_status'  => 'publish',
				)
			)
		);
	}

	/**
	 * Put the checkout page back.
	 */
	public function tearDown(): void {
		try {
			update_option( 'woocommerce_checkout_page_id', $this->original_checkout_page_id );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Build an order carrying one shipping line of the given method.
	 *
	 * @param string $method_id Shipping method id.
	 * @param array  $meta      Meta to put on the shipping line.
	 * @return \WC_Order
	 */
	private function order_shipped_by( string $method_id, array $meta = array() ): \WC_Order {
		$order = new \WC_Order();
		$item  = new \WC_Order_Item_Shipping();
		$item->set_method_id( $method_id );
		$item->set_method_title( 'Shipping' );

		foreach ( $meta as $key => $value ) {
			$item->add_meta_data( $key, $value );
		}

		$order->add_item( $item );
		$order->save();

		return wc_get_order( $order->get_id() );
	}

	/**
	 * @testdox An order being collected is not asked for a delivery address, while a delivered one is.
	 *
	 * @testWith ["pickup_location", false]
	 *           ["flat_rate", true]
	 *
	 * @param string $method_id Method the order was placed with.
	 * @param bool   $expected  Whether a delivery address is still wanted.
	 */
	public function test_a_collected_order_is_not_asked_for_a_delivery_address( string $method_id, bool $expected ): void {
		$order = $this->order_shipped_by( $method_id );

		$this->assertSame(
			$expected,
			$order->needs_shipping_address(),
			'An order placed with ' . $method_id . '.'
		);
	}

	/**
	 * The list is a filter so that extensions can add their own collection method, and asserting a
	 * closed list would pin the very thing the filter exists to open.
	 *
	 * @testdox A collection method an extension adds is treated as collection too.
	 */
	public function test_a_collection_method_an_extension_adds_is_treated_as_collection(): void {
		$order = $this->order_shipped_by( 'depot_collection' );

		$this->assertTrue( $order->needs_shipping_address(), 'Before the extension speaks up, it is an ordinary delivery.' );

		add_filter(
			'woocommerce_order_hide_shipping_address',
			static function ( $methods ) {
				$methods[] = 'depot_collection';
				return $methods;
			}
		);

		$this->assertFalse( $order->needs_shipping_address(), 'Once the extension says it is collection, no delivery address is wanted.' );
	}

	/**
	 * Two lists answer "is this collection" and they do not agree. The canonical
	 * `woocommerce_local_pickup_methods` list names `legacy_local_pickup` so that an order placed
	 * with the pre-zones method is still taxed at the shop. The registered list is built from the
	 * methods that declare `local-pickup` support, and the legacy class never declares it, so it is
	 * missing there even on a store where it is loaded and enabled.
	 *
	 * This records the divergence rather than blessing it. The two lists are read by different
	 * callers, so the same order can be taxed as collection and still asked for a delivery address.
	 *
	 * @testdox The canonical list names a method the registered list leaves out.
	 */
	public function test_the_canonical_list_keeps_a_method_the_registered_list_does_not(): void {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Documented in WC_Abstract_Order::get_tax_location().
		$canonical  = apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) );
		$registered = LocalPickupUtils::get_local_pickup_method_ids();

		$this->assertContains( 'legacy_local_pickup', $canonical, 'An order placed with the legacy method still has to be recognised.' );
		$this->assertNotContains( 'legacy_local_pickup', $registered, 'The registered list leaves it out, because that class never declares local-pickup support.' );
		$this->assertContains( 'pickup_location', $canonical, 'The block method joins the canonical list through the filter.' );
		$this->assertContains( 'pickup_location', $registered, 'And it is registered, so it is in the other list as well.' );
	}

	/**
	 * @testdox The order shows where to collect from, in place of where it would have been sent.
	 */
	public function test_the_order_shows_where_to_collect_from(): void {
		$order = $this->order_shipped_by(
			'pickup_location',
			array(
				'pickup_location' => 'Downtown',
				'pickup_address'  => '1 Market St, San Francisco, CA 94105',
				'pickup_details'  => 'Ring the bell at the side door.',
			)
		);

		$shown = $order->get_shipping_to_display();

		$this->assertStringContainsString( 'Downtown', $shown, 'The shopper should be told which branch to collect from.' );
		$this->assertStringContainsString( 'Market St', $shown, 'And where that branch is.' );
		$this->assertStringContainsString( 'Ring the bell', $shown, 'And whatever the merchant told them to do on arrival.' );
	}
}
