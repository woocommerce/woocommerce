/**
 * External dependencies
 */
import { sanitizeHTML } from '@woocommerce/sanitize';

/**
 * Shared swap for server-preformatted HTML fields (`price_html`,
 * `image_html`, …) that iAPI callbacks paste into the DOM. The allow-lists
 * live here, next to the swap, so tests can pin what survives sanitization
 * without registering the stores.
 */

export type PreformattedHtmlConfig = {
	tags: readonly string[];
	attr: readonly string[];
};

// Bidi isolation (dir) and no-translate (translate) attributes wc_price()
// puts on the currency symbol. Stripping them lets the bidi algorithm move
// an RTL-script symbol to the wrong side of the amount.
const CURRENCY_SYMBOL_ATTR = [ 'dir', 'translate' ] as const;

// Covers what wc_price() (sale/discount markup, currency symbol) and product
// element fields rendered by the product-elements `updateValue` callback emit.
export const PRODUCT_ELEMENT_HTML_CONFIG: PreformattedHtmlConfig = {
	tags: [
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
		'small',
	],
	attr: [
		'class',
		'target',
		'href',
		'rel',
		'name',
		'download',
		'aria-hidden',
		...CURRENCY_SYMBOL_ATTR,
	],
};

// Covers the shopper-list schema's preformatted fields: what wc_price()
// emits for `price_html`, and what `wp_get_attachment_image` /
// `wc_placeholder_img` emit for `image_html` (responsive image + dimensions
// + lazy loading).
export const LIST_ITEM_HTML_CONFIG: PreformattedHtmlConfig = {
	tags: [
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
	],
	attr: [
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
		...CURRENCY_SYMBOL_ATTR,
	],
};

/**
 * Sanitizes a preformatted HTML field and swaps it into the element.
 * No-op when the element is missing or the field is not a string.
 */
export const swapPreformattedHtml = (
	ref: HTMLElement | null | undefined,
	html: unknown,
	config: PreformattedHtmlConfig
): void => {
	if ( ! ref || typeof html !== 'string' ) {
		return;
	}
	ref.innerHTML = sanitizeHTML( html, config );
};
