/**
 * External dependencies
 */
import { sanitizeHTML as sanitizeHTMLFromPackage } from '@woocommerce/sanitize';

export const sanitizeHTML = (
	html: string,
	config?: { tags?: string[]; attr?: string[] }
) => {
	return sanitizeHTMLFromPackage( html, config );
};
