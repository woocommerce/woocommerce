/**
 * External dependencies
 */
import DOMPurify from 'dompurify';

/**
 * Internal dependencies
 */
import { initializeTrustedTypesPolicy } from './trusted-types-policy';

/**
 * Default allowed HTML tags for basic sanitization.
 */
export const DEFAULT_ALLOWED_TAGS = [
	'a',
	'b',
	'em',
	'i',
	'strong',
	'p',
	'br',
	'abbr',
] as const;

/**
 * Default allowed HTML attributes for basic sanitization.
 */
export const DEFAULT_ALLOWED_ATTR = [
	'target',
	'href',
	'rel',
	'name',
	'download',
	'title',
] as const;

/**
 * Configuration options for HTML sanitization.
 */
export interface SanitizeConfig {
	/** Allowed HTML tags */
	tags?: readonly string[];
	/** Allowed HTML attributes */
	attr?: readonly string[];
}

/**
 * Sanitizes HTML content using DOMPurify with default allowed tags and attributes.
 *
 * @param html   - The HTML content to sanitize.
 * @param config - Optional configuration for allowed tags and attributes.
 * @return Sanitized HTML content.
 */
export function sanitizeHTML( html: string, config?: SanitizeConfig ): string {
	const allowedTags = config?.tags || DEFAULT_ALLOWED_TAGS;
	const allowedAttr = config?.attr || DEFAULT_ALLOWED_ATTR;

	return DOMPurify.sanitize( html, {
		ALLOWED_TAGS: [ ...allowedTags ],
		ALLOWED_ATTR: [ ...allowedAttr ],
	} );
}

// Initialize trusted types policy when the module is loaded.
initializeTrustedTypesPolicy();

export { TRUSTED_POLICY_NAME } from './trusted-types-policy';
