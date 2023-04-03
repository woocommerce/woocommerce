/**
 * External dependencies
 */
import { sanitize } from 'dompurify';

const ALLOWED_TAGS = [ 'a', 'b', 'em', 'i', 'strong', 'p', 'br' ];
const ALLOWED_ATTR = [ 'target', 'href', 'rel', 'name', 'download' ];

export function sanitizeHTML( html: string ) {
	return {
		__html: sanitize( html, { ALLOWED_TAGS, ALLOWED_ATTR } ),
	};
}
