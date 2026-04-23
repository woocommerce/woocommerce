/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import { useNetworkStatus } from '../use-network-status';

describe( 'useNetworkStatus', () => {
	// Restore navigator.onLine between tests — we mutate it below.
	let originalOnLine: PropertyDescriptor | undefined;
	beforeAll( () => {
		originalOnLine = Object.getOwnPropertyDescriptor(
			window.navigator,
			'onLine'
		);
	} );
	afterEach( () => {
		if ( originalOnLine ) {
			Object.defineProperty( window.navigator, 'onLine', originalOnLine );
		}
	} );

	it( 'should initially set isNetworkOffline to false when navigator is online', () => {
		Object.defineProperty( window.navigator, 'onLine', {
			configurable: true,
			value: true,
		} );
		const { result } = renderHook( () => useNetworkStatus() );
		expect( result.current ).toBe( false );
	} );

	it( 'should initially set isNetworkOffline to true when navigator is already offline at mount', () => {
		Object.defineProperty( window.navigator, 'onLine', {
			configurable: true,
			value: false,
		} );
		const { result } = renderHook( () => useNetworkStatus() );
		expect( result.current ).toBe( true );
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
