/**
 * External dependencies
 */
import { StrictMode, Suspense, createRoot, lazy } from '@wordpress/element';
import {
	Root,
	// @ts-expect-error missing types.
} from 'react-dom/client';

/**
 * Internal dependencies
 */
import { AppErrorBoundary } from './app-error-boundary';

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

	if ( ! target ) {
		throw new Error(
			`Could not initialize products dashboard: element with id "${ id }" was not found.`
		);
	}

	const root = createRoot( target );
	root.render(
		<StrictMode>
			<AppErrorBoundary>
				<Suspense fallback={ null }>
					<ProductsApp />
				</Suspense>
			</AppErrorBoundary>
		</StrictMode>
	);

	return root;
}
