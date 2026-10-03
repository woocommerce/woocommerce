/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

jest.mock( '../../data/api', () => ( {
	runChunk: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import { runChunk } from '../../data/api';
import { useChunkedImport } from '../use-chunked-import';
import type { UseChunkedImportArgs } from '../use-chunked-import';
import type { RunChunkResponse } from '../../data/types';

const mockedRunChunk = runChunk as jest.MockedFunction< typeof runChunk >;

const MAPPING = {
	0: 'order_number',
	1: 'tracking_number',
	2: 'shipment_provider',
} as const;

function buildResponse(
	processed: number,
	total: number,
	done: boolean,
	withSummary = true
): RunChunkResponse {
	return {
		processed,
		total,
		done,
		counts: {
			created: processed,
			updated: 0,
			skipped: 0,
			failed: 0,
			notified: 0,
		},
		rows: [],
		errors: [],
		...( done && withSummary
			? {
					summary: {
						created: processed,
						updated: 0,
						skipped: 0,
						failed: 0,
						notified: 0,
						rows: [],
					},
			  }
			: {} ),
	};
}

function renderImport( overrides: Partial< UseChunkedImportArgs > = {} ) {
	const onChunk = jest.fn();
	const onFinish = jest.fn();
	const onError = jest.fn();
	const hook = renderHook( () =>
		useChunkedImport( {
			token: 'tok',
			total: 4,
			mapping: MAPPING,
			notifyCustomer: false,
			updateExisting: true,
			chunkSize: 2,
			onChunk,
			onFinish,
			onError,
			...overrides,
		} )
	);
	return { ...hook, onChunk, onFinish, onError };
}

/**
 * Queue a runChunk call that stays pending until resolved here, and rejects
 * with an AbortError when its signal is aborted, like apiFetch does.
 */
function deferredChunk() {
	let resolve!: ( response: RunChunkResponse ) => void;
	const promise = new Promise< RunChunkResponse >( ( res, rej ) => {
		resolve = res;
		mockedRunChunk.mockImplementationOnce( ( args ) => {
			args.signal?.addEventListener( 'abort', () =>
				rej( new DOMException( 'Aborted', 'AbortError' ) )
			);
			return promise;
		} );
	} );
	return { resolve };
}

describe( 'useChunkedImport', () => {
	beforeEach( () => {
		mockedRunChunk.mockReset();
	} );

	afterEach( () => {
		delete window.wcFulfillmentsImporterSettings;
		jest.useRealTimers();
	} );

	it( 'loops chunks from the server-reported offset until done and invokes onFinish', async () => {
		mockedRunChunk
			.mockResolvedValueOnce( buildResponse( 3, 4, false ) )
			.mockResolvedValueOnce( buildResponse( 4, 4, true ) );

		const { result, onChunk, onFinish, onError } = renderImport();

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 2 );
		expect( mockedRunChunk.mock.calls[ 0 ][ 0 ].offset ).toBe( 0 );
		// The server processed 3 rows, not a whole chunk of 2.
		expect( mockedRunChunk.mock.calls[ 1 ][ 0 ].offset ).toBe( 3 );
		expect( onChunk ).toHaveBeenCalledTimes( 2 );
		expect( onFinish ).toHaveBeenCalledWith(
			expect.objectContaining( { created: 4 } )
		);
		expect( onError ).not.toHaveBeenCalled();
		expect( result.current.isRunning ).toBe( false );
	} );

	it( 'advances by the chunk size when the server omits processed', async () => {
		const noProcessed = {
			...buildResponse( 0, 4, false ),
			processed: undefined,
		} as unknown as RunChunkResponse;
		mockedRunChunk
			.mockResolvedValueOnce( noProcessed )
			.mockResolvedValueOnce( buildResponse( 4, 4, true ) );

		const { result, onError } = renderImport();

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk.mock.calls[ 1 ][ 0 ].offset ).toBe( 2 );
		expect( onError ).not.toHaveBeenCalled();
	} );

	it( 'honors a chunk size localized as a string', async () => {
		// wp_localize_script casts scalars to strings.
		window.wcFulfillmentsImporterSettings = {
			importRoute: '/wc/v3/fulfillments/import',
			chunkSize: '500',
			maxRows: '5000',
			providers: [],
		};
		mockedRunChunk.mockResolvedValueOnce( buildResponse( 2, 2, true ) );

		const { result } = renderImport( { total: 2, chunkSize: undefined } );

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledWith(
			expect.objectContaining( { limit: 500 } )
		);
	} );

	it( 'does nothing without a session token', async () => {
		const { result } = renderImport( { token: null } );

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).not.toHaveBeenCalled();
		expect( result.current.isRunning ).toBe( false );
	} );

	it( 'waits for the backoff before retrying a transient failure', async () => {
		jest.useFakeTimers();
		mockedRunChunk
			.mockRejectedValueOnce( new Error( 'flaky network' ) )
			.mockResolvedValueOnce( buildResponse( 2, 2, true ) );

		const { result, onError, onFinish } = renderImport( { total: 2 } );

		let running!: Promise< void >;
		await act( async () => {
			running = result.current.run();
			await jest.advanceTimersByTimeAsync( 100 );
		} );
		expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );

		await act( async () => {
			await jest.advanceTimersByTimeAsync( 150 );
			await running;
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 2 );
		expect( onError ).not.toHaveBeenCalled();
		expect( onFinish ).toHaveBeenCalled();
	} );

	it( 'surfaces an error once retries are exhausted', async () => {
		jest.useFakeTimers();
		mockedRunChunk.mockRejectedValue( new Error( 'persistent failure' ) );

		const { result, onError } = renderImport( { total: 2 } );

		await act( async () => {
			const running = result.current.run();
			await jest.runAllTimersAsync();
			await running;
		} );

		// One initial attempt + two retries.
		expect( mockedRunChunk ).toHaveBeenCalledTimes( 3 );
		expect( onError ).toHaveBeenCalledWith( 'persistent failure', false );
		expect( result.current.isRunning ).toBe( false );
	} );

	it( 'fails immediately without retrying on a 4xx response', async () => {
		mockedRunChunk.mockRejectedValue( {
			code: 'woocommerce_fulfillments_import_mapping_invalid',
			message: 'Mapping is missing required column(s).',
			data: { status: 400 },
		} );

		const { result, onError } = renderImport( { total: 2, mapping: {} } );

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );
		expect( onError ).toHaveBeenCalledWith(
			'Mapping is missing required column(s).',
			false
		);
	} );

	it.each( [
		[
			'woocommerce_fulfillments_import_token_invalid',
			'Import session is missing or has expired.',
		],
		[
			'woocommerce_fulfillments_import_file_changed',
			'The uploaded file changed since it was prepared.',
		],
	] )(
		'flags %s as a session end so the caller stops offering a retry',
		async ( code, message ) => {
			mockedRunChunk.mockRejectedValue( {
				code,
				message,
				data: { status: 400 },
			} );

			const { result, onError } = renderImport( { total: 2 } );

			await act( async () => {
				await result.current.run();
			} );

			expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );
			expect( onError ).toHaveBeenCalledWith( message, true );
		}
	);

	it( 'retries a 409 chunk-in-progress conflict until the lock clears', async () => {
		jest.useFakeTimers();
		mockedRunChunk
			.mockRejectedValueOnce( {
				code: 'woocommerce_fulfillments_import_chunk_in_progress',
				message:
					'Another chunk of this import is still being processed.',
				data: { status: 409 },
			} )
			.mockResolvedValueOnce( buildResponse( 2, 2, true ) );

		const { result, onError, onFinish } = renderImport( { total: 2 } );

		await act( async () => {
			const running = result.current.run();
			await jest.runAllTimersAsync();
			await running;
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 2 );
		expect( onError ).not.toHaveBeenCalled();
		expect( onFinish ).toHaveBeenCalled();
	} );

	it( 'stops when a chunk reports no progress instead of looping', async () => {
		mockedRunChunk.mockResolvedValue( buildResponse( 0, 4, false ) );

		const { result, onError, onFinish } = renderImport();

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );
		expect( onError ).toHaveBeenCalledWith(
			'The import stopped making progress. Try again.'
		);
		expect( onFinish ).not.toHaveBeenCalled();
	} );

	it( 'stops when the offset reaches the total without a done flag', async () => {
		mockedRunChunk.mockResolvedValue( buildResponse( 4, 4, false ) );

		const { result, onError, onFinish } = renderImport();

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );
		expect( onError ).toHaveBeenCalledWith(
			'The import did not complete cleanly. Please try again.'
		);
		expect( onFinish ).not.toHaveBeenCalled();
	} );

	it( 'reports a done response that carries no summary', async () => {
		mockedRunChunk.mockResolvedValue( buildResponse( 4, 4, true, false ) );

		const { result, onError, onFinish } = renderImport();

		await act( async () => {
			await result.current.run();
		} );

		expect( onFinish ).not.toHaveBeenCalled();
		expect( onError ).toHaveBeenCalledWith(
			'The import finished but the summary was missing. Please try again.'
		);
	} );

	it( 'cancel aborts the in-flight chunk without reporting an error', async () => {
		deferredChunk();
		const { result, onError, onFinish } = renderImport();

		let running!: Promise< void >;
		act( () => {
			running = result.current.run();
		} );
		expect( result.current.isRunning ).toBe( true );

		act( () => {
			result.current.cancel();
		} );
		await act( async () => {
			await running;
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );
		expect( onError ).not.toHaveBeenCalled();
		expect( onFinish ).not.toHaveBeenCalled();
		expect( result.current.isRunning ).toBe( false );
	} );

	it( 'keeps a run started after cancel alive when the cancelled loop exits', async () => {
		deferredChunk();
		const second = deferredChunk();
		const { result, onFinish } = renderImport();

		let firstRun!: Promise< void >;
		let secondRun!: Promise< void >;
		act( () => {
			firstRun = result.current.run();
		} );
		act( () => {
			result.current.cancel();
		} );
		act( () => {
			secondRun = result.current.run();
		} );

		// The cancelled loop unwinds now; it must not clear the new run's state.
		await act( async () => {
			await firstRun;
		} );
		expect( result.current.isRunning ).toBe( true );

		await act( async () => {
			second.resolve( buildResponse( 4, 4, true ) );
			await secondRun;
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 2 );
		expect( onFinish ).toHaveBeenCalledTimes( 1 );
		expect( result.current.isRunning ).toBe( false );
	} );

	it( 'ignores run() while a loop is already in flight', async () => {
		const chunk = deferredChunk();
		const { result, onFinish } = renderImport();

		let firstRun!: Promise< void >;
		let secondRun!: Promise< void >;
		act( () => {
			firstRun = result.current.run();
			secondRun = result.current.run();
		} );

		await act( async () => {
			chunk.resolve( buildResponse( 4, 4, true ) );
			await Promise.all( [ firstRun, secondRun ] );
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );
		expect( onFinish ).toHaveBeenCalledTimes( 1 );
	} );
} );
