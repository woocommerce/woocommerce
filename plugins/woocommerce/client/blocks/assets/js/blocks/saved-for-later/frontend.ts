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

const LIST_SLUG = 'saved-for-later';

type SavedForLaterConfig = {
	quantityLabelTemplate: string;
	removeLabelTemplate: string;
};

type BlockContext = SharedListBlockContext & {
	// Wrapper-scoped flag: starts as `items.length > 0` from SSR and the
	// `trackShownItems` callback flips it to `true` the first time the
	// list has any items at runtime. Lives in iAPI context so it resets
	// on every full page load.
	hasShownItems: boolean;
};

type BlockStore = {
	state: {
		currentItems: RawShopperListItem[];
		isCurrentItemPending: boolean;
		isEmpty: boolean;
		isMoveToCartHidden: boolean;
		isPriceHidden: boolean;
		currentItemDisplayName: string;
		currentItemQuantityLabel: string;
		currentItemRemoveLabel: string;
		currentItemVariationLabel: string;
	};
	actions: {
		onClickRemove: () => Generator< unknown, void >;
		onClickMoveToCart: () => Generator< unknown, void >;
	};
	callbacks: {
		updateInnerHtml: () => void;
		trackShownItems: () => void;
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
	'woocommerce/saved-for-later',
	{
		state: {
			get currentItems(): RawShopperListItem[] {
				return getList( shopperListsState, LIST_SLUG )?.items ?? [];
			},

			get isCurrentItemPending(): boolean {
				const { listItem, pendingKeys } = getContext< BlockContext >();
				return !! listItem && !! pendingKeys[ listItem.key ];
			},

			get isEmpty(): boolean {
				const list = getList( shopperListsState, LIST_SLUG );
				if ( ! list ) {
					return false;
				}
				const ctx = getContext< BlockContext >();
				return (
					ctx.hasShownItems &&
					! list.isLoading &&
					list.items.length === 0
				);
			},

			get isPriceHidden(): boolean {
				const { listItem } = getContext< BlockContext >();
				return ! listItem?.price_html;
			},

			get isMoveToCartHidden(): boolean {
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

			get currentItemQuantityLabel(): string {
				const { listItem } = getContext< BlockContext >();
				if ( ! listItem ) {
					return '';
				}
				const { quantityLabelTemplate } = getConfig(
					'woocommerce/saved-for-later'
				) as SavedForLaterConfig;
				return quantityLabelTemplate.replace(
					'%d',
					String( listItem.quantity )
				);
			},

			get currentItemRemoveLabel(): string {
				const { listItem } = getContext< BlockContext >();
				if ( ! listItem ) {
					return '';
				}
				const { removeLabelTemplate } = getConfig(
					'woocommerce/saved-for-later'
				) as SavedForLaterConfig;
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

			*onClickMoveToCart(): AsyncAction< void > {
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

				// `addItem` POSTs add-item (server adds the quantity to any
				// existing line) and resolves with the affected cart line, or
				// `undefined` when the add failed (it catches its own errors and
				// surfaces them as store notices). Only remove from the saved
				// list when the add succeeded.
				pendingKeys[ listItem.key ] = true;
				try {
					const addedLine = yield cartActions.addItem( {
						id: listItem.id,
						quantity: listItem.quantity,
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
			// Wrapper-level watcher: flips `hasShownItems` to `true` the
			// first time the list has any items. Pairs with `state.isEmpty`
			// to gate the empty message — a new shopper landing on a page
			// with nothing saved keeps the flag at its SSR-seeded `false`
			// and never sees the message; once they save an item (or
			// landed with items) the flag is `true`, so emptying the list
			// from that point surfaces the message. The flag never flips
			// back to `false`, which is what gives the "had-items → now-empty"
			// transition we want during the session.
			trackShownItems: () => {
				const ctx = getContext< BlockContext >();
				const list = getList( shopperListsState, LIST_SLUG );
				if ( list && list.items.length > 0 && ! ctx.hasShownItems ) {
					ctx.hasShownItems = true;
				}
			},

			updateInnerHtml,
		},
	},
	{ lock: universalLock }
);
