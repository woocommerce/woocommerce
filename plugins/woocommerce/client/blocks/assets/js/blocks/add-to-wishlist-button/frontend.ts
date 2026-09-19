/**
 * External dependencies
 */
import {
	getConfig,
	getContext,
	store,
	type AsyncAction,
} from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce';
import '@woocommerce/stores/woocommerce/shopper-lists';
import type { WooCommerceStore } from '@woocommerce/stores/woocommerce';
import type {
	RawShopperListItem,
	Store as ShopperListsStore,
} from '@woocommerce/stores/woocommerce/shopper-lists';

/**
 * Internal dependencies
 */
import { matchVariationItem } from './match-variation-item';

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

const { state: wooState } = store< WooCommerceStore >(
	'woocommerce',
	{},
	{
		lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
	}
);

const { state: shopperListsState, actions: shopperListsActions } =
	store< ShopperListsStore >(
		'woocommerce/shopper-lists',
		{},
		{
			lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
		}
	);

const { state } = store< BlockStore >(
	'woocommerce/add-to-wishlist-button',
	{
		state: {
			// For variable products, the effective product is the selected
			// variation — resolved through the shared product scope's
			// `productVariation`, `null` while the shopper hasn't picked
			// attributes yet, which `isDisabled` reads as "not yet
			// selectable." For every other product type it's the scope's
			// `baseProduct`.
			get effectiveProductId(): number {
				const context = getContext< BlockContext >();
				const product = context.isVariableType
					? wooState.productScope.productVariation
					: wooState.productScope.baseProduct;
				return product?.id ?? 0;
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
				const selected = wooState.productScope.variation;
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
						// We read the shared product scope this block
						// already sits in (enforced by `ancestor` in
						// `block.json`, which keeps it inside ATCWO's form).
						// That's how we get the shopper-picked attributes —
						// needed for variations with "any" slots, where the
						// server can't resolve the line item without them.
						//
						// The scope stores them by display label ("Color"),
						// but the shopper-lists route expects taxonomy slugs
						// ("pa_color"). Map via the parent product's
						// `attributes` table; fall back to the raw name for
						// non-taxonomy custom attributes.
						const parent = wooState.productScope.baseProduct;
						const attrMap = new Map< string, string >();
						parent?.attributes.forEach( ( a ) => {
							if ( a.name ) {
								attrMap.set( a.name, a.taxonomy || a.name );
							}
						} );
						const variation = wooState.productScope.variation.map(
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
	{
		lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
	}
);
