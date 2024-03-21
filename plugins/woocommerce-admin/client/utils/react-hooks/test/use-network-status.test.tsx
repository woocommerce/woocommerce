/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import { useNetworkStatus } from '../use-network-status';

describe( 'useNetworkStatus', () => {
	it( 'should initially set isNetworkOffline to false', () => {
		const { result } = renderHook( () => useNetworkStatus() );
		expect( result.current ).toBe( false );
	} );

	it( 'should set isNetworkOffline to true when window goes offline', () => {
		const { result } = renderHook( () => useNetworkStatus() );
		act( () => {
			window.dispatchEvent( new Event( 'offline' ) );
		} );
		expect( result.current ).toBe( true );
	} );

	it( 'should set isNetworkOffline to false when window goes online', () => {
		const { result } = renderHook( () => useNetworkStatus() );
		act( () => {
			window.dispatchEvent( new Event( 'offline' ) );
		} );
		act( () => {
			window.dispatchEvent( new Event( 'online' ) );
		} );
		expect( result.current ).toBe( false );
	} );
} );
