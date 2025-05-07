/**
 * Extended list of tags and attributes for sanitizing product preview HTML.
 * Dompurify automatically allows data and aria attributes.
 */

/**
 * Internal dependencies
 */
import {
	EXTENDED_ALLOWED_TAGS,
	EXTENDED_ALLOWED_ATTR,
} from '~/lib/sanitize-html/sanitize-html-extended.js';

const sanitizeHtmlConfig = {
	allowedTags: [
		...EXTENDED_ALLOWED_TAGS,
		'path',
		'svg',
		'footer',
		'header',
	],
	allowedAttributes: [
		...EXTENDED_ALLOWED_ATTR,
		'd',
		'fill',
		'viewBox',
		'xmlns',
	],
};

export default sanitizeHtmlConfig;
