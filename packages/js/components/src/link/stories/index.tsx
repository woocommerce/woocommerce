/**
 * External dependencies
 */
import { withConsole } from '@storybook/addon-console';
import { createElement } from '@wordpress/element';
import React from 'react';

/**
 * Internal dependencies
 */
import Link from '..';

function logLinkClick( event ) {
	const a = event.currentTarget;
	const logMessage = `[${ a.textContent }](${ a.href }) ${ a.dataset.linkType } link clicked`;

	// eslint-disable-next-line no-console
	console.log( logMessage );

	event.preventDefault();
	return false;
}

export default {
	title: 'WooCommerce Admin/components/Link',
	component: Link,
	decorators: [ ( storyFn, context ) => withConsole()( storyFn )( context ) ],
};

export const External = () => {
	return (
		<Link href="https://woo.com" type="external" onClick={ logLinkClick }>
			WooCommerce.com
		</Link>
	);
};

export const WCAdmin = () => {
	return (
		<Link
			href="admin.php?page=wc-admin&path=%2Fanalytics%2Forders"
			type="wc-admin"
			onClick={ logLinkClick }
		>
			Analytics: Orders
		</Link>
	);
};

export const WPAdmin = () => {
	return (
		<Link
			href="post-new.php?post_type=product"
			type="wp-admin"
			onClick={ logLinkClick }
		>
			New Product
		</Link>
	);
};
