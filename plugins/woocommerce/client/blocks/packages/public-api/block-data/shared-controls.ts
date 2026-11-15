/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import triggerFetch, { APIFetchOptions } from '@wordpress/api-fetch';
import {
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
 * The queue collects all requests over this window of time (and as a consequence, adds this amount of latency).
 */
const triggerBatchFetchDelay = 300;

/**
 * The batch endpoint accepts at most 25 requests.
 */
const maxBatchSize = 25;

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

// Writes to these paths are sent on their own, not batched. They skip the batching window and can be aborted.
const preventBatching = [
	'/wc/store/v1/checkout',
	'/wc/store/v1/checkout?__experimental_calc_totals=true',
	'/wc/store/v1/cart/update-item',
	// Shopper-lists routes don't declare allow_batch yet. Drop these once
	// the routes opt into batching server-side.
	'/wc/store/v1/shopper-lists/saved-for-later/items',
];

type Result = { response: unknown; headers: unknown };

/**
 * Sends a request on its own. Parse is disabled to get the headers as well as the body.
 */
const fetchUnbatched = async (
	options: APIFetchOptions
): Promise< Result > => {
	// A synchronous throw (for example, in a middleware) happens before .catch() and rejects with the error itself.
	const fetchResponse: unknown = await triggerFetch( {
		...options,
		parse: false,
	} ).catch( async ( errorResponse ) => {
		// Propagate AbortError directly so callers can detect cancelled requests.
		if ( errorResponse?.name === 'AbortError' ) {
			throw errorResponse;
		}
		if ( errorResponse?.headers ) {
			processHeadersOnFetch( errorResponse.headers );
		}
		if ( typeof errorResponse?.json === 'function' ) {
			throw await errorResponse.json().catch( () => invalidJsonError );
		}
		throw errorResponse?.message;
	} );
	if ( ! ( fetchResponse instanceof Response ) ) {
		throw invalidJsonError;
	}
	const response = await fetchResponse.json().catch( () => {
		throw invalidJsonError;
	} );
	processHeadersOnFetch( fetchResponse.headers );
	return { response, headers: fetchResponse.headers };
};

/**
 * Returns the result of one request from a batch response, or throws its error.
 */
const parseBatchedResponse = ( response: unknown ): Result => {
	assertResponseIsValid( response );
	if ( response.headers ) {
		processHeadersOnFetch( response.headers );
	}
	if ( response.status >= 200 && response.status < 300 ) {
		return { response: response.body, headers: response.headers };
	}
	throw response.body || response;
};

type Write = {
	options: APIFetchOptions;
	resolve: ( value: Result ) => void;
	reject: ( reason?: unknown ) => void;
};

// Writes waiting to be sent, in the order they were made.
let queue: Write[] = [];
let isSending = false;
let timer: ReturnType< typeof setTimeout > | null = null;

const isUnbatched = ( write: Write ) =>
	preventBatching.includes( write.options.path || '' );

/**
 * Sends one write on its own, or several writes in one batch. Resolves when the server has finished.
 */
const send = ( writes: Write[] ) => {
	if ( isUnbatched( writes[ 0 ] ) ) {
		return fetchUnbatched( writes[ 0 ].options ).then(
			writes[ 0 ].resolve,
			writes[ 0 ].reject
		);
	}
	// Promise.resolve().then() turns a synchronous throw (for example, in a middleware) into a rejection.
	return Promise.resolve()
		.then( () =>
			triggerBatchFetch( writes.map( ( write ) => write.options ) )
		)
		.then(
			( responses ) => {
				// Settle successful writes first, so a later cart response does not clear the error of a failed write.
				const failures: Array< () => void > = [];
				writes.forEach( ( write, index ) => {
					try {
						write.resolve(
							parseBatchedResponse( responses[ index ] )
						);
					} catch ( error ) {
						failures.push( () => write.reject( error ) );
					}
				} );
				failures.forEach( ( fail ) => fail() );
			},
			( error ) => writes.forEach( ( write ) => write.reject( error ) )
		);
};

/**
 * Sends the next writes in the queue once the server has finished the previous ones.
 */
const sendNext = () => {
	timer = null;
	if ( isSending || queue.length === 0 ) {
		return;
	}
	let count = 1;
	if ( ! isUnbatched( queue[ 0 ] ) ) {
		while (
			count < queue.length &&
			count < maxBatchSize &&
			! isUnbatched( queue[ count ] )
		) {
			count++;
		}
	}
	isSending = true;
	void send( queue.splice( 0, count ) ).finally( () => {
		isSending = false;
		sendNext();
	} );
};

/**
 * Queues a write. Writes are sent one request at a time, because the server saves the whole session at the end
 * of each request and the last request to finish overwrites the others.
 *
 * Aborting a write in preventBatching removes it from the queue, or rejects it straight away if it was already
 * sent. Other writes ignore the signal, as they did when they were batched.
 */
const queueWrite = ( options: APIFetchOptions ) =>
	new Promise< Result >( ( resolve, reject ) => {
		const { signal, ...requestOptions } = options;
		const write: Write = { options: requestOptions, resolve, reject };
		const unbatched = isUnbatched( write );
		if ( unbatched && signal ) {
			const abort = () => {
				queue = queue.filter( ( queued ) => queued !== write );
				reject(
					new DOMException( 'The request was aborted.', 'AbortError' )
				);
			};
			if ( signal.aborted ) {
				abort();
				return;
			}
			signal.addEventListener( 'abort', abort, { once: true } );
		}
		queue.push( write );
		if ( ! isSending && ! timer ) {
			timer = setTimeout(
				sendNext,
				unbatched ? 0 : triggerBatchFetchDelay
			);
		}
	} );

/**
 * The underlying function that actually does the fetch. This is used by both the generator (control) version of
 * apiFetchWithHeadersControl and the async function apiFetchWithHeaders.
 */
const doApiFetchWithHeaders = ( options: APIFetchOptions ) =>
	! options.method || options.method === 'GET'
		? fetchUnbatched( options )
		: queueWrite( options );

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
