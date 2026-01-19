/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { ProductResponseItem } from '../../../types';

/**
 * Quantity constraints normalized from the Store API format.
 */
export type QuantityConstraints = {
	min: number;
	max: number;
	step: number;
};

/**
 * The state shape for the products store.
 * This matches the server-side ProductsStore state structure.
 */
export type ProductsStoreState = {
	/**
	 * Products keyed by product ID.
	 * These are in Store API format (ProductResponseItem).
	 */
	products: Record< number, ProductResponseItem >;
};

/**
 * API error response shape.
 */
type ApiErrorResponse = {
	code: string;
	message: string;
	data?: {
		status: number;
	};
};

/**
 * Check if response is an API error.
 */
function isApiErrorResponse(
	response: Response,
	json: unknown
): json is ApiErrorResponse {
	return ! response.ok;
}

/**
 * Server state that may be hydrated.
 */
type ServerState = Partial< ProductsStoreState >;

/**
 * The products store type definition.
 * Action types are simplified - the actual implementations are generators
 * but the Interactivity API wraps them as async functions.
 */
export type ProductsStore = {
	state: ProductsStoreState & ServerState;
	actions: {
		loadProduct: ( productId: number ) => void;
	};
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// Track in-flight requests to avoid duplicate fetches.
const pendingProductRequests = new Map< number, Promise< ProductResponseItem | null > >();

/**
 * The woocommerce/products store.
 *
 * This store manages product data in Store API format for use with the
 * Interactivity API. It supports both server-side hydration (via PHP
 * ProductsStore) and client-side loading.
 *
 * State structure:
 * - products: Record<productId, ProductResponseItem>
 */
// We need to access the store result to use state inside actions.
// TypeScript has trouble with the circular reference, so we use a
// two-step initialization pattern.
let state: ProductsStore[ 'state' ];

const storeResult = store< ProductsStore >(
	'woocommerce/products',
	{
		state: {
			products: {},
		},
		actions: {
			*loadProduct( productId: number ) {
				// Return from cache if already loaded (including SSR).
				if ( state.products[ productId ] ) {
					return state.products[ productId ];
				}

				// If there's already a request in flight for this product, wait for it.
				const pendingRequest = pendingProductRequests.get( productId );
				if ( pendingRequest ) {
					return ( yield pendingRequest ) as ProductResponseItem | null;
				}

				// Create the fetch promise and track it.
				const fetchPromise = ( async () => {
					try {
						const response = await fetch(
							`/wp-json/wc/store/v1/products/${ productId }`,
							{
								method: 'GET',
								headers: {
									'Content-Type': 'application/json',
								},
							}
						);

						const json = await response.json();

						if ( isApiErrorResponse( response, json ) ) {
							throw new Error(
								`Failed to load product ${ productId }: ${ ( json as ApiErrorResponse ).message }`
							);
						}

						// Store the product.
						state.products[ productId ] =
							json as ProductResponseItem;

						return json as ProductResponseItem;
					} finally {
						// Clean up the pending request.
						pendingProductRequests.delete( productId );
					}
				} )();

				pendingProductRequests.set( productId, fetchPromise );

				return ( yield fetchPromise ) as ProductResponseItem | null;
			},
		},
	},
	{ lock: universalLock }
);

// Assign from store result after initialization.
state = storeResult.state;
const { actions } = storeResult;

/**
 * Re-export the store with both state and actions for consumers
 * who want to access it as a single object.
 */
const productsStore = { state, actions };

/**
 * Get a product from state by ID.
 * Returns null if the product is not loaded.
 *
 * @param productId The product ID.
 * @return The product or null.
 */
export const getProduct = (
	productId: number
): ProductResponseItem | null => {
	return state.products[ productId ] ?? null;
};

/**
 * Extract quantity constraints from a product in Store API format.
 *
 * @param product The product in Store API format.
 * @return Normalized quantity constraints.
 */
export const getQuantityConstraints = (
	product: ProductResponseItem | null
): QuantityConstraints => {
	if ( ! product ) {
		return { min: 1, max: Number.MAX_SAFE_INTEGER, step: 1 };
	}

	const addToCart = product.add_to_cart;
	const maximum = addToCart?.maximum ?? 0;

	return {
		min: addToCart?.minimum ?? 1,
		max: maximum > 0 ? maximum : Number.MAX_SAFE_INTEGER,
		step: addToCart?.multiple_of ?? 1,
	};
};

/**
 * Check if a product is purchasable (can be added to cart).
 *
 * @param product The product in Store API format.
 * @return True if purchasable.
 */
export const isPurchasable = (
	product: ProductResponseItem | null
): boolean => {
	if ( ! product ) {
		return false;
	}
	return product.is_purchasable && product.is_in_stock;
};

export { state, actions, productsStore };
export default productsStore;
