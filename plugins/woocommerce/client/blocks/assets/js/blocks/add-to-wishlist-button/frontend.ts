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
import '@woocommerce/stores/woocommerce/cart';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
import type {
	SelectedAttributes,
	Store as WooCommerce,
} from '@woocommerce/stores/woocommerce/cart';
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

const { state: cartState } = store< WooCommerce >(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);

// The shopper's picked attributes, read from the shared cart draft's
// `variation` (the selection truth). This block is an inner block of ATCWO, so
// the cart store's `itemInContext` resolves the draft via the inherited
// products context.
const getSelectedAttributes = (): SelectedAttributes[] =>
	( cartState.itemInContext.draft?.variation ?? [] ) as SelectedAttributes[];

const { state } = store< BlockStore >(
	'woocommerce/add-to-wishlist-button',
	{
		state: {
			// The effective product is whatever the selection resolves to as
			// one concrete product — a selected variation, or a product that
			// needs no options picked — via the products store's
			// `resolvedProductInContext` derived getter. It is `null` (→ 0
			// here) while a variable product still has options to pick, which
			// `isDisabled` reads as "not yet selectable." No `type === 'variable'`
			// branch: the polymorphism is resolved once, in the store getter.
			get effectiveProductId(): number {
				return productsState.resolvedProductInContext?.id ?? 0;
			},

			get currentItem(): RawShopperListItem | null {
				const id = state.effectiveProductId;
				if ( ! id ) {
					return null;
				}
				// The shopper's picked attributes disambiguate "any"-slot
				// variations that share a single variation product id; for a
				// product with no options the selection is empty and the store
				// matches on id alone. Either way `findListItem` applies the
				// unified matching semantics — no type branch here.
				return shopperListsState.findListItem( {
					slug: LIST_SLUG,
					id,
					variation: getSelectedAttributes(),
				} );
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
						// The shopper-picked attributes come from the shared
						// cart draft's `variation` — needed for variations
						// with "any" slots, where the server can't resolve
						// the line item without them.
						//
						// The draft stores them by display label ("Color"),
						// but the shopper-lists route expects taxonomy slugs
						// ("pa_color"). Map via the parent product's
						// `attributes` table; fall back to the raw name for
						// non-taxonomy custom attributes.
						const parent = productsState.mainProductInContext;
						const attrMap = new Map< string, string >();
						parent?.attributes?.forEach(
							( a: {
								name?: string;
								taxonomy?: string | null;
							} ) => {
								if ( a.name ) {
									attrMap.set( a.name, a.taxonomy || a.name );
								}
							}
						);
						const variation = getSelectedAttributes().map(
							( { attribute, value } ) => ( {
								attribute:
									attrMap.get( attribute ) ?? attribute,
								value,
							} )
						);
						yield shopperListsActions.addItem( LIST_SLUG, {
							product_id: id,
							...( variation.length && { variation } ),
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
