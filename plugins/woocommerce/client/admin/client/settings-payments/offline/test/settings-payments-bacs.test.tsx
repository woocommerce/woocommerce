/**
 * External dependencies
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { SettingsPaymentsBacs } from '../settings-payments-bacs';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: jest.fn(),
} ) );

jest.mock( '~/settings-payments/components/bank-accounts-list', () => ( {
	BankAccountsList: ( { defaultCountry }: { defaultCountry: string } ) => (
		<div
			data-testid="bank-accounts-list"
			data-default-country={ defaultCountry }
		/>
	),
} ) );

const bacsSettings = {
	enabled: true,
	description: 'Make your payment directly into our bank account.',
	settings: {
		title: { value: 'Direct bank transfer' },
		instructions: { value: 'Use your order ID as the payment reference.' },
	},
};

const accountsOption = [
	{
		id: 'extra-field-that-should-not-be-saved',
		account_name: 'Main account',
		account_number: '12345678',
		bank_name: 'Test Bank',
		sort_code: '12-34-56',
		iban: 'GB29NWBK60161331926819',
		bic: 'NWBKGB2L',
		country_code: 'GB',
	},
];

describe( 'SettingsPaymentsBacs', () => {
	let updatePaymentGateway: jest.Mock;
	let updateOptions: jest.Mock;

	beforeEach( () => {
		updatePaymentGateway = jest.fn().mockResolvedValue( {} );
		updateOptions = jest.fn().mockResolvedValue( {} );
		( useDispatch as jest.Mock ).mockReturnValue( {
			createSuccessNotice: jest.fn(),
			createErrorNotice: jest.fn(),
			updatePaymentGateway,
			updateOptions,
			invalidateResolution: jest.fn(),
			invalidateResolutionForStoreSelector: jest.fn(),
		} );
		( useSelect as jest.Mock ).mockReturnValue( {
			bacsSettings,
			isLoading: false,
			accountsOption,
			isLoadingAccounts: false,
		} );
	} );

	it( 'renders all settings fields with stored values', () => {
		render( <SettingsPaymentsBacs /> );

		expect(
			screen.getByLabelText( 'Enable direct bank transfers' )
		).toBeChecked();
		expect( screen.getByLabelText( 'Title' ) ).toHaveValue(
			'Direct bank transfer'
		);
		expect( screen.getByLabelText( 'Description' ) ).toHaveValue(
			'Make your payment directly into our bank account.'
		);
		expect( screen.getByLabelText( 'Instructions' ) ).toHaveValue(
			'Use your order ID as the payment reference.'
		);
	} );

	it( 'renders the bank accounts section', () => {
		render( <SettingsPaymentsBacs /> );

		expect(
			screen.getByTestId( 'bank-accounts-list' )
		).toBeInTheDocument();
	} );

	describe( 'country a new bank account starts in', () => {
		const setWcSettings = ( admin: unknown ) => {
			Object.defineProperty( window, 'wcSettings', {
				value: { admin },
				writable: true,
			} );
		};

		afterEach( () => {
			Object.defineProperty( window, 'wcSettings', {
				value: undefined,
				writable: true,
			} );
		} );

		it( 'uses the business location set on the Payments settings screen', () => {
			setWcSettings( {
				woocommerce_payments_nox_profile: {
					business_country_code: 'TN',
				},
				preloadSettings: {
					general: { woocommerce_default_country: 'US:CA' },
				},
			} );

			render( <SettingsPaymentsBacs /> );

			expect(
				screen.getByTestId( 'bank-accounts-list' )
			).toHaveAttribute( 'data-default-country', 'TN' );
		} );

		it( "falls back to the store's base country, without its state suffix", () => {
			setWcSettings( {
				preloadSettings: {
					general: { woocommerce_default_country: 'US:CA' },
				},
			} );

			render( <SettingsPaymentsBacs /> );

			expect(
				screen.getByTestId( 'bank-accounts-list' )
			).toHaveAttribute( 'data-default-country', 'US' );
		} );

		it( 'ignores locations that are stored empty', () => {
			setWcSettings( {
				woocommerce_payments_nox_profile: {
					business_country_code: '',
				},
				preloadSettings: {
					general: { woocommerce_default_country: '' },
				},
			} );

			render( <SettingsPaymentsBacs /> );

			expect(
				screen.getByTestId( 'bank-accounts-list' )
			).toHaveAttribute( 'data-default-country', 'US' );
		} );

		it( 'ignores locations that are not strings', () => {
			setWcSettings( {
				woocommerce_payments_nox_profile: {
					business_country_code: { country: 'TN' },
				},
				preloadSettings: {
					general: { woocommerce_default_country: true },
				},
			} );

			render( <SettingsPaymentsBacs /> );

			expect(
				screen.getByTestId( 'bank-accounts-list' )
			).toHaveAttribute( 'data-default-country', 'US' );
		} );

		it( 'falls through an unusable business location to the store', () => {
			setWcSettings( {
				woocommerce_payments_nox_profile: {
					business_country_code: { country: 'TN' },
				},
				preloadSettings: {
					// Deliberately not US, so this cannot be confused with
					// the last-resort fallback at the end of the chain.
					general: { woocommerce_default_country: 'GB' },
				},
			} );

			render( <SettingsPaymentsBacs /> );

			expect(
				screen.getByTestId( 'bank-accounts-list' )
			).toHaveAttribute( 'data-default-country', 'GB' );
		} );

		it( 'falls back to US when the store knows no location at all', () => {
			setWcSettings( {} );

			render( <SettingsPaymentsBacs /> );

			expect(
				screen.getByTestId( 'bank-accounts-list' )
			).toHaveAttribute( 'data-default-country', 'US' );
		} );
	} );

	it( 'renders placeholders while loading', () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			bacsSettings: null,
			isLoading: true,
			accountsOption: undefined,
			isLoadingAccounts: true,
		} );

		const { container } = render( <SettingsPaymentsBacs /> );

		expect(
			container.querySelectorAll( '.woocommerce-field-placeholder' )
				.length
		).toBeGreaterThan( 0 );
		expect( screen.queryByLabelText( 'Title' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'bank-accounts-list' )
		).not.toBeInTheDocument();
	} );

	it( 'disables save until a change is made', () => {
		render( <SettingsPaymentsBacs /> );

		expect(
			screen.getByRole( 'button', { name: 'Save changes' } )
		).toBeDisabled();

		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: { value: 'Bank transfer payments' },
		} );

		expect(
			screen.getByRole( 'button', { name: 'Save changes' } )
		).toBeEnabled();
	} );

	it( 'saves the edited values with the expected payload shape', async () => {
		render( <SettingsPaymentsBacs /> );

		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: { value: 'Bank transfer payments' },
		} );
		fireEvent.click(
			screen.getByLabelText( 'Enable direct bank transfers' )
		);
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Save changes' } )
		);

		await waitFor( () => {
			expect( updatePaymentGateway ).toHaveBeenCalledWith( 'bacs', {
				enabled: false,
				description:
					'Make your payment directly into our bank account.',
				settings: {
					title: 'Bank transfer payments',
					instructions: 'Use your order ID as the payment reference.',
				},
			} );
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_bacs_accounts: [
				{
					account_name: 'Main account',
					account_number: '12345678',
					bank_name: 'Test Bank',
					sort_code: '12-34-56',
					iban: 'GB29NWBK60161331926819',
					bic: 'NWBKGB2L',
					country_code: 'GB',
				},
			],
		} );
	} );

	it( 'disables save again after a successful save', async () => {
		render( <SettingsPaymentsBacs /> );

		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: { value: 'Bank transfer payments' },
		} );
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Save changes' } )
		);

		await waitFor( () => {
			expect(
				screen.getByRole( 'button', { name: 'Save changes' } )
			).toBeDisabled();
		} );
	} );

	it( 'supports keyboard navigation through the form fields', async () => {
		render( <SettingsPaymentsBacs /> );

		// Make a change first so the Save button is enabled (and tabbable).
		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: { value: 'Edited title' },
		} );

		await userEvent.tab();
		expect(
			screen.getByLabelText( 'Enable direct bank transfers' )
		).toHaveFocus();
		await userEvent.tab();
		expect( screen.getByLabelText( 'Title' ) ).toHaveFocus();
		await userEvent.tab();
		expect( screen.getByLabelText( 'Description' ) ).toHaveFocus();
		await userEvent.tab();
		expect( screen.getByLabelText( 'Instructions' ) ).toHaveFocus();
		await userEvent.tab();
		expect(
			screen.getByRole( 'button', { name: 'Save changes' } )
		).toHaveFocus();
	} );

	it( 'shows an error notice when saving fails', async () => {
		const createErrorNotice = jest.fn();
		updatePaymentGateway.mockRejectedValueOnce(
			new Error( 'save failed' )
		);
		( useDispatch as jest.Mock ).mockReturnValue( {
			createSuccessNotice: jest.fn(),
			createErrorNotice,
			updatePaymentGateway,
			updateOptions: jest.fn().mockResolvedValue( {} ),
			invalidateResolution: jest.fn(),
			invalidateResolutionForStoreSelector: jest.fn(),
		} );

		render( <SettingsPaymentsBacs /> );

		fireEvent.change( screen.getByLabelText( 'Title' ), {
			target: { value: 'Edited title' },
		} );
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Save changes' } )
		);

		await waitFor( () => {
			expect( createErrorNotice ).toHaveBeenCalledWith(
				'Failed to update settings'
			);
		} );
	} );
} );
