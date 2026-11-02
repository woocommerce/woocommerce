/**
 * External dependencies
 */
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { setDefaultPaymentMethod } from '../utils/set-default-payment-method';
import { PlainPaymentMethods } from '../../../types';
import '../../checkout';
import { store as paymentStore } from '..';
import { checkoutData } from '../../checkout/constants';

const originalSelect = jest.requireActual( '@wordpress/data' ).select;
const originalDispatch = jest.requireActual( '@wordpress/data' ).dispatch;

jest.mock( '@wordpress/data', () => {
	return {
		...jest.requireActual( '@wordpress/data' ),
		select: jest.fn(),
		dispatch: jest.fn(),
	};
} );

jest.mock( '@woocommerce/utils', () => {
	return {
		isSiteEditorPage: jest.fn().mockReturnValue( true ),
	};
} );

// What the server hydrated as the checkout's payment method. Unset here, as in the editor.
const hydratedCheckoutData = checkoutData as { payment_method?: string };

describe( 'setDefaultPaymentMethod', () => {
	afterEach( () => {
		delete hydratedCheckoutData.payment_method;
		jest.resetAllMocks();
		jest.resetModules();
	} );

	const paymentMethods: PlainPaymentMethods = {
		'wc-payment-gateway-1': {
			name: 'wc-payment-gateway-1',
		},
		'wc-payment-gateway-2': {
			name: 'wc-payment-gateway-2',
		},
	};

	const mockStores = ( {
		activePaymentMethod = '',
		savedPaymentMethods = {},
	}: {
		activePaymentMethod?: string;
		savedPaymentMethods?: Record< string, unknown[] >;
	} ) => {
		const setActivePaymentMethodMock = jest.fn();
		( select as jest.Mock ).mockImplementation( ( storeName ) => {
			const originalStore = originalSelect( storeName );
			if ( storeName === paymentStore ) {
				return {
					...originalStore,
					getActivePaymentMethod: () => activePaymentMethod,
					getAvailableExpressPaymentMethods: () => ( {} ),
					getSavedPaymentMethods: () => savedPaymentMethods,
				};
			}
			return originalStore;
		} );
		( dispatch as jest.Mock ).mockImplementation( ( storeName ) => {
			const originalStore = originalDispatch( storeName );
			if ( storeName === paymentStore ) {
				return {
					...originalStore,
					__internalSetActivePaymentMethod:
						setActivePaymentMethodMock,
					__internalSetPaymentIdle: () => void 0,
				};
			}
			return originalStore;
		} );
		return setActivePaymentMethodMock;
	};

	describe( 'when the server preselected nothing', () => {
		beforeEach( () => {
			hydratedCheckoutData.payment_method = '';
		} );

		it( 'leaves no payment method selected', async () => {
			const setActivePaymentMethodMock = mockStores( {} );

			await setDefaultPaymentMethod( paymentMethods );

			expect( setActivePaymentMethodMock ).not.toHaveBeenCalled();
		} );

		it( 'does not select a saved payment method either', async () => {
			const setActivePaymentMethodMock = mockStores( {
				savedPaymentMethods: {
					cc: [
						{
							method: { gateway: 'saved-method' },
							is_default: true,
							tokenId: 2,
						},
					],
				},
			} );

			await setDefaultPaymentMethod( paymentMethods );

			expect( setActivePaymentMethodMock ).not.toHaveBeenCalled();
		} );

		it( 'clears a selected method that is no longer available instead of picking another', async () => {
			const setActivePaymentMethodMock = mockStores( {
				activePaymentMethod: 'wc-payment-gateway-removed',
			} );

			await setDefaultPaymentMethod( paymentMethods );

			expect( setActivePaymentMethodMock ).toHaveBeenCalledWith( '' );
		} );
	} );

	it( 'still picks the first method when the server preselected one and the store lost it', async () => {
		hydratedCheckoutData.payment_method = 'wc-payment-gateway-2';
		const setActivePaymentMethodMock = mockStores( {} );

		await setDefaultPaymentMethod( paymentMethods );

		expect( setActivePaymentMethodMock ).toHaveBeenCalledWith(
			'wc-payment-gateway-1'
		);
	} );

	it( 'still picks the first method where the server gave no answer, as in the editor', async () => {
		const setActivePaymentMethodMock = mockStores( {} );

		await setDefaultPaymentMethod( paymentMethods );

		expect( setActivePaymentMethodMock ).toHaveBeenCalledWith(
			'wc-payment-gateway-1'
		);
	} );

	it( 'correctly sets the first payment method in the list of available payment methods', async () => {
		const setActivePaymentMethodMock = jest.fn();
		( select as jest.Mock ).mockImplementation( ( storeName ) => {
			const originalStore = originalSelect( storeName );
			if ( storeName === paymentStore ) {
				return {
					...originalStore,
					getAvailableExpressPaymentMethods: () => ( {
						express_payment_1: {
							name: 'express_payment_1',
						},
					} ),
					getSavedPaymentMethods: () => ( {} ),
				};
			}
			return originalStore;
		} );
		( dispatch as jest.Mock ).mockImplementation( ( storeName ) => {
			const originalStore = originalDispatch( storeName );
			if ( storeName === paymentStore ) {
				return {
					...originalStore,
					__internalSetActivePaymentMethod:
						setActivePaymentMethodMock,
				};
			}
			return originalStore;
		} );

		await setDefaultPaymentMethod( paymentMethods );
		expect( setActivePaymentMethodMock ).toHaveBeenCalledWith(
			'wc-payment-gateway-1'
		);
	} );
	it( 'correctly sets the saved payment method if one is available', async () => {
		( select as jest.Mock ).mockImplementation( ( storeName ) => {
			const originalStore = originalSelect( storeName );
			if ( storeName === paymentStore ) {
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
		} );

		const setActivePaymentMethodMock = jest.fn();
		( dispatch as jest.Mock ).mockImplementation( ( storeName ) => {
			const originalStore = originalDispatch( storeName );
			if ( storeName === paymentStore ) {
				return {
					...originalStore,
					__internalSetActivePaymentMethod:
						setActivePaymentMethodMock,
					__internalSetPaymentError: () => void 0,
					__internalSetPaymentIdle: () => void 0,
					__internalSetExpressPaymentStarted: () => void 0,
					__internalSetPaymentProcessing: () => void 0,
					__internalSetPaymentReady: () => void 0,
				};
			}
			return originalStore;
		} );
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
