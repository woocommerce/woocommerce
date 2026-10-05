import {
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type MockedFunction,
} from 'vitest';

/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	useQRLoginAvailability,
	QRLoginUnavailableReasons,
} from '../useQRLoginAvailability';

vi.mock( '@wordpress/api-fetch' );

const mockApiFetch = apiFetch as unknown as MockedFunction<
	( options: { path: string; method: string } ) => Promise< unknown >
>;

describe( 'useQRLoginAvailability', () => {
	beforeEach( () => {
		vi.clearAllMocks();
	} );

	it( 'starts in the loading state and resolves to available on a positive response', async () => {
		let resolveFetch:
			| ( ( value: { available: boolean; reason: null } ) => void )
			| undefined;
		mockApiFetch.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveFetch = resolve;
			} )
		);

		const { result } = renderHook( () => useQRLoginAvailability() );

		// Synchronously after mount the probe is still in flight.
		expect( result.current.isLoading ).toBe( true );
		expect( result.current.available ).toBe( false );
		expect( result.current.reason ).toBeNull();

		await act( async () => {
			resolveFetch?.( { available: true, reason: null } );
			await Promise.resolve();
		} );

		expect( result.current.isLoading ).toBe( false );
		expect( result.current.available ).toBe( true );
		expect( result.current.reason ).toBeNull();
	} );

	it( 'resolves to unavailable + a reason code when the server reports the feature off', async () => {
		mockApiFetch.mockResolvedValue( {
			available: false,
			reason: QRLoginUnavailableReasons.APPLICATION_PASSWORDS_DISABLED_BY_FILTER,
		} );

		const { result } = renderHook( () => useQRLoginAvailability() );

		await act( async () => {
			await Promise.resolve();
		} );

		expect( result.current.isLoading ).toBe( false );
		expect( result.current.available ).toBe( false );
		expect( result.current.reason ).toBe(
			QRLoginUnavailableReasons.APPLICATION_PASSWORDS_DISABLED_BY_FILTER
		);
	} );

	it( 'falls through to optimistic-available on a network failure so the existing token-fetch path takes over', async () => {
		mockApiFetch.mockRejectedValue( new Error( 'network down' ) );

		const { result } = renderHook( () => useQRLoginAvailability() );

		await act( async () => {
			await Promise.resolve();
		} );

		expect( result.current.isLoading ).toBe( false );
		expect( result.current.available ).toBe( true );
		expect( result.current.reason ).toBeNull();
	} );

	it( 'is defensive against unexpected response shapes (still falls through to optimistic-available)', async () => {
		mockApiFetch.mockResolvedValue( { foo: 'bar' } );

		const { result } = renderHook( () => useQRLoginAvailability() );

		await act( async () => {
			await Promise.resolve();
		} );

		expect( result.current.isLoading ).toBe( false );
		expect( result.current.available ).toBe( true );
	} );

	it( 'hits the expected REST path', async () => {
		mockApiFetch.mockResolvedValue( { available: true, reason: null } );

		renderHook( () => useQRLoginAvailability() );
		await act( async () => {
			await Promise.resolve();
		} );

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc-admin/mobile-app/qr-login-availability',
			method: 'GET',
		} );
	} );
} );
