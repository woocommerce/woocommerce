import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { BankAccountModal } from '../bank-account-modal';
describe( 'BankAccountModal', () => {
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	let wcSettings;
	const defaultProps = {
		account: null,
		onClose: vi.fn(),
		onSave: vi.fn(),
		defaultCountry: 'GB',
	};
	beforeEach( () => {
		wcSettings = {
			countries: {
				GB: 'United Kingdom',
				US: 'United States',
			},
		};
	} );
	it( 'renders modal with initial empty fields', () => {
		render( <BankAccountModal { ...defaultProps } /> );
		expect(
			screen.getByRole( 'dialog', {
				name: /add a bank account/i,
			} )
		).toBeInTheDocument();
		expect( screen.getByLabelText( /account name/i ) ).toHaveValue( '' );
		expect( screen.getByLabelText( /account number/i ) ).toHaveValue( '' );
		expect( screen.getByLabelText( /sort code/i ) ).toHaveValue( '' );
	} );
	it( 'displays validation errors if required fields are empty on save', async () => {
		render( <BankAccountModal { ...defaultProps } /> );
		await act( async () => {
			await userEvent.click( screen.getByText( 'Save' ) );
		} );
		expect(
			screen.getAllByText( 'This field is required.' ).length
		).toBeGreaterThan( 0 );
	} );
	it( 'calls onSave with valid data', async () => {
		const onSave = vi.fn();
		render( <BankAccountModal { ...defaultProps } onSave={ onSave } /> );
		await act( async () => {
			await userEvent.type(
				screen.getByLabelText( /account name/i ),
				'Test Account'
			);
		} );
		await act( async () => {
			await userEvent.type(
				screen.getByLabelText( /account number/i ),
				'12345678'
			);
		} );
		await act( async () => {
			await userEvent.type(
				screen.getByLabelText( /sort code/i ),
				'001122'
			);
		} );
		await act( async () => {
			await userEvent.click( screen.getByText( 'Save' ) );
		} );
		expect( onSave ).toHaveBeenCalledWith(
			expect.objectContaining( {
				account_name: 'Test Account',
				account_number: '12345678',
				sort_code: '001122',
			} )
		);
	} );
	it( 'calls onClose when Cancel is clicked', async () => {
		const onClose = vi.fn();
		render( <BankAccountModal { ...defaultProps } onClose={ onClose } /> );
		await act( async () => {
			await userEvent.click( screen.getByText( 'Cancel' ) );
		} );
		expect( onClose ).toHaveBeenCalled();
	} );
} );
