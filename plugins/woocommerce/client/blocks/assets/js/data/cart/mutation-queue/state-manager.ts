/**
 * StateManager Adapter
 *
 * This module provides the bridge between the MutationQueue and the WooCommerce
 * cart store. It handles:
 * - Getting and applying cart state
 * - Sending API requests
 * - Displaying errors to users
 */

/**
 * External dependencies
 */
import type { Cart, CartResponse } from '@woocommerce/types';
import { camelCaseKeys, createNotice } from '@woocommerce/base-utils';
import { decodeEntities } from '@wordpress/html-entities';
import {
	type CurriedSelectorsOf,
	type ConfigOf,
	type ActionCreatorsOf,
} from '@wordpress/data/build-types/types';
import { cartStore } from '@woocommerce/block-data';
import { dispatch as wpDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import type { StateManager, MutationRequest, BatchResponse } from './types';
import { apiFetchWithHeaders } from '../../shared-controls';
import { setTriggerStoreSyncEvent } from '../utils';

/**
 * Type for cart store dispatch.
 */
type CartDispatch = ActionCreatorsOf< ConfigOf< typeof cartStore > >;

/**
 * Type for cart store select.
 */
type CartSelect = CurriedSelectorsOf< typeof cartStore >;

/**
 * Context string for cart error notices.
 */
const CART_NOTICE_CONTEXT = 'wc/cart';

/**
 * ID prefix for mutation queue error notices.
 */
const ERROR_NOTICE_ID_PREFIX = 'mutation-queue-error-';

/**
 * Create a StateManager adapter for the WooCommerce cart store.
 *
 * @param dispatch Cart store dispatch actions.
 * @param select   Cart store selectors.
 * @return StateManager implementation.
 */
export function createCartStateManager(
	dispatch: CartDispatch,
	select: CartSelect
): StateManager {
	/**
	 * Track active error notice IDs for cleanup.
	 */
	let activeErrorIds: string[] = [];

	return {
		/**
		 * Get a snapshot of the current cart state.
		 */
		getSnapshot(): Cart {
			const cartData = select.getCartData();
			// Deep clone to ensure true snapshot (prevents mutations)
			return JSON.parse( JSON.stringify( cartData ) );
		},

		/**
		 * Apply an optimistic update to the cart state.
		 * This updates the store immediately before server response.
		 */
		applyOptimisticUpdate( update: ( state: Cart ) => Cart ): void {
			const currentState = select.getCartData();
			const newState = update( currentState );
			dispatch.setCartData( newState );
		},

		/**
		 * Apply the server-returned state to the cart store.
		 * Converts snake_case response to camelCase and updates store.
		 */
		applyServerState( state: Cart ): void {
			// Convert from snake_case (API response) to camelCase (store format)
			// Note: If state is already in camelCase (from getSnapshot), this is a no-op identity
			const cartData =
				'items' in state && ! ( 'shippingRates' in state )
					? ( camelCaseKeys(
							state as unknown as CartResponse
					  ) as unknown as Cart )
					: state;

			// Disable sync event to prevent infinite loops with Interactivity API store
			setTriggerStoreSyncEvent( false );
			dispatch.setCartData( cartData );
			setTriggerStoreSyncEvent( true );
		},

		/**
		 * Send a request to the server.
		 * Handles batch requests to the Store API.
		 */
		async sendRequest< T >( request: MutationRequest ): Promise< T > {
			try {
				const fetchOptions: {
					path: string;
					method: string;
					data: unknown;
					cache: 'no-store';
					headers?: Record< string, string >;
				} = {
					path: request.path,
					method: request.method,
					data: request.body,
					cache: 'no-store',
				};

				// Only add headers if defined
				if ( request.headers ) {
					fetchOptions.headers = request.headers;
				}

				const { response } = await apiFetchWithHeaders< {
					response: BatchResponse[ 'data' ];
				} >( fetchOptions );

				// The batch endpoint returns { responses: [...] }
				// We need to convert this to our BatchResponse format
				if ( response && 'responses' in response ) {
					return {
						success: true,
						data: response,
					} as T;
				}

				// Direct response (not a batch)
				return {
					success: true,
					data: { responses: [ { status: 200, body: response } ] },
				} as T;
			} catch ( error: unknown ) {
				// Handle API error responses
				if ( error && typeof error === 'object' && 'code' in error ) {
					return {
						success: false,
						error: new Error(
							( error as { message?: string } ).message ||
								'Request failed'
						),
					} as T;
				}

				// Handle network or other errors
				return {
					success: false,
					error:
						error instanceof Error
							? error
							: new Error( String( error ) ),
				} as T;
			}
		},

		/**
		 * Display accumulated errors to the user.
		 * Creates notices in the WooCommerce notice system.
		 */
		showErrors( errors: Error[] ): void {
			// Clear previous error notices from this system
			const { removeNotice } = wpDispatch( noticesStore );
			activeErrorIds.forEach( ( id ) => {
				removeNotice( id, CART_NOTICE_CONTEXT );
			} );
			activeErrorIds = [];

			// Create new notices for each unique error
			const seenMessages = new Set< string >();

			errors.forEach( ( error, index ) => {
				const message = error.message;

				// Skip duplicate error messages
				if ( seenMessages.has( message ) ) {
					return;
				}
				seenMessages.add( message );

				const noticeId = `${ ERROR_NOTICE_ID_PREFIX }${ index }-${ Date.now() }`;
				activeErrorIds.push( noticeId );

				createNotice( 'error', decodeEntities( message ), {
					id: noticeId,
					context: CART_NOTICE_CONTEXT,
				} );
			} );
		},
	};
}

/**
 * Create a StateManager for testing purposes with mock implementations.
 *
 * @param overrides Partial StateManager to override defaults.
 * @return StateManager with mocks.
 */
export function createMockStateManager(
	overrides: Partial< StateManager > = {}
): StateManager {
	const mockCart: Cart = {
		items: [],
		coupons: [],
		shippingRates: [],
		shippingAddress: {
			first_name: '',
			last_name: '',
			company: '',
			address_1: '',
			address_2: '',
			city: '',
			state: '',
			postcode: '',
			country: '',
			phone: '',
		},
		billingAddress: {
			first_name: '',
			last_name: '',
			company: '',
			address_1: '',
			address_2: '',
			city: '',
			state: '',
			postcode: '',
			country: '',
			phone: '',
			email: '',
		},
		itemsCount: 0,
		itemsWeight: 0,
		crossSells: [],
		needsPayment: false,
		needsShipping: false,
		hasCalculatedShipping: false,
		fees: [],
		totals: {
			currency_code: 'USD',
			currency_symbol: '$',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '$',
			currency_suffix: '',
			total_items: '0',
			total_items_tax: '0',
			total_fees: '0',
			total_fees_tax: '0',
			total_discount: '0',
			total_discount_tax: '0',
			total_shipping: '0',
			total_shipping_tax: '0',
			total_price: '0',
			total_tax: '0',
			tax_lines: [],
		},
		errors: [],
		paymentMethods: [],
		paymentRequirements: [],
		extensions: {},
	};

	let currentState = { ...mockCart };

	return {
		getSnapshot: () => JSON.parse( JSON.stringify( currentState ) ),
		applyOptimisticUpdate: ( update ) => {
			currentState = update( currentState );
		},
		applyServerState: ( state ) => {
			currentState = state;
		},
		sendRequest: async () =>
			( { success: true, data: { responses: [] } } as never ),
		// eslint-disable-next-line @typescript-eslint/no-empty-function
		showErrors: () => {},
		...overrides,
	};
}
