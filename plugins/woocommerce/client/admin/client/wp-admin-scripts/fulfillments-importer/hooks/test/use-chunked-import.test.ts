/**
 * External dependencies
 */
import { StrictMode, useEffect } from 'react';
import { act, renderHook, waitFor } from '@testing-library/react';

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

const CHUNK_IN_PROGRESS = {
	code: 'woocommerce_fulfillments_import_chunk_in_progress',
	message: 'Another chunk of this import is still being processed.',
	data: { status: 409 },
};

const TOKEN_INVALID = {
	code: 'woocommerce_fulfillments_import_token_invalid',
	message: 'Import session is missing or has expired.',
	data: { status: 400 },
};

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
	let signal: AbortSignal | undefined;
	const promise = new Promise< RunChunkResponse >( ( res, rej ) => {
		resolve = res;
		mockedRunChunk.mockImplementationOnce( ( args ) => {
			signal = args.signal;
			args.signal?.addEventListener( 'abort', () =>
				rej( new DOMException( 'Aborted', 'AbortError' ) )
			);
			return promise;
		} );
	} );
	return { resolve, signal: () => signal };
}

async function runToEnd( result: { current: { run: () => Promise< void > } } ) {
	await act( async () => {
		const running = result.current.run();
		await jest.runAllTimersAsync();
		await running;
	} );
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
		expect( mockedRunChunk.mock.calls[ 0 ][ 0 ] ).toEqual(
			expect.objectContaining( {
				token: 'tok',
				offset: 0,
				limit: 2,
				mapping: MAPPING,
				notifyCustomer: false,
				updateExisting: true,
			} )
		);
		// The server processed 3 rows, not a whole chunk of 2.
		expect( mockedRunChunk.mock.calls[ 1 ][ 0 ].offset ).toBe( 3 );
		expect( onChunk ).toHaveBeenCalledTimes( 2 );
		expect( onFinish ).toHaveBeenCalledWith(
			expect.objectContaining( { created: 4 } )
		);
		expect( onError ).not.toHaveBeenCalled();
		expect( result.current.isRunning ).toBe( false );
	} );

	it.each( [
		[ 'no processed count', { processed: undefined } ],
		[ 'a negative processed count', { processed: -1 } ],
		[ 'a fractional processed count', { processed: 2.5 } ],
		[ 'no counts', { counts: null } ],
		[ 'empty counts', { counts: {} } ],
		[
			'a negative count',
			{
				counts: {
					created: 1,
					updated: 0,
					skipped: 0,
					failed: -1,
					notified: 0,
				},
			},
		],
	] )(
		'stops without advancing on a response with %s',
		async ( _label, patch ) => {
			mockedRunChunk.mockResolvedValueOnce( {
				...buildResponse( 2, 4, false ),
				...patch,
			} as unknown as RunChunkResponse );

			const { result, onChunk, onError } = renderImport();

			await act( async () => {
				await result.current.run();
			} );

			expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );
			expect( onChunk ).not.toHaveBeenCalled();
			expect( onError ).toHaveBeenCalledWith(
				'The server sent an unexpected response. Please try again.',
				false
			);
		}
	);

	it( 'stops on an empty response body', async () => {
		mockedRunChunk.mockResolvedValueOnce(
			null as unknown as RunChunkResponse
		);

		const { result, onError } = renderImport();

		await act( async () => {
			await result.current.run();
		} );

		expect( onError ).toHaveBeenCalledWith(
			'The server sent an unexpected response. Please try again.',
			false
		);
	} );

	it.each( [
		[ '500', 'chunk size from the settings', 500 ],
		[ '5000', 'clamped to the /run maximum', 1000 ],
		[ '12.7', 'rounded down', 12 ],
		[ '0', 'replaced by the default', 200 ],
		[ 'abc', 'replaced by the default', 200 ],
	] )(
		'sends a localized chunk size of %s as the %s',
		async ( setting, _label, expected ) => {
			// wp_localize_script casts scalars to strings.
			window.wcFulfillmentsImporterSettings = {
				importRoute: '/wc/v3/fulfillments/import',
				chunkSize: setting,
				maxRows: '5000',
				providers: [],
			};
			mockedRunChunk.mockResolvedValueOnce( buildResponse( 2, 2, true ) );

			const { result } = renderImport( {
				total: 2,
				chunkSize: undefined,
			} );

			await act( async () => {
				await result.current.run();
			} );

			expect( mockedRunChunk ).toHaveBeenCalledWith(
				expect.objectContaining( { limit: expected } )
			);
		}
	);

	it( 'clamps a chunk size passed by the caller', async () => {
		mockedRunChunk.mockResolvedValueOnce( buildResponse( 2, 2, true ) );

		const { result } = renderImport( { total: 2, chunkSize: 4000 } );

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledWith(
			expect.objectContaining( { limit: 1000 } )
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

	it( 'waits for the backoff before resending a chunk the server is still running', async () => {
		jest.useFakeTimers();
		mockedRunChunk
			.mockRejectedValueOnce( CHUNK_IN_PROGRESS )
			.mockResolvedValueOnce( buildResponse( 2, 2, true ) );

		const { result, onError, onFinish } = renderImport( { total: 2 } );

		let running!: Promise< void >;
		await act( async () => {
			running = result.current.run();
			await jest.advanceTimersByTimeAsync( 999 );
		} );
		expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );

		await act( async () => {
			await jest.advanceTimersByTimeAsync( 1 );
			await running;
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 2 );
		expect( mockedRunChunk.mock.calls[ 1 ][ 0 ].offset ).toBe( 0 );
		expect( onError ).not.toHaveBeenCalled();
		expect( onFinish ).toHaveBeenCalled();
	} );

	it( 'resends a 429 and gives up after three waits', async () => {
		jest.useFakeTimers();
		mockedRunChunk.mockRejectedValue( {
			code: 'rest_too_many_requests',
			message: 'Too many requests.',
			data: { status: 429 },
		} );

		const { result, onError } = renderImport( { total: 2 } );

		await runToEnd( result );

		// One attempt plus one per backoff step.
		expect( mockedRunChunk ).toHaveBeenCalledTimes( 4 );
		expect( onError ).toHaveBeenCalledWith( 'Too many requests.', false );
		expect( result.current.isRunning ).toBe( false );
	} );

	it.each( [
		[ 'a network failure', new Error( 'Failed to fetch' ) ],
		[
			'a 500',
			{
				code: 'woocommerce_fulfillments_import_progress_failed',
				message: 'Could not save the import progress.',
				data: { status: 500 },
			},
		],
		[
			'a 503',
			{ code: 'unavailable', message: 'Down.', data: { status: 503 } },
		],
		[
			'a 409 that is not chunk-in-progress',
			{
				code: 'woocommerce_fulfillments_import_offset_mismatch',
				message: 'The offset does not match.',
				data: { status: 409 },
			},
		],
		[
			'a 400',
			{
				code: 'woocommerce_fulfillments_import_mapping_invalid',
				message: 'Mapping is missing required column(s).',
				data: { status: 400 },
			},
		],
	] )(
		'does not resend the chunk after %s, which may have written it',
		async ( _label, error ) => {
			jest.useFakeTimers();
			mockedRunChunk.mockRejectedValue( error );

			const { result, onError } = renderImport( { total: 2 } );

			await runToEnd( result );

			expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );
			expect( onError ).toHaveBeenCalledWith(
				( error as { message: string } ).message,
				false
			);
		}
	);

	it.each( [
		TOKEN_INVALID,
		{
			code: 'woocommerce_fulfillments_import_file_changed',
			message: 'The uploaded file changed since it was prepared.',
			data: { status: 409 },
		},
	] )( 'flags $code as a session end', async ( error ) => {
		mockedRunChunk.mockRejectedValue( error );

		const { result, onError } = renderImport( { total: 2 } );

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );
		expect( onError ).toHaveBeenCalledWith( error.message, true );
	} );

	it( 'resumes from the last confirmed offset when run again after an error', async () => {
		mockedRunChunk
			.mockResolvedValueOnce( buildResponse( 2, 4, false ) )
			.mockRejectedValueOnce( new Error( 'Failed to fetch' ) )
			.mockResolvedValueOnce( buildResponse( 4, 4, true ) );

		const { result, onError, onFinish } = renderImport();

		await act( async () => {
			await result.current.run();
		} );
		expect( onError ).toHaveBeenCalledWith( 'Failed to fetch', false );

		await act( async () => {
			await result.current.run();
		} );

		expect(
			mockedRunChunk.mock.calls.map( ( c ) => c[ 0 ].offset )
		).toEqual( [ 0, 2, 2 ] );
		expect( onFinish ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'says the import may have finished when the session is gone after a lost response', async () => {
		mockedRunChunk
			.mockResolvedValueOnce( buildResponse( 2, 4, false ) )
			.mockRejectedValueOnce( {
				code: 'gateway_timeout',
				message: 'Gateway timeout.',
				data: { status: 504 },
			} )
			.mockRejectedValueOnce( TOKEN_INVALID );

		const { result, onError } = renderImport();

		await act( async () => {
			await result.current.run();
		} );
		await act( async () => {
			await result.current.run();
		} );

		expect( onError ).toHaveBeenLastCalledWith(
			'The connection dropped near the end of the import, so it may have finished. Check your orders before you upload the file again.',
			true
		);
	} );

	it( 'reports an expired session normally once a later chunk succeeded', async () => {
		mockedRunChunk
			.mockRejectedValueOnce( new Error( 'Failed to fetch' ) )
			.mockResolvedValueOnce( buildResponse( 2, 4, false ) )
			.mockRejectedValueOnce( TOKEN_INVALID );

		const { result, onError } = renderImport();

		await act( async () => {
			await result.current.run();
		} );
		await act( async () => {
			await result.current.run();
		} );

		expect( onError ).toHaveBeenLastCalledWith(
			TOKEN_INVALID.message,
			true
		);
	} );

	it( 'reports an expired session normally when the last request had a clear answer', async () => {
		mockedRunChunk
			.mockRejectedValueOnce( {
				code: 'woocommerce_fulfillments_import_mapping_invalid',
				message: 'Mapping is missing required column(s).',
				data: { status: 400 },
			} )
			.mockRejectedValueOnce( TOKEN_INVALID );

		const { result, onError } = renderImport();

		await act( async () => {
			await result.current.run();
		} );
		await act( async () => {
			await result.current.run();
		} );

		expect( onError ).toHaveBeenLastCalledWith(
			TOKEN_INVALID.message,
			true
		);
	} );

	it( 'keeps the confirmed offset when a response goes backwards', async () => {
		mockedRunChunk
			.mockResolvedValueOnce( buildResponse( 3, 6, false ) )
			.mockResolvedValueOnce( buildResponse( 1, 6, false ) )
			.mockResolvedValueOnce( buildResponse( 6, 6, true ) );

		const { result, onChunk, onError } = renderImport( { total: 6 } );

		await act( async () => {
			await result.current.run();
		} );
		expect( onChunk ).toHaveBeenCalledTimes( 1 );
		expect( onError ).toHaveBeenCalledWith(
			'The import stopped making progress. Please try again.',
			false
		);

		await act( async () => {
			await result.current.run();
		} );
		expect(
			mockedRunChunk.mock.calls.map( ( c ) => c[ 0 ].offset )
		).toEqual( [ 0, 3, 3 ] );
	} );

	it( 'finishes when the last chunk is done without moving the offset', async () => {
		mockedRunChunk
			.mockResolvedValueOnce( buildResponse( 4, 6, false ) )
			.mockResolvedValueOnce( buildResponse( 4, 6, true ) );

		const { result, onError, onFinish } = renderImport( { total: 6 } );

		await act( async () => {
			await result.current.run();
		} );

		expect( onError ).not.toHaveBeenCalled();
		expect( onFinish ).toHaveBeenCalledTimes( 1 );
	} );

	it.each( [
		[
			'a refused resend',
			{
				code: 'rest_too_many_requests',
				message: 'Too many requests.',
				data: { status: 429 },
			},
			4,
		],
		[
			'a 400',
			{
				code: 'woocommerce_fulfillments_import_mapping_invalid',
				message: 'Mapping is missing required column(s).',
				data: { status: 400 },
			},
			1,
		],
	] )(
		'keeps the may-have-finished warning after %s',
		async ( _label, error, attempts ) => {
			jest.useFakeTimers();
			mockedRunChunk.mockRejectedValueOnce(
				new Error( 'Failed to fetch' )
			);
			for ( let i = 0; i < attempts; i++ ) {
				mockedRunChunk.mockRejectedValueOnce( error );
			}
			mockedRunChunk.mockRejectedValueOnce( TOKEN_INVALID );

			const { result, onError } = renderImport();

			// Lost response, then the error, then the ended session.
			await runToEnd( result );
			await runToEnd( result );
			await runToEnd( result );

			expect( mockedRunChunk ).toHaveBeenCalledTimes( attempts + 2 );
			expect( onError ).toHaveBeenLastCalledWith(
				'The connection dropped near the end of the import, so it may have finished. Check your orders before you upload the file again.',
				true
			);
		}
	);

	it( 'warns that the import may have finished after a response behind the confirmed offset', async () => {
		mockedRunChunk
			.mockResolvedValueOnce( buildResponse( 3, 6, false ) )
			.mockResolvedValueOnce( buildResponse( 1, 6, true ) )
			.mockRejectedValueOnce( TOKEN_INVALID );

		const { result, onError } = renderImport( { total: 6 } );

		await act( async () => {
			await result.current.run();
		} );
		await act( async () => {
			await result.current.run();
		} );

		expect( onError ).toHaveBeenLastCalledWith(
			'The connection dropped near the end of the import, so it may have finished. Check your orders before you upload the file again.',
			true
		);
	} );

	it( 'warns that the import may have finished after an unexpected response', async () => {
		mockedRunChunk
			.mockResolvedValueOnce( {
				processed: 'x',
			} as unknown as RunChunkResponse )
			.mockRejectedValueOnce( TOKEN_INVALID );

		const { result, onError } = renderImport();

		await act( async () => {
			await result.current.run();
		} );
		await act( async () => {
			await result.current.run();
		} );

		expect( onError ).toHaveBeenLastCalledWith(
			'The connection dropped near the end of the import, so it may have finished. Check your orders before you upload the file again.',
			true
		);
	} );

	it( 'stops when a chunk reports no progress instead of looping', async () => {
		mockedRunChunk.mockResolvedValue( buildResponse( 0, 4, false ) );

		const { result, onError, onFinish } = renderImport();

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );
		expect( onError ).toHaveBeenCalledWith(
			'The import stopped making progress. Please try again.',
			false
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
			'The import did not finish. Please try again.',
			false
		);
		expect( onFinish ).not.toHaveBeenCalled();
	} );

	it( 'keeps going past the total when the total is unknown', async () => {
		mockedRunChunk
			.mockResolvedValueOnce( buildResponse( 2, 0, false ) )
			.mockResolvedValueOnce( buildResponse( 3, 0, true ) );

		const { result, onError, onFinish } = renderImport( { total: 0 } );

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 2 );
		expect( onError ).not.toHaveBeenCalled();
		expect( onFinish ).toHaveBeenCalled();
	} );

	it( 'treats a done response without a summary as a finished session', async () => {
		mockedRunChunk.mockResolvedValue( buildResponse( 4, 4, true, false ) );

		const { result, onError, onFinish } = renderImport();

		await act( async () => {
			await result.current.run();
		} );

		expect( onFinish ).not.toHaveBeenCalled();
		expect( onError ).toHaveBeenCalledWith(
			'The import finished, but its summary could not be loaded. Check your orders to see what was imported.',
			true
		);
	} );

	it( 'aborts the request on unmount and reports nothing', async () => {
		const chunk = deferredChunk();
		const { result, unmount, onError, onChunk } = renderImport();

		let running!: Promise< void >;
		act( () => {
			running = result.current.run();
		} );
		unmount();
		await act( async () => {
			await running;
		} );

		expect( chunk.signal()?.aborted ).toBe( true );
		expect( onChunk ).not.toHaveBeenCalled();
		expect( onError ).not.toHaveBeenCalled();
	} );

	it( 'sends nothing more when unmounted during a backoff wait', async () => {
		jest.useFakeTimers();
		mockedRunChunk.mockRejectedValue( CHUNK_IN_PROGRESS );
		const { result, unmount, onError } = renderImport();

		let running!: Promise< void >;
		await act( async () => {
			running = result.current.run();
			await jest.advanceTimersByTimeAsync( 10 );
		} );
		unmount();
		await act( async () => {
			await jest.runAllTimersAsync();
			await running;
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );
		expect( onError ).not.toHaveBeenCalled();
	} );

	it( 'ignores a response that lands after unmount', async () => {
		// Unlike deferredChunk this does not reject on abort: the request has
		// already left and its response arrives after the unmount.
		let resolve!: ( response: RunChunkResponse ) => void;
		mockedRunChunk.mockImplementationOnce(
			() =>
				new Promise< RunChunkResponse >( ( res ) => {
					resolve = res;
				} )
		);
		const { result, unmount, onChunk, onFinish, onError } = renderImport();

		let running!: Promise< void >;
		act( () => {
			running = result.current.run();
		} );
		unmount();
		await act( async () => {
			resolve( buildResponse( 4, 4, true ) );
			await running;
		} );

		expect( onChunk ).not.toHaveBeenCalled();
		expect( onFinish ).not.toHaveBeenCalled();
		expect( onError ).not.toHaveBeenCalled();
	} );

	it( 'starts again after a StrictMode remount aborts the first loop', async () => {
		const first = deferredChunk();
		mockedRunChunk.mockResolvedValueOnce( buildResponse( 4, 4, true ) );
		const onFinish = jest.fn();
		const onError = jest.fn();

		// Starts the loop on mount, the way the import step does.
		renderHook(
			() => {
				const { run } = useChunkedImport( {
					token: 'tok',
					total: 4,
					mapping: MAPPING,
					notifyCustomer: false,
					updateExisting: true,
					chunkSize: 2,
					onFinish,
					onError,
				} );
				useEffect( () => {
					void run();
				}, [] ); // eslint-disable-line react-hooks/exhaustive-deps
			},
			{ wrapper: StrictMode }
		);

		await waitFor( () => expect( onFinish ).toHaveBeenCalledTimes( 1 ) );
		expect( first.signal()?.aborted ).toBe( true );
		expect( mockedRunChunk ).toHaveBeenCalledTimes( 2 );
		expect( onError ).not.toHaveBeenCalled();
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
