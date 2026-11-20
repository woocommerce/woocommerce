/**
 * A link from the view data, or undefined when its scheme isn't http or https. Relative links
 * stay, so a `javascript:` or `data:` URL in stored data never reaches an href.
 *
 * @param href Link from the view.
 */
export const safeHref = ( href: string | null | undefined ) => {
	if ( ! href ) {
		return undefined;
	}
	try {
		const { protocol } = new URL( href, window.location.href );
		return protocol === 'http:' || protocol === 'https:' ? href : undefined;
	} catch {
		return undefined;
	}
};
