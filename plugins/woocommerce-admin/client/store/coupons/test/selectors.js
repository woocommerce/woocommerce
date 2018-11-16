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

const { getCoupons, isGetCouponsRequesting, isGetCouponsError } = selectors;
jest.mock( '@wordpress/data', () => ( {
	...require.requireActual( '@wordpress/data' ),
	select: jest.fn().mockReturnValue( {} ),
} ) );

const query = { orderby: 'date' };
const queryKey = getJsonString( query );

describe( 'getCoupons()', () => {
	it( 'returns an empty array when no coupons are available', () => {
		const state = deepFreeze( {} );
		expect( getCoupons( state, query ) ).toEqual( [] );
	} );

	it( 'returns stored coupons for current query', () => {
		const coupons = [ { coupon_id: 1214 }, { coupon_id: 1215 }, { coupon_id: 1216 } ];
		const state = deepFreeze( {
			coupons: {
				[ queryKey ]: coupons,
			},
		} );
		expect( getCoupons( state, query ) ).toEqual( coupons );
	} );
} );

describe( 'isGetCouponsRequesting()', () => {
	beforeAll( () => {
		select( 'core/data' ).isResolving = jest.fn().mockReturnValue( false );
	} );

	afterAll( () => {
		select( 'core/data' ).isResolving.mockRestore();
	} );

	function setIsResolving( isResolving ) {
		select( 'core/data' ).isResolving.mockImplementation(
			( reducerKey, selectorName ) =>
				isResolving && reducerKey === 'wc-admin' && selectorName === 'getCoupons'
		);
	}

	it( 'returns false if never requested', () => {
		const result = isGetCouponsRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns false if request finished', () => {
		setIsResolving( false );
		const result = isGetCouponsRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns true if requesting', () => {
		setIsResolving( true );
		const result = isGetCouponsRequesting( query );
		expect( result ).toBe( true );
	} );
} );

describe( 'isGetCouponsError()', () => {
	it( 'returns false by default', () => {
		const state = deepFreeze( {} );
		expect( isGetCouponsError( state, query ) ).toEqual( false );
	} );

	it( 'returns true if ERROR constant is found', () => {
		const state = deepFreeze( {
			coupons: {
				[ queryKey ]: ERROR,
			},
		} );
		expect( isGetCouponsError( state, query ) ).toEqual( true );
	} );
} );
