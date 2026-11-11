/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	MAX_SKELETON_WAIT,
	MIN_SKELETON_TIME,
	useSkeletonPhase,
	useSkeletonPhaseValue,
} from '../use-skeleton-phase';

const mountedSkeletons: Array< { unmount: () => void } > = [];

const renderSkeleton = ( isLoading: boolean ) => {
	const skeleton = renderHook(
		( { isLoading: loading }: { isLoading: boolean } ) =>
			useSkeletonPhase( loading ),
		{ initialProps: { isLoading } }
	);
	mountedSkeletons.push( skeleton );
	return skeleton;
};

describe( 'useSkeletonPhase', () => {
	const originalMatchMedia = window.matchMedia;

	const setReducedMotion = ( isReducedMotion: boolean ) => {
		window.matchMedia = jest.fn().mockImplementation( ( query ) => ( {
			matches: isReducedMotion,
			media: query,
		} ) );
	};

	beforeEach( () => {
		jest.useFakeTimers();
		setReducedMotion( false );
	} );

	afterEach( () => {
		// Unmount first, so that the phase ends on the fake timers and does not hold the next test.
		mountedSkeletons.splice( 0 ).forEach( ( { unmount } ) => unmount() );
		act( () => jest.runOnlyPendingTimers() );
		jest.useRealTimers();
		window.matchMedia = originalMatchMedia;
	} );

	it( 'shows the skeleton for a short minimum time, so that it does not flash', () => {
		const { result, rerender } = renderSkeleton( true );
		expect( result.current ).toBe( true );

		act( () => jest.advanceTimersByTime( 100 ) );
		rerender( { isLoading: false } );
		expect( result.current ).toBe( true );

		act( () => jest.advanceTimersByTime( MIN_SKELETON_TIME - 100 ) );
		expect( result.current ).toBe( false );
	} );

	it( 'ends as soon as the content loads with reduced motion', () => {
		setReducedMotion( true );
		const { result, rerender } = renderSkeleton( true );

		rerender( { isLoading: false } );
		expect( result.current ).toBe( false );
	} );

	it( 'ends all the skeletons of a loading phase together', () => {
		const first = renderSkeleton( true );
		const second = renderSkeleton( true );

		act( () => jest.advanceTimersByTime( MIN_SKELETON_TIME ) );
		first.rerender( { isLoading: false } );
		expect( first.result.current ).toBe( true );

		act( () => jest.advanceTimersByTime( 200 ) );
		expect( first.result.current ).toBe( true );

		second.rerender( { isLoading: false } );
		expect( first.result.current ).toBe( false );
		expect( second.result.current ).toBe( false );
	} );

	it( 'shows content that did not load during the phase', () => {
		renderSkeleton( true );
		const { result } = renderSkeleton( false );

		expect( result.current ).toBe( false );
	} );

	it( 'does not let a skeleton that never ends hold the others', () => {
		const stuck = renderSkeleton( true );
		const first = renderSkeleton( true );

		act( () => jest.advanceTimersByTime( MIN_SKELETON_TIME ) );
		first.rerender( { isLoading: false } );
		act( () => jest.advanceTimersByTime( MAX_SKELETON_WAIT ) );
		expect( first.result.current ).toBe( false );
		expect( stuck.result.current ).toBe( true );

		// A later phase does not wait for it either.
		const later = renderSkeleton( true );
		act( () => jest.advanceTimersByTime( MIN_SKELETON_TIME ) );
		later.rerender( { isLoading: false } );
		expect( later.result.current ).toBe( false );
		expect( stuck.result.current ).toBe( true );

		stuck.rerender( { isLoading: false } );
		expect( stuck.result.current ).toBe( false );
	} );
} );

describe( 'useSkeletonPhaseValue', () => {
	it( 'holds the value while skeletons show, and changes it when they end', () => {
		const { result, rerender } = renderHook(
			( { value }: { value: number } ) => useSkeletonPhaseValue( value ),
			{ initialProps: { value: 1 } }
		);
		const skeleton = renderSkeleton( true );

		rerender( { value: 2 } );
		expect( result.current ).toBe( 1 );

		// Jest prefers reduced motion, so the skeleton ends as soon as it loads.
		skeleton.rerender( { isLoading: false } );
		expect( result.current ).toBe( 2 );
	} );

	it( 'passes the value through when no skeleton shows', () => {
		const { result, rerender } = renderHook(
			( { value }: { value: number } ) => useSkeletonPhaseValue( value ),
			{ initialProps: { value: 1 } }
		);

		rerender( { value: 2 } );
		expect( result.current ).toBe( 2 );
	} );
} );
