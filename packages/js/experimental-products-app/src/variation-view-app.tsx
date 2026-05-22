/**
 * External dependencies
 */
import { StrictMode, Suspense, createRoot, lazy } from '@wordpress/element';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { privateApis as themeProviderPrivateApis } from '@wordpress/theme';

/**
 * Internal dependencies
 */
import { unlock } from './lock-unlock';

const { RouterProvider } = unlock( routerPrivateApis );
const { ThemeProvider } = unlock( themeProviderPrivateApis );

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
 * Initializes the classic product editor product attributes view.
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
