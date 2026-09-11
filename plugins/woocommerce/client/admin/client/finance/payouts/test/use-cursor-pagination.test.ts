/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react-hooks/dom';

/**
 * Internal dependencies
 */
import {
	getPaginationInfo,
	useCursorPagination,
} from '../use-cursor-pagination';

describe( 'getPaginationInfo', () => {
	it( 'reports nothing while the page is unknown', () => {
		expect(
			getPaginationInfo(
				{ page: 1, perPage: 10, cursors: [ null ] },
				null,
				false
			)
		).toEqual( { totalItems: 0, totalPages: 0 } );
	} );

	it( 'exposes one extra page while the API reports more items', () => {
		expect(
			getPaginationInfo(
				{ page: 1, perPage: 10, cursors: [ null ] },
				10,
				true
			)
		).toEqual( { totalItems: 11, totalPages: 2 } );
	} );

	it( 'stops at the current page when there are no more items', () => {
		expect(
			getPaginationInfo(
				{ page: 3, perPage: 10, cursors: [ null, 'b', 'c' ] },
				4,
				false
			)
		).toEqual( { totalItems: 24, totalPages: 3 } );
	} );

	it( 'keeps already visited pages reachable when navigating back', () => {
		expect(
			getPaginationInfo(
				{ page: 1, perPage: 10, cursors: [ null, 'b', 'c' ] },
				10,
				true
			)
		).toEqual( { totalItems: 11, totalPages: 3 } );
	} );
} );

describe( 'useCursorPagination', () => {
	it( 'starts on the first page without a cursor', () => {
		const { result } = renderHook( () => useCursorPagination( 25 ) );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBe( 25 );
		expect( result.current.cursor ).toBeNull();
	} );

	it( 'registers the next cursor once per page and only at the frontier', () => {
		const { result } = renderHook( () => useCursorPagination( 10 ) );

		act( () => result.current.registerNext( 'page-2' ) );
		act( () => result.current.registerNext( 'page-2-again' ) );

		expect( result.current.state.cursors ).toEqual( [ null, 'page-2' ] );

		act( () => result.current.registerNext( null ) );

		expect( result.current.state.cursors ).toEqual( [ null, 'page-2' ] );
	} );

	it( 'only moves to pages with a known cursor', () => {
		const { result } = renderHook( () => useCursorPagination( 10 ) );
		let moved = false;

		act( () => {
			moved = result.current.goToPage( 2 );
		} );
		expect( moved ).toBe( false );
		expect( result.current.page ).toBe( 1 );

		act( () => result.current.registerNext( 'page-2' ) );
		act( () => {
			moved = result.current.goToPage( 2 );
		} );
		expect( moved ).toBe( true );
		expect( result.current.page ).toBe( 2 );
		expect( result.current.cursor ).toBe( 'page-2' );

		act( () => {
			moved = result.current.goToPage( 0 );
		} );
		expect( moved ).toBe( false );
	} );

	it( 'does not register a cursor when not on the last known page', () => {
		const { result } = renderHook( () => useCursorPagination( 10 ) );

		act( () => result.current.registerNext( 'page-2' ) );
		act( () => {
			result.current.goToPage( 2 );
		} );
		act( () => result.current.registerNext( 'page-3' ) );
		act( () => {
			result.current.goToPage( 1 );
		} );
		act( () => result.current.registerNext( 'page-2-replayed' ) );

		expect( result.current.state.cursors ).toEqual( [
			null,
			'page-2',
			'page-3',
		] );
	} );

	it( 'resets to the first page and optionally changes the page size', () => {
		const { result } = renderHook( () => useCursorPagination( 10 ) );

		act( () => result.current.registerNext( 'page-2' ) );
		act( () => {
			result.current.goToPage( 2 );
		} );
		act( () => result.current.reset( 50 ) );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBe( 50 );
		expect( result.current.state.cursors ).toEqual( [ null ] );

		act( () => result.current.reset() );
		expect( result.current.perPage ).toBe( 50 );
	} );
} );
