<?php
/**
 * ScheduledSalePriceReconciler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal;

use WC_Product;

/**
 * Corrects a product's price at read time when a scheduled sale has started or ended
 * but the stored `_price` has not been updated yet.
 *
 * The Action Scheduler events that update `_price` at the sale boundaries can run
 * late (up to one runner interval); until they do, the stored price disagrees with
 * the sale dates and the wrong price is shown and charged. The
 * `woocommerce_product_get_price` filter registered here returns the date-correct
 * price for that window. It applies in 'view' context only and writes nothing, so
 * 'edit'-context reads (saves, CRUD, the product edit form, the boundary events)
 * and the stored `_price` are untouched.
 * Cases intentionally not reconciled are documented in `reconcile_price()`.
 *
 * @internal Just for internal use.
 *
 * @since 11.1.0
 */
class ScheduledSalePriceReconciler implements RegisterHooksInterface {

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		add_filter( 'woocommerce_product_get_price', array( $this, 'reconcile_price' ), 99, 2 );
	}

	/**
	 * Reconcile the active price with the sale schedule when the stored price is stale.
	 *
	 * Intentionally not covered: a variable parent's aggregate display (the "From"
	 * price, on-sale badge, and min/max prices). Those values are served from the
	 * `wc_var_prices_{id}` transient without passing through this filter, and the
	 * cache key has no sale-window component, so they can stay stale until the
	 * boundary event or a save refreshes the cache. Covering them would mean changing
	 * cache keys or invalidation, not a read-time filter — scoped out along with the
	 * product lookup table. The gap is display-only: the cart prices the variation
	 * itself, which re-derives its price from the sale dates on every read. Other
	 * skipped cases are noted on the guards below.
	 *
	 * @internal
	 *
	 * @param string     $price   The product's active price.
	 * @param WC_Product $product The product object.
	 * @return string The active price, reconciled with the sale schedule when applicable.
	 */
	public function reconcile_price( $price, $product ) {
		if ( ! $product instanceof WC_Product || '' === (string) $price ) {
			return $price;
		}

		// Fast path: products with no sale price (the vast majority, plus variable/grouped
		// parents whose own sale price is empty) can never drift out of sync.
		$sale_price = $product->get_sale_price( 'edit' );
		if ( '' === (string) $sale_price ) {
			return $price;
		}

		// Only scheduled sales can drift: an unscheduled sale's price is fixed at save time.
		$date_from = $product->get_date_on_sale_from( 'edit' );
		$date_to   = $product->get_date_on_sale_to( 'edit' );
		if ( ! $date_from && ! $date_to ) {
			return $price;
		}

		// Require a real discount. The strict `>` (i.e. bail on `<=`) mirrors is_on_sale() so
		// the resolved price never contradicts the on-sale flag.
		$regular_price = $product->get_regular_price( 'edit' );
		if ( '' === (string) $regular_price || (float) $regular_price <= (float) $sale_price ) {
			return $price;
		}

		// Respect third-party prices: if another callback already changed the price away from
		// the stored value, leave it alone. This is deliberate even for ambient transforms
		// such as currency conversion: by this priority the price is already transformed, and
		// returning a raw sale/regular price would bypass the transform, which cannot be
		// re-applied from here. Those stores keep their pre-existing behavior during the gap.
		// The 'edit' context returns the raw stored price without re-applying this filter, so
		// there is no recursion.
		$stored_price = $product->get_price( 'edit' );
		if ( (string) $price !== (string) $stored_price ) {
			return $price;
		}

		// Pure date-window check, intentionally inline rather than calling is_on_sale() to
		// avoid recursing through plugins that read get_price() from a
		// woocommerce_product_is_on_sale callback.
		$now           = time();
		$within_window = ! ( ( $date_from && $date_from->getTimestamp() > $now ) || ( $date_to && $date_to->getTimestamp() < $now ) );

		// Reconcile only when the stored price is exactly the sale or regular price. A
		// deliberate custom `_price` that happens to equal one of them is indistinguishable
		// from a stale value and gets reconciled; any other custom value falls through.
		if ( ! $within_window && (float) $stored_price === (float) $sale_price ) {
			return $regular_price;
		}
		if ( $within_window && (float) $stored_price === (float) $regular_price ) {
			return $sale_price;
		}

		return $price;
	}
}
