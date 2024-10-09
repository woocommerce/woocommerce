/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createElement,
	// @ts-expect-error createRoot is available.
	createRoot,
} from '@wordpress/element';
import React from 'react';

/**
 * Internal dependencies
 */
import { getGutenbergVersion } from './utils/get-gutenberg-version';

const ProductsApp = React.lazy( () =>
	import( './products-app' ).then( ( module ) => ( {
		default: module.ProductsApp,
	} ) )
);

/**
 * Initializes the "Products Dashboard".
 *
 * @param {string} id DOM element id.
 */
export function initializeProductsDashboard( id: string ) {
	const target = document.getElementById( id );
	const root = createRoot( target );
	const isGutenbergEnabled = getGutenbergVersion() > 0;

	root.render(
		<React.StrictMode>
			{ isGutenbergEnabled ? (
				<React.Suspense fallback={ null }>
					<ProductsApp />
				</React.Suspense>
			) : (
				<div>
					{ __(
						'Please enabled Gutenberg for this feature',
						'woocommerce'
					) }
				</div>
			) }
		</React.StrictMode>
	);

	return root;
}
