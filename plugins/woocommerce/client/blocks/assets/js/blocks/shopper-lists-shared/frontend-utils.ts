/**
 * External dependencies
 */
import { getContext, getElement } from '@wordpress/interactivity';
import type { AsyncAction } from '@wordpress/interactivity';
import type {
	RawShopperListItem,
	Store as ShopperListsStore,
} from '@woocommerce/stores/woocommerce/shopper-lists';
import { sanitizeHTML } from '@woocommerce/sanitize';

/**
 * Shared frontend helpers for the shopper-list blocks (Wishlist and Saved for
 * Later). Both blocks render an identical per-row shell over the
 * `woocommerce/shopper-lists` store — the only differences are their list slug,
 * their config keys, and a couple of block-specific getters/actions. Everything
 * they share verbatim lives here so the two `frontend.ts` files import one copy.
 */

/**
 * The subset of a shopper-list block's row context that the shared helpers read.
 * Each block extends this with its own fields (e.g. Saved for Later's
 * `hasShownItems`).
 */
export type SharedListBlockContext = {
	listItem?: RawShopperListItem;
	htmlField?: 'price_html' | 'image_html';
	// Item keys currently mid-mutation, used to disable per-row buttons.
	pendingKeys: Record< string, true >;
};

/**
 * Allow-list for sanitizing the schema's preformatted strings on innerHTML swap.
 * Covers what `wc_price` (sale/discount markup, currency symbol) and
 * `wp_get_attachment_image` / `wc_placeholder_img` emit (responsive image +
 * dimensions + lazy loading).
 */
export const ALLOWED_TAGS = [
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
export const ALLOWED_ATTR = [
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

/**
 * Decode HTML entities in a schema string via a detached textarea (the standard
 * `data-wp-text`-safe decode). `data-wp-text` writes text-content without
 * decoding, so bind display getters to the decoded value.
 *
 * @param encoded The entity-encoded string.
 * @return The decoded string.
 */
export const decodeEntities = ( encoded: string ): string => {
	const txt = document.createElement( 'textarea' );
	txt.innerHTML = encoded;
	return txt.value;
};

/**
 * Format a list item's variation attributes as a comma-separated
 * "Attribute: Value" label, decoding entities on both sides.
 *
 * @param item The list row.
 * @return The variation label, or '' when the item has no variation.
 */
export const formatVariationLabel = ( item: RawShopperListItem ): string => {
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

/**
 * Read a list from the shopper-lists store by slug.
 *
 * @param shopperListsState The shopper-lists store state.
 * @param slug              The list slug.
 * @return The list state, or `null` when not present.
 */
export const getList = (
	shopperListsState: ShopperListsStore[ 'state' ],
	slug: string
) => shopperListsState.lists[ slug ] ?? null;

/**
 * Map a list row's `variation` (schema shape) to the cart's `SelectedAttributes`
 * shape. The schema returns the slug-form attribute under `raw_attribute` (e.g.
 * `attribute_pa_color`) plus a display label under `attribute` (e.g. "Color");
 * we override `attribute` with `raw_attribute` here. Either form resolves — see
 * the schema's "Canonical variation-payload shape (`SelectedAttributes`)" note:
 * `findProduct`'s matcher absorbs both the label and slug/`raw_attribute` forms.
 * Empty for simple products.
 *
 * @param item The list row.
 * @return The cart add-item `variation` array.
 */
export const mapListItemVariation = (
	item: RawShopperListItem
): Array< { attribute: string; value: string } > =>
	item.variation.map(
		( { raw_attribute: rawAttribute, value, attribute } ) => ( {
			attribute: rawAttribute || attribute,
			value,
		} )
	);

/**
 * The single shared innerHTML-swap callback for any slot whose content is one of
 * the schema's preformatted HTML fields. Mirrors the atomic product-elements
 * `updateValue` callback: the watched element carries
 * `data-wp-context='{"htmlField":"price_html"}'` (or `"image_html"`), and this
 * reads that field off the row's `listItem` and pastes its sanitized HTML into
 * `element.ref`. PHP renders the same HTML server-side, so hydration is a no-op
 * when the row's listItem hasn't changed, and a clean swap when it has (e.g.
 * after Remove shifts the next item into this slot).
 */
export const updateInnerHtml = (): void => {
	const { ref } = getElement();
	const { listItem, htmlField } = getContext< SharedListBlockContext >();
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
};

/**
 * Build the shared per-row "Remove" action generator for a given list slug. It
 * guards against double-mutation via the context's `pendingKeys` map and removes
 * the row from the shopper-lists store.
 *
 * @param shopperListsActions The shopper-lists store actions.
 * @param listSlug            The list slug this block operates on.
 * @return A generator action bound to the slug.
 */
export const createOnClickRemove = (
	shopperListsActions: ShopperListsStore[ 'actions' ],
	listSlug: string
) =>
	function* onClickRemove(): AsyncAction< void > {
		const { listItem, pendingKeys } =
			getContext< SharedListBlockContext >();
		if ( ! listItem || pendingKeys[ listItem.key ] ) {
			return;
		}
		pendingKeys[ listItem.key ] = true;
		try {
			yield shopperListsActions.removeItem( listSlug, listItem.key );
		} finally {
			delete pendingKeys[ listItem.key ];
		}
	};
