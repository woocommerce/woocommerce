/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import { PaymentSettingsState } from '../types';
import { ACTION_TYPES } from '../action-types';
import {
	providersStub,
	offlinePaymentGatewaysStub,
	suggestionsStub,
	suggestionCategoriesStub,
} from '../test-helpers/stub';

const defaultState: PaymentSettingsState = {
	providers: [],
	offlinePaymentGateways: [],
	suggestions: [],
	suggestionCategories: [],
	isFetching: false,
	isWooPayEligible: false,
	errors: {},
};

const restApiError = {
	code: 'error code',
	message: 'error message',
};

describe( 'payment settings reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle GET_PAYMENT_PROVIDERS_REQUEST', () => {
		const state = reducer( defaultState, {
			type: ACTION_TYPES.GET_PAYMENT_PROVIDERS_REQUEST,
		} );

		expect( state.isFetching ).toBe( true );
	} );

	it( 'should handle GET_PAYMENT_PROVIDERS_ERROR', () => {
		const state = reducer( defaultState, {
			type: ACTION_TYPES.GET_PAYMENT_PROVIDERS_ERROR,
			error: restApiError,
		} );

		expect( state.errors.getPaymentGatewaySuggestions ).toBe(
			restApiError
		);
	} );

	it( 'should handle GET_PAYMENT_PROVIDERS_SUCCESS', () => {
		const state = reducer( defaultState, {
			type: ACTION_TYPES.GET_PAYMENT_PROVIDERS_SUCCESS,
			providers: providersStub,
			offlinePaymentGateways: offlinePaymentGatewaysStub,
			suggestions: suggestionsStub,
			suggestionCategories: suggestionCategoriesStub,
		} );

		expect( state.providers ).toHaveLength( 3 );
		expect( state.providers ).toBe( providersStub );

		expect( state.offlinePaymentGateways ).toHaveLength( 3 );
		expect( state.offlinePaymentGateways ).toBe(
			offlinePaymentGatewaysStub
		);

		expect( state.suggestions ).toHaveLength( 2 );
		expect( state.suggestions ).toBe( suggestionsStub );

		expect( state.suggestionCategories ).toHaveLength( 3 );
		expect( state.suggestionCategories ).toBe( suggestionCategoriesStub );
	} );
} );
