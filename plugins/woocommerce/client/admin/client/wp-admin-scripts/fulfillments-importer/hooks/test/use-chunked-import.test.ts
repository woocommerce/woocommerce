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
		mockedRunChunk
			.mockRejectedValue( new Error( 'persistent failure' ) );

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
		expect( onError ).toHaveBeenCalledWith( 'persistent failure' );
	} );
} );
