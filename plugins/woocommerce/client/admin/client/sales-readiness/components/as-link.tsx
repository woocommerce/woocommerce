/**
 * Internal dependencies
 */
import { safeHref } from '../safe-href';

/**
 * Props that make a @wordpress/ui Button a link, or that disable it when the URL is rejected.
 * This version of the package has no ButtonLink.
 *
 * @param href    Link from the view.
 * @param onClick Called when the link is followed, such as to record it.
 */
export const asLink = ( href: string, onClick?: () => void ) => {
	const safe = safeHref( href );
	if ( ! safe ) {
		return { disabled: true };
	}
	return {
		nativeButton: false as const,
		role: 'link' as const,
		onClick,
		// The Button renders the text into it.
		// eslint-disable-next-line jsx-a11y/anchor-has-content
		render: <a href={ safe } />,
	};
};
