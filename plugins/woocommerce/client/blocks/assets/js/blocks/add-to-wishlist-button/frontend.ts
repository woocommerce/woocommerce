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
	// the request is in flight. Single-instance block, no `pendingKeys`
	// map needed (Wishlist/SFL use one because they're per-row).
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
			// For variable products, the effective product is the selected
			// variation — resolved through the products store's
			// `productInContext` derived getter, which already encapsulates
			// "variation if one is selected, otherwise the parent." Returns
			// 0 when the current resolution is still the variable parent
			// (i.e. the shopper hasn't picked attributes yet), which
			// `isDisabled` reads as "not yet selectable."
			get effectiveProductId(): number {
				const context = getContext< BlockContext >();
				const product = productsState.productInContext;
				if ( ! product ) {
					return 0;
				}
				if ( context.isVariableType && product.type === 'variable' ) {
					return 0;
				}
				return product.id;
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
				return ! state.effectiveProductId;
			},

			get currentLabel(): string {
				const { addLabel, savedLabel, selectOptionsLabel } = getConfig(
					'woocommerce/add-to-wishlist-button'
				) as ButtonConfig;

				if ( ! state.effectiveProductId ) {
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
