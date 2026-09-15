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

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

const mockedUseSelect = wpData.useSelect as jest.Mock;
// Mock use select so we can override it when wc/store/checkout is accessed, but return the original select function if any other store is accessed.
mockedUseSelect.mockImplementation(
	jest.fn().mockImplementation( ( passedMapSelect ) => {
		const { paymentStore } = jest.requireActual(
			'@woocommerce/block-data'
		);
		const mockedSelect = jest.fn().mockImplementation( ( storeName ) => {
			if (
				storeName === 'wc/store/payment' ||
				storeName === paymentStore
			) {
				return {
					...jest
						.requireActual( '@wordpress/data' )
						.select( storeName ),
					getActiveSavedToken: () => 1,
					getSavedPaymentMethods: () => {
						return {
							cc: [
								{
									tokenId: 1,
									expires: '1/2099',
									// A wallet-enriched card, as WooPayments produces: the extension label is richer than the token's own name.
									display_name:
										'Visa ending in 1234 (expires 1/2099)',
									method: {
										brand: 'Google Pay Visa',
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
									display_name:
										'Visa ending in 1001 (expires 1/2099)',
									method: {
										brand: 'Visa',
										display_brand: 'Cartes Bancaires',
										gateway:
											'can-pay-true-test-payment-method',
										last4: '1001',
									},
								},
							],
							bank_account: [
								{
									tokenId: 5,
									expires: 'N/A',
									display_name:
										'Checkout test account ending in 9876',
									method: {
										brand: '',
										gateway:
											'can-pay-true-test-payment-method',
										last4: '',
									},
								},
								{
									tokenId: 6,
									expires: 'N/A',
									display_name:
										'SEPA IBAN ending in 3000 (DE)',
									method: {
										brand: 'SEPA IBAN',
										gateway:
											'can-pay-true-test-payment-method',
										last4: '3000',
									},
								},
								{
									tokenId: 7,
									expires: 'N/A',
									display_name: '   ',
									method: {
										brand: '',
										gateway:
											'can-pay-true-test-payment-method',
										last4: '',
									},
								},
								{
									tokenId: 8,
									expires: 'N/A',
									display_name: 2468 as unknown as string,
									method: {
										brand: '',
										gateway:
											'can-pay-true-test-payment-method',
										last4: '',
									},
								},
							],
						};
					},
				};
			}
			return jest.requireActual( '@wordpress/data' ).select( storeName );
		} );
		return passedMapSelect( mockedSelect, {
			dispatch: jest.requireActual( '@wordpress/data' ).dispatch,
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

		// First saved token for can-pay-true-test-payment-method: the wallet-enriched card label wins over display_name.
		expect(
			screen.getByRole( 'radio', {
				name: 'Google Pay Visa ending in 1234 (expires 1/2099)',
			} )
		).toBeInTheDocument();
		expect(
			screen.queryByText( 'Visa ending in 1234 (expires 1/2099)' )
		).not.toBeInTheDocument();

		// Second saved token for can-pay-true-test-payment-method.
		expect(
			screen.getByText( 'Visa ending in 2345 (expires 1/2099)' )
		).toBeInTheDocument();

		// Third saved token for can-pay-false-test-payment-method - this should not show because the method is not registered.
		expect(
			screen.queryByText( 'Visa ending in 3456 (expires 1/2099)' )
		).not.toBeInTheDocument();

		// Fourth saved token for can-pay-true-test-payment-method - co-branded credit card: display_brand wins over display_name.
		expect(
			screen.getByRole( 'radio', {
				name: 'Cartes Bancaires ending in 1001 (expires 1/2099)',
			} )
		).toBeInTheDocument();

		// Custom token type without brand/last4: the token's own display name labels the radio.
		expect(
			screen.getByRole( 'radio', {
				name: 'Checkout test account ending in 9876',
			} )
		).toBeInTheDocument();

		// Custom token type with brand and last4: the extension-provided label wins over display_name.
		expect(
			screen.getByRole( 'radio', {
				name: 'SEPA IBAN ending in 3000',
			} )
		).toBeInTheDocument();

		// Blank and non-string display names fall back to the generic label.
		const fallbackRadios = screen.getAllByRole( 'radio', {
			name: 'Saved token for can-pay-true-test-payment-method',
		} );
		expect(
			fallbackRadios.map(
				( radio ) => ( radio as HTMLInputElement ).value
			)
		).toEqual( [ '7', '8' ] );
	} );
	it( "does not show saved methods when the method's canPay function returns false", () => {
		registerPaymentMethod( {
			name: 'can-pay-true-first-false-second-test-payment-method',
			label: 'Can Pay True First False Second Test Payment Method',
			edit: <div>edit</div>,
			ariaLabel: 'Can Pay True First False Second Test Payment Method',
			// This mock will return true the first time it runs, then false on subsequent calls.
			canMakePayment: jest
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
