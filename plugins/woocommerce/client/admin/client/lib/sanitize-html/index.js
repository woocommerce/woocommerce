/**
 * External dependencies
 */
import { sanitize } from 'dompurify';

export const ALLOWED_TAGS = [ 'a', 'b', 'em', 'i', 'strong', 'p', 'br' ];
export const ALLOWED_ATTR = [ 'target', 'href', 'rel', 'name', 'download' ];

export default ( html ) => {
	return {
		__html: sanitize( html, { ALLOWED_TAGS, ALLOWED_ATTR } ),
	};
};
