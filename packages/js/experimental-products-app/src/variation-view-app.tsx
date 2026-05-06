/**
 * External dependencies
 */
import { StrictMode, Suspense, createRoot, lazy } from '@wordpress/element';

const VariationView = lazy( () =>
	import(
		/* webpackChunkName: "experimental-products-app-variation-view-main" */
		'./variation-view'
	).then( ( module ) => ( {
		default: module.VariationView,
	} ) )
);

/**
 * Initializes the classic product editor variation view.
 *
 * @param {string} containerId DOM element ID.
 * @param {number} productId   Parent product ID.
 */
export function initializeVariationView(
	containerId: string,
	productId: number
): void {
	const target = document.getElementById( containerId );

	if ( ! target ) {
		return undefined;
	}

	const root = createRoot( target );
	root.render(
		<StrictMode>
			<Suspense fallback={ null }>
				<VariationView productId={ productId } />
			</Suspense>
		</StrictMode>
	);
}
