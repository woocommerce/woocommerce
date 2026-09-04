/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Internal dependencies
 */
import { runChunk } from '../data/api';
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
	onError?: ( message: string, sessionEnded?: boolean ) => void;
}

const FALLBACK_CHUNK_SIZE = 200;
// Mirrors FulfillmentsCsvImporter::MAX_CHUNK_SIZE; the /run route rejects larger limits.
const MAX_CHUNK_SIZE = 1000;
const RETRY_BACKOFFS_MS = [ 250, 1000 ];

/**
 * Coerce a chunk size to an integer the /run route accepts, falling back to
 * the default when the value is missing or not a positive number. Accepts
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
 * Reads the server-resolved chunk size from the localized settings so the
 * client never disagrees with the REST controller's `resolve_chunk_size`
 * (and the `woocommerce_fulfillments_csv_importer_chunk_size` filter).
 */
function resolvedChunkSize(): number {
	return normalizeChunkSize(
		window.wcFulfillmentsImporterSettings?.chunkSize
	);
}

function delay( ms: number, signal?: AbortSignal ): Promise< void > {
	return new Promise( ( resolve, reject ) => {
		if ( signal?.aborted ) {
			reject( new DOMException( 'Aborted', 'AbortError' ) );
			return;
		}
		const timer = setTimeout( resolve, ms );
		signal?.addEventListener(
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
 * Returns the HTTP status of an apiFetch/REST error if one is present.
 * apiFetch surfaces failures as `{ code, message, data: { status } }`.
 */
function errorStatus( error: unknown ): number | null {
	if (
		error &&
		typeof error === 'object' &&
		'data' in error &&
		( error as { data?: unknown } ).data &&
		typeof ( error as { data: { status?: unknown } } ).data.status ===
			'number'
	) {
		return ( error as { data: { status: number } } ).data.status;
	}
	return null;
}

/**
 * Returns the REST error code of an apiFetch error if one is present.
 */
function errorCode( error: unknown ): string | null {
	if (
		error &&
		typeof error === 'object' &&
		'code' in error &&
		typeof ( error as { code?: unknown } ).code === 'string'
	) {
		return ( error as { code: string } ).code;
	}
	return null;
}

/**
 * True for errors that are worth retrying: network failures (no status),
 * 408 Request Timeout, 429 Too Many Requests, any 5xx, and the server's
 * chunk-in-progress conflict, which resolves once the running chunk ends.
 * Other 4xx responses are caller errors and should not retry.
 */
function isRetriable( error: unknown ): boolean {
	const status = errorStatus( error );
	if ( status === null ) {
		return true;
	}
	if ( status === 408 || status === 429 ) {
		return true;
	}
	if (
		status === 409 &&
		errorCode( error ) ===
			'woocommerce_fulfillments_import_chunk_in_progress'
	) {
		return true;
	}
	return status >= 500;
}

/**
 * REST error codes the server returns after it has destroyed the import session.
 * Retrying these can never succeed; the wizard has to start from the upload step.
 */
const SESSION_ENDED_CODES = [
	'woocommerce_fulfillments_import_token_invalid',
	'woocommerce_fulfillments_import_file_changed',
];

/**
 * True when the error means the import session no longer exists.
 */
export function isSessionEnded( error: unknown ): boolean {
	const code = errorCode( error );
	return code !== null && SESSION_ENDED_CODES.includes( code );
}

/**
 * Extracts a human-readable message from an Error or an apiFetch rejection,
 * which is a plain `{ code, message, data }` object rather than an Error.
 */
export function errorMessage( error: unknown ): string {
	if ( error instanceof Error ) {
		return error.message;
	}
	if (
		error &&
		typeof error === 'object' &&
		'message' in error &&
		typeof ( error as { message?: unknown } ).message === 'string'
	) {
		return ( error as { message: string } ).message;
	}
	return __( 'Unknown error', 'woocommerce' );
}

/**
 * Drives the chunk loop for the Import step. Each chunk is retried twice with exponential
 * backoff before surfacing the failure; the caller can resume from the most recent offset.
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
	// Keep the latest callbacks in a ref so run() does not change identity each render.
	const callbacksRef = useRef( { onChunk, onFinish, onError } );
	useEffect( () => {
		callbacksRef.current = { onChunk, onFinish, onError };
	}, [ onChunk, onFinish, onError ] );

	const cancel = useCallback( () => {
		abortRef.current?.abort();
		abortRef.current = null;
		runningRef.current = false;
		setIsRunning( false );
	}, [] );

	useEffect( () => {
		return () => {
			abortRef.current?.abort();
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

		try {
			// Loop until the server reports done OR we exceed the total (defensive).

			while ( true ) {
				if ( signal.aborted ) {
					return;
				}

				let attempt = 0;
				let response: RunChunkResponse | null = null;
				let lastError: unknown = null;

				while ( attempt <= RETRY_BACKOFFS_MS.length ) {
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
						lastError = error;
						if (
							( error as DOMException )?.name === 'AbortError'
						) {
							return;
						}
						if (
							! isRetriable( error ) ||
							attempt >= RETRY_BACKOFFS_MS.length
						) {
							throw error;
						}
						const backoff = RETRY_BACKOFFS_MS[ attempt ] ?? 0;
						await delay( backoff, signal );
						attempt++;
					}
				}

				if ( ! response ) {
					throw lastError ?? new Error( 'Chunk request failed' );
				}

				callbacksRef.current.onChunk?.( response );

				// Always track server-reported progress so we never over-advance past unread rows.
				if (
					typeof response.processed === 'number' &&
					response.processed >= 0
				) {
					offsetRef.current = response.processed;
				} else {
					offsetRef.current += chunkSize;
				}

				if ( response.done ) {
					if ( response.summary ) {
						callbacksRef.current.onFinish?.( response.summary );
					} else {
						callbacksRef.current.onError?.(
							__(
								'The import finished but the summary was missing. Please try again.',
								'woocommerce'
							)
						);
					}
					return;
				}

				if ( offsetRef.current >= total && total > 0 ) {
					// Server should have set done=true; surface so the UI can recover.
					callbacksRef.current.onError?.(
						__(
							'The import did not complete cleanly. Please try again.',
							'woocommerce'
						)
					);
					return;
				}
			}
		} catch ( error ) {
			if ( ( error as DOMException )?.name === 'AbortError' ) {
				return;
			}
			callbacksRef.current.onError?.(
				errorMessage( error ),
				isSessionEnded( error )
			);
		} finally {
			abortRef.current = null;
			runningRef.current = false;
			setIsRunning( false );
		}
	}, [ token, total, mapping, notifyCustomer, updateExisting, chunkSize ] );

	const retry = useCallback( () => {
		// Resume from wherever we left off without resetting offsetRef.
		void run();
	}, [ run ] );

	const reset = useCallback( () => {
		cancel();
		offsetRef.current = 0;
	}, [ cancel ] );

	return { run, retry, cancel, reset, isRunning };
}
