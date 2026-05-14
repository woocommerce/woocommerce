/**
 * Thin wrapper around `@wordpress/api-fetch` for the order REST endpoints.
 *
 * v1 demo targets `/wc/v3/orders` (stable, always registered). The plan
 * references v4 as the eventual target, but v3 covers everything we need
 * for the read/write paths and the v4 surface may not be enabled on every
 * site.
 *
 * Note: v3 nests notes under the order (`/wc/v3/orders/{id}/notes`), unlike
 * v4 which has a top-level `/wc/v4/order-notes?order_id=N`. We map both
 * shapes onto the same `OrderNote` TypeScript type — v3 returns a similar
 * structure but lacks `note_group`, so the History timeline tab filters
 * fall back to heuristics on `added_by_user`.
 */

import apiFetch from '@wordpress/api-fetch';
import type {
	Order,
	OrderNote,
	OrderStatusOption,
	CustomerSummary,
	ProductSearchResult,
	ProductVariationResult,
} from './types';

const ORDERS_BASE = '/wc/v3/orders';
const CUSTOMERS_BASE = '/wc/v3/customers';
const ORDER_STATUSES_BASE = '/wc/v3/orders/statuses';
const PRODUCTS_BASE = '/wc/v3/products';

/**
 * Extract a human-readable message from any error thrown by `apiFetch`.
 * WP REST errors come back as plain objects `{ code, message, data }` — not
 * Error instances — which is why `String(err)` produces "[object Object]".
 */
export function describeError( err: unknown ): string {
	if ( err instanceof Error ) {
		return err.message;
	}
	if ( err && typeof err === 'object' ) {
		const obj = err as { message?: string; code?: string };
		if ( typeof obj.message === 'string' ) {
			return obj.code ? `${ obj.message } (${ obj.code })` : obj.message;
		}
	}
	return String( err );
}

export async function fetchOrder( orderId: number ): Promise< Order > {
	return apiFetch( { path: `${ ORDERS_BASE }/${ orderId }` } );
}

/**
 * v3 PUT supports `set_paid: true` as a request-only flag that triggers
 * `WC_Order::payment_complete()` server-side (sets date_paid, fires
 * payment_complete hooks). It's not a field on the Order entity, so we type
 * the delta as a loose union to allow it.
 */
export type OrderUpdateDelta = Partial< Order > & { set_paid?: boolean };

export async function updateOrder(
	orderId: number,
	delta: OrderUpdateDelta
): Promise< Order > {
	return apiFetch( {
		path: `${ ORDERS_BASE }/${ orderId }`,
		method: 'PUT',
		data: delta,
	} );
}

export async function fetchOrderNotes(
	orderId: number
): Promise< OrderNote[] > {
	// `context=edit` is REQUIRED to get `added_by_user` in the response —
	// the schema marks that field as edit-context only. Without it, every
	// note comes back missing the flag, our filter sees `undefined`
	// (falsy), and everything ends up in the History timeline.
	return apiFetch( {
		path: `${ ORDERS_BASE }/${ orderId }/notes?per_page=100&context=edit`,
	} );
}

export async function createOrderNote(
	orderId: number,
	note: string,
	customerVisible: boolean,
	addedByUser = true
): Promise< OrderNote > {
	return apiFetch( {
		path: `${ ORDERS_BASE }/${ orderId }/notes`,
		method: 'POST',
		data: {
			note,
			customer_note: customerVisible,
			// Defaults to true (the user-authored case). Callers that want
			// to create a system-style note (e.g. an "email sent" tracking
			// entry that should land in History) pass false explicitly.
			added_by_user: addedByUser,
		},
	} );
}

export async function deleteOrderNote(
	orderId: number,
	noteId: number
): Promise< void > {
	await apiFetch( {
		path: `${ ORDERS_BASE }/${ orderId }/notes/${ noteId }?force=true`,
		method: 'DELETE',
	} );
}

export async function fetchCustomer(
	customerId: number
): Promise< CustomerSummary > {
	return apiFetch( { path: `${ CUSTOMERS_BASE }/${ customerId }` } );
}

/**
 * Search customers by name / email for the customer picker in the
 * customer edit drawer. Returns up to 10 matches at a time.
 */
export interface CustomerSearchResult {
	id: number;
	first_name?: string;
	last_name?: string;
	email?: string;
	username?: string;
	billing?: {
		first_name?: string;
		last_name?: string;
		company?: string;
		address_1?: string;
		address_2?: string;
		city?: string;
		state?: string;
		postcode?: string;
		country?: string;
		email?: string;
		phone?: string;
	};
	shipping?: {
		first_name?: string;
		last_name?: string;
		company?: string;
		address_1?: string;
		address_2?: string;
		city?: string;
		state?: string;
		postcode?: string;
		country?: string;
		phone?: string;
	};
}

export async function searchCustomers(
	query: string
): Promise< CustomerSearchResult[] > {
	const params = new URLSearchParams( {
		search: query,
		per_page: '10',
		role: 'all',
	} );
	return apiFetch( { path: `${ CUSTOMERS_BASE }?${ params.toString() }` } );
}

/**
 * Search products by name / SKU for the order-items "+ Add product" picker.
 * Filters down to publishable products at any stock level (admins should be
 * able to add out-of-stock items too — the result rows just annotate that).
 */
export async function searchProducts(
	query: string
): Promise< ProductSearchResult[] > {
	const params = new URLSearchParams( {
		search: query,
		per_page: '20',
		status: 'publish',
		orderby: 'title',
		order: 'asc',
	} );
	return apiFetch( { path: `${ PRODUCTS_BASE }?${ params.toString() }` } );
}

/**
 * Fetch the variations for a given variable parent. Used by the picker
 * modal's drill-in view; `per_page=100` should cover all but pathological
 * variable products without needing pagination plumbing here.
 */
export async function fetchProductVariations(
	productId: number
): Promise< ProductVariationResult[] > {
	return apiFetch( {
		path: `${ PRODUCTS_BASE }/${ productId }/variations?per_page=100`,
	} );
}

/**
 * Fetch a customer's recent orders, excluding the current one being viewed.
 * Returns both the order list and the total count (from `X-WP-Total`) so
 * the Customer card can display "OTHER ORDERS (n)" with the true count even
 * when only the first `limit` orders are listed.
 */
export async function fetchCustomerOrders(
	customerId: number,
	excludeOrderId: number,
	limit = 3
): Promise< { orders: Order[]; total: number } > {
	const params = new URLSearchParams( {
		customer: String( customerId ),
		exclude: String( excludeOrderId ),
		per_page: String( limit ),
		orderby: 'date',
		order: 'desc',
	} );
	const response = ( await apiFetch( {
		path: `${ ORDERS_BASE }?${ params.toString() }`,
		parse: false,
	} ) ) as unknown as Response;
	const orders = ( await response.json() ) as Order[];
	const total = parseInt(
		response.headers.get( 'X-WP-Total' ) || String( orders.length ),
		10
	);
	return { orders, total };
}

export async function fetchOrderStatuses(): Promise< OrderStatusOption[] > {
	// The endpoint returns an array of `{slug, name}` objects already in our
	// target shape — no transformation needed.
	const raw: OrderStatusOption[] = await apiFetch( {
		path: ORDER_STATUSES_BASE,
	} );
	return raw.map( ( s ) => ( {
		slug: s.slug.replace( /^wc-/, '' ),
		name: s.name,
	} ) );
}
