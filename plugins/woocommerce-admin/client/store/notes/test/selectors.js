/*
* @format
*/

/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import selectors from '../selectors';
import { select } from '@wordpress/data';
import { getJsonString } from 'store/utils';

const { getNotes, isGetNotesRequesting, isGetNotesError } = selectors;
jest.mock( '@wordpress/data', () => ( {
	...require.requireActual( '@wordpress/data' ),
	select: jest.fn().mockReturnValue( {} ),
} ) );

const query = { page: 1 };
const queryKey = getJsonString( query );

describe( 'getNotes()', () => {
	it( 'returns an empty array when no notes are available', () => {
		const state = deepFreeze( {} );
		expect( getNotes( state, query ) ).toEqual( [] );
	} );

	it( 'returns stored notes for current query', () => {
		const notes = [ { id: 1214 }, { id: 1215 }, { id: 1216 } ];
		const state = deepFreeze( {
			notes: {
				[ queryKey ]: notes,
			},
		} );
		expect( getNotes( state, query ) ).toEqual( notes );
	} );
} );

describe( 'isGetNotesRequesting()', () => {
	beforeAll( () => {
		select( 'core/data' ).isResolving = jest.fn().mockReturnValue( false );
	} );

	afterAll( () => {
		select( 'core/data' ).isResolving.mockRestore();
	} );

	function setIsResolving( isResolving ) {
		select( 'core/data' ).isResolving.mockImplementation(
			( reducerKey, selectorName ) =>
				isResolving && reducerKey === 'wc-admin' && selectorName === 'getNotes'
		);
	}

	it( 'returns false if never requested', () => {
		const result = isGetNotesRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns false if request finished', () => {
		setIsResolving( false );
		const result = isGetNotesRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns true if requesting', () => {
		setIsResolving( true );
		const result = isGetNotesRequesting( query );
		expect( result ).toBe( true );
	} );
} );

describe( 'isGetNotesError()', () => {
	it( 'returns false by default', () => {
		const state = deepFreeze( {} );
		expect( isGetNotesError( state, query ) ).toEqual( false );
	} );

	it( 'returns true if ERROR constant is found', () => {
		const state = deepFreeze( {
			notes: {
				[ queryKey ]: ERROR,
			},
		} );
		expect( isGetNotesError( state, query ) ).toEqual( true );
	} );
} );
