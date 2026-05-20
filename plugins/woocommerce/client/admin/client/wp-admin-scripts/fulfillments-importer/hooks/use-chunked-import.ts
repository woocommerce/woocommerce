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
	onError?: ( message: string ) => void;
}

const FALLBACK_CHUNK_SIZE = 200;
const RETRY_BACKOFFS_MS = [ 250, 1000 ];

/**
 * Reads the server-resolved chunk size from the localized settings so the
 * client never disagrees with the REST controller's `resolve_chunk_size`
 * (and the `woocommerce_fulfillments_csv_importer_chunk_size` filter).
 */
function resolvedChunkSize(): number {
	const settings = window.wcFulfillmentsImporterSettings;
	const fromServer = settings?.chunkSize;
	if ( typeof fromServer === 'number' && fromServer > 0 ) {
		return Math.max( 1, Math.floor( fromServer ) );
	}
	return FALLBACK_CHUNK_SIZE;
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
 * True for errors that are worth retrying: network failures (no status),
 * 408 Request Timeout, 429 Too Many Requests, and any 5xx.
 * 4xx responses other than 408/429 are caller errors and should not retry.
 */
function isRetriable( error: unknown ): boolean {
	const status = errorStatus( error );
	if ( status === null ) {
		return true;
	}
	if ( status === 408 || status === 429 ) {
		return true;
	}
	return status >= 500;
}

function errorMessage( error: unknown ): string {
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
	return 'Unknown error';
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
		chunkSize = resolvedChunkSize(),
		onChunk,
		onFinish,
		onError,
	} = args;

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
			// eslint-disable-next-line no-constant-condition
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
				if ( typeof response.processed === 'number' && response.processed >= 0 ) {
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
			callbacksRef.current.onError?.( errorMessage( error ) );
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
