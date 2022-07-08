/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';

/**
 * Internal dependencies
 */
import { STATUS as PAYMENT_METHOD_STATUS } from '../../../base/context/providers/cart-checkout/payment-methods/constants';
import reducer from '../reducers';
import { ACTION_TYPES } from '../action-types';

describe( 'paymentMethodDataReducer', () => {
	const originalState = deepFreeze( {
		paymentStatuses: PAYMENT_METHOD_STATUS,
		currentStatus: {
			isPristine: true,
			isStarted: false,
			isProcessing: false,
			isFinished: false,
			hasError: false,
			hasFailed: false,
			isSuccessful: false,
			isDoingExpressPayment: false,
		},
		registeredPaymentMethods: [],
		registeredExpressPaymentMethods: [],
		paymentMethodData: {},
		paymentMethodsInitialized: false,
		expressPaymentMethodsInitialized: false,
		isExpressPaymentMethodActive: false,
		shouldSavePaymentMethod: false,
		errorMessage: '',
		activePaymentMethod: '',
		activeSavedToken: '',
	} );

	it( 'sets state as expected when adding a registered payment method', () => {
		const nextState = reducer( originalState, {
			type: ACTION_TYPES.ADD_REGISTERED_PAYMENT_METHOD,
			name: 'my-new-method',
		} );
		expect( nextState ).toEqual( {
			paymentStatuses: PAYMENT_METHOD_STATUS,
			currentStatus: {
				isPristine: true,
				isStarted: false,
				isProcessing: false,
				isFinished: false,
				hasError: false,
				hasFailed: false,
				isSuccessful: false,
				isDoingExpressPayment: false,
			},
			registeredPaymentMethods: [ 'my-new-method' ],
			registeredExpressPaymentMethods: [],
			paymentMethodData: {},
			paymentMethodsInitialized: false,
			expressPaymentMethodsInitialized: false,
			isExpressPaymentMethodActive: false,
			shouldSavePaymentMethod: false,
			errorMessage: '',
			activePaymentMethod: '',
			activeSavedToken: '',
		} );
	} );

	it( 'sets state as expected when removing a registered payment method', () => {
		const stateWithRegisteredMethod = deepFreeze( {
			paymentStatuses: PAYMENT_METHOD_STATUS,
			currentStatus: {
				isPristine: true,
				isStarted: false,
				isProcessing: false,
				isFinished: false,
				hasError: false,
				hasFailed: false,
				isSuccessful: false,
				isDoingExpressPayment: false,
			},
			registeredPaymentMethods: [ 'my-new-method' ],
			registeredExpressPaymentMethods: [],
			paymentMethodData: {},
			paymentMethodsInitialized: false,
			expressPaymentMethodsInitialized: false,
			isExpressPaymentMethodActive: false,
			shouldSavePaymentMethod: false,
			errorMessage: '',
			activePaymentMethod: '',
			activeSavedToken: '',
		} );
		const nextState = reducer( stateWithRegisteredMethod, {
			type: ACTION_TYPES.REMOVE_REGISTERED_PAYMENT_METHOD,
			name: 'my-new-method',
		} );
		expect( nextState ).toEqual( {
			paymentStatuses: PAYMENT_METHOD_STATUS,
			currentStatus: {
				isPristine: true,
				isStarted: false,
				isProcessing: false,
				isFinished: false,
				hasError: false,
				hasFailed: false,
				isSuccessful: false,
				isDoingExpressPayment: false,
			},
			registeredPaymentMethods: [],
			registeredExpressPaymentMethods: [],
			paymentMethodData: {},
			paymentMethodsInitialized: false,
			expressPaymentMethodsInitialized: false,
			isExpressPaymentMethodActive: false,
			shouldSavePaymentMethod: false,
			errorMessage: '',
			activePaymentMethod: '',
			activeSavedToken: '',
		} );
	} );

	it( 'sets state as expected when adding a registered express payment method', () => {
		const nextState = reducer( originalState, {
			type: ACTION_TYPES.ADD_REGISTERED_EXPRESS_PAYMENT_METHOD,
			name: 'my-new-method',
		} );
		expect( nextState ).toEqual( {
			paymentStatuses: PAYMENT_METHOD_STATUS,
			currentStatus: {
				isPristine: true,
				isStarted: false,
				isProcessing: false,
				isFinished: false,
				hasError: false,
				hasFailed: false,
				isSuccessful: false,
				isDoingExpressPayment: false,
			},
			registeredPaymentMethods: [],
			registeredExpressPaymentMethods: [ 'my-new-method' ],
			paymentMethodData: {},
			paymentMethodsInitialized: false,
			expressPaymentMethodsInitialized: false,
			isExpressPaymentMethodActive: false,
			shouldSavePaymentMethod: false,
			errorMessage: '',
			activePaymentMethod: '',
			activeSavedToken: '',
		} );
	} );

	it( 'sets state as expected when removing a registered express payment method', () => {
		const stateWithRegisteredMethod = deepFreeze( {
			paymentStatuses: PAYMENT_METHOD_STATUS,
			currentStatus: {
				isPristine: true,
				isStarted: false,
				isProcessing: false,
				isFinished: false,
				hasError: false,
				hasFailed: false,
				isSuccessful: false,
				isDoingExpressPayment: false,
			},
			registeredPaymentMethods: [],
			registeredExpressPaymentMethods: [ 'my-new-method' ],
			paymentMethodData: {},
			paymentMethodsInitialized: false,
			expressPaymentMethodsInitialized: false,
			isExpressPaymentMethodActive: false,
			shouldSavePaymentMethod: false,
			errorMessage: '',
			activePaymentMethod: '',
			activeSavedToken: '',
		} );
		const nextState = reducer( stateWithRegisteredMethod, {
			type: ACTION_TYPES.REMOVE_REGISTERED_EXPRESS_PAYMENT_METHOD,
			name: 'my-new-method',
		} );
		expect( nextState ).toEqual( {
			paymentStatuses: PAYMENT_METHOD_STATUS,
			currentStatus: {
				isPristine: true,
				isStarted: false,
				isProcessing: false,
				isFinished: false,
				hasError: false,
				hasFailed: false,
				isSuccessful: false,
				isDoingExpressPayment: false,
			},
			registeredPaymentMethods: [],
			registeredExpressPaymentMethods: [],
			paymentMethodData: {},
			paymentMethodsInitialized: false,
			expressPaymentMethodsInitialized: false,
			isExpressPaymentMethodActive: false,
			shouldSavePaymentMethod: false,
			errorMessage: '',
			activePaymentMethod: '',
			activeSavedToken: '',
		} );
	} );
} );
