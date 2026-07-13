/**
 * External dependencies
 */
import { StrictMode, Suspense, createRoot, lazy } from '@wordpress/element';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { ThemeProvider } from '@wordpress/theme';

/**
 * Internal dependencies
 */
import { unlock } from './lock-unlock';

const { RouterProvider } = unlock( routerPrivateApis );

const VariationView = lazy( () =>
	import(
		/* webpackChunkName: "experimental-products-app-variation-view-main" */
		'./variation-view'
	).then( ( module ) => ( {
		default: module.VariationView,
	} ) )
);

const ProductAttributes = lazy( () =>
	import(
		/* webpackChunkName: "experimental-products-app-variation-view-main" */
		'./variation-view'
	).then( ( module ) => ( {
		default: module.ProductAttributes,
	} ) )
);

// The variations redesign mounts into separate PHP-provided metabox roots,
// but each root needs the same app providers.
function renderWithProviders(
	containerId: string,
	children: JSX.Element
): void {
	const target = document.getElementById( containerId );

	if ( ! target ) {
		return undefined;
	}

	const root = createRoot( target );
	root.render(
		<StrictMode>
			<Suspense fallback={ null }>
				<RouterProvider>
					<ThemeProvider>{ children }</ThemeProvider>
				</RouterProvider>
			</Suspense>
		</StrictMode>
	);
}

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
	renderWithProviders(
		containerId,
		<VariationView productId={ productId } />
	);
}

/**
 * Initializes the product data attributes panel for the variations redesign.
 * This is mounted separately from the variations view because the attributes
 * panel has its own metabox DOM root.
 *
 * @param {string} containerId DOM element ID.
 * @param {number} productId   Parent product ID.
 */
export function initializeProductAttributesView(
	containerId: string,
	productId: number
): void {
	renderWithProviders(
		containerId,
		<ProductAttributes productId={ productId } />
	);
}
