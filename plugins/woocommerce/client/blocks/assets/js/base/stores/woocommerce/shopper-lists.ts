/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { AsyncAction, TypeYield } from '@wordpress/interactivity';
import type { CurrencyResponse } from '@woocommerce/types';
import type { Store as StoreNotices } from '@woocommerce/stores/store-notices';

/**
 * Mirror of {@see \Automattic\WooCommerce\StoreApi\Schemas\V1\ShopperListItemSchema::get_properties()}.
 * Keep in sync with the schema. UI-derived fields do not belong here. Display values are kept in
 * block-private stores or rendered server-side.
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
		// Shared with the cart routes.
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

// Locked to prevent third-party use until the API stabilizes.
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
 * Send a Store API request using the cart store's auth shape: Nonce header
 * (`wc_store_api` action), cookie auth via `credentials: 'include'`, and
 * `cache: 'no-store'` for user-specific data. The nonce is seeded by PHP and
 * refreshed from the `Nonce` response header on each reply.
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

					// TODO: track in-flight mutation count and skip applying load results when mutations
					// are pending, so a slow loadList cannot overwrite a fresh add or remove.
					list.items = items;
				} catch ( error ) {
					// Logged for diagnostics.
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

					// Merge the returned item by key: replace if present, append otherwise. The server
					// merges quantity on duplicate saves, and this mirrors that behaviour client-side.
					const existingIndex = list.items.findIndex(
						( i ) => i.key === item.key
					);
					if ( existingIndex >= 0 ) {
						list.items[ existingIndex ] = item;
					} else {
						list.items.push( item );
					}
				} catch ( error ) {
					actions.showNoticeError( error as Error );
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

				// Pessimistic remove: keep the row in place until the server confirms, to avoid a
				// momentary disappearance on failure. Buttons stay disabled meanwhile via `pendingKeys`.
				try {
					yield restRequest(
						state,
						`wc/store/v1/shopper-lists/${ encodeURIComponent(
							slug
						) }/items/${ encodeURIComponent( key ) }`,
						{ method: 'DELETE' }
					);
				} catch ( error ) {
					actions.showNoticeError( error as Error );
					return;
				}

				// Re-find. The list may have mutated during the await.
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

// Syncs wp.data into this iAPI store after a wp.data action (e.g. the cart store's `saveForLater` thunk).
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
