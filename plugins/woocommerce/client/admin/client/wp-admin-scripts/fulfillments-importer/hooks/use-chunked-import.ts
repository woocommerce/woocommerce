/**
 * External dependencies
 */
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

const DEFAULT_CHUNK_SIZE = 200;
const RETRY_BACKOFFS_MS = [ 250, 1000 ];

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
		chunkSize = DEFAULT_CHUNK_SIZE,
		onChunk,
		onFinish,
		onError,
	} = args;

	const [ isRunning, setIsRunning ] = useState( false );
	const offsetRef = useRef< number >( 0 );
	const abortRef = useRef< AbortController | null >( null );

	const cancel = useCallback( () => {
		abortRef.current?.abort();
		abortRef.current = null;
		setIsRunning( false );
	}, [] );

	useEffect( () => {
		return () => {
			abortRef.current?.abort();
		};
	}, [] );

	const run = useCallback( async () => {
		if ( ! token || isRunning ) {
			return;
		}
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
						if ( attempt >= RETRY_BACKOFFS_MS.length ) {
							throw error;
						}
						await delay(
							RETRY_BACKOFFS_MS[ attempt ] as number,
							signal
						);
						attempt++;
					}
				}

				if ( ! response ) {
					throw lastError ?? new Error( 'Chunk request failed' );
				}

				onChunk?.( response );

				if ( response.done ) {
					if ( response.summary ) {
						onFinish?.( response.summary );
					}
					return;
				}

				offsetRef.current += chunkSize;
				if ( offsetRef.current >= total && total > 0 ) {
					// Defensive: server should have set done=true. Stop to avoid an infinite loop.
					return;
				}
			}
		} catch ( error ) {
			if ( ( error as DOMException )?.name === 'AbortError' ) {
				return;
			}
			onError?.( errorMessage( error ) );
		} finally {
			abortRef.current = null;
			setIsRunning( false );
		}
	}, [
		token,
		total,
		mapping,
		notifyCustomer,
		updateExisting,
		chunkSize,
		onChunk,
		onFinish,
		onError,
		isRunning,
	] );

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
