/**
 * External dependencies
 */
import {
	StrictMode,
	Suspense,
	createElement,
	// @ts-expect-error createRoot is available.
	createRoot,
	lazy,
} from '@wordpress/element';

const ProductsApp = lazy( () =>
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

	root.render(
		<StrictMode>
			<Suspense fallback={ null }>
				<ProductsApp />
			</Suspense>
		</StrictMode>
	);

	return root;
}
