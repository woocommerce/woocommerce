/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { AsyncAction, TypeYield } from '@wordpress/interactivity';
import type { CurrencyResponse } from '@woocommerce/types';
import type { Store as StoreNotices } from '@woocommerce/stores/store-notices';

/**
 * Mirror of `Automattic\WooCommerce\StoreApi\Schemas\V1\ShopperListItemSchema::get_properties()`.
 *
 * Keep this in sync with the schema. State here must not include any UI-derived
 * fields — display values belong in block-private stores or PHP SSR.
 * TO DO: decide where UI-derived state lives
 */
export type ShopperListItemImage = {
	id: number;
	src: string;
	thumbnail: string;
	srcset: string;
	sizes: string;
	name: string;
	alt: string;
	thumbnail_srcset: string;
	thumbnail_sizes: string;
};

export type ShopperListItemVariation = {
	raw_attribute: string;
	attribute: string;
	value: string;
};

export type ShopperListItemPrices = CurrencyResponse & {
	price: string;
	regular_price: string;
	sale_price: string;
};

export type RawShopperListItem = {
	key: string;
	id: number;
	product_id: number;
	variation_id: number;
	quantity: number;
	is_live: boolean;
	is_purchasable: boolean;
	name: string;
	permalink: string | null;
	images: ShopperListItemImage[];
	variation: ShopperListItemVariation[];
	prices: ShopperListItemPrices | null;
	price_html: string;
	image_html: string;
	date_added_gmt: string;
};

export type ShopperListState = {
	items: RawShopperListItem[];
	isLoading: boolean;
};

export type AddItemPayload = {
	product_id?: number;
	cart_item_key?: string;
	variation?: Array< { attribute: string; value: string } >;
	quantity?: number;
};

export type Store = {
	state: {
		restUrl: string;
		// TODO: revisit nonce handling when we look at authentication for
		// the shopper-lists routes. Today PHP seeds this via
		// `wp_create_nonce( 'wc_store_api' )` and we refresh it from
		// response headers (see restRequest below). Likely changes once
		// the routes start enforcing nonces server-side: align with the
		// cart store's bootstrap-from-response-header pattern, share the
		// cart's `state.nonce` instead of duplicating, or move to a
		// caching-friendlier transport.
		nonce: string;
		lists: Record< string, ShopperListState >;
	};
	actions: {
		loadList: ( slug: string ) => Promise< void >;
		addItem: ( slug: string, payload: AddItemPayload ) => Promise< void >;
		removeItem: ( slug: string, key: string ) => Promise< void >;
		showNoticeError: ( error: Error ) => Promise< void >;
	};
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const isShopperListItem = ( value: unknown ): value is RawShopperListItem =>
	!! value &&
	typeof value === 'object' &&
	typeof ( value as { key?: unknown } ).key === 'string';

const ensureListState = (
	state: Store[ 'state' ],
	slug: string
): ShopperListState => {
	let list = state.lists[ slug ];
	if ( ! list ) {
		list = { items: [], isLoading: false };
		state.lists[ slug ] = list;
	}
	return list;
};

/**
 * Send a Store API request following the cart store's auth shape:
 * Nonce header, `wc_store_api` action on the server side, cookie auth via
 * `credentials: 'include'`, and `cache: 'no-store'` so user-specific data is
 * never cached.
 *
 * The starter nonce is seeded by PHP via `wp_interactivity_state` and
 * refreshed from the `Nonce` response header on every subsequent request,
 * so the server-side enforcement (landing in a follow-up PR) can be
 * flipped on without rewriting the client.
 */
async function restRequest< T >(
	state: Store[ 'state' ],
	path: string,
	init: RequestInit = {}
): Promise< T | null > {
	const headers = new Headers( init.headers );
	headers.set( 'Content-Type', 'application/json' );
	if ( state.nonce ) {
		headers.set( 'Nonce', state.nonce );
	}

	const response = await fetch( `${ state.restUrl }${ path }`, {
		...init,
		headers,
		cache: 'no-store',
		credentials: 'include',
	} );

	const nextNonce = response.headers.get( 'Nonce' );
	if ( nextNonce ) {
		state.nonce = nextNonce;
	}

	if ( response.status === 204 ) {
		return null;
	}

	const text = await response.text();
	const contentType = response.headers.get( 'Content-Type' ) || '';
	const json =
		text && contentType.includes( 'json' ) ? JSON.parse( text ) : null;

	if ( ! response.ok ) {
		const message =
			( json && typeof json === 'object' && 'message' in json
				? String( ( json as { message: unknown } ).message )
				: '' ) ||
			response.statusText ||
			'Request failed.';
		throw new Error( message );
	}

	return json as T | null;
}

// Do NOT supply `nonce` / `restUrl` defaults here. iAPI's deep-merge has the
// JS-supplied state win over the existing (PHP-seeded) state for primitives,
// so an empty-string default would clobber the values seeded server-side via
// `wp_interactivity_state`. State for those fields comes purely from PHP. Same
// reason the cart store doesn't ship state defaults — see cart.ts.
const { state, actions } = store< Store >(
	'woocommerce/shopper-lists',
	{
		actions: {
			*loadList( slug: string ): AsyncAction< void > {
				const list = ensureListState( state, slug );
				list.isLoading = true;

				try {
					const response = ( yield restRequest<
						RawShopperListItem[]
					>(
						state,
						`wc/store/v1/shopper-lists/${ encodeURIComponent(
							slug
						) }/items`,
						{ method: 'GET' }
					) ) as TypeYield<
						typeof restRequest< RawShopperListItem[] >
					>;

					if ( ! Array.isArray( response ) ) {
						throw new Error( 'Invalid shopper list response.' );
					}

					const items = response.filter( isShopperListItem );

					// TODO: track in-flight mutation count and skip applying
					// load results when mutations are pending, so a slow
					// loadList cannot clobber a fresh add/remove.
					list.items = items;
				} catch ( error ) {
					// No user trigger to attach a banner to; log for ops.
					// eslint-disable-next-line no-console
					console.error( error );
				} finally {
					list.isLoading = false;
				}
			},

			*addItem(
				slug: string,
				payload: AddItemPayload
			): AsyncAction< void > {
				const list = ensureListState( state, slug );

				try {
					const item = ( yield restRequest< RawShopperListItem >(
						state,
						`wc/store/v1/shopper-lists/${ encodeURIComponent(
							slug
						) }/items`,
						{
							method: 'POST',
							body: JSON.stringify( payload ),
						}
					) ) as TypeYield<
						typeof restRequest< RawShopperListItem >
					>;

					if ( ! isShopperListItem( item ) ) {
						throw new Error(
							'Invalid shopper list item response.'
						);
					}

					// Merge the returned item by key — replace if present,
					// append otherwise. Re-saving the same product POSTs
					// twice and the server merges quantity, so we mirror
					// that behaviour locally.
					const existingIndex = list.items.findIndex(
						( i ) => i.key === item.key
					);
					if ( existingIndex >= 0 ) {
						list.items[ existingIndex ] = item;
					} else {
						list.items.push( item );
					}
				} catch ( error ) {
					void actions.showNoticeError( error as Error );
				}
			},

			*removeItem( slug: string, key: string ): AsyncAction< void > {
				const list = state.lists[ slug ];
				if ( ! list ) {
					return;
				}

				if ( list.items.findIndex( ( i ) => i.key === key ) < 0 ) {
					return;
				}

				// Pessimistic remove: leave the row in place until the
				// server confirms, so failures don't flash. Buttons are
				// disabled meanwhile via the block's `pendingKeys`.
				try {
					yield restRequest(
						state,
						`wc/store/v1/shopper-lists/${ encodeURIComponent(
							slug
						) }/items/${ encodeURIComponent( key ) }`,
						{ method: 'DELETE' }
					);
				} catch ( error ) {
					void actions.showNoticeError( error as Error );
					return;
				}

				// Re-find — the list may have mutated during the await.
				const removedIndex = list.items.findIndex(
					( i ) => i.key === key
				);
				if ( removedIndex >= 0 ) {
					list.items.splice( removedIndex, 1 );
				}
			},

			// Mirrors `cart.ts::showNoticeError`.
			*showNoticeError( error: Error ): AsyncAction< void > {
				yield import( '@woocommerce/stores/store-notices' );
				const { actions: noticeActions } = store< StoreNotices >(
					'woocommerce/store-notices',
					{},
					{ lock: universalLock }
				);

				noticeActions.addNotice( {
					notice: error.message,
					type: 'error',
					dismissible: true,
				} );

				// eslint-disable-next-line no-console
				console.error( error );
			},
		},
	},
	{ lock: universalLock }
);

// Listen for shopper-list item additions emitted from the wp.data side (e.g.
// the cart store's saveForLater thunk). Mirrors the cart's iAPI → wp.data
// sync direction, which also ships a payload (`from_iAPI` carries
// `quantityChanges`). The event carries the saved item directly so we can
// splice it in without an extra GET — keeps the merge ordering deterministic
// and avoids the loadList-vs-mutation race the iAPI store's loadList still
// has a TODO about.
//
// Keeps the discriminator + payload contract in sync with
// `assets/js/data/cart/thunks.ts::saveForLater`.
window.addEventListener( 'wc-blocks_store_sync_required', ( event: Event ) => {
	const detail = ( event as CustomEvent ).detail as
		| { type?: string; slug?: string; item?: RawShopperListItem }
		| undefined;
	if ( detail?.type !== 'shopper-list-item-added' ) {
		return;
	}
	if (
		typeof detail.slug !== 'string' ||
		detail.slug.trim().length === 0 ||
		! isShopperListItem( detail.item )
	) {
		return;
	}
	const list = ensureListState( state, detail.slug );
	const item = detail.item;
	const existingIndex = list.items.findIndex( ( i ) => i.key === item.key );
	if ( existingIndex >= 0 ) {
		list.items[ existingIndex ] = item;
	} else {
		list.items.push( item );
	}
} );
