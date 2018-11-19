/*
* @format
*/

/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import { getJsonString } from 'store/utils';
import selectors from '../selectors';

const { getTaxes, isGetTaxesRequesting, isGetTaxesError } = selectors;
jest.mock( '@wordpress/data', () => ( {
	...require.requireActual( '@wordpress/data' ),
	select: jest.fn().mockReturnValue( {} ),
} ) );

const query = { orderby: 'tax_rate_id' };
const queryKey = getJsonString( query );

describe( 'getTaxes()', () => {
	it( 'returns an empty array when no taxes are available', () => {
		const state = deepFreeze( {} );
		expect( getTaxes( state, query ) ).toEqual( [] );
	} );

	it( 'returns stored taxes for current query', () => {
		const taxes = [ { tax_rate_id: 1214 }, { tax_rate_id: 1215 }, { tax_rate_id: 1216 } ];
		const state = deepFreeze( {
			taxes: {
				[ queryKey ]: taxes,
			},
		} );
		expect( getTaxes( state, query ) ).toEqual( taxes );
	} );
} );

describe( 'isGetTaxesRequesting()', () => {
	beforeAll( () => {
		select( 'core/data' ).isResolving = jest.fn().mockReturnValue( false );
	} );

	afterAll( () => {
		select( 'core/data' ).isResolving.mockRestore();
	} );

	function setIsResolving( isResolving ) {
		select( 'core/data' ).isResolving.mockImplementation(
			( reducerKey, selectorName ) =>
				isResolving && reducerKey === 'wc-admin' && selectorName === 'getTaxes'
		);
	}

	it( 'returns false if never requested', () => {
		const result = isGetTaxesRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns false if request finished', () => {
		setIsResolving( false );
		const result = isGetTaxesRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns true if requesting', () => {
		setIsResolving( true );
		const result = isGetTaxesRequesting( query );
		expect( result ).toBe( true );
	} );
} );

describe( 'isGetTaxesError()', () => {
	it( 'returns false by default', () => {
		const state = deepFreeze( {} );
		expect( isGetTaxesError( state, query ) ).toEqual( false );
	} );

	it( 'returns true if ERROR constant is found', () => {
		const state = deepFreeze( {
			taxes: {
				[ queryKey ]: ERROR,
			},
		} );
		expect( isGetTaxesError( state, query ) ).toEqual( true );
	} );
} );
