/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useImportStatus } from '../use-import-status';
import type { ImportStatus } from '../types';

jest.mock( '@wordpress/api-fetch' );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

describe( 'useImportStatus', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		jest.useFakeTimers();
	} );

	afterEach( () => {
		jest.runOnlyPendingTimers();
		jest.useRealTimers();
	} );

	const createMockStatus = (
		overrides: Partial< ImportStatus > = {}
	): ImportStatus => ( {
		mode: 'scheduled',
		last_processed_date: '2024-11-21 00:00:00',
		next_scheduled: '2024-11-21 12:00:00',
		import_in_progress_or_due: false,
		...overrides,
	} );

	it( 'should fetch initial status on mount', async () => {
		const mockStatus = createMockStatus();
		mockApiFetch.mockResolvedValue( mockStatus );

		const { result, waitForNextUpdate } = renderHook( () =>
			useImportStatus()
		);

		expect( result.current.isLoading ).toBe( true );
		expect( result.current.status ).toBeNull();

		await waitForNextUpdate();

		expect( result.current.isLoading ).toBe( false );
		expect( result.current.status ).toEqual( mockStatus );
		expect( result.current.error ).toBeNull();
		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc-analytics/imports/status',
			method: 'GET',
		} );
	} );

	it( 'should start polling when import_in_progress_or_due is true', async () => {
		const mockStatus = createMockStatus( {
			import_in_progress_or_due: true,
		} );
		mockApiFetch.mockResolvedValue( mockStatus );

		const { waitForNextUpdate } = renderHook( () => useImportStatus() );
		await waitForNextUpdate();

		// Initial fetch
		expect( mockApiFetch ).toHaveBeenCalledTimes( 1 );

		// Advance timer by 5 seconds
		act( () => {
			jest.advanceTimersByTime( 5000 );
		} );

		// Wait for the async fetch to complete
		await act( async () => {
			await Promise.resolve();
		} );

		expect( mockApiFetch ).toHaveBeenCalledTimes( 2 );

		// Advance again
		act( () => {
			jest.advanceTimersByTime( 5000 );
		} );

		await act( async () => {
			await Promise.resolve();
		} );

		expect( mockApiFetch ).toHaveBeenCalledTimes( 3 );
	} );

	it( 'should stop polling when import_in_progress_or_due becomes false', async () => {
		const initialStatus = createMockStatus( {
			import_in_progress_or_due: true,
		} );
		const updatedStatus = createMockStatus( {
			import_in_progress_or_due: false,
		} );

		mockApiFetch
			.mockResolvedValueOnce( initialStatus )
			.mockResolvedValueOnce( updatedStatus );

		const { waitForNextUpdate } = renderHook( () => useImportStatus() );
		await waitForNextUpdate();

		// Polling starts
		act( () => {
			jest.advanceTimersByTime( 5000 );
		} );
		await waitForNextUpdate();

		// Now polling should have stopped
		const callCountAfterStop = mockApiFetch.mock.calls.length;

		act( () => {
			jest.advanceTimersByTime( 10000 );
		} );

		// No additional calls should be made
		expect( mockApiFetch ).toHaveBeenCalledTimes( callCountAfterStop );
	} );

	it( 'should trigger import and refetch status', async () => {
		const mockStatus = createMockStatus();
		mockApiFetch.mockResolvedValue( mockStatus );

		const { result, waitForNextUpdate } = renderHook( () =>
			useImportStatus()
		);
		await waitForNextUpdate();

		mockApiFetch.mockClear();

		const updatedStatus = createMockStatus( {
			import_in_progress_or_due: true,
		} );
		mockApiFetch
			.mockResolvedValueOnce( {} ) // POST response
			.mockResolvedValueOnce( updatedStatus ); // GET refetch

		await act( async () => {
			await result.current.triggerImport();
		} );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc-analytics/imports/trigger',
			method: 'POST',
		} );
		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc-analytics/imports/status',
			method: 'GET',
		} );
		expect( result.current.status ).toEqual( updatedStatus );
	} );

	it( 'should handle fetch errors', async () => {
		const errorMessage = 'Network error';
		mockApiFetch.mockRejectedValue( new Error( errorMessage ) );

		const { result, waitForNextUpdate } = renderHook( () =>
			useImportStatus()
		);
		await waitForNextUpdate();

		expect( result.current.error ).toBe( errorMessage );
		expect( result.current.status ).toBeNull();
		expect( result.current.isLoading ).toBe( false );
	} );

	it( 'should handle trigger import errors', async () => {
		const mockStatus = createMockStatus();
		mockApiFetch.mockResolvedValue( mockStatus );

		const { result, waitForNextUpdate } = renderHook( () =>
			useImportStatus()
		);
		await waitForNextUpdate();

		const errorMessage = 'API Error';
		mockApiFetch.mockRejectedValue( new Error( errorMessage ) );

		await expect(
			act( async () => {
				await result.current.triggerImport();
			} )
		).rejects.toThrow( errorMessage );

		expect( result.current.error ).toBe( errorMessage );
	} );

	it( 'should set isTriggeringImport during trigger', async () => {
		const mockStatus = createMockStatus();
		mockApiFetch.mockResolvedValue( mockStatus );

		const { result, waitForNextUpdate } = renderHook( () =>
			useImportStatus()
		);
		await waitForNextUpdate();

		expect( result.current.isTriggeringImport ).toBe( false );

		let triggerPromise: Promise< void >;
		act( () => {
			triggerPromise = result.current.triggerImport();
		} );

		expect( result.current.isTriggeringImport ).toBe( true );

		await act( async () => {
			await triggerPromise;
		} );

		expect( result.current.isTriggeringImport ).toBe( false );
	} );

	it( 'should cleanup interval on unmount', async () => {
		const mockStatus = createMockStatus( {
			import_in_progress_or_due: true,
		} );
		mockApiFetch.mockResolvedValue( mockStatus );

		const { unmount, waitForNextUpdate } = renderHook( () =>
			useImportStatus()
		);
		await waitForNextUpdate();

		const callCountBeforeUnmount = mockApiFetch.mock.calls.length;
		unmount();

		// Advance timers to verify no more calls
		act( () => {
			jest.advanceTimersByTime( 10000 );
		} );

		// No additional calls after unmount
		expect( mockApiFetch ).toHaveBeenCalledTimes( callCountBeforeUnmount );
	} );

	it( 'should not start polling when import_in_progress_or_due is false', async () => {
		const mockStatus = createMockStatus( {
			import_in_progress_or_due: false,
		} );
		mockApiFetch.mockResolvedValue( mockStatus );

		const { waitForNextUpdate } = renderHook( () => useImportStatus() );
		await waitForNextUpdate();

		// Only initial fetch
		expect( mockApiFetch ).toHaveBeenCalledTimes( 1 );

		// Advance timers
		act( () => {
			jest.advanceTimersByTime( 15000 );
		} );

		// No additional calls
		expect( mockApiFetch ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should handle non-Error fetch failures gracefully', async () => {
		mockApiFetch.mockRejectedValue( 'String error' );

		const { result, waitForNextUpdate } = renderHook( () =>
			useImportStatus()
		);
		await waitForNextUpdate();

		expect( result.current.error ).toBe( 'Failed to fetch status' );
	} );

	it( 'should handle non-Error trigger failures gracefully', async () => {
		const mockStatus = createMockStatus();
		mockApiFetch.mockResolvedValue( mockStatus );

		const { result, waitForNextUpdate } = renderHook( () =>
			useImportStatus()
		);
		await waitForNextUpdate();

		mockApiFetch.mockRejectedValue( 'String error' );

		await expect(
			act( async () => {
				await result.current.triggerImport();
			} )
		).rejects.toBe( 'String error' );

		expect( result.current.error ).toBe( 'Failed to trigger import' );
	} );
} );
