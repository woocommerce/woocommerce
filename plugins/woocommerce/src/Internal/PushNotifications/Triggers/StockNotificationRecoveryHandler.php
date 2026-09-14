<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Triggers;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\StockNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Clears the per-event sent-meta on a product when its stock recovers
 * above the threshold that would have re-triggered the notification.
 *
 * The dedup meta written by {@see NotificationProcessor} is namespaced
 * per event subtype (e.g. `_wc_push_notification_sent_low_stock`). Without
 * recovery, a product only ever emits one push per subtype for its entire
 * lifetime — every subsequent low → restocked → low cycle is silently
 * suppressed. This handler clears each meta the moment stock crosses back
 * above the relevant threshold, so the next downward crossing emits a
 * fresh push.
 *
 * @since 10.9.0
 */
class StockNotificationRecoveryHandler {
	/**
	 * Registers the recovery hook.
	 *
	 * Hooks both `woocommerce_product_set_stock` and
	 * `woocommerce_variation_set_stock` because variations dispatch a
	 * separate action (see `wc-stock-functions.php`). The trigger side
	 * fires for variations too, so without the variation hook a variable
	 * product's sent-meta would never clear.
	 *
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public function register(): void {
		add_action( 'woocommerce_product_set_stock', array( $this, 'on_stock_change' ) );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'on_stock_change' ) );
	}

	/**
	 * Evaluates each event subtype's recovery threshold and clears
	 * the corresponding sent-meta when the new stock level has recovered.
	 *
	 * @param WC_Product $product The product whose stock changed.
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public function on_stock_change( WC_Product $product ): void {
		$product_id = $product->get_id();

		if ( $product_id <= 0 ) {
			return;
		}

		$stock_quantity = $product->get_stock_quantity();

		if ( null === $stock_quantity ) {
			return;
		}

		$stock = (int) $stock_quantity;

		if ( $stock > (int) wc_get_low_stock_amount( $product ) ) {
			$this->clear_meta( $product_id, StockNotification::EVENT_LOW_STOCK );
		}

		if ( $stock > (int) get_option( 'woocommerce_notify_no_stock_amount', 0 ) ) {
			$this->clear_meta( $product_id, StockNotification::EVENT_OUT_OF_STOCK );
		}

		if ( $stock >= 0 ) {
			$this->clear_meta( $product_id, StockNotification::EVENT_ON_BACKORDER );
		}
	}

	/**
	 * Clears the namespaced sent-meta for a given product and event subtype.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $event_type One of the StockNotification::EVENT_* constants.
	 * @return void
	 */
	private function clear_meta( int $product_id, string $event_type ): void {
		( new StockNotification( $product_id, $event_type ) )->delete_meta( NotificationProcessor::SENT_META_KEY );
	}
}
