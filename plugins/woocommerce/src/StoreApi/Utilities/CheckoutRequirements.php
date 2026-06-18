<?php
/**
 * CheckoutRequirements class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

/**
 * Cart-driven view of what a checkout must capture before it can succeed.
 *
 * Checkout consumers that relax the Store API's default field guards — POS,
 * agentic commerce, and payment gateways acting on a shopper's behalf — still
 * need some fields when the cart contents demand them (a downloadable has to be
 * delivered somewhere, a gift card needs a recipient, …). This class answers
 * those per-cart questions in one shared place so each consumer, and each
 * product-type extension, expresses the rule once instead of reimplementing it.
 *
 * Extensions contribute rules through the per-requirement filters (e.g.
 * `woocommerce_store_api_checkout_requires_email`) rather than depending on this
 * class directly.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
final class CheckoutRequirements {

	/**
	 * Order whose line items drive the requirement decisions.
	 *
	 * @var WC_Order
	 */
	private $order;

	/**
	 * Constructor.
	 *
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
	 * True by default when any line item is a downloadable product — the only
	 * way to deliver a download link is to send it somewhere. Virtual-but-not-
	 * downloadable items (in-store services etc.) don't drive this.
	 *
	 * @return bool
	 */
	public function requires_email(): bool {
		$requires_email = $this->has_downloadable_item();

		/**
		 * Filters whether the cart contents require a customer email at checkout.
		 *
		 * Lets product-type extensions (gift cards, subscriptions, …) require an
		 * email for their own reasons, shared across every checkout consumer that
		 * consults this capability.
		 *
		 * @since 10.9.0
		 *
		 * @param bool     $requires_email Whether an email is required for the cart contents.
		 * @param WC_Order $order          Order being inspected.
		 */
		return (bool) apply_filters( 'woocommerce_store_api_checkout_requires_email', $requires_email, $this->order );
	}

	/**
	 * Whether any line item is a downloadable product.
	 *
	 * @return bool
	 */
	private function has_downloadable_item(): bool {
		foreach ( $this->order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$product = $item->get_product();
			if ( $product instanceof WC_Product && $product->is_downloadable() ) {
				return true;
			}
		}
		return false;
	}
}
