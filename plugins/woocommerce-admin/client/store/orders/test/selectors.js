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

const { getOrders, isGetOrdersRequesting, isGetOrdersError } = selectors;
jest.mock( '@wordpress/data', () => ( {
	...require.requireActual( '@wordpress/data' ),
	select: jest.fn().mockReturnValue( {} ),
} ) );

const query = { orderby: 'date' };
const queryKey = getJsonString( query );

describe( 'getOrders()', () => {
	it( 'returns an empty array when no orders are available', () => {
		const state = deepFreeze( {} );
		expect( getOrders( state, query ) ).toEqual( [] );
	} );

	it( 'returns stored orders for current query', () => {
		const orders = [ { id: 1214 }, { id: 1215 }, { id: 1216 } ];
		const state = deepFreeze( {
			orders: {
				[ queryKey ]: orders,
			},
		} );
		expect( getOrders( state, query ) ).toEqual( orders );
	} );
} );

describe( 'isGetOrdersRequesting()', () => {
	beforeAll( () => {
		select( 'core/data' ).isResolving = jest.fn().mockReturnValue( false );
	} );

	afterAll( () => {
		select( 'core/data' ).isResolving.mockRestore();
	} );

	function setIsResolving( isResolving ) {
		select( 'core/data' ).isResolving.mockImplementation(
			( reducerKey, selectorName ) =>
				isResolving && reducerKey === 'wc-admin' && selectorName === 'getOrders'
		);
	}

	it( 'returns false if never requested', () => {
		const result = isGetOrdersRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns false if request finished', () => {
		setIsResolving( false );
		const result = isGetOrdersRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns true if requesting', () => {
		setIsResolving( true );
		const result = isGetOrdersRequesting( query );
		expect( result ).toBe( true );
	} );
} );

describe( 'isGetOrdersError()', () => {
	it( 'returns false by default', () => {
		const state = deepFreeze( {} );
		expect( isGetOrdersError( state, query ) ).toEqual( false );
	} );

	it( 'returns true if ERROR constant is found', () => {
		const state = deepFreeze( {
			orders: {
				[ queryKey ]: ERROR,
			},
		} );
		expect( isGetOrdersError( state, query ) ).toEqual( true );
	} );
} );
