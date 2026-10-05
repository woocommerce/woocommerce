import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';
let { mockQuery } = vi.hoisted( () => {
	const mockQuery = {};
	return {
		mockQuery,
	};
} );

/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import React from 'react';
import { navigateTo } from '@woocommerce/navigation';
vi.mock( '@woocommerce/navigation', () => {
	const mock = {
		getNewPath: vi.fn(
			( query: Record< string, string > ) =>
				'/extensions?' + new URLSearchParams( query ).toString()
		),
		navigateTo: vi.fn(),
		useQuery: vi.fn( () => mockQuery ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/tracks', () => {
	const mock = {
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

/**
 * Internal dependencies
 */
import Search from '../search';
const navigateToMock = navigateTo as unknown as Mock;
function lastNavigationQuery() {
	const url: string = navigateToMock.mock.calls.at( -1 )[ 0 ].url;
	return Object.fromEntries( new URLSearchParams( url.split( '?' )[ 1 ] ) );
}
describe( 'Marketplace search field', () => {
	beforeEach( () => {
		mockQuery = {};
		navigateToMock.mockClear();
	} );
	it( 'shows no clear button while the field is empty', () => {
		render( <Search /> );
		expect( screen.queryByRole( 'button' ) ).toBeNull();
	} );
	it( 'shows a clear button once something is typed', () => {
		render( <Search /> );
		fireEvent.change( screen.getByRole( 'searchbox' ), {
			target: {
				value: 'ship',
			},
		} );
		expect(
			screen.getByRole( 'button', {
				name: 'Reset search',
			} )
		).toBeInTheDocument();
		expect( navigateToMock ).not.toHaveBeenCalled();
	} );
	it( 'runs the search on Enter and moves to the extensions tab from Discover', () => {
		render( <Search /> );
		const input = screen.getByRole( 'searchbox' );
		fireEvent.change( input, {
			target: {
				value: 'shipping',
			},
		} );
		fireEvent.keyUp( input, {
			key: 'Enter',
		} );
		expect( lastNavigationQuery() ).toMatchObject( {
			term: 'shipping',
			tab: 'extensions',
			search: '1',
		} );
	} );
	it( 'clears an active search when the clear button is used', () => {
		mockQuery = {
			tab: 'extensions',
			term: 'shipping',
		};
		render( <Search /> );
		expect( screen.getByRole( 'searchbox' ) ).toHaveValue( 'shipping' );
		fireEvent.click(
			screen.getByRole( 'button', {
				name: 'Reset search',
			} )
		);
		expect( screen.getByRole( 'searchbox' ) ).toHaveValue( '' );
		const query = lastNavigationQuery();
		expect( query.term ).toBeUndefined();
		expect( query.tab ).toBe( 'extensions' );
	} );
	it( 'does not navigate when the field is emptied with no search active', () => {
		render( <Search /> );
		const input = screen.getByRole( 'searchbox' );
		fireEvent.change( input, {
			target: {
				value: 'a',
			},
		} );
		fireEvent.change( input, {
			target: {
				value: '',
			},
		} );
		expect( navigateToMock ).not.toHaveBeenCalled();
		expect( screen.queryByRole( 'button' ) ).toBeNull();
	} );
} );
