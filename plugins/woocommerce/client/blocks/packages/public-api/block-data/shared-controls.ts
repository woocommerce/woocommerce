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
	} ) as const;

// List of paths which should not be batched.
const preventBatching = [
	// Shopper-lists routes don't declare allow_batch yet. Drop these once
	// the routes opt into batching server-side.
	'/wc/store/v1/shopper-lists/saved-for-later/items',
];

// Session writes that were sent on their own before session writes were queued. They don't wait for the batching
// window when no other session write is running, and they can still be aborted with a signal. Batched requests never
// supported a signal, and some callers share one signal between requests that must all be sent.
const directSessionWrites = [
	'/wc/store/v1/checkout',
	'/wc/store/v1/checkout?__experimental_calc_totals=true',
	'/wc/store/v1/cart/update-item',
];

type Resolve = ( value: unknown ) => void;
type Reject = ( reason?: unknown ) => void;

/**
 * Sends a request on its own, not through the batch endpoint.
 */
const fetchUnbatched = (
	options: APIFetchOptions,
	resolve: Resolve,
	reject: Reject
) => {
	// Parse is disabled here to avoid returning just the body--we also need headers.
	triggerFetch( {
		...options,
		parse: false,
	} )
		.then( ( fetchResponse: unknown ) => {
			if ( fetchResponse instanceof Response ) {
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
						reject( invalidJsonError );
					} );
			} else {
				reject( invalidJsonError );
			}
		} )
		.catch( ( errorResponse ) => {
			// Propagate AbortError directly so callers can detect cancelled requests.
			if ( errorResponse?.name === 'AbortError' ) {
				reject( errorResponse );
				return;
			}
			if ( errorResponse?.headers ) {
				processHeadersOnFetch( errorResponse.headers );
			}
			if ( typeof errorResponse?.json === 'function' ) {
				// Parse error response before rejecting it.
				errorResponse
					.json()
					.then( ( error: unknown ) => {
						reject( error );
					} )
					.catch( () => {
						reject( invalidJsonError );
					} );
			} else {
				reject( errorResponse?.message );
			}
		} );
};

/**
 * Resolves or rejects a request from its entry in a batch response.
 */
const settleBatchedRequest = (
	batchedResponse: Promise< unknown >,
	resolve: Resolve,
	reject: Reject
) => {
	batchedResponse
		.then( ( response: unknown ) => {
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
		.catch(
			( errorResponse: ApiResponse< unknown > | null | undefined ) => {
				if ( errorResponse?.headers ) {
					processHeadersOnFetch( errorResponse.headers );
				}
				if ( errorResponse?.body ) {
					reject( errorResponse.body );
				} else {
					reject( errorResponse );
				}
			}
		);
};

/**
 * Returns true when the path is the route or one of its sub-routes, with or without query arguments.
 * For example, '/wc/store/v1/cart' matches '/wc/store/v1/cart/update-item' but not '/wc/store/v1/carts'.
 */
const isRoute = ( path: string, route: string ) => {
	if ( ! path.startsWith( route ) ) {
		return false;
	}
	const nextCharacter = path.charAt( route.length );
	return (
		nextCharacter === '' || nextCharacter === '/' || nextCharacter === '?'
	);
};

/**
 * Returns true for requests that change the shopper's cart or checkout session.
 *
 * The server saves the whole session at the end of each request. When two of these requests run at the same time,
 * the one that ends last overwrites the changes of the other, so they are sent one at a time.
 */
const isSessionWrite = ( options: APIFetchOptions ) =>
	!! options.method &&
	options.method !== 'GET' &&
	( isRoute( options.path || '', '/wc/store/v1/cart' ) ||
		isRoute( options.path || '', '/wc/store/v1/checkout' ) );

/**
 * Returns true for session writes that the batch endpoint can run. Checkout routes do not allow batching.
 */
const isBatchableSessionWrite = ( options: APIFetchOptions ) =>
	isRoute( options.path || '', '/wc/store/v1/cart' );

type SessionWrite = {
	options: APIFetchOptions;
	resolve: Resolve;
	reject: Reject;
	// True once the caller has its result, or stopped waiting because the request was aborted.
	isSettled: boolean;
};

/**
 * Session writes waiting to be sent, in the order they were made.
 */
let queuedSessionWrites: SessionWrite[] = [];

/**
 * True while session writes are being sent. Nothing else is sent until they have finished.
 */
let isSendingSessionWrites = false;

let sessionWriteTimer: ReturnType< typeof setTimeout > | null = null;

const createAbortError = () =>
	new DOMException( 'The request was aborted.', 'AbortError' );

/**
 * Sends one session write on its own, and resolves when the server has finished it.
 */
const sendSessionWrite = ( write: SessionWrite ) =>
	new Promise< void >( ( done ) => {
		try {
			fetchUnbatched(
				write.options,
				( value ) => {
					write.resolve( value );
					done();
				},
				( reason ) => {
					write.reject( reason );
					done();
				}
			);
		} catch ( error ) {
			// The request failed before it was sent, for example in an apiFetch middleware.
			write.reject( error );
			done();
		}
	} );

/**
 * Sends session writes together through the batch endpoint, and resolves when the server has finished them.
 * The server runs them one after the other and saves the session once.
 */
const sendSessionWriteBatch = ( writes: SessionWrite[] ) => {
	let batchResponse: ReturnType< typeof triggerBatchFetch >;
	try {
		batchResponse = triggerBatchFetch(
			writes.map( ( write ) => write.options )
		);
	} catch ( error ) {
		// The request failed before it was sent, for example in an apiFetch middleware.
		batchResponse = Promise.reject( error );
	}
	return Promise.all(
		writes.map(
			( write, index ) =>
				new Promise< void >( ( done ) => {
					settleBatchedRequest(
						batchResponse.then(
							( responses ) => responses[ index ]
						),
						( value ) => {
							write.resolve( value );
							done();
						},
						( reason ) => {
							write.reject( reason );
							done();
						}
					);
				} )
		)
	);
};

/**
 * Sends the queued session writes. Writes that can be batched are sent together; a write that cannot is sent on
 * its own. The next writes are only sent once the server has finished the current ones.
 */
const sendQueuedSessionWrites = async (): Promise< void > => {
	sessionWriteTimer = null;
	if ( isSendingSessionWrites || queuedSessionWrites.length === 0 ) {
		return;
	}
	isSendingSessionWrites = true;
	try {
		if ( isBatchableSessionWrite( queuedSessionWrites[ 0 ].options ) ) {
			let count = 0;
			while (
				count < queuedSessionWrites.length &&
				count < 25 &&
				isBatchableSessionWrite( queuedSessionWrites[ count ].options )
			) {
				count++;
			}
			await sendSessionWriteBatch(
				queuedSessionWrites.splice( 0, count )
			);
		} else {
			const write = queuedSessionWrites.shift() as SessionWrite;
			await sendSessionWrite( write );
		}
	} finally {
		isSendingSessionWrites = false;
		if ( queuedSessionWrites.length > 0 ) {
			void sendQueuedSessionWrites();
		}
	}
};

/**
 * Queues a session write, to be sent once no other session write is running.
 *
 * When nothing is running, a write waits for the same batching window as other requests, so writes made close
 * together are sent in one batch. Writes in directSessionWrites are sent straight away.
 *
 * For writes in directSessionWrites, aborting the signal before the write is sent removes it from the queue.
 * Aborting it after the write is sent rejects it straight away, but the queue still waits for the server to finish
 * it, because the server runs it anyway. Other writes ignore the signal, as they did when they were batched.
 */
const queueSessionWrite = (
	options: APIFetchOptions,
	resolve: Resolve,
	reject: Reject
) => {
	const { signal: requestSignal, ...requestOptions } = options;
	const isDirect = directSessionWrites.includes( options.path || '' );
	const signal = isDirect ? requestSignal : undefined;
	if ( signal?.aborted ) {
		reject( createAbortError() );
		return;
	}
	const write: SessionWrite = {
		options: requestOptions,
		isSettled: false,
		resolve: ( value ) => {
			if ( ! write.isSettled ) {
				write.isSettled = true;
				resolve( value );
			}
		},
		reject: ( reason ) => {
			if ( ! write.isSettled ) {
				write.isSettled = true;
				reject( reason );
			}
		},
	};
	signal?.addEventListener(
		'abort',
		() => {
			queuedSessionWrites = queuedSessionWrites.filter(
				( queuedWrite ) => queuedWrite !== write
			);
			write.reject( createAbortError() );
		},
		{ once: true }
	);
	queuedSessionWrites.push( write );

	if ( isSendingSessionWrites || sessionWriteTimer ) {
		return;
	}
	sessionWriteTimer = setTimeout(
		sendQueuedSessionWrites,
		isDirect ? 0 : triggerBatchFetchDelay
	);
};

/**
 * The underlying function that actually does the fetch. This is used by both the generator (control) version of
 * apiFetchWithHeadersControl and the async function apiFetchWithHeaders.
 */
const doApiFetchWithHeaders = ( options: APIFetchOptions ) =>
	new Promise( ( resolve, reject ) => {
		if ( isSessionWrite( options ) ) {
			queueSessionWrite( options, resolve, reject );
		} else if (
			// GET Requests cannot be batched.
			! options.method ||
			options.method === 'GET' ||
			preventBatching.includes( options.path || '' )
		) {
			fetchUnbatched( options, resolve, reject );
		} else {
			settleBatchedRequest( batchFetch( options ), resolve, reject );
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
