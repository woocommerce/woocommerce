/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { sanitizeHTML as sanitizeHTMLFromPackage } from '@woocommerce/sanitize';

export function isGutenbergVersionAtLeast( version: number ) {
	const adminSettings: { gutenberg_version?: string } = getSetting( 'admin' );
	if ( adminSettings.gutenberg_version ) {
		return parseFloat( adminSettings?.gutenberg_version ) >= version;
	}
	return false;
}

const ALLOWED_TAGS = [
	'a',
	'b',
	'em',
	'i',
	'strong',
	'p',
	'br',
	'code',
	'mark',
	'sub',
	'sup',
	'pre',
	'span',
	'ul',
	'ol',
	'li',
	'blockquote',
	'hr',
];

/**
 * Sanitizes HTML content to ensure it only contains allowed tags and attributes.
 *
 * @param html - The HTML content to sanitize.
 * @return Sanitized HTML content.
 */
export function sanitizeHTML( html: string ) {
	return sanitizeHTMLFromPackage( html, { tags: ALLOWED_TAGS } );
}
