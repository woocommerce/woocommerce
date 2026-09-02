<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Triggers;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\StockNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Listens for WooCommerce stock events and feeds stock notifications into
 * the PendingNotificationStore.
 *
 * @since 10.9.0
 */
class StockNotificationTrigger {
	/**
	 * Registers WordPress hooks for stock events.
	 *
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public function register(): void {
		add_action( 'woocommerce_low_stock', array( $this, 'on_low_stock' ) );
		add_action( 'woocommerce_no_stock', array( $this, 'on_no_stock' ) );
		add_action( 'woocommerce_product_on_backorder', array( $this, 'on_backorder' ) );
	}

	/**
	 * Handles the woocommerce_low_stock hook.
	 *
	 * Captures the product's stock quantity at this moment so the dispatcher,
	 * which runs in a separate process and re-fetches the product, doesn't
	 * read a stale value if cache invalidation hasn't fully propagated.
	 *
	 * @param WC_Product $product The product whose stock is low.
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public function on_low_stock( WC_Product $product ): void {
		$stock = $product->get_stock_quantity();
		$this->add_notification(
			$product->get_id(),
			StockNotification::EVENT_LOW_STOCK,
			null !== $stock ? (int) $stock : null
		);
	}

	/**
	 * Handles the woocommerce_no_stock hook.
	 *
	 * @param WC_Product $product The product that is out of stock.
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public function on_no_stock( WC_Product $product ): void {
		$this->add_notification( $product->get_id(), StockNotification::EVENT_OUT_OF_STOCK );
	}

	/**
	 * Handles the woocommerce_product_on_backorder hook.
	 *
	 * @param array $args Backorder event data.
	 * @phpstan-param array{product: WC_Product, order_id: int, quantity: int|float} $args
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public function on_backorder( array $args ): void {
		$product = $args['product'] ?? null;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$this->add_notification( $product->get_id(), StockNotification::EVENT_ON_BACKORDER );
	}

	/**
	 * Creates a stock notification and adds it to the pending store.
	 *
	 * @param int      $product_id                The product ID.
	 * @param string   $event_type                The stock event type.
	 * @param int|null $stock_quantity_at_trigger Stock quantity at the moment WC fired the event, or null when not applicable.
	 * @return void
	 */
	private function add_notification( int $product_id, string $event_type, ?int $stock_quantity_at_trigger = null ): void {
		if ( $product_id <= 0 ) {
			return;
		}

		wc_get_container()->get( PendingNotificationStore::class )->add(
			new StockNotification( $product_id, $event_type, $stock_quantity_at_trigger )
		);
	}
}
