/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';
import { useReducedMotion } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { useDelayedSkeleton } from '../index';

jest.mock( '@wordpress/compose', () => ( {
	...jest.requireActual( '@wordpress/compose' ),
	useReducedMotion: jest.fn(),
} ) );

const renderSkeleton = ( isLoading: boolean ) =>
	renderHook( ( loading: boolean ) => useDelayedSkeleton( loading ), {
		initialProps: isLoading,
	} );

describe( 'useDelayedSkeleton', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		jest.mocked( useReducedMotion ).mockReturnValue( false );
		globalThis.wcBlocksSkeletonPhaseStart = undefined;
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'shows a skeleton for at least 300ms', () => {
		const skeleton = renderSkeleton( true );
		act( () => jest.advanceTimersByTime( 100 ) );
		skeleton.rerender( false );

		expect( skeleton.result.current ).toBe( true );
		act( () => jest.advanceTimersByTime( 200 ) );
		expect( skeleton.result.current ).toBe( false );
	} );

	it( 'ends a skeleton that starts later in the phase with the first one', () => {
		const first = renderSkeleton( true );
		act( () => jest.advanceTimersByTime( 200 ) );
		const second = renderSkeleton( true );
		first.rerender( false );
		second.rerender( false );

		act( () => jest.advanceTimersByTime( 100 ) );
		expect( first.result.current ).toBe( false );
		expect( second.result.current ).toBe( false );
	} );

	it( 'ends as soon as loading ends with reduced motion', () => {
		jest.mocked( useReducedMotion ).mockReturnValue( true );
		const skeleton = renderSkeleton( true );
		skeleton.rerender( false );

		expect( skeleton.result.current ).toBe( false );
	} );
} );
