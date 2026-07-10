<?php
/**
 * ScheduledSalePriceReconciler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal;

use WC_Product;

/**
 * Reconciles a product's displayed/charged price with its sale schedule at read time.
 *
 * Scheduled sales write the active price into the `price` prop at save time (see
 * `WC_Product_Data_Store_CPT::handle_updated_props()`) and at the per-product Action
 * Scheduler boundary events. `WC_Product::is_on_sale()`, by contrast, is a live date
 * check. Between a sale's start/end time elapsing and the next save or AS event, the
 * stored price can disagree with what the dates imply: the simple-product data store
 * reads a stale `_price` straight into the price prop, and own-cache extensions such as
 * Product Bundles read a stale cached base price. (Individual variations self-heal —
 * their data store re-derives the price from `is_on_sale()` on every read — but the
 * variable parent's cached aggregate does not; see the limitations below.) The result is
 * the wrong price shown and charged for up to one AS runner interval.
 *
 * The `woocommerce_product_get_price` filter registered here resolves the active price
 * from the raw sale/regular prices and the sale dates whenever the stored price still
 * equals one of them but disagrees with the dates, so display and cart/checkout use the
 * date-correct price without waiting for the AS event. It is display-layer only:
 * `get_prop()` applies the hook solely in 'view' context, and the filter writes nothing,
 * so admin/edit/CRUD/save flows — including the scheduled-sale AS handlers, which read
 * 'edit' context — and the stored `_price` meta are untouched.
 *
 * Hot-path aware: the common case (a product with no sale price, including variable and
 * grouped parents whose own sale price is empty) exits after a single prop read, before
 * any date or price-comparison work.
 *
 * Limitations: a deliberate custom `_price` that happens to equal the sale or regular
 * price while a sale is scheduled cannot be distinguished from a stale schedule price
 * and will be reconciled; a custom price unrelated to either is left untouched. A
 * variable parent's aggregate display — the "From" price, on-sale badge, and min/max
 * prices served from the `wc_var_prices_{id}` transient — is built from cached values
 * with no sale-window component in the cache key, so it can stay stale until the AS
 * event or a save bumps the product transient version. That gap is display-only: the
 * cart prices the variation itself, which self-heals at read. Prices already changed by
 * an earlier filter (deliberate overrides, but also ambient transforms such as currency
 * conversion) are intentionally not healed — see the guard in `reconcile_price()`.
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

		// Reconcile only when the stored price is exactly the sale or regular price.
		if ( ! $within_window && (float) $stored_price === (float) $sale_price ) {
			return $regular_price;
		}
		if ( $within_window && (float) $stored_price === (float) $regular_price ) {
			return $sale_price;
		}

		return $price;
	}
}
