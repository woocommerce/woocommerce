/**
 * A link from the view data, or undefined when its scheme isn't http or https. Relative links
 * stay, so a `javascript:` or `data:` URL in stored data never reaches an href. The scheme is
 * compared without regard to case, and written back in lowercase: PHP's HTTP stream wrapper
 * skips TLS for a mixed-case scheme such as HTTPS://.
 *
 * @param href Link from the view.
 */
export const safeHref = ( href: string | null | undefined ) => {
	const trimmed = href?.trim();
	if ( ! trimmed ) {
		return undefined;
	}
	try {
		const { protocol } = new URL( trimmed, window.location.href );
		if ( protocol !== 'http:' && protocol !== 'https:' ) {
			return undefined;
		}
		const colon = trimmed.indexOf( ':' );
		const scheme = colon > 0 ? trimmed.slice( 0, colon ) : '';
		// A colon in a query or path is not a scheme. Relative links are returned as given.
		if (
			! scheme ||
			scheme.includes( '/' ) ||
			scheme.includes( '?' ) ||
			scheme.includes( '#' )
		) {
			return trimmed;
		}
		return protocol + trimmed.slice( colon + 1 );
	} catch {
		return undefined;
	}
};
