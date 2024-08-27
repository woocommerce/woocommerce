/**
 * External dependencies
 */
import {
	StrictMode,
	createElement,
	// @ts-expect-error createRoot is available.
	createRoot,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import ProductsApp from './products-app';

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
			<ProductsApp />
		</StrictMode>
	);

	return root;
}
