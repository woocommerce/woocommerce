<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsManager;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsSettings;
use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Order;

/**
 * Tests for the Complete orders setting.
 */
class FulfillmentsCompleteOrderTest extends \WC_Unit_Test_Case {

	/**
	 * Original value of the fulfillments feature flag.
	 *
	 * @var mixed
	 */
	private static $original_fulfillments_flag;

	/**
	 * Enable the fulfillments feature.
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
	 * Restore the fulfillments feature flag.
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
	 * Register the hooks that keep the order fulfillment status in step.
	 */
	public function setUp(): void {
		parent::setUp();
		wc_get_container()->get( FulfillmentsManager::class )->register();
		wc_get_container()->get( FulfillmentsSettings::class )->register();
	}

	/**
	 * Turn Complete orders back off.
	 */
	public function tearDown(): void {
		delete_option( FulfillmentsSettings::COMPLETE_ORDER_OPTION );
		delete_option( 'auto_fulfill_virtual' );
		remove_all_filters( 'woocommerce_fulfillments_complete_fulfilled_order' );
		parent::tearDown();
	}

	/**
	 * Create an order in the given status.
	 *
	 * @param string $status The order status.
	 * @return WC_Order
	 */
	private function create_order( string $status ): WC_Order {
		$order = WC_Helper_Order::create_order();
		$order->set_status( $status );
		$order->save();
		return $order;
	}

	/**
	 * Fulfill a quantity of every product line in the order.
	 *
	 * @param WC_Order $order The order.
	 * @param int|null $qty   The quantity to fulfill per line, or null for all of it.
	 */
	private function fulfill( WC_Order $order, ?int $qty = null ): void {
		$items = array();
		foreach ( $order->get_items() as $item ) {
			$items[] = array(
				'item_id' => $item->get_id(),
				'qty'     => $qty ?? $item->get_quantity(),
			);
		}
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( WC_Order::class );
		$fulfillment->set_entity_id( (string) $order->get_id() );
		$fulfillment->set_status( 'fulfilled' );
		$fulfillment->set_items( $items );
		$fulfillment->save();
	}

	/**
	 * @testdox With Complete orders on, a processing order completes once all of its items are fulfilled.
	 */
	public function test_completes_fulfilled_processing_order(): void {
		update_option( FulfillmentsSettings::COMPLETE_ORDER_OPTION, 'yes' );
		$order = $this->create_order( OrderStatus::PROCESSING );

		$this->fulfill( $order );

		$this->assertSame( OrderStatus::COMPLETED, wc_get_order( $order->get_id() )->get_status() );
		$notes = wp_list_pluck( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), 'content' );
		$this->assertContains( 'All items are fulfilled. Order status changed from Processing to Completed.', $notes );
	}

	/**
	 * @testdox Complete orders is off by default.
	 */
	public function test_off_by_default(): void {
		$order = $this->create_order( OrderStatus::PROCESSING );

		$this->fulfill( $order );

		$this->assertSame( OrderStatus::PROCESSING, wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox A partly fulfilled order is not completed.
	 */
	public function test_partly_fulfilled_order_is_not_completed(): void {
		update_option( FulfillmentsSettings::COMPLETE_ORDER_OPTION, 'yes' );
		$order = $this->create_order( OrderStatus::PROCESSING );

		$this->fulfill( $order, 1 );

		$this->assertSame( OrderStatus::PROCESSING, wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Only a processing order is completed.
	 */
	public function test_only_processing_orders_are_completed(): void {
		update_option( FulfillmentsSettings::COMPLETE_ORDER_OPTION, 'yes' );
		$order = $this->create_order( OrderStatus::ON_HOLD );

		$this->fulfill( $order );

		$this->assertSame( OrderStatus::ON_HOLD, wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox The woocommerce_fulfillments_complete_fulfilled_order filter decides per order.
	 */
	public function test_filter_decides_per_order(): void {
		$order = $this->create_order( OrderStatus::PROCESSING );
		add_filter(
			'woocommerce_fulfillments_complete_fulfilled_order',
			function ( $enabled, $filtered_order ) use ( $order ) {
				return $filtered_order->get_id() === $order->get_id();
			},
			10,
			2
		);

		$this->fulfill( $order );

		$this->assertSame( OrderStatus::COMPLETED, wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox An order whose items are auto-fulfilled as it starts processing ends up completed and stays completed.
	 */
	public function test_auto_fulfilled_order_completes(): void {
		update_option( FulfillmentsSettings::COMPLETE_ORDER_OPTION, 'yes' );
		update_option( 'auto_fulfill_virtual', 'yes' );
		$product = WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		$order   = WC_Helper_Order::create_order( 1, $product );

		$order->set_status( OrderStatus::PROCESSING );
		$order->save();
		// A caller that still holds the processing order and saves it again must not undo the completion.
		$order->add_order_note( 'Later save' );
		$order->save();

		$this->assertSame( OrderStatus::COMPLETED, wc_get_order( $order->get_id() )->get_status() );
	}
}
