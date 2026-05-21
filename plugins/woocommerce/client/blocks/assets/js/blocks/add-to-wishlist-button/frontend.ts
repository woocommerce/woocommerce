/**
 * External dependencies
 */
import {
	getConfig,
	getContext,
	store,
	type AsyncAction,
} from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/products';
import '@woocommerce/stores/woocommerce/shopper-lists';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
import type {
	RawShopperListItem,
	Store as ShopperListsStore,
} from '@woocommerce/stores/woocommerce/shopper-lists';

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const LIST_SLUG = 'wishlist';

type ButtonConfig = {
	addLabel: string;
	savedLabel: string;
	selectOptionsLabel: string;
};

type BlockContext = {
	productId: number;
	isVariableType: boolean;
	// Mid-click flag, gated per-block so the button can be disabled while
	// the request is in flight without coupling to a global signal.
	isPending: boolean;
};

type BlockStore = {
	state: {
		effectiveProductId: number;
		currentItem: RawShopperListItem | null;
		isInWishlist: boolean;
		isDisabled: boolean;
		currentLabel: string;
	};
	actions: {
		onClickToggle: () => Generator< unknown, void >;
	};
};

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

const { state: shopperListsState, actions: shopperListsActions } =
	store< ShopperListsStore >(
		'woocommerce/shopper-lists',
		{},
		{ lock: universalLock }
	);

const { state } = store< BlockStore >(
	'woocommerce/add-to-wishlist-button',
	{
		state: {
			// For variable products, resolve the matched variation from
			// the shared products store (the variation-selector writes the
			// matched ID into `productsState.variationId` whenever the
			// shopper picks an option). Falls back to 0 until a variation
			// resolves, which `isDisabled` reads as "not yet selectable".
			get effectiveProductId(): number {
				const context = getContext< BlockContext >();
				if ( context.isVariableType ) {
					return productsState.variationId ?? 0;
				}
				return context.productId;
			},

			get currentItem(): RawShopperListItem | null {
				const id = state.effectiveProductId;
				if ( ! id ) {
					return null;
				}
				const list = shopperListsState.lists[ LIST_SLUG ];
				if ( ! list ) {
					return null;
				}
				return list.items.find( ( item ) => item.id === id ) ?? null;
			},

			get isInWishlist(): boolean {
				return state.currentItem !== null;
			},

			get isDisabled(): boolean {
				const context = getContext< BlockContext >();
				if ( context.isPending ) {
					return true;
				}
				// Variable parent with no variation chosen yet.
				return ! state.effectiveProductId;
			},

			get currentLabel(): string {
				const context = getContext< BlockContext >();
				const { addLabel, savedLabel, selectOptionsLabel } = getConfig(
					'woocommerce/add-to-wishlist-button'
				) as ButtonConfig;

				if ( context.isVariableType && ! productsState.variationId ) {
					return selectOptionsLabel;
				}
				return state.isInWishlist ? savedLabel : addLabel;
			},
		},

		actions: {
			*onClickToggle(): AsyncAction< void > {
				const context = getContext< BlockContext >();
				if ( context.isPending ) {
					return;
				}
				const id = state.effectiveProductId;
				if ( ! id ) {
					return;
				}

				const existing = state.currentItem;
				context.isPending = true;
				try {
					if ( existing ) {
						yield shopperListsActions.removeItem(
							LIST_SLUG,
							existing.key
						);
					} else {
						// Passing the variation ID directly as `product_id`
						// lets the server resolve variation attributes
						// from the variation itself — no need to ship the
						// selected-attributes payload from the client.
						yield shopperListsActions.addItem( LIST_SLUG, {
							product_id: id,
						} );
					}
				} finally {
					context.isPending = false;
				}
			},
		},
	},
	{ lock: universalLock }
);
