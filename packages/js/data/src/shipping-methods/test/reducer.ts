/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer, { ShippingMethodsState } from '../reducer';
import { ACTION_TYPES } from '../action-types';
import { shippingMethodsStub } from './helpers/stub';
import { Actions } from '../actions';

const defaultState: ShippingMethodsState = {
	shippingMethods: [],
	isUpdating: false,
	errors: {},
};

const restApiError = {
	code: 'error code',
	message: 'error message',
};

describe( 'Shipping methods reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined, {} as Actions );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle GET_SHIPPING_METHODS_REQUEST', () => {
		const state = reducer( defaultState, {
			type: ACTION_TYPES.GET_SHIPPING_METHODS_REQUEST,
		} );

		expect( state.isUpdating ).toBe( true );
	} );

	it( 'should handle GET_SHIPPING_METHODS_ERROR', () => {
		const state = reducer( defaultState, {
			type: ACTION_TYPES.GET_SHIPPING_METHODS_ERROR,
			error: restApiError,
		} );

		expect( state.errors.getShippingMethods ).toBe( restApiError );
	} );

	it( 'should handle GET_SHIPPING_METHODS_SUCCESS', () => {
		const state = reducer( defaultState, {
			type: ACTION_TYPES.GET_SHIPPING_METHODS_SUCCESS,
			shippingMethods: shippingMethodsStub,
		} );

		expect( state.shippingMethods ).toHaveLength( 2 );
		expect( state.shippingMethods ).toBe( shippingMethodsStub );
	} );
} );
