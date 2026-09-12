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
import type { RunChunkResponse } from '../../data/types';

const mockedRunChunk = runChunk as jest.MockedFunction< typeof runChunk >;

function buildResponse(
	processed: number,
	total: number,
	done: boolean
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
		...( done
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

describe( 'useChunkedImport', () => {
	beforeEach( () => {
		mockedRunChunk.mockReset();
	} );

	it( 'loops chunks until the server reports done and invokes onFinish', async () => {
		mockedRunChunk
			.mockResolvedValueOnce( buildResponse( 2, 4, false ) )
			.mockResolvedValueOnce( buildResponse( 4, 4, true ) );

		const onChunk = jest.fn();
		const onFinish = jest.fn();
		const onError = jest.fn();

		const { result } = renderHook( () =>
			useChunkedImport( {
				token: 'tok',
				total: 4,
				mapping: {
					0: 'order_number',
					1: 'tracking_number',
					2: 'shipment_provider',
				},
				notifyCustomer: false,
				updateExisting: true,
				chunkSize: 2,
				onChunk,
				onFinish,
				onError,
			} )
		);

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 2 );
		expect( onChunk ).toHaveBeenCalledTimes( 2 );
		expect( onFinish ).toHaveBeenCalledWith(
			expect.objectContaining( { created: 4 } )
		);
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

		const { result } = renderHook( () =>
			useChunkedImport( {
				token: 'tok',
				total: 2,
				mapping: { 0: 'order_number' },
				notifyCustomer: false,
				updateExisting: true,
				onChunk: jest.fn(),
				onFinish: jest.fn(),
				onError: jest.fn(),
			} )
		);

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledWith(
			expect.objectContaining( { limit: 500 } )
		);
		delete window.wcFulfillmentsImporterSettings;
	} );

	it( 'retries a transient chunk failure with backoff before succeeding', async () => {
		mockedRunChunk
			.mockRejectedValueOnce( new Error( 'flaky network' ) )
			.mockResolvedValueOnce( buildResponse( 2, 2, true ) );

		const onError = jest.fn();
		const onFinish = jest.fn();

		const { result } = renderHook( () =>
			useChunkedImport( {
				token: 'tok',
				total: 2,
				mapping: {
					0: 'order_number',
					1: 'tracking_number',
					2: 'shipment_provider',
				},
				notifyCustomer: false,
				updateExisting: true,
				chunkSize: 2,
				onFinish,
				onError,
			} )
		);

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 2 );
		expect( onError ).not.toHaveBeenCalled();
		expect( onFinish ).toHaveBeenCalled();
	} );

	it( 'surfaces an error once retries are exhausted', async () => {
		mockedRunChunk.mockRejectedValue( new Error( 'persistent failure' ) );

		const onError = jest.fn();

		const { result } = renderHook( () =>
			useChunkedImport( {
				token: 'tok',
				total: 2,
				mapping: {
					0: 'order_number',
					1: 'tracking_number',
					2: 'shipment_provider',
				},
				notifyCustomer: false,
				updateExisting: true,
				chunkSize: 2,
				onError,
			} )
		);

		await act( async () => {
			await result.current.run();
		} );

		// One initial attempt + two retries.
		expect( mockedRunChunk ).toHaveBeenCalledTimes( 3 );
		expect( onError ).toHaveBeenCalledWith( 'persistent failure', false );
	} );

	it( 'fails immediately without retrying on a 4xx response', async () => {
		mockedRunChunk.mockRejectedValue( {
			code: 'woocommerce_fulfillments_import_mapping_invalid',
			message: 'Mapping is missing required column(s).',
			data: { status: 400 },
		} );

		const onError = jest.fn();

		const { result } = renderHook( () =>
			useChunkedImport( {
				token: 'tok',
				total: 2,
				mapping: {},
				notifyCustomer: false,
				updateExisting: true,
				chunkSize: 2,
				onError,
			} )
		);

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );
		expect( onError ).toHaveBeenCalledWith(
			'Mapping is missing required column(s).',
			false
		);
	} );

	it( 'flags an expired session so the caller can stop offering a retry', async () => {
		mockedRunChunk.mockRejectedValue( {
			code: 'woocommerce_fulfillments_import_token_invalid',
			message: 'Import session is missing or has expired.',
			data: { status: 400 },
		} );

		const onError = jest.fn();

		const { result } = renderHook( () =>
			useChunkedImport( {
				token: 'tok',
				total: 2,
				mapping: {},
				notifyCustomer: false,
				updateExisting: true,
				chunkSize: 2,
				onError,
			} )
		);

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 1 );
		expect( onError ).toHaveBeenCalledWith(
			'Import session is missing or has expired.',
			true
		);
	} );

	it( 'retries a 409 chunk-in-progress conflict until the lock clears', async () => {
		mockedRunChunk
			.mockRejectedValueOnce( {
				code: 'woocommerce_fulfillments_import_chunk_in_progress',
				message:
					'Another chunk of this import is still being processed.',
				data: { status: 409 },
			} )
			.mockResolvedValueOnce( buildResponse( 2, 2, true ) );

		const onError = jest.fn();
		const onFinish = jest.fn();

		const { result } = renderHook( () =>
			useChunkedImport( {
				token: 'tok',
				total: 2,
				mapping: {},
				notifyCustomer: false,
				updateExisting: true,
				chunkSize: 2,
				onFinish,
				onError,
			} )
		);

		await act( async () => {
			await result.current.run();
		} );

		expect( mockedRunChunk ).toHaveBeenCalledTimes( 2 );
		expect( onError ).not.toHaveBeenCalled();
		expect( onFinish ).toHaveBeenCalled();
	} );
} );
