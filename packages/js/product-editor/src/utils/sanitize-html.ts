/**
 * External dependencies
 */
import { sanitize } from 'dompurify';

const ALLOWED_TAGS = [ 'a', 'b', 'em', 'i', 'strong', 'p', 'br', 'abbr' ];
const ALLOWED_ATTR = [ 'target', 'href', 'rel', 'name', 'download', 'title' ];

export function sanitizeHTML(
	html: string,
	config?: { tags?: string[]; attr?: string[] }
) {
	const allowedTags = config?.tags || ALLOWED_TAGS;
	const allowedAttr = config?.attr || ALLOWED_ATTR;

	return {
		__html: sanitize( html, {
			ALLOWED_TAGS: allowedTags,
			ALLOWED_ATTR: allowedAttr,
		} ),
	};
}
