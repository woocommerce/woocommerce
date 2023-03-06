/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { partial } from 'lodash';
import { createElement } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';
import React from 'react';

interface LinkProps {
	/** Type of link. For wp-admin and wc-admin, the correct prefix is appended. */
	type?: 'wc-admin' | 'wp-admin' | 'external';
	/** The resource to link to. */
	href: string;
}
// eslint-disable-next-line @typescript-eslint/no-explicit-any
type LinkOnClickHandlerFunction = ( ...props: any ) => any; // we don't want to restrict this function at all
type LinkOnclickHandler = (
	onClick: LinkOnClickHandlerFunction | undefined,
	event: React.MouseEvent< Element > | undefined
) => void;

/**
 * Use `Link` to create a link to another resource. It accepts a type to automatically
 * create wp-admin links, wc-admin links, and external links.
 */
export const Link = ( {
	href,
	children,
	type = 'wc-admin',
	...props
}: React.AnchorHTMLAttributes< HTMLAnchorElement > &
	LinkProps ): React.ReactElement => {
	// ( { children, href, type, ...props } ) => {
	// @todo Investigate further if we can use <Link /> directly.
	// With React Router 5+, <RouterLink /> cannot be used outside of the main <Router /> elements,
	// which seems to include components imported from @woocommerce/components. For now, we can use the history object directly.
	const wcAdminLinkHandler: LinkOnclickHandler = ( onClick, event ) => {
		// If cmd, ctrl, alt, or shift are used, use default behavior to allow opening in a new tab.
		if (
			event?.ctrlKey ||
			event?.metaKey ||
			event?.altKey ||
			event?.shiftKey
		) {
			return;
		}

		event?.preventDefault();

		// If there is an onclick event, execute it.
		const onClickResult = onClick && event ? onClick( event ) : true;

		// Mimic browser behavior and only continue if onClickResult is not explicitly false.
		if ( onClickResult === false ) {
			return;
		}

		if ( event?.target instanceof Element ) {
			const closestEventTarget = event.target
				.closest( 'a' )
				?.getAttribute( 'href' );
			if ( closestEventTarget ) {
				getHistory().push( closestEventTarget );
			} else {
				// eslint-disable-next-line no-console
				console.error(
					'@woocommerce/components/link is trying to push an undefined state into navigation stack'
				); // This shouldn't happen as we wrap with <a> below
			}
		}
	};

	const passProps = {
		...props,
		'data-link-type': type,
	};

	if ( type === 'wc-admin' ) {
		passProps.onClick = partial( wcAdminLinkHandler, passProps.onClick );
	}

	return (
		<a href={ href } { ...passProps }>
			{ children }
		</a>
	);
};

Link.contextTypes = {
	router: PropTypes.object,
};

export default Link;
