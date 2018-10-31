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

const { getVariations, isGetVariationsRequesting, isGetVariationsError } = selectors;
jest.mock( '@wordpress/data', () => ( {
	...require.requireActual( '@wordpress/data' ),
	select: jest.fn().mockReturnValue( {} ),
} ) );

const query = { orderby: 'date' };
const queryKey = getJsonString( query );

describe( 'getVariations', () => {
	it( 'returns an empty array when no query matches values in state', () => {
		const state = deepFreeze( {} );
		expect( getVariations( state, query ) ).toEqual( [] );
	} );

	it( 'returns variations for a given query', () => {
		const variations = [
			{
				id: 1,
				attributes: [],
			},
		];
		const state = deepFreeze( {
			variations: {
				[ queryKey ]: variations,
			},
		} );
		expect( getVariations( state, query ) ).toEqual( variations );
	} );
} );

describe( 'isGetVariationsRequesting', () => {
	beforeAll( () => {
		select( 'core/data' ).isResolving = jest.fn().mockReturnValue( false );
	} );

	afterAll( () => {
		select( 'core/data' ).isResolving.mockRestore();
	} );

	function setIsResolving( isResolving ) {
		select( 'core/data' ).isResolving.mockImplementation(
			( reducerKey, selectorName ) =>
				isResolving && reducerKey === 'wc-admin' && selectorName === 'getVariations'
		);
	}

	it( 'returns false if never requested', () => {
		const result = isGetVariationsRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns false if request finished', () => {
		setIsResolving( false );
		const result = isGetVariationsRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns true if requesting', () => {
		setIsResolving( true );
		const result = isGetVariationsRequesting( query );
		expect( result ).toBe( true );
	} );
} );

describe( 'isGetVariationsError', () => {
	it( 'returns false by default', () => {
		const state = deepFreeze( {} );
		expect( isGetVariationsError( state, query ) ).toEqual( false );
	} );
	it( 'returns true if ERROR constant is found', () => {
		const state = deepFreeze( {
			variations: {
				[ queryKey ]: ERROR,
			},
		} );
		expect( isGetVariationsError( state, query ) ).toEqual( true );
	} );
} );
