/**
 * External dependencies
 */
import { select, dispatch } from '@wordpress/data';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { setDefaultPaymentMethod } from '../utils/set-default-payment-method';
import { PlainPaymentMethods } from '../../../types';
import '../../checkout';
import { store as paymentStore } from '..';

const originalSelect = jest.requireActual( '@wordpress/data' ).select;
const originalDispatch = jest.requireActual( '@wordpress/data' ).dispatch;

jest.mock( '@wordpress/data', () => {
	return {
		...jest.requireActual( '@wordpress/data' ),
		select: jest.fn(),
		dispatch: jest.fn(),
	};
} );

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting: jest.fn( ( name: string, fallback: unknown ) =>
		jest
			.requireActual( '@woocommerce/settings' )
			.getSetting( name, fallback )
	),
} ) );

const disablePreselection = () => {
	( getSetting as jest.Mock ).mockImplementation(
		( name: string, fallback: unknown ) =>
			name === 'checkoutPreselectPaymentMethod' ? false : fallback
	);
};

jest.mock( '@woocommerce/utils', () => {
	return {
		isSiteEditorPage: jest.fn().mockReturnValue( true ),
	};
} );

describe( 'setDefaultPaymentMethod', () => {
	const paymentMethods: PlainPaymentMethods = {
		'wc-payment-gateway-1': {
			name: 'wc-payment-gateway-1',
		},
		'wc-payment-gateway-2': {
			name: 'wc-payment-gateway-2',
		},
	};

	beforeEach( () => {
		// resetAllMocks in afterEach wipes the factory implementation, so restore the passthrough.
		( getSetting as jest.Mock ).mockImplementation(
			( name: string, fallback: unknown ) =>
				jest
					.requireActual( '@woocommerce/settings' )
					.getSetting( name, fallback )
		);
	} );

	afterEach( () => {
		jest.resetAllMocks();
		jest.resetModules();
	} );

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

	describe( 'when preselection is disabled', () => {
		it( 'leaves no payment method selected', async () => {
			disablePreselection();
			const setActivePaymentMethodMock = mockStores( {} );

			await setDefaultPaymentMethod( paymentMethods );

			expect( setActivePaymentMethodMock ).not.toHaveBeenCalled();
		} );

		it( 'does not select a saved payment method either', async () => {
			disablePreselection();
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

		it( 'clears a selected method that is no longer available instead of picking the first one', async () => {
			disablePreselection();
			const setActivePaymentMethodMock = mockStores( {
				activePaymentMethod: 'wc-payment-gateway-removed',
			} );

			await setDefaultPaymentMethod( paymentMethods );

			expect( setActivePaymentMethodMock ).toHaveBeenCalledWith( '' );
		} );

		it( 'keeps a selected method that is still available', async () => {
			disablePreselection();
			const setActivePaymentMethodMock = mockStores( {
				activePaymentMethod: 'wc-payment-gateway-2',
			} );

			await setDefaultPaymentMethod( paymentMethods );

			expect( setActivePaymentMethodMock ).not.toHaveBeenCalled();
		} );
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
