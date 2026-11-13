/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Internal dependencies
 */
import { runChunk } from '../data/api';
import { errorCode, errorMessage, errorStatus } from '../data/errors';
import type {
	ColumnMapping,
	ImporterSummary,
	RunChunkResponse,
} from '../data/types';

export interface UseChunkedImportArgs {
	token: string | null;
	total: number;
	mapping: ColumnMapping;
	notifyCustomer: boolean;
	updateExisting: boolean;
	chunkSize?: number;
	onChunk?: ( response: RunChunkResponse ) => void;
	onFinish?: ( summary: ImporterSummary ) => void;
	onError?: ( message: string, sessionEnded: boolean ) => void;
}

const FALLBACK_CHUNK_SIZE = 200;
// The highest limit the /run endpoint accepts; it must match that endpoint.
const MAX_CHUNK_SIZE = 1000;
// Waits before resending a chunk the server refused without running it.
const RETRY_BACKOFFS_MS = [ 1000, 3000, 6000 ];

const CHUNK_IN_PROGRESS_CODE =
	'woocommerce_fulfillments_import_chunk_in_progress';
const TOKEN_INVALID_CODE = 'woocommerce_fulfillments_import_token_invalid';

/**
 * Codes the /run endpoint returns once the import session no longer exists.
 * Sending the chunk again cannot succeed; the wizard has to start over.
 */
const SESSION_ENDED_CODES = [
	TOKEN_INVALID_CODE,
	'woocommerce_fulfillments_import_file_changed',
];

/**
 * Coerce a chunk size to an integer the /run endpoint accepts, falling back
 * to the default when the value is missing or not a positive number. Accepts
 * numeric strings because wp_localize_script casts scalars to strings.
 */
function normalizeChunkSize( value: unknown ): number {
	const size = Number( value );
	if ( Number.isFinite( size ) && size > 0 ) {
		return Math.min( MAX_CHUNK_SIZE, Math.max( 1, Math.floor( size ) ) );
	}
	return FALLBACK_CHUNK_SIZE;
}

/**
 * Reads the chunk size from the localized settings. Nothing sets it yet, so
 * the default applies until the PHP side passes the size /run uses.
 */
function resolvedChunkSize(): number {
	return normalizeChunkSize(
		window.wcFulfillmentsImporterSettings?.chunkSize
	);
}

function delay( ms: number, signal: AbortSignal ): Promise< void > {
	return new Promise( ( resolve, reject ) => {
		if ( signal.aborted ) {
			reject( new DOMException( 'Aborted', 'AbortError' ) );
			return;
		}
		const timer = setTimeout( resolve, ms );
		signal.addEventListener(
			'abort',
			() => {
				clearTimeout( timer );
				reject( new DOMException( 'Aborted', 'AbortError' ) );
			},
			{ once: true }
		);
	} );
}

/**
 * True when the server refused the chunk without running it, so sending it
 * again cannot write a row twice: a 429, or another request for the same
 * session still holding the chunk lock.
 */
function isSafeToResend( error: unknown ): boolean {
	const status = errorStatus( error );
	return (
		status === 429 ||
		( status === 409 && errorCode( error ) === CHUNK_IN_PROGRESS_CODE )
	);
}

/**
 * True for a failure after which the chunk may or may not have been written:
 * no response at all, or a server error.
 */
function hasUnknownOutcome( error: unknown ): boolean {
	const status = errorStatus( error );
	return status === null || status >= 500;
}

function isSessionEnded( error: unknown ): boolean {
	const code = errorCode( error );
	return code !== null && SESSION_ENDED_CODES.includes( code );
}

function isCount( value: unknown ): value is number {
	return typeof value === 'number' && Number.isInteger( value ) && value >= 0;
}

const COUNT_KEYS = [
	'created',
	'updated',
	'skipped',
	'failed',
	'notified',
] as const;

function isChunkResponse( response: unknown ): response is RunChunkResponse {
	if ( ! response || typeof response !== 'object' ) {
		return false;
	}
	const { processed, done, counts } = response as Partial< RunChunkResponse >;
	return (
		isCount( processed ) &&
		typeof done === 'boolean' &&
		!! counts &&
		typeof counts === 'object' &&
		COUNT_KEYS.every( ( key ) => isCount( counts[ key ] ) )
	);
}

/**
 * Drives the chunk loop for the import step.
 *
 * A chunk is sent again automatically only when the server refused it without
 * running it. Any other failure stops the loop with an error, because the
 * chunk may already be written and sending it again could repeat customer
 * emails. Calling run() again resumes from the last offset the server reported.
 */
export function useChunkedImport( args: UseChunkedImportArgs ) {
	const {
		token,
		total,
		mapping,
		notifyCustomer,
		updateExisting,
		onChunk,
		onFinish,
		onError,
	} = args;
	// Caller-provided sizes go through the same clamp as the localized setting.
	const chunkSize =
		args.chunkSize === undefined
			? resolvedChunkSize()
			: normalizeChunkSize( args.chunkSize );

	const [ isRunning, setIsRunning ] = useState( false );
	const offsetRef = useRef< number >( 0 );
	const abortRef = useRef< AbortController | null >( null );
	const runningRef = useRef( false );
	// Set when the last request failed in a way that may have written its chunk.
	const outcomeUnknownRef = useRef( false );
	// Keep the latest callbacks in a ref so run() does not change identity each render.
	const callbacksRef = useRef( { onChunk, onFinish, onError } );
	useEffect( () => {
		callbacksRef.current = { onChunk, onFinish, onError };
	}, [ onChunk, onFinish, onError ] );

	useEffect( () => {
		return () => {
			// Also release the run guard, so a remount that keeps these refs can start again.
			abortRef.current?.abort();
			abortRef.current = null;
			runningRef.current = false;
		};
	}, [] );

	const run = useCallback( async () => {
		if ( ! token || runningRef.current ) {
			return;
		}
		runningRef.current = true;
		setIsRunning( true );
		const controller = new AbortController();
		abortRef.current = controller;
		const { signal } = controller;
		// After an unmount a newer run may own the refs; a late response must not reach the callbacks.
		const ownsLoop = () =>
			! signal.aborted && abortRef.current === controller;
		const fail = ( message: string, sessionEnded = false ) => {
			callbacksRef.current.onError?.( message, sessionEnded );
		};

		try {
			while ( true ) {
				let response: unknown = null;
				for ( let attempt = 0; ; attempt++ ) {
					try {
						response = await runChunk( {
							token,
							offset: offsetRef.current,
							limit: chunkSize,
							mapping,
							notifyCustomer,
							updateExisting,
							signal,
						} );
						break;
					} catch ( error ) {
						if (
							! isSafeToResend( error ) ||
							attempt >= RETRY_BACKOFFS_MS.length
						) {
							throw error;
						}
						await delay( RETRY_BACKOFFS_MS[ attempt ], signal );
					}
				}

				if ( ! ownsLoop() ) {
					return;
				}
				if ( ! isChunkResponse( response ) ) {
					// The server answered, so it may have written the chunk.
					outcomeUnknownRef.current = true;
					fail(
						__(
							'The server sent an unexpected response. Please try again.',
							'woocommerce'
						)
					);
					return;
				}
				// A response behind the confirmed offset, or one that does not move it
				// before the import is done, would rewind or loop; keep the offset.
				if (
					response.processed < offsetRef.current ||
					( ! response.done &&
						response.processed === offsetRef.current )
				) {
					// The server answered, so it may have written the chunk.
					outcomeUnknownRef.current = true;
					fail(
						__(
							'The import stopped making progress. Please try again.',
							'woocommerce'
						)
					);
					return;
				}
				outcomeUnknownRef.current = false;

				callbacksRef.current.onChunk?.( response );
				offsetRef.current = response.processed;

				if ( response.done ) {
					if ( response.summary ) {
						callbacksRef.current.onFinish?.( response.summary );
					} else {
						// The server ends the session with the last chunk, so there is nothing to retry.
						fail(
							__(
								'The import finished, but its summary could not be loaded. Check your orders to see what was imported.',
								'woocommerce'
							),
							true
						);
					}
					return;
				}

				if ( total > 0 && offsetRef.current >= total ) {
					fail(
						__(
							'The import did not finish. Please try again.',
							'woocommerce'
						)
					);
					return;
				}
			}
		} catch ( error ) {
			if (
				( error as DOMException )?.name === 'AbortError' ||
				! ownsLoop()
			) {
				return;
			}
			if (
				outcomeUnknownRef.current &&
				errorCode( error ) === TOKEN_INVALID_CODE
			) {
				// The failed chunk may have been the last one: the server ends the session when the import finishes.
				fail(
					__(
						'The connection dropped near the end of the import, so it may have finished. Check your orders before you upload the file again.',
						'woocommerce'
					),
					true
				);
				return;
			}
			// Only a valid response settles an unknown outcome; another error does not.
			if ( hasUnknownOutcome( error ) ) {
				outcomeUnknownRef.current = true;
			}
			fail( errorMessage( error ), isSessionEnded( error ) );
		} finally {
			if ( abortRef.current === controller ) {
				abortRef.current = null;
				runningRef.current = false;
				setIsRunning( false );
			}
		}
	}, [ token, total, mapping, notifyCustomer, updateExisting, chunkSize ] );

	return { run, isRunning };
}
