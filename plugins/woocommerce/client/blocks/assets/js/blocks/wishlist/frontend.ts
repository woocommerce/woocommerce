/**
 * External dependencies
 */
import {
	getConfig,
	getContext,
	store,
	type AsyncAction,
} from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/shopper-lists';
import '@woocommerce/stores/woocommerce/cart';
import type {
	RawShopperListItem,
	Store as ShopperListsStore,
} from '@woocommerce/stores/woocommerce/shopper-lists';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import {
	type SharedListBlockContext,
	createOnClickRemove,
	decodeEntities,
	formatVariationLabel,
	getList,
	mapListItemVariation,
	updateInnerHtml,
} from '../shopper-lists-shared/frontend-utils';

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const LIST_SLUG = 'wishlist';

type WishlistConfig = {
	removeLabelTemplate: string;
};

type BlockContext = SharedListBlockContext;

type BlockStore = {
	state: {
		currentItems: RawShopperListItem[];
		isCurrentItemPending: boolean;
		isEmpty: boolean;
		isAddToCartHidden: boolean;
		isPriceHidden: boolean;
		currentItemDisplayName: string;
		currentItemRemoveLabel: string;
		currentItemVariationLabel: string;
	};
	actions: {
		onClickRemove: () => Generator< unknown, void >;
		onClickAddToCart: () => Generator< unknown, void >;
	};
	callbacks: {
		updateInnerHtml: () => void;
	};
};

const { state: shopperListsState, actions: shopperListsActions } =
	store< ShopperListsStore >(
		'woocommerce/shopper-lists',
		{},
		{ lock: universalLock }
	);

const { actions: cartActions } = store< WooCommerce >(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);

store< BlockStore >(
	'woocommerce/wishlist',
	{
		state: {
			get currentItems(): RawShopperListItem[] {
				return getList( shopperListsState, LIST_SLUG )?.items ?? [];
			},

			get isCurrentItemPending(): boolean {
				const { listItem, pendingKeys } = getContext< BlockContext >();
				return !! listItem && !! pendingKeys[ listItem.key ];
			},

			// No `hasShownItems` gate: the visitor reached this block
			// deliberately (My Account endpoint or merchant-placed), so
			// showing the empty message immediately when the list is
			// empty is the right signal.
			get isEmpty(): boolean {
				const list = getList( shopperListsState, LIST_SLUG );
				if ( ! list ) {
					return false;
				}
				return ! list.isLoading && list.items.length === 0;
			},

			get isPriceHidden(): boolean {
				const { listItem } = getContext< BlockContext >();
				return ! listItem?.price_html;
			},

			get isAddToCartHidden(): boolean {
				const { listItem } = getContext< BlockContext >();
				if ( ! listItem ) {
					return true;
				}
				return ! listItem.is_purchasable;
			},

			// `data-wp-text` writes its argument as text-content without
			// running entity decoding, so a name returned by the schema as
			// `Tom &amp; Jerry` would render literally that way. Bind
			// templates and SSR text spans to this getter instead of the
			// raw context field so what the browser shows matches what
			// PHP wrote on first paint.
			get currentItemDisplayName(): string {
				const { listItem } = getContext< BlockContext >();
				return listItem ? decodeEntities( listItem.name ) : '';
			},

			get currentItemRemoveLabel(): string {
				const { listItem } = getContext< BlockContext >();
				if ( ! listItem ) {
					return '';
				}
				const { removeLabelTemplate } = getConfig(
					'woocommerce/wishlist'
				) as WishlistConfig;
				return removeLabelTemplate.replace(
					'%s',
					decodeEntities( listItem.name )
				);
			},

			get currentItemVariationLabel(): string {
				const { listItem } = getContext< BlockContext >();
				return listItem ? formatVariationLabel( listItem ) : '';
			},
		},

		actions: {
			onClickRemove: createOnClickRemove(
				shopperListsActions,
				LIST_SLUG
			),

			*onClickAddToCart(): AsyncAction< void > {
				const { listItem, pendingKeys } = getContext< BlockContext >();
				if (
					! listItem ||
					! listItem.is_purchasable ||
					pendingKeys[ listItem.key ]
				) {
					return;
				}

				const variation = mapListItemVariation( listItem );
				const isVariation = listItem.variation_id > 0;

				// Wishlist always adds quantity 1 (no quantity column).
				// `addItem` POSTs add-item and resolves with the affected cart
				// line, or `undefined` when the add failed (it catches its own
				// errors and surfaces them as store notices). Only remove from
				// the wishlist when the add succeeded — guarding against
				// partial-stock and silent-failure paths.
				pendingKeys[ listItem.key ] = true;
				try {
					const addedLine = yield cartActions.addItem( {
						id: listItem.id,
						quantity: 1,
						...( isVariation && { variation } ),
					} );

					if ( ! addedLine ) {
						return;
					}

					yield shopperListsActions.removeItem(
						LIST_SLUG,
						listItem.key
					);
				} finally {
					delete pendingKeys[ listItem.key ];
				}
			},
		},

		callbacks: {
			updateInnerHtml,
		},
	},
	{ lock: universalLock }
);
