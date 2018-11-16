/**
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
import couponsReducer from '../reducer';
import { getJsonString } from 'store/utils';

describe( 'couponsReducer()', () => {
	it( 'returns an empty data object by default', () => {
		const state = couponsReducer( undefined, {} );
		expect( state ).toEqual( {} );
	} );

	it( 'returns with received coupons data', () => {
		const originalState = deepFreeze( {} );
		const query = {
			orderby: 'orders_count',
		};
		const coupons = [ { coupon_id: 1214 }, { coupon_id: 1215 }, { coupon_id: 1216 } ];

		const state = couponsReducer( originalState, {
			type: 'SET_COUPONS',
			query,
			coupons,
		} );

		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( coupons );
	} );

	it( 'tracks multiple queries in coupons data', () => {
		const otherQuery = {
			orderby: 'coupon_id',
		};
		const otherQueryKey = getJsonString( otherQuery );
		const otherCoupons = [ { coupon_id: 1 }, { coupon_id: 2 }, { coupon_id: 3 } ];
		const otherQueryState = {
			[ otherQueryKey ]: otherCoupons,
		};
		const originalState = deepFreeze( otherQueryState );
		const query = {
			orderby: 'orders_count',
		};
		const coupons = [ { coupon_id: 1214 }, { coupon_id: 1215 }, { coupon_id: 1216 } ];

		const state = couponsReducer( originalState, {
			type: 'SET_COUPONS',
			query,
			coupons,
		} );

		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( coupons );
		expect( state[ otherQueryKey ] ).toEqual( otherCoupons );
	} );

	it( 'returns with received error data', () => {
		const originalState = deepFreeze( {} );
		const query = {
			orderby: 'orders_count',
		};

		const state = couponsReducer( originalState, {
			type: 'SET_COUPONS_ERROR',
			query,
		} );

		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( ERROR );
	} );
} );
