import { describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import * as wpData from '@wordpress/data';

/**
 * Internal dependencies
 */
import SavedPaymentMethodOptions from '../saved-payment-method-options';
const _actual = await vi.importActual( '@woocommerce/block-data' );
const _actual2 = await vi.importActual( '@wordpress/data' );
vi.mock( '@wordpress/data', async () => {
	const mock = {
		__esModule: true,
		...( await vi.importActual( '@wordpress/data' ) ),
		useSelect: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const mockedUseSelect = wpData.useSelect as Mock;
// Mock use select so we can override it when wc/store/checkout is accessed, but return the original select function if any other store is accessed.
mockedUseSelect.mockImplementation(
	vi.fn().mockImplementation( ( passedMapSelect ) => {
		const { paymentStore } = _actual;
		const mockedSelect = vi.fn().mockImplementation( ( storeName ) => {
			if (
				storeName === 'wc/store/payment' ||
				storeName === paymentStore
			) {
				return {
					..._actual2.select( storeName ),
					getActiveSavedToken: () => 1,
					getSavedPaymentMethods: () => {
						return {
							cc: [
								{
									tokenId: 1,
									expires: '1/2099',
									method: {
										brand: 'Visa',
										gateway:
											'can-pay-true-test-payment-method',
										last4: '1234',
									},
								},
								{
									tokenId: 2,
									expires: '1/2099',
									method: {
										brand: 'Visa',
										gateway:
											'can-pay-true-test-payment-method',
										last4: '2345',
									},
								},
								{
									tokenId: 3,
									expires: '1/2099',
									method: {
										brand: 'Visa',
										gateway:
											'can-pay-true-first-false-second-test-payment-method',
										last4: '3456',
									},
								},
								{
									tokenId: 4,
									expires: '1/2099',
									method: {
										brand: 'Cartes Bancaires',
										gateway:
											'can-pay-true-test-payment-method',
										last4: '1001',
									},
								},
							],
						};
					},
				};
			}
			return _actual2.select( storeName );
		} );
		return passedMapSelect( mockedSelect, {
			dispatch: _actual2.dispatch,
		} );
	} )
);
describe( 'SavedPaymentMethodOptions', () => {
	it( 'renders saved methods when a registered method exists', () => {
		registerPaymentMethod( {
			name: 'can-pay-true-test-payment-method',
			label: 'Can Pay True Test Payment Method',
			edit: <div>edit</div>,
			ariaLabel: 'Can Pay True Test Payment Method',
			canMakePayment: () => true,
			content: <div>content</div>,
			supports: {
				showSavedCards: true,
				showSaveOption: true,
				features: [ 'products' ],
			},
		} );
		render( <SavedPaymentMethodOptions /> );

		// First saved token for can-pay-true-test-payment-method.
		expect(
			screen.getByText( 'Visa ending in 1234 (expires 1/2099)' )
		).toBeInTheDocument();

		// Second saved token for can-pay-true-test-payment-method.
		expect(
			screen.getByText( 'Visa ending in 2345 (expires 1/2099)' )
		).toBeInTheDocument();

		// Third saved token for can-pay-false-test-payment-method - this should not show because the method is not registered.
		expect(
			screen.queryByText( 'Visa ending in 3456 (expires 1/2099)' )
		).not.toBeInTheDocument();

		// Fourth saved token for can-pay-true-test-payment-method - co-branded credit card.
		expect(
			screen.getByText(
				'Cartes Bancaires ending in 1001 (expires 1/2099)'
			)
		).toBeInTheDocument();
	} );
	it( "does not show saved methods when the method's canPay function returns false", () => {
		registerPaymentMethod( {
			name: 'can-pay-true-first-false-second-test-payment-method',
			label: 'Can Pay True First False Second Test Payment Method',
			edit: <div>edit</div>,
			ariaLabel: 'Can Pay True First False Second Test Payment Method',
			// This mock will return true the first time it runs, then false on subsequent calls.
			canMakePayment: vi
				.fn()
				.mockReturnValueOnce( true )
				.mockReturnValue( false ),
			content: <div>content</div>,
			supports: {
				showSavedCards: true,
				showSaveOption: true,
				features: [ 'products' ],
			},
		} );
		const { rerender } = render( <SavedPaymentMethodOptions /> );
		// Saved token for can-pay-true-first-false-second-test-payment-method - this should show because canPay is true on first call.
		expect(
			screen.queryByText( 'Visa ending in 3456 (expires 1/2099)' )
		).toBeInTheDocument();
		rerender( <SavedPaymentMethodOptions /> );

		// Saved token for can-pay-true-first-false-second-test-payment-method - this should not show because canPay is false on subsequent calls.
		expect(
			screen.queryByText( 'Visa ending in 3456 (expires 1/2099)' )
		).not.toBeInTheDocument();
	} );
} );
