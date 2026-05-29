<?php
/**
 * PosCheckoutRequirements class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi;

use WC_Order;
use WC_Order_Item_Product;

/**
 * Cart-driven view of what POS needs to capture before checkout can succeed.
 *
 * POS opts out of several Store API checkout guards (email, address) because
 * the typical in-store sale doesn't have customer contact info to capture.
 * Some cart contents reverse that — a downloadable product needs to be
 * delivered somewhere. This class answers those per-cart questions in one
 * place so each policy hook stays a one-liner.
 *
 * Currently only `requires_email()` is implemented. As more drivers are
 * added (gift cards, subscriptions, an explicit "deliver" transaction flag),
 * they plug in here without each policy hook growing its own copy of the
 * line-item walk.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
final class PosCheckoutRequirements {

	/**
	 * Order whose items drive the requirement decisions.
	 *
	 * @var WC_Order
	 */
	private $order;

	/**
	 * @param WC_Order $order Order whose line items are inspected.
	 */
	public function __construct( WC_Order $order ) {
		$this->order = $order;
	}

	/**
	 * Convenience factory.
	 *
	 * @param WC_Order $order Order whose line items are inspected.
	 * @return self
	 */
	public static function for_order( WC_Order $order ): self {
		return new self( $order );
	}

	/**
	 * Whether the cart needs a customer email to be deliverable.
	 *
	 * True when any line item is a downloadable product — the only way to
	 * deliver a download link is to send it somewhere. Virtual-but-not-
	 * downloadable items (in-store services etc.) don't drive this.
	 *
	 * @return bool
	 */
	public function requires_email(): bool {
		foreach ( $this->order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$product = $item->get_product();
			if ( $product && $product->is_downloadable() ) {
				return true;
			}
		}
		return false;
	}
}
