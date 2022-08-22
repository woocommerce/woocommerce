/* eslint-disable no-unused-expressions */
/**
 * External dependencies
 */
import * as wpDataFunctions from '@wordpress/data';

/**
 * Internal dependencies
 */
import { setDefaultPaymentMethod } from '../set-default-payment-method';
import { PaymentMethods } from '../../../types';
import { PAYMENT_METHOD_DATA_STORE_KEY } from '..';

const originalSelect = jest.requireActual( '@wordpress/data' ).select;

describe( 'setDefaultPaymentMethod', () => {
	afterEach( () => {
		jest.resetAllMocks();
		jest.resetModules();
	} );

	const paymentMethods: PaymentMethods = {
		'wc-payment-gateway-1': {
			name: 'wc-payment-gateway-1',
		},
		'wc-payment-gateway-2': {
			name: 'wc-payment-gateway-2',
		},
	};

	it( ' correctly sets the first payment method in the list of available payment methods', async () => {
		jest.spyOn( wpDataFunctions, 'select' ).mockImplementation(
			( storeName ) => {
				const originalStore = originalSelect( storeName );
				if ( storeName === PAYMENT_METHOD_DATA_STORE_KEY ) {
					return {
						...originalStore,
						getAvailableExpressPaymentMethods: () => {
							return {
								express_payment_1: {
									name: 'express_payment_1',
								},
							};
						},
						getSavedPaymentMethods: () => {
							return {};
						},
					};
				}
				return originalStore;
			}
		);

		const originalDispatch =
			jest.requireActual( '@wordpress/data' ).dispatch;
		const setActivePaymentMethodMock = jest.fn();
		jest.spyOn( wpDataFunctions, 'dispatch' ).mockImplementation(
			( storeName ) => {
				const originalStore = originalDispatch( storeName );
				if ( storeName === PAYMENT_METHOD_DATA_STORE_KEY ) {
					return {
						...originalStore,
						setActivePaymentMethod: setActivePaymentMethodMock,
					};
				}
				return originalStore;
			}
		);
		await setDefaultPaymentMethod( paymentMethods );
		expect( setActivePaymentMethodMock ).toHaveBeenCalledWith(
			'wc-payment-gateway-1'
		);
	} );
	it( ' correctly sets the saved payment method if one is available', async () => {
		jest.spyOn( wpDataFunctions, 'select' ).mockImplementation(
			( storeName ) => {
				const originalStore = originalSelect( storeName );
				if ( storeName === PAYMENT_METHOD_DATA_STORE_KEY ) {
					return {
						...originalStore,
						getAvailableExpressPaymentMethods: () => {
							return {
								express_payment_1: {
									name: 'express_payment_1',
								},
							};
						},
						getSavedPaymentMethods: () => {
							return {
								cc: [
									{
										method: {
											gateway: 'saved-method',
											last4: '4242',
											brand: 'Visa',
										},
										expires: '04/44',
										is_default: true,
										actions: {
											delete: {
												url: 'https://example.com/delete',
												name: 'Delete',
											},
										},
										tokenId: 2,
									},
								],
							};
						},
					};
				}
				return originalStore;
			}
		);

		const originalDispatch =
			jest.requireActual( '@wordpress/data' ).dispatch;
		const setActivePaymentMethodMock = jest.fn();
		jest.spyOn( wpDataFunctions, 'dispatch' ).mockImplementation(
			( storeName ) => {
				const originalStore = originalDispatch( storeName );
				if ( storeName === PAYMENT_METHOD_DATA_STORE_KEY ) {
					return {
						...originalStore,
						setActivePaymentMethod: setActivePaymentMethodMock,
						setPaymentStatus: () => void 0,
					};
				}
				return originalStore;
			}
		);
		await setDefaultPaymentMethod( paymentMethods );
		expect( setActivePaymentMethodMock ).toHaveBeenCalledWith(
			'saved-method',
			{
				isSavedToken: true,
				payment_method: 'saved-method',
				token: '2',
				'wc-saved-method-payment-token': '2',
			}
		);
	} );
} );
