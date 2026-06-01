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

const LIST_SLUG = 'saved-for-later';

type SavedForLaterConfig = {
	quantityLabelTemplate: string;
	removeLabelTemplate: string;
};

type BlockContext = {
	// Wrapper-scoped flag: starts as `items.length > 0` from SSR and the
	// `trackShownItems` callback flips it to `true` the first time the
	// list has any items at runtime. Lives in iAPI context so it resets
	// on every full page load.
	hasShownItems: boolean;
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

// Allow-list for sanitizing the schema's preformatted strings on innerHTML
// swap. Covers what `wc_price` (sale/discount markup, currency symbol) and
// `wp_get_attachment_image` / `wc_placeholder_img` emit (responsive image
// + dimensions + lazy loading).
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
	'woocommerce/saved-for-later',
	{
		state: {
			get currentItems(): RawShopperListItem[] {
				return getList( LIST_SLUG )?.items ?? [];
			},

			get isCurrentItemPending(): boolean {
				const { listItem, pendingKeys } = getContext< BlockContext >();
				return !! listItem && !! pendingKeys[ listItem.key ];
			},

			get isEmpty(): boolean {
				const list = getList( LIST_SLUG );
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

			*onClickMoveToCart(): AsyncAction< void > {
				const { listItem, pendingKeys } = getContext< BlockContext >();
				if (
					! listItem ||
					! listItem.is_purchasable ||
					pendingKeys[ listItem.key ]
				) {
					return;
				}

				// Map the schema's `variation` shape to the cart's
				// SelectedAttributes shape. The schema returns the
				// slug-form attribute under `raw_attribute` (e.g.
				// `attribute_pa_color`) plus a display label under
				// `attribute` (e.g. "Color"); the cart matches by the
				// slug-form, so override `attribute` with `raw_attribute`.
				// Same swap mini-cart's `changeQuantity` does. Empty for
				// simple products.
				const variation = listItem.variation.map(
					( { raw_attribute: rawAttribute, value, attribute } ) => ( {
						attribute: rawAttribute || attribute,
						value,
					} )
				);
				const isVariation = listItem.variation_id > 0;

				// `cartActions.addCartItem` catches its own errors and
				// surfaces them as store notices, so the yield resolves
				// the same way on success and failure. Snapshot the
				// matching line's quantity, run the add, then only remove
				// from the saved list if it actually grew.
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
						quantityToAdd: listItem.quantity,
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
				const list = getList( LIST_SLUG );
				if ( list && list.items.length > 0 && ! ctx.hasShownItems ) {
					ctx.hasShownItems = true;
				}
			},

			// Single shared innerHTML-swap callback for any slot whose
			// content is one of the schema's preformatted HTML fields.
			// Mirrors the atomic product-elements `updateValue` callback:
			// the watched element carries `data-wp-context='{"htmlField":"price_html"}'`
			// (or `"image_html"`), and this callback reads that field
			// off the row's `listItem` and pastes its sanitized HTML into
			// `element.ref`. PHP renders the same HTML server-side, so
			// hydration is a no-op when the row's listItem hasn't changed,
			// and a clean swap when it has (e.g. after Remove shifts the
			// next item into this slot).
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
