/**
 * External dependencies
 */
import { getConfig, getContext, store } from '@wordpress/interactivity';
import type { AsyncAction } from '@wordpress/interactivity';
import type {
	CartImageItem,
	CartVariationItem,
	CurrencyInfo,
	Currency,
} from '@woocommerce/types';
import type {
	Store as CartStore,
	SelectedAttributes,
} from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import { formatPriceWithCurrency } from '../../../../../packages/prices/utils/currency';

// Mirrors `ShopperListItemSchema`.
type ShopperListItemPrices = CurrencyInfo & {
	price: string;
	regular_price: string;
	sale_price: string;
};

type RawShopperListItem = {
	key: string;
	id: number;
	product_id: number;
	variation_id: number;
	quantity: number;
	product_exists: boolean;
	name: string;
	permalink: string;
	images: CartImageItem[];
	variation: CartVariationItem[];
	prices: ShopperListItemPrices | null;
	date_added_gmt: string;
};

// Flat fields directives bind to; mirrored by `ShopperCollection::enrich_item_for_display()` in PHP.
type DisplayFields = {
	thumbnail: string;
	alt: string;
	hasImage: boolean;
	priceLabel: string;
	quantityLabel: string;
};

export type ShopperListItem = RawShopperListItem & DisplayFields;

type ListState = {
	items: ShopperListItem[];
	isLoading: boolean;
	error: string | null;
};

type Lists = Record< string, ListState >;

type ListContext = {
	listName: string;
};

type IteratedContext = ListContext & {
	listItem?: ShopperListItem;
};

type AddItemPayload = {
	slug: string;
	productId: number;
	variationId?: number;
	variation?: SelectedAttributes[];
	quantity?: number;
};

type AddItemFromCartKeyPayload = {
	slug: string;
	cartItemKey: string;
};

type ListItemRef = {
	slug: string;
	key: string;
};

export type ShopperListsStore = {
	state: {
		lists: Lists;
	};
	actions: {
		loadList: ( slug: string ) => Promise< void >;
		addItem: ( payload: AddItemPayload ) => Promise< void >;
		addItemFromCartKey: (
			payload: AddItemFromCartKeyPayload
		) => Promise< void >;
		removeItem: ( payload: ListItemRef ) => Promise< void >;
		moveItemToCart: ( payload: ListItemRef ) => Promise< void >;
		removeCurrentItem: () => Promise< void >;
		moveCurrentItemToCart: () => Promise< void >;
	};
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const NAMESPACE = 'woocommerce/shopper-collections';
const REST_PATH = 'wc/store/v1/shopper-lists';

type ShopperCollectionsConfig = {
	restUrl: string;
	nonce: string;
	placeholderImgSrc: string;
};

const config = ( getConfig( NAMESPACE ) ||
	{} ) as Partial< ShopperCollectionsConfig >;

const restState = {
	restUrl: config.restUrl ?? '',
	nonce: config.nonce ?? '',
};

const placeholderImgSrc = config.placeholderImgSrc ?? '';

const currencyFromItemPrices = (
	prices: NonNullable< RawShopperListItem[ 'prices' ] >
): Currency => ( {
	code: prices.currency_code as Currency[ 'code' ],
	symbol: prices.currency_symbol,
	thousandSeparator: prices.currency_thousand_separator,
	decimalSeparator: prices.currency_decimal_separator,
	minorUnit: prices.currency_minor_unit,
	prefix: prices.currency_prefix,
	suffix: prices.currency_suffix,
} );

const enrichItemForDisplay = ( item: RawShopperListItem ): ShopperListItem => {
	const thumbnail = item.images?.[ 0 ]?.thumbnail ?? '';
	const alt = item.images?.[ 0 ]?.alt ?? '';
	const name = item.name ?? '';
	const exists = Boolean( item.product_exists );

	const priceLabel =
		exists && item.prices
			? formatPriceWithCurrency(
					item.prices.price,
					currencyFromItemPrices( item.prices )
			  )
			: '';

	return {
		...item,
		name,
		thumbnail: thumbnail || placeholderImgSrc,
		alt: alt || name,
		hasImage: Boolean( thumbnail || placeholderImgSrc ),
		priceLabel,
		quantityLabel: `Qty: ${ item.quantity }`,
	};
};

const enrichItems = ( items: RawShopperListItem[] ): ShopperListItem[] =>
	items.map( enrichItemForDisplay );

const restRequest = async (
	path: string,
	init: { method: string; body?: Record< string, unknown > }
): Promise< unknown > => {
	if ( ! restState.restUrl ) {
		throw new Error(
			'Shopper Collections store is missing its REST URL config.'
		);
	}
	const headers: Record< string, string > = {
		Accept: 'application/json',
	};
	if ( restState.nonce ) {
		headers[ 'X-WP-Nonce' ] = restState.nonce;
	}
	const requestInit: RequestInit = {
		method: init.method,
		credentials: 'include',
		headers,
	};
	if ( init.body !== undefined ) {
		headers[ 'Content-Type' ] = 'application/json';
		requestInit.body = JSON.stringify( init.body );
	}
	const response = await fetch( restState.restUrl + path, requestInit );
	const rotatedNonce = response.headers.get( 'X-WP-Nonce' );
	if ( rotatedNonce ) {
		restState.nonce = rotatedNonce;
	}
	const contentType = response.headers.get( 'Content-Type' ) || '';
	// 204 responses can advertise application/json with an empty body, which
	// would make `response.json()` throw on parse. Read as text first.
	const rawBody =
		response.status === 204 ? '' : await response.text();
	const data =
		rawBody && contentType.includes( 'application/json' )
			? JSON.parse( rawBody )
			: null;
	if ( ! response.ok ) {
		const message =
			data &&
			typeof data === 'object' &&
			'message' in data &&
			typeof ( data as { message: unknown } ).message === 'string'
				? ( data as { message: string } ).message
				: `Request failed with status ${ response.status }.`;
		throw new Error( message );
	}
	return data;
};

const ensureListSlot = ( lists: Lists, slug: string ): ListState => {
	if ( ! lists[ slug ] ) {
		lists[ slug ] = { items: [], isLoading: false, error: null };
	}
	return lists[ slug ];
};

// Replace the item with the same key, or append it. POST may merge quantities
// server-side, so we always trust the returned row over any local copy.
const upsertItem = ( slot: ListState, raw: RawShopperListItem ): void => {
	const enriched = enrichItemForDisplay( raw );
	const index = slot.items.findIndex( ( row ) => row.key === enriched.key );
	if ( index >= 0 ) {
		slot.items[ index ] = enriched;
	} else {
		slot.items.push( enriched );
	}
};

const toSelectedAttributes = (
	variation: ShopperListItem[ 'variation' ]
): SelectedAttributes[] =>
	variation.map( ( v ) => ( {
		attribute: v.attribute,
		value: v.value,
	} ) );

const errorMessage = ( error: unknown ): string => {
	if ( error instanceof Error ) {
		return error.message;
	}
	if (
		error &&
		typeof error === 'object' &&
		'message' in error &&
		typeof ( error as { message: unknown } ).message === 'string'
	) {
		return ( error as { message: string } ).message;
	}
	return 'Unknown error.';
};

const { state, actions } = store< ShopperListsStore >(
	NAMESPACE,
	{
		state: {
			lists: {},
		},

		actions: {
			*loadList( slug: string ): AsyncAction< void > {
				const slot = ensureListSlot( state.lists, slug );
				slot.isLoading = true;
				slot.error = null;
				try {
					const items = ( yield restRequest(
						`${ REST_PATH }/${ slug }/items`,
						{ method: 'GET' }
					) ) as RawShopperListItem[];
					slot.items = enrichItems( items );
				} catch ( error ) {
					slot.error = errorMessage( error );
				} finally {
					slot.isLoading = false;
				}
			},

			*addItem( payload: AddItemPayload ): AsyncAction< void > {
				const { slug, productId, variationId, variation, quantity } =
					payload;
				const slot = ensureListSlot( state.lists, slug );
				slot.error = null;
				try {
					const body: Record< string, unknown > = {
						product_id: productId,
					};
					if ( variationId ) {
						body.variation_id = variationId;
					}
					if ( variation && variation.length ) {
						body.variation = variation;
					}
					if ( typeof quantity === 'number' ) {
						body.quantity = quantity;
					}
					const saved = ( yield restRequest(
						`${ REST_PATH }/${ slug }/items`,
						{ method: 'POST', body }
					) ) as RawShopperListItem;
					upsertItem( slot, saved );
				} catch ( error ) {
					slot.error = errorMessage( error );
					throw error;
				}
			},

			*addItemFromCartKey(
				payload: AddItemFromCartKeyPayload
			): AsyncAction< void > {
				const { slug, cartItemKey } = payload;
				const slot = ensureListSlot( state.lists, slug );
				slot.error = null;
				try {
					const saved = ( yield restRequest(
						`${ REST_PATH }/${ slug }/items`,
						{
							method: 'POST',
							body: { cart_item_key: cartItemKey },
						}
					) ) as RawShopperListItem;
					upsertItem( slot, saved );
				} catch ( error ) {
					slot.error = errorMessage( error );
					throw error;
				}
			},

			*removeItem( payload: ListItemRef ): AsyncAction< void > {
				const { slug, key } = payload;
				const slot = ensureListSlot( state.lists, slug );
				slot.error = null;

				// Optimistic update: drop the row first so the UI feels
				// instant, then send the request. Restore on failure.
				const index = slot.items.findIndex(
					( row ) => row.key === key
				);
				if ( index < 0 ) {
					return;
				}
				const [ removed ] = slot.items.splice( index, 1 );

				try {
					yield restRequest(
						`${ REST_PATH }/${ slug }/items/${ key }`,
						{ method: 'DELETE' }
					);
				} catch ( error ) {
					slot.items.splice( index, 0, removed );
					slot.error = errorMessage( error );
					throw error;
				}
			},

			*moveItemToCart( payload: ListItemRef ): AsyncAction< void > {
				const { slug, key } = payload;
				const slot = ensureListSlot( state.lists, slug );
				const item = slot.items.find( ( row ) => row.key === key );
				if ( ! item ) {
					return;
				}
				if ( ! item.product_exists ) {
					slot.error =
						'This product is no longer available in the catalog.';
					return;
				}
				const { actions: cartActions } = store< CartStore >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);
				yield cartActions.addCartItem( {
					id: item.id,
					quantity: item.quantity,
					variation: toSelectedAttributes( item.variation ),
					type: item.variation_id > 0 ? 'variation' : 'simple',
				} );
				yield actions.removeItem( { slug, key } );
			},

			*removeCurrentItem(): AsyncAction< void > {
				const ctx = getContext< IteratedContext >();
				if ( ! ctx?.listName || ! ctx.listItem ) {
					return;
				}
				yield actions.removeItem( {
					slug: ctx.listName,
					key: ctx.listItem.key,
				} );
			},

			*moveCurrentItemToCart(): AsyncAction< void > {
				const ctx = getContext< IteratedContext >();
				if ( ! ctx?.listName || ! ctx.listItem ) {
					return;
				}
				yield actions.moveItemToCart( {
					slug: ctx.listName,
					key: ctx.listItem.key,
				} );
			},
		},
	},
	{ lock: universalLock }
);
