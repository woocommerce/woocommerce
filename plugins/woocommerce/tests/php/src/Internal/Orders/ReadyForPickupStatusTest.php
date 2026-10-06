<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Orders\ReadyForPickupStatus;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Order;
use WC_Order_Item_Shipping;
use WC_Shipping_Zone;
use WC_Unit_Test_Case;

/**
 * Tests for the ReadyForPickupStatus class.
 */
class ReadyForPickupStatusTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ReadyForPickupStatus
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( ReadyForPickupStatus::class );
	}

	/**
	 * @testdox Should not register the status on a store that does not offer local pickup.
	 */
	public function test_status_is_not_registered_without_local_pickup(): void {
		$this->assertFalse( $this->sut->is_enabled(), 'The status should be off when local pickup is not offered' );
		$this->assertArrayNotHasKey( ReadyForPickupStatus::DB_STATUS, wc_get_order_statuses() );
		$this->assertSame( array( OrderStatus::PROCESSING, OrderStatus::COMPLETED ), wc_get_is_paid_statuses(), 'Paid statuses should be unchanged' );
	}

	/**
	 * @testdox Should register the status right after Processing when pickup locations are enabled.
	 */
	public function test_status_is_registered_after_processing_when_pickup_locations_are_enabled(): void {
		$this->enable_pickup_locations();

		$statuses = array_keys( wc_get_order_statuses() );
		$position = array_search( 'wc-processing', $statuses, true );

		$this->assertTrue( wc_is_order_status( ReadyForPickupStatus::DB_STATUS ) );
		$this->assertSame( ReadyForPickupStatus::DB_STATUS, $statuses[ $position + 1 ], 'The status should follow Processing' );
	}

	/**
	 * @testdox Should follow a shipping zone's Local pickup method as it is added, disabled and enabled.
	 */
	public function test_status_follows_zone_local_pickup_method(): void {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Local' );
		$zone->save();

		$this->assertFalse( $this->sut->is_enabled(), 'A zone with no Local pickup method should not turn the status on' );

		$instance_id = $zone->add_shipping_method( 'local_pickup' );
		$this->assertTrue( $this->sut->is_enabled(), 'An enabled Local pickup method should turn the status on' );

		$this->toggle_zone_method( $instance_id, $zone->get_id(), 0 );
		$this->assertFalse( $this->sut->is_enabled(), 'A disabled Local pickup method should turn the status off' );

		$this->toggle_zone_method( $instance_id, $zone->get_id(), 1 );
		$this->assertTrue( $this->sut->is_enabled(), 'Enabling the method again should turn the status back on' );

		$zone->delete_shipping_method( $instance_id );
		$this->assertFalse( $this->sut->is_enabled(), 'Deleting the method should turn the status off' );
	}

	/**
	 * @testdox Should not turn the status on for a zone method that is not Local pickup.
	 */
	public function test_status_ignores_other_zone_methods(): void {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Everywhere' );
		$zone->save();
		$zone->add_shipping_method( 'flat_rate' );

		$this->assertFalse( $this->sut->is_enabled() );
	}

	/**
	 * @testdox Should let an extension turn the status on through the filter.
	 */
	public function test_filter_can_enable_the_status(): void {
		add_filter( 'woocommerce_ready_for_pickup_order_status_enabled', '__return_true' );

		$this->assertTrue( wc_is_order_status( ReadyForPickupStatus::DB_STATUS ) );
	}

	/**
	 * @testdox Should keep an order in the status after local pickup is turned off.
	 */
	public function test_order_keeps_status_after_local_pickup_is_turned_off(): void {
		$this->enable_pickup_locations();
		$order = WC_Helper_Order::create_order();
		$order->update_status( OrderStatus::READY_FOR_PICKUP );

		$this->disable_pickup_locations();

		$this->assertTrue( wc_is_order_status( ReadyForPickupStatus::DB_STATUS ), 'The status should stay registered once an order has used it' );

		$reloaded = wc_get_order( $order->get_id() );
		$reloaded->set_customer_note( 'Saved after local pickup was turned off.' );
		$reloaded->save();

		$this->assertSame( OrderStatus::READY_FOR_PICKUP, wc_get_order( $order->get_id() )->get_status(), 'Saving the order should not reset its status' );
	}

	/**
	 * @testdox Should add the status to the paid, report and purchase note status lists when it is available.
	 *
	 * @testWith ["woocommerce_order_is_paid_statuses"]
	 *           ["woocommerce_reports_order_statuses"]
	 *           ["woocommerce_purchase_note_order_statuses"]
	 *
	 * @param string $filter The filter that builds the list.
	 */
	public function test_status_joins_lists_that_include_processing( string $filter ): void {
		$this->enable_pickup_locations();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$statuses = apply_filters( $filter, array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ) );

		$this->assertSame( array( OrderStatus::COMPLETED, OrderStatus::PROCESSING, OrderStatus::READY_FOR_PICKUP ), $statuses );
	}

	/**
	 * @testdox Should leave a status list alone when it does not include Processing.
	 */
	public function test_status_does_not_join_lists_without_processing(): void {
		$this->enable_pickup_locations();

		$this->assertSame( array( OrderStatus::REFUNDED ), $this->sut->add_status_where_processing_is_listed( array( OrderStatus::REFUNDED ) ) );
		$this->assertFalse( $this->sut->add_status_where_processing_is_listed( false ), 'A value that is not a list should be returned as it is' );
	}

	/**
	 * @testdox Should treat an order moved straight to the status like a processing order.
	 */
	public function test_order_moved_to_status_is_paid_and_reduces_stock(): void {
		$this->enable_pickup_locations();
		update_option( 'woocommerce_manage_stock', 'yes' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 10 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->update_status( OrderStatus::READY_FOR_PICKUP );

		$this->assertTrue( $order->is_paid(), 'The order should count as paid' );
		$this->assertSame( 6, wc_get_product( $product->get_id() )->get_stock_quantity(), 'Stock should be reduced by the four units ordered' );
		$this->assertSame( 4, (int) wc_get_product( $product->get_id() )->get_total_sales(), 'Sales should be recorded' );
	}

	/**
	 * @testdox Should grant downloads for an order in the status only when access is granted after payment.
	 *
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $grant_access_after_payment The "Grant access to downloadable products after payment" setting.
	 * @param bool   $expected                   Whether downloads should be permitted.
	 */
	public function test_download_permission_matches_processing( string $grant_access_after_payment, bool $expected ): void {
		$this->enable_pickup_locations();
		update_option( 'woocommerce_downloads_grant_access_after_payment', $grant_access_after_payment );

		$order = WC_Helper_Order::create_order();
		$order->update_status( OrderStatus::READY_FOR_PICKUP );

		$this->assertSame( $expected, $order->is_download_permitted() );
		$this->assertSame( $expected, (bool) $order->get_data_store()->get_download_permissions_granted( $order ) );
	}

	/**
	 * @testdox Should return the name, address and details of a pickup location.
	 */
	public function test_get_pickup_locations_returns_pickup_location_details(): void {
		$order = $this->create_order_with_shipping_method(
			'pickup_location',
			'Pickup',
			array(
				'pickup_location' => 'Downtown store',
				'pickup_address'  => '123 Main Street, Austin, TX 78701',
				'pickup_details'  => 'Ask at the front counter.',
			)
		);

		$this->assertSame(
			array(
				array(
					'name'    => 'Downtown store',
					'address' => '123 Main Street, Austin, TX 78701',
					'details' => 'Ask at the front counter.',
				),
			),
			$this->sut->get_pickup_locations( $order )
		);
		$this->assertTrue( $this->sut->order_has_local_pickup( $order ) );
	}

	/**
	 * @testdox Should return the method title for a zone Local pickup method, which stores no location.
	 */
	public function test_get_pickup_locations_uses_method_title_for_zone_local_pickup(): void {
		$order = $this->create_order_with_shipping_method( 'local_pickup', 'Collect in store' );

		$this->assertSame(
			array(
				array(
					'name'    => 'Collect in store',
					'address' => '',
					'details' => '',
				),
			),
			$this->sut->get_pickup_locations( $order )
		);
	}

	/**
	 * @testdox Should return no pickup locations for an order that is shipped.
	 */
	public function test_get_pickup_locations_is_empty_for_shipped_order(): void {
		$order = $this->create_order_with_shipping_method( 'flat_rate', 'Flat rate' );

		$this->assertSame( array(), $this->sut->get_pickup_locations( $order ) );
		$this->assertFalse( $this->sut->order_has_local_pickup( $order ) );
	}

	/**
	 * Enable pickup locations, the local pickup used by the Checkout block.
	 */
	private function enable_pickup_locations(): void {
		update_option( 'woocommerce_pickup_location_settings', array( 'enabled' => 'yes' ) );
	}

	/**
	 * Disable pickup locations.
	 */
	private function disable_pickup_locations(): void {
		update_option( 'woocommerce_pickup_location_settings', array( 'enabled' => 'no' ) );
	}

	/**
	 * Enable or disable a zone method the way the REST API does, which leaves the shipping cache version alone.
	 *
	 * @param int $instance_id The method instance ID.
	 * @param int $zone_id     The zone ID.
	 * @param int $is_enabled  1 to enable, 0 to disable.
	 */
	private function toggle_zone_method( int $instance_id, int $zone_id, int $is_enabled ): void {
		global $wpdb;

		$wpdb->update( "{$wpdb->prefix}woocommerce_shipping_zone_methods", array( 'is_enabled' => $is_enabled ), array( 'instance_id' => $instance_id ) );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_shipping_zone_method_status_toggled', $instance_id, 'local_pickup', $zone_id, $is_enabled );
	}

	/**
	 * Create an order with a single shipping method.
	 *
	 * @param string $method_id The shipping method ID.
	 * @param string $title     The shipping method title.
	 * @param array  $meta      Meta to store on the shipping line.
	 * @return WC_Order
	 */
	private function create_order_with_shipping_method( string $method_id, string $title, array $meta = array() ): WC_Order {
		$order = WC_Helper_Order::create_order();

		foreach ( array_keys( $order->get_items( 'shipping' ) ) as $item_id ) {
			$order->remove_item( $item_id );
		}

		$shipping_item = new WC_Order_Item_Shipping();
		$shipping_item->set_props(
			array(
				'method_title' => $title,
				'method_id'    => $method_id,
				'total'        => 0,
			)
		);
		foreach ( $meta as $key => $value ) {
			$shipping_item->add_meta_data( $key, $value, true );
		}

		$order->add_item( $shipping_item );
		$order->save();

		return $order;
	}
}
