/**
 * External dependencies
 */
import {
	StrictMode,
	Suspense,
	createElement,
	createRoot,
	lazy,
} from '@wordpress/element';
import {
	Root,
	// @ts-expect-error missing types.
} from 'react-dom/client';

/**
 * Internal dependencies
 */

const ProductsApp = lazy( () =>
	import( './app' ).then( ( module ) => ( {
		default: module.ProductsApp,
	} ) )
);

/**
 * Initializes the "Products Dashboard".
 *
 * @param {string} id DOM element id.
 */
export function initializeProductsDashboard( id: string ): Root {
	const target = document.getElementById( id );
	const root = createRoot( target! );
	root.render(
		<StrictMode>
			<Suspense fallback={ null }>
				<ProductsApp />
			</Suspense>
		</StrictMode>
	);

	return root;
}
