import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen, act } from '@testing-library/react';
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
				onChange={ vi.fn() }
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
				onChange={ vi.fn() }
				defaultCountry="US"
			/>
		);
		await act( async () => {
			await userEvent.click( screen.getByText( '+ Add account' ) );
		} );
		expect(
			screen.getByRole( 'dialog', {
				name: /add/i,
			} )
		).toBeInTheDocument();
	} );
	it( 'calls onChange when an account is deleted', async () => {
		const onChange = vi.fn();
		render(
			<BankAccountsList
				accounts={ mockAccounts }
				onChange={ onChange }
				defaultCountry="US"
			/>
		);

		// Open menu and click delete.
		await act( async () => {
			// Open menu and click delete.
			await userEvent.click(
				screen.getByRole( 'button', {
					name: 'Options',
				} )
			);
		} );
		await act( async () => {
			await userEvent.click( screen.getByText( 'Delete' ) );

			// Confirm deletion
		} ); // Confirm deletion
		await act( async () => {
			// Confirm deletion
			await userEvent.click( screen.getByText( 'Delete' ) );
		} );
		expect( onChange ).toHaveBeenCalledWith( [] );
	} );
} );
