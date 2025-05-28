/**
 * External dependencies
 */
import { sanitize } from 'dompurify';

/**
 * Default allowed HTML tags for extended sanitization.
 */
export const EXTENDED_ALLOWED_TAGS = [
	'a',
	'b',
	'blockquote',
	'br',
	'button',
	'cite',
	'code',
	'dd',
	'div',
	'dl',
	'dt',
	'em',
	'h1',
	'h2',
	'h3',
	'h4',
	'h5',
	'h6',
	'hr',
	'i',
	'img',
	'li',
	'mark',
	'ol',
	'p',
	'pre',
	'small',
	'span',
	'strong',
	'sub',
	'sup',
	'table',
	'tbody',
	'td',
	'th',
	'thead',
	'tr',
	'ul',
];

/**
 * Default allowed HTML attributes for extended sanitization.
 */
export const EXTENDED_ALLOWED_ATTR = [
	'alt',
	'border',
	'class',
	'download',
	'href',
	'id',
	'height',
	'name',
	'rel',
	'role',
	'sizes',
	'src',
	'srcset',
	'style',
	'target',
	'title',
	'width',
];

/**
 * Extended HTML sanitization with a broader allowlist of tags and attributes.
 * Also allows passing custom allow lists in a config object.
 * Useful for rich content areas that need more formatting options.
 *
 * @param {string}   html                     The HTML to sanitize.
 * @param {Object}   config                   Optional configuration to extend/override defaults.
 * @param {string[]} config.allowedTags       Array of allowed HTML tags.
 * @param {Object}   config.allowedAttributes Object of allowed attributes by tag.
 * @return {Object} Object with sanitized HTML in _html property.
 */
export default function sanitizeHtmlExtended( html, config = {} ) {
	if ( ! html ) {
		return '';
	}

	const finalConfig = {
		ALLOWED_TAGS: config.allowedTags || EXTENDED_ALLOWED_TAGS,
		ALLOWED_ATTR: config.allowedAttributes || EXTENDED_ALLOWED_ATTR,
	};

	return {
		__html: sanitize( html, finalConfig ),
	};
}
