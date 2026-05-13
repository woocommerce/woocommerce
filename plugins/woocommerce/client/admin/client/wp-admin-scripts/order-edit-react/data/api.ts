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
import type { Order, OrderNote, OrderStatusOption, CustomerSummary } from './types';

const ORDERS_BASE = '/wc/v3/orders';
const CUSTOMERS_BASE = '/wc/v3/customers';
const ORDER_STATUSES_BASE = '/wc/v3/orders/statuses';

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

export async function fetchOrderNotes( orderId: number ): Promise< OrderNote[] > {
	return apiFetch( {
		path: `${ ORDERS_BASE }/${ orderId }/notes?per_page=100`,
	} );
}

export async function createOrderNote(
	orderId: number,
	note: string,
	customerVisible: boolean
): Promise< OrderNote > {
	return apiFetch( {
		path: `${ ORDERS_BASE }/${ orderId }/notes`,
		method: 'POST',
		data: {
			note,
			customer_note: customerVisible,
		},
	} );
}

export async function deleteOrderNote( orderId: number, noteId: number ): Promise< void > {
	await apiFetch( {
		path: `${ ORDERS_BASE }/${ orderId }/notes/${ noteId }?force=true`,
		method: 'DELETE',
	} );
}

export async function fetchCustomer( customerId: number ): Promise< CustomerSummary > {
	return apiFetch( { path: `${ CUSTOMERS_BASE }/${ customerId }` } );
}

export async function fetchOrderStatuses(): Promise< OrderStatusOption[] > {
	// The endpoint returns an array of `{slug, name}` objects already in our
	// target shape — no transformation needed.
	const raw: OrderStatusOption[] = await apiFetch( { path: ORDER_STATUSES_BASE } );
	return raw.map( ( s ) => ( {
		slug: s.slug.replace( /^wc-/, '' ),
		name: s.name,
	} ) );
}
