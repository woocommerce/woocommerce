/**
 * External dependencies
 */
import {
	getConfig,
	getContext,
	getElement,
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
import { sanitizeHTML } from '@woocommerce/sanitize';

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const LIST_SLUG = 'wishlist';

type WishlistConfig = {
	removeLabelTemplate: string;
};

type BlockContext = {
	listItem?: RawShopperListItem;
	htmlField?: 'price_html' | 'image_html';
	// Item keys currently mid-mutation, used to disable per-row buttons.
	pendingKeys: Record< string, true >;
};

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

// Allow-list for sanitizing the schema's preformatted strings on innerHTML swap. Covers the markup
// emitted by `wc_price` and `wp_get_attachment_image` / `wc_placeholder_img`.
const ALLOWED_TAGS = [
	'a',
	'b',
	'em',
	'i',
	'strong',
	'p',
	'br',
	'span',
	'bdi',
	'del',
	'ins',
	'img',
	'picture',
	'source',
];
const ALLOWED_ATTR = [
	'class',
	'target',
	'href',
	'rel',
	'name',
	'download',
	'aria-hidden',
	'src',
	'srcset',
	'sizes',
	'alt',
	'width',
	'height',
	'loading',
	'decoding',
];

const { state: shopperListsState, actions: shopperListsActions } =
	store< ShopperListsStore >(
		'woocommerce/shopper-lists',
		{},
		{ lock: universalLock }
	);

const { state: cartState, actions: cartActions } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

const decodeEntities = ( encoded: string ): string => {
	const txt = document.createElement( 'textarea' );
	txt.innerHTML = encoded;
	return txt.value;
};

const formatVariationLabel = ( item: RawShopperListItem ): string => {
	if ( ! item.variation || item.variation.length === 0 ) {
		return '';
	}
	return item.variation
		.map(
			( v ) =>
				`${ decodeEntities( v.attribute ) }: ${ decodeEntities(
					v.value
				) }`
		)
		.join( ', ' );
};

const getList = ( slug: string ) => shopperListsState.lists[ slug ] ?? null;

store< BlockStore >(
	'woocommerce/wishlist',
	{
		state: {
			get currentItems(): RawShopperListItem[] {
				return getList( LIST_SLUG )?.items ?? [];
			},

			get isCurrentItemPending(): boolean {
				const { listItem, pendingKeys } = getContext< BlockContext >();
				return !! listItem && !! pendingKeys[ listItem.key ];
			},

			// Unlike Saved for Later, Wishlist has no `hasShownItems` first-paint guard. The block is
			// reached deliberately (My Account endpoint or a merchant-placed instance), so the empty
			// state should be visible immediately on first paint when the list is empty.
			get isEmpty(): boolean {
				const list = getList( LIST_SLUG );
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

			// `data-wp-text` writes its argument as text-content without entity decoding, so a name like
			// `Tom &amp; Jerry` would render with the literal entity. Bind templates and SSR text spans
			// to this getter (not the raw context field) so rendered text matches PHP's first paint.
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
			*onClickRemove(): AsyncAction< void > {
				const { listItem, pendingKeys } = getContext< BlockContext >();
				if ( ! listItem || pendingKeys[ listItem.key ] ) {
					return;
				}
				pendingKeys[ listItem.key ] = true;
				try {
					yield shopperListsActions.removeItem(
						LIST_SLUG,
						listItem.key
					);
				} finally {
					delete pendingKeys[ listItem.key ];
				}
			},

			*onClickAddToCart(): AsyncAction< void > {
				const { listItem, pendingKeys } = getContext< BlockContext >();
				if (
					! listItem ||
					! listItem.is_purchasable ||
					pendingKeys[ listItem.key ]
				) {
					return;
				}

				// Map the schema's `variation` shape to the cart's `SelectedAttributes` shape. The schema
				// exposes the slug-form attribute under `raw_attribute` and a display label under
				// `attribute`. The cart matches by the slug form, so `attribute` is overridden with
				// `raw_attribute`. Empty for simple products.
				const variation = listItem.variation.map(
					( { raw_attribute: rawAttribute, value, attribute } ) => ( {
						attribute: rawAttribute || attribute,
						value,
					} )
				);
				const isVariation = listItem.variation_id > 0;

				// Wishlist always adds quantity 1 (no quantity column). `cartActions.addCartItem` catches
				// its own errors and surfaces them as store notices, so the yield resolves identically
				// on success and failure. Snapshot the matching line's quantity, run the add, and only
				// remove from the wishlist if the cart line grew. Guards against partial-stock paths
				// where the wishlist entry must remain.
				const lookup = {
					id: listItem.id,
					...( isVariation && { variation } ),
				};
				const beforeItem = cartState.findItemInCart( lookup );
				const beforeQuantity = beforeItem?.quantity ?? 0;

				pendingKeys[ listItem.key ] = true;
				try {
					yield cartActions.addCartItem( {
						id: listItem.id,
						quantityToAdd: 1,
						type: isVariation ? 'variation' : 'simple',
						...( isVariation && { variation } ),
					} );

					const afterItem = cartState.findItemInCart( lookup );
					const afterQuantity = afterItem?.quantity ?? 0;

					if ( afterQuantity <= beforeQuantity ) {
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
			// Shared innerHTML-swap callback for slots whose content is one of the schema's preformatted
			// HTML fields. The watched element carries `data-wp-context='{"htmlField":"price_html"}'` (or
			// `"image_html"`). This reads the named field off the row's `listItem` and writes its
			// sanitized HTML into `element.ref`. PHP renders the same HTML server-side, so hydration is
			// a no-op until the row's `listItem` changes.
			updateInnerHtml: () => {
				const { ref } = getElement();
				const { listItem, htmlField } = getContext< BlockContext >();
				if ( ! ref || ! listItem || ! htmlField ) {
					return;
				}
				const html = listItem[ htmlField ];
				if ( typeof html === 'string' ) {
					ref.innerHTML = sanitizeHTML( html, {
						tags: ALLOWED_TAGS,
						attr: ALLOWED_ATTR,
					} );
				}
			},
		},
	},
	{ lock: universalLock }
);
