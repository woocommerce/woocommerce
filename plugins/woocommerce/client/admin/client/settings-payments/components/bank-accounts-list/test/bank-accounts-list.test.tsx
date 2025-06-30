/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { BankAccountsList } from '../bank-accounts-list';
import { BankAccount } from '../types';

const mockAccounts: BankAccount[] = [
	{
		account_name: 'Example Bank',
		account_number: '123456',
		bank_name: 'ExampleBank',
		sort_code: '12-34-56',
		iban: 'GB82WEST12345698765432',
		bic: 'WESTGB22',
	},
];

describe( 'BankAccountsList', () => {
	it( 'renders existing accounts', () => {
		render(
			<BankAccountsList
				accounts={ mockAccounts }
				onChange={ jest.fn() }
				defaultCountry="US"
			/>
		);
		expect( screen.getByText( 'Example Bank' ) ).toBeInTheDocument();
		expect( screen.getByText( '123456' ) ).toBeInTheDocument();
	} );

	it( 'opens modal to add new account', async () => {
		render(
			<BankAccountsList
				accounts={ [] }
				onChange={ jest.fn() }
				defaultCountry="US"
			/>
		);
		await userEvent.click( screen.getByText( '+ Add account' ) );
		expect(
			screen.getByRole( 'dialog', { name: /add/i } )
		).toBeInTheDocument();
	} );

	it( 'calls onChange when an account is deleted', async () => {
		const onChange = jest.fn();
		render(
			<BankAccountsList
				accounts={ mockAccounts }
				onChange={ onChange }
				defaultCountry="US"
			/>
		);

		// Open menu and click delete.
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Options' } )
		);
		await userEvent.click( screen.getByText( 'Delete' ) );

		// Confirm deletion
		await userEvent.click( screen.getByText( 'Delete' ) );
		expect( onChange ).toHaveBeenCalledWith( [] );
	} );
} );
