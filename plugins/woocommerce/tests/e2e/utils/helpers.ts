/**
 * External dependencies
 */
import crypto from 'crypto';

const random = ( size = 4 ) => {
	return crypto.randomBytes( size ).toString( 'hex' );
};

/**
 * Wrap a regular expression pattern in a matching pair of straight or curly
 * double quotes.
 *
 * Since WordPress 7.2, kses decodes `&quot;` in notice text, so `wptexturize`
 * curls the quotes when a block template renders the notice.
 *
 * @param {string} pattern The regular expression pattern to wrap.
 */
const quotedPattern = ( pattern: string ) => {
	return `(?:"(?:${ pattern })"|“(?:${ pattern })”)`;
};

export { random, quotedPattern };
