/**
 * External dependencies
 */
import type { Cart } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { MutationRequest, StateManager } from './types';

/**
 * Configuration options for creating a CartStateManager.
 */
export interface CartStateManagerConfig {
	/**
	 * Function to get the current cart state.
	 */
	getState: () => Cart;

	/**
	 * Function to set the cart state.
	 */
	setState: ( state: Cart ) => void;

	/**
	 * The REST API base URL.
	 */
	restUrl: string;

	/**
	 * Function to get the current nonce for API authentication.
	 */
	getNonce: () => string;

	/**
	 * Function to display errors to the user.
	 */
	showErrors: ( errors: Error[] ) => void;
}

/**
 * Creates a StateManager adapter for the cart store.
 *
 * This adapter bridges the MutationQueue with the existing cart store,
 * providing the necessary methods for state management and API requests.
 *
 * @param config Configuration options for the state manager.
 * @return A StateManager implementation.
 */
export function createCartStateManager(
	config: CartStateManagerConfig
): StateManager {
	const { getState, setState, restUrl, getNonce, showErrors } = config;

	return {
		/**
		 * Get a snapshot of the current cart state.
		 */
		getSnapshot(): Cart {
			return getState();
		},

		/**
		 * Apply an optimistic update to the cart state.
		 */
		applyOptimisticUpdate( update: ( state: Cart ) => Cart ): void {
			const currentState = getState();
			const newState = update( currentState );
			setState( newState );
		},

		/**
		 * Apply the authoritative server state to the cart.
		 */
		applyServerState( state: Cart ): void {
			setState( state );
		},

		/**
		 * Send a request to the server.
		 */
		async sendRequest< T >( request: MutationRequest ): Promise< T > {
			const url = `${ restUrl }${ request.path.replace( /^\//, '' ) }`;

			const response = await fetch( url, {
				method: request.method,
				headers: {
					'Content-Type': 'application/json',
					Nonce: getNonce(),
					...request.headers,
				},
				body: JSON.stringify( request.body ),
			} );

			const data = await response.json();

			if ( ! response.ok ) {
				const errorMessage =
					data?.message ||
					`Request failed with status ${ response.status }`;
				throw new Error( errorMessage );
			}

			return data as T;
		},

		/**
		 * Display accumulated errors to the user.
		 */
		showErrors,
	};
}
