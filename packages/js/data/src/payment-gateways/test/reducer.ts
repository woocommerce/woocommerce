/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import { ACTION_TYPES } from '../action-types';
import { PluginsState } from '../types';
import { paymentGatewaysStub } from '../test-helpers/stub';

const defaultState: PluginsState = {
	paymentGateways: [],
	isUpdating: false,
	errors: {},
};

const restApiError = {
	code: 'error code',
	message: 'error message',
};

describe( 'plugins reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle UPDATE_PAYMENT_GATEWAY_REQUEST', () => {
		const state = reducer( defaultState, {
			type: ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_REQUEST,
		} );

		expect( state.isUpdating ).toBe( true );
	} );

	it( 'should handle GET_PAYMENT_GATEWAYS_ERROR', () => {
		const state = reducer( defaultState, {
			type: ACTION_TYPES.GET_PAYMENT_GATEWAYS_ERROR,
			error: restApiError,
		} );

		expect( state.errors.getPaymentGateways ).toBe( restApiError );
	} );

	it( 'should handle GET_PAYMENT_GATEWAY_ERROR', () => {
		const state = reducer( defaultState, {
			type: ACTION_TYPES.GET_PAYMENT_GATEWAY_ERROR,
			error: restApiError,
		} );

		expect( state.errors.getPaymentGateway ).toBe( restApiError );
	} );

	it( 'should handle UPDATE_PAYMENT_GATEWAY_ERROR', () => {
		const state = reducer( defaultState, {
			type: ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_ERROR,
			error: restApiError,
		} );

		expect( state.errors.updatePaymentGateway ).toBe( restApiError );
		expect( state.isUpdating ).toBe( false );
	} );

	it( 'should handle GET_PAYMENT_GATEWAYS_SUCCESS', () => {
		const state = reducer( defaultState, {
			type: ACTION_TYPES.GET_PAYMENT_GATEWAYS_SUCCESS,
			paymentGateways: paymentGatewaysStub,
		} );

		expect( state.paymentGateways ).toHaveLength( 2 );
		expect( state.paymentGateways ).toBe( paymentGatewaysStub );
	} );

	it( 'should replace an existing payment gateway on UPDATE_PAYMENT_GATEWAY_SUCCESS', () => {
		const updatedPaymentGateway = {
			...paymentGatewaysStub[ 1 ],
			description: 'update test',
		};
		const state = reducer(
			{
				...defaultState,
				paymentGateways: paymentGatewaysStub,
			},
			{
				type: ACTION_TYPES.UPDATE_PAYMENT_GATEWAY_SUCCESS,
				paymentGateway: updatedPaymentGateway,
			}
		);

		expect( state.paymentGateways[ 1 ].id ).toBe(
			paymentGatewaysStub[ 1 ].id
		);
		expect( state.paymentGateways[ 1 ].description ).toBe( 'update test' );
	} );
} );
