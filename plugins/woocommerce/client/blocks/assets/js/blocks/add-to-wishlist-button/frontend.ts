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
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';
import type {
	RawShopperListItem,
	Store as ShopperListsStore,
} from '@woocommerce/stores/woocommerce/shopper-lists';

/**
 * Internal dependencies
 */
import { matchVariationItem } from './match-variation-item';

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

// The narrow slice of ATCWO's iAPI context this block consumes. Reuses
// `SelectedAttributes` from the cart store — the same type ATCWO uses for
// its own `selectedAttributes` context field — so any shape change there
// (e.g. adding a `taxonomy` field) flows through here automatically.
type ATCWOContext = {
	selectedAttributes: SelectedAttributes[];
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
				const product = productsState.productInContext;
				if ( ! product ) {
					return 0;
				}
				const context = getContext< BlockContext >();
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
				const context = getContext< BlockContext >();
				// For non-variable products, id alone uniquely identifies
				// the wishlist row. For variable products with "any"
				// attribute slots, several attribute combinations can map
				// to the same variation product, so we additionally
				// disambiguate by the shopper's picked attributes — see
				// `matchVariationItem` for details.
				if ( ! context.isVariableType ) {
					return (
						list.items.find( ( item ) => item.id === id ) ?? null
					);
				}
				const addToCartContext = getContext< ATCWOContext >(
					'woocommerce/add-to-cart-with-options'
				);
				const selected = addToCartContext?.selectedAttributes ?? [];
				return (
					list.items.find( ( item ) =>
						matchVariationItem( item, id, selected )
					) ?? null
				);
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
						// We inherit ATCWO's iAPI context because this block
						// is an inner block of ATCWO (enforced by
						// `ancestor` in `block.json`). That lets us read
						// the shopper-picked attributes — needed for
						// variations with "any" slots, where the server
						// can't resolve the line item without them.
						//
						// ATCWO stores them by display label ("Color"), but
						// the shopper-lists route expects taxonomy slugs
						// ("pa_color"). Map via the parent product's
						// `attributes` table; fall back to the raw name for
						// non-taxonomy custom attributes.
						//
						// TODO: drop this mapping once ATCWO exposes the
						// taxonomy on `selectedAttributes` directly.
						const addToCartContext = getContext< ATCWOContext >(
							'woocommerce/add-to-cart-with-options'
						);
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
						const variation =
							addToCartContext?.selectedAttributes?.map(
								( { attribute, value } ) => ( {
									attribute:
										attrMap.get( attribute ) ?? attribute,
									value,
								} )
							) ?? [];
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
