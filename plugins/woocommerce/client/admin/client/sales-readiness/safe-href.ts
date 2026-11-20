/**
 * A link from the view data, or undefined when its scheme isn't http or https. Relative links
 * stay, so a `javascript:` or `data:` URL in stored data never reaches an href. The URL parser
 * lowercases the scheme, so HTTPS:// is kept as it was written.
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
		return protocol === 'http:' || protocol === 'https:'
			? trimmed
			: undefined;
	} catch {
		return undefined;
	}
};
