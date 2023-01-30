/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';

/**
 * Internal dependencies
 */
import cartReducer from '../reducers';
import { ACTION_TYPES as types } from '../action-types';

describe( 'cartReducer', () => {
	const originalState = deepFreeze( {
		cartData: {
			coupons: [],
			items: [],
			itemsCount: 0,
			itemsWeight: 0,
			needsShipping: true,
			totals: {},
		},
		metaData: {},
		errors: [
			{
				code: '100',
				message: 'Test Error',
				data: {},
			},
		],
	} );
	it( 'sets expected state when a cart is received', () => {
		const testAction = {
			type: types.RECEIVE_CART,
			response: {
				coupons: [],
				items: [],
				itemsCount: 0,
				itemsWeight: 0,
				needsShipping: true,
				totals: {},
			},
		};
		const newState = cartReducer( originalState, testAction );
		expect( newState ).not.toBe( originalState );
		expect( newState.cartData ).toEqual( {
			coupons: [],
			items: [],
			itemsCount: 0,
			itemsWeight: 0,
			needsShipping: true,
			totals: {},
		} );
	} );
	it( 'sets expected state when errors are replaced', () => {
		const testAction = {
			type: types.REPLACE_ERRORS,
			error: {
				code: '101',
				message: 'Test Error',
				data: {},
			},
		};
		const newState = cartReducer( originalState, testAction );
		expect( newState ).not.toBe( originalState );
		expect( newState.errors ).toEqual( [
			{
				code: '101',
				message: 'Test Error',
				data: {},
			},
		] );
	} );
	it( 'sets expected state when an error is added', () => {
		const testAction = {
			type: types.RECEIVE_ERROR,
			error: {
				code: '101',
				message: 'Test Error',
				data: {},
			},
		};
		const newState = cartReducer( originalState, testAction );
		expect( newState ).not.toBe( originalState );
		expect( newState.errors ).toEqual( [
			{
				code: '100',
				message: 'Test Error',
				data: {},
			},
			{
				code: '101',
				message: 'Test Error',
				data: {},
			},
		] );
	} );
	it( 'sets expected state when a coupon is applied', () => {
		const testAction = {
			type: types.APPLYING_COUPON,
			couponCode: 'APPLYME',
		};
		const newState = cartReducer( originalState, testAction );
		expect( newState ).not.toBe( originalState );
		expect( newState.metaData.applyingCoupon ).toEqual( 'APPLYME' );
	} );
	it( 'sets expected state when a coupon is removed', () => {
		const testAction = {
			type: types.REMOVING_COUPON,
			couponCode: 'REMOVEME',
		};
		const newState = cartReducer( originalState, testAction );
		expect( newState ).not.toBe( originalState );
		expect( newState.metaData.removingCoupon ).toEqual( 'REMOVEME' );
	} );
} );
