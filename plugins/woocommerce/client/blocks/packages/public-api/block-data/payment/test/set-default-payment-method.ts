import { afterEach, describe, expect, it, vi, type Mock } from 'vitest';

/* eslint-disable no-unused-expressions */
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

const originalSelect = ( await vi.importActual( '@wordpress/data' ) ).select;
const originalDispatch = ( await vi.importActual( '@wordpress/data' ) )
	.dispatch;

vi.mock( '@wordpress/data', async () => {
	return ( ( mock ) => ( { default: mock, ...mock } ) )( {
		...( await vi.importActual( '@wordpress/data' ) ),
		select: vi.fn(),
		dispatch: vi.fn(),
	} );
} );

vi.mock( '@woocommerce/utils', () => {
	return ( ( mock ) => ( { default: mock, ...mock } ) )( {
		isSiteEditorPage: vi.fn().mockReturnValue( true ),
	} );
} );

describe( 'setDefaultPaymentMethod', () => {
	afterEach( () => {
		vi.resetAllMocks();
		vi.resetModules();
	} );

	const paymentMethods: PlainPaymentMethods = {
		'wc-payment-gateway-1': {
			name: 'wc-payment-gateway-1',
		},
		'wc-payment-gateway-2': {
			name: 'wc-payment-gateway-2',
		},
	};

	it( 'correctly sets the first payment method in the list of available payment methods', async () => {
		const setActivePaymentMethodMock = vi.fn();
		( select as Mock ).mockImplementation( ( storeName ) => {
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
		( dispatch as Mock ).mockImplementation( ( storeName ) => {
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
		( select as Mock ).mockImplementation( ( storeName ) => {
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

		const setActivePaymentMethodMock = vi.fn();
		( dispatch as Mock ).mockImplementation( ( storeName ) => {
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
