/**
 * External dependencies
 */
import { sanitizeHTML as sanitizeHTMLFromPackage } from '@woocommerce/sanitize';

export function sanitizeHTML(
	html: string,
	config?: { tags?: string[]; attr?: string[] }
): { __html: string } {
	return {
		__html: sanitizeHTMLFromPackage( html, config ),
	};
}
