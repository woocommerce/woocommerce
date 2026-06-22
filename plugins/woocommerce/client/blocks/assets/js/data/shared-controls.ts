/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import triggerFetch, { APIFetchOptions } from '@wordpress/api-fetch';
import DataLoader from 'dataloader';
import {
	ApiResponse,
	assertBatchResponseIsValid,
	assertResponseIsValid,
} from '@woocommerce/types';

const EMPTY_OBJECT = {};

/**
 * Error thrown when JSON cannot be parsed.
 */
const invalidJsonError = {
	code: 'invalid_json',
	message: __( 'The response is not a valid JSON response.', 'woocommerce' ),
};

const processHeadersOnFetch = ( headers: Headers ): void => {
	if (
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- this does exist because it's monkey patched in
		// middleware/store-api-nonce.
		triggerFetch.setNonce &&
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- this does exist because it's monkey patched in
		// middleware/store-api-nonce.
		typeof triggerFetch.setNonce === 'function'
	) {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- this does exist because it's monkey patched in
		// middleware/store-api-nonce.
		triggerFetch.setNonce( headers );
	} else {
		// eslint-disable-next-line no-console
		console.error(
			'The monkey patched function on APIFetch, "setNonce", is not present, likely another plugin or some other code has removed this augmentation'
		);
	}
	if (
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- this does exist because it's monkey patched in
		// middleware/store-api-cart-hash.
		triggerFetch.setCartHash &&
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- this does exist because it's monkey patched in
		// middleware/store-api-cart-hash.
		typeof triggerFetch?.setCartHash === 'function'
	) {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore -- this does exist because it's monkey patched in
		// middleware/store-api-cart-hash.
		triggerFetch.setCartHash( headers );
	} else {
		// eslint-disable-next-line no-console
		console.error(
			'The monkey patched function on APIFetch, "setCartHash", is not present, likely another plugin or some other code has removed this augmentation'
		);
	}
};

/**
 * Trigger a fetch from the API using the batch endpoint.
 */
const triggerBatchFetch = ( keys: readonly APIFetchOptions[] ) => {
	return triggerFetch( {
		path: `/wc/store/v1/batch`,
		method: 'POST',
		data: {
			requests: keys.map( ( request: APIFetchOptions ) => {
				return {
					...request,
					body: request?.data,
				};
			} ),
		},
	} ).then( ( response: unknown ) => {
		assertBatchResponseIsValid( response );
		return keys.map(
			( key, index: number ) =>
				response.responses[ index ] || EMPTY_OBJECT
		);
	} );
};

/**
 * In ms, how long we should wait for requests to batch.
 *
 * DataLoader collects all requests over this window of time (and as a consequence, adds this amount of latency).
 */
const triggerBatchFetchDelay = 300;

/**
 * DataLoader instance for triggerBatchFetch.
 */
const triggerBatchFetchLoader = new DataLoader( triggerBatchFetch, {
	batchScheduleFn: ( callback: () => void ) =>
		setTimeout( callback, triggerBatchFetchDelay ),
	cache: false,
	maxBatchSize: 25,
} );

/**
 * Trigger a fetch from the API using the batch endpoint.
 *
 * @param {APIFetchOptions} request Request object containing API request.
 */
const batchFetch = async ( request: APIFetchOptions ) => {
	return await triggerBatchFetchLoader.load( request );
};

/**
 * Dispatched a control action for triggering an api fetch call with no parsing.
 * Typically this would be used in scenarios where headers are needed.
 *
 * @param {APIFetchOptions} options The options for the API request.
 */
export const apiFetchWithHeadersControl = ( options: APIFetchOptions ) =>
	( {
		type: 'API_FETCH_WITH_HEADERS',
		options,
	} as const );

// List of paths which should not be batched.
const preventBatching = [
	'/wc/store/v1/checkout',
	'/wc/store/v1/checkout?__experimental_calc_totals=true',
	'/wc/store/v1/cart/update-item',
	// Shopper-lists routes don't declare allow_batch yet. Drop these once
	// the routes opt into batching server-side.
	'/wc/store/v1/shopper-lists/saved-for-later/items',
];

/**
 * The underlying function that actually does the fetch. This is used by both the generator (control) version of
 * apiFetchWithHeadersControl and the async function apiFetchWithHeaders.
 */
const doApiFetchWithHeaders = ( options: APIFetchOptions ) =>
	new Promise( ( resolve, reject ) => {
		// GET Requests cannot be batched.
		if (
			! options.method ||
			options.method === 'GET' ||
			preventBatching.includes( options.path || '' )
		) {
			// Parse is disabled here to avoid returning just the body--we also need headers.
			triggerFetch( {
				...options,
				parse: false,
			} )
				.then( ( fetchResponse: unknown ) => {
					if ( fetchResponse instanceof Response ) {
						// QAO-524 PROBE (revert before merge): clone so the body
						// can be inspected if JSON parsing fails.
						const qao524Clone = fetchResponse.clone();
						fetchResponse
							.json()
							.then( ( response: unknown ) => {
								resolve( {
									response,
									headers: fetchResponse.headers,
								} );
								processHeadersOnFetch( fetchResponse.headers );
							} )
							.catch( () => {
								// QAO-524 PROBE: which endpoint returned non-JSON?
								qao524Clone
									.text()
									.then( ( body: string ) => {
										// eslint-disable-next-line no-console
										console.error(
											`[QAO524-PROBE json] site=a path=${
												options.path
											} url=${ options.url } finalUrl=${
												fetchResponse.url
											} redirected=${
												fetchResponse.redirected
											} resType=${
												fetchResponse.type
											} status=${
												fetchResponse.status
											} ct=${ fetchResponse.headers.get(
												'content-type'
											) } len=${
												body.length
											} body=${ JSON.stringify(
												body.slice( 0, 1200 )
											) }`
										);
									} )
									.catch( () => {
										// QAO-524 probe read is best-effort.
									} );
								reject( invalidJsonError );
							} );
					} else {
						// QAO-524 PROBE (revert before merge)
						// eslint-disable-next-line no-console
						console.error(
							`[QAO524-PROBE json] site=b path=${
								options.path
							} url=${
								options.url
							} not-a-Response value=${ String(
								fetchResponse
							).slice( 0, 120 ) }`
						);
						reject( invalidJsonError );
					}
				} )
				.catch( ( errorResponse ) => {
					// Propagate AbortError directly so callers can detect cancelled requests.
					if ( errorResponse.name === 'AbortError' ) {
						reject( errorResponse );
						return;
					}
					if ( errorResponse.headers ) {
						processHeadersOnFetch( errorResponse.headers );
					}
					if ( typeof errorResponse.json === 'function' ) {
						// QAO-524 PROBE (revert before merge): clone so the error
						// body can be inspected if it isn't JSON.
						const qao524ErrClone =
							typeof errorResponse.clone === 'function'
								? errorResponse.clone()
								: null;
						// Parse error response before rejecting it.
						errorResponse
							.json()
							.then( ( error: unknown ) => {
								reject( error );
							} )
							.catch( () => {
								// QAO-524 PROBE
								if ( qao524ErrClone ) {
									qao524ErrClone
										.text()
										.then( ( body: string ) => {
											// eslint-disable-next-line no-console
											console.error(
												`[QAO524-PROBE json] site=c path=${
													options.path
												} url=${ options.url } status=${
													errorResponse.status
												} ct=${ errorResponse.headers?.get?.(
													'content-type'
												) } len=${
													body.length
												} body=${ JSON.stringify(
													body.slice( 0, 200 )
												) }`
											);
										} )
										.catch( () => {
											// QAO-524 probe read is best-effort.
										} );
								} else {
									// eslint-disable-next-line no-console
									console.error(
										`[QAO524-PROBE json] site=c path=${ options.path } url=${ options.url } (uncloneable error response)`
									);
								}
								reject( invalidJsonError );
							} );
					} else {
						reject( errorResponse.message );
					}
				} );
		} else {
			batchFetch( options )
				.then( ( response: ApiResponse< unknown > ) => {
					assertResponseIsValid( response );

					if ( response.status >= 200 && response.status < 300 ) {
						resolve( {
							response: response.body,
							headers: response.headers,
						} );
						processHeadersOnFetch( response.headers );
					}

					// Status code indicates error.
					throw response;
				} )
				.catch( ( errorResponse: ApiResponse< unknown > ) => {
					if ( errorResponse.headers ) {
						processHeadersOnFetch( errorResponse.headers );
					}
					if ( errorResponse.body ) {
						reject( errorResponse.body );
					} else {
						reject( errorResponse );
					}
				} );
		}
	} );

/**
 * Triggers an api fetch call with no parsing.
 * Typically this would be used in scenarios where headers are needed.
 *
 * @param {APIFetchOptions} options The options for the API request.
 */
export const apiFetchWithHeaders = < T = unknown >(
	options: APIFetchOptions
): Promise< T > => {
	return doApiFetchWithHeaders( options ) as Promise< T >;
};

/**
 * Default export for registering the controls with the store.
 *
 * @return {Object} An object with the controls to register with the store on
 *                  the controls property of the registration object.
 */
export const controls = {
	API_FETCH_WITH_HEADERS: ( {
		options,
	}: ReturnType<
		typeof apiFetchWithHeadersControl
	> ): Promise< unknown > => {
		return doApiFetchWithHeaders( options );
	},
};
