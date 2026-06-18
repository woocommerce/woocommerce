/**
 * External dependencies
 */
import { act, fireEvent, render, screen } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { WooPaymentsAccountSettings } from '../account-settings';
import type { WooPaymentsAccount, WooPaymentsAccountResponse } from '../types';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockApiFetch = apiFetch as jest.MockedFunction< typeof apiFetch >;

const createAccountResponse = (
	overrides: Partial< WooPaymentsAccount > = {},
	urls: Partial< WooPaymentsAccountResponse[ 'urls' ] > = {
		overview_page:
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=/woopayments/overview',
		setup: 'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=/woopayments/onboarding',
	}
): WooPaymentsAccountResponse => ( {
	account: {
		id: 'acct_123',
		mode: 'test',
		default_currency: 'USD',
		connected: true,
		working: true,
		can_process_payments: true,
		test_mode: true,
		test_drive: false,
		sandbox: false,
		live: false,
		...overrides,
	},
	urls: {
		overview_page: '',
		setup: '',
		...urls,
	},
} );

describe( 'WooPaymentsAccountSettings', () => {
	beforeEach( () => {
		mockApiFetch.mockReset();
	} );

	it( 'should load the account from the WooPayments account endpoint', () => {
		mockApiFetch.mockReturnValue( new Promise( () => {} ) );

		render( <WooPaymentsAccountSettings /> );

		expect(
			screen.getByRole( 'heading', { name: 'WooPayments settings' } )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Loading WooPayments account…' )
		).toBeInTheDocument();
		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc-admin/settings/payments/woopayments/account',
			method: 'GET',
		} );
	} );

	it( 'should render an error message when the account cannot be loaded', async () => {
		mockApiFetch.mockRejectedValue( new Error( 'Connection failed' ) );

		render( <WooPaymentsAccountSettings /> );

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			'Connection failed'
		);
		expect(
			screen.getByRole( 'heading', {
				name: 'Unable to load WooPayments account',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Try again' } )
		).toBeInTheDocument();
	} );

	it( 'should retry loading the account after an error', async () => {
		mockApiFetch
			.mockRejectedValueOnce( new Error( 'Connection failed' ) )
			.mockResolvedValueOnce( createAccountResponse() );

		render( <WooPaymentsAccountSettings /> );

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			'Connection failed'
		);

		fireEvent.click( screen.getByRole( 'button', { name: 'Try again' } ) );

		expect(
			await screen.findByRole( 'heading', {
				name: 'Connected account',
			} )
		).toBeInTheDocument();
		expect( mockApiFetch ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'should preserve focus during retry and move focus to the loaded account state', async () => {
		let resolveRetry: ( response: WooPaymentsAccountResponse ) => void;
		const retryResponse = new Promise< WooPaymentsAccountResponse >(
			( resolve ) => {
				resolveRetry = resolve;
			}
		);
		mockApiFetch
			.mockRejectedValueOnce( new Error( 'Connection failed' ) )
			.mockReturnValueOnce( retryResponse );

		render( <WooPaymentsAccountSettings /> );

		const retryButton = await screen.findByRole( 'button', {
			name: 'Try again',
		} );
		retryButton.focus();

		fireEvent.click( retryButton );

		expect(
			screen.getByRole( 'button', { name: 'Trying again…' } )
		).toHaveFocus();

		await act( async () => {
			resolveRetry( createAccountResponse() );
			await retryResponse;
		} );

		expect(
			await screen.findByRole( 'heading', {
				name: 'Connected account',
			} )
		).toHaveFocus();
	} );

	it( 'should not move focus to the loaded account state when focus leaves during retry', async () => {
		let resolveRetry: ( response: WooPaymentsAccountResponse ) => void;
		const retryResponse = new Promise< WooPaymentsAccountResponse >(
			( resolve ) => {
				resolveRetry = resolve;
			}
		);
		mockApiFetch
			.mockRejectedValueOnce( new Error( 'Connection failed' ) )
			.mockReturnValueOnce( retryResponse );

		render(
			<>
				<button type="button">Outside action</button>
				<WooPaymentsAccountSettings />
			</>
		);

		const retryButton = await screen.findByRole( 'button', {
			name: 'Try again',
		} );
		fireEvent.click( retryButton );

		const outsideButton = screen.getByRole( 'button', {
			name: 'Outside action',
		} );
		outsideButton.focus();

		await act( async () => {
			resolveRetry( createAccountResponse() );
			await retryResponse;
		} );

		expect(
			await screen.findByRole( 'heading', {
				name: 'Connected account',
			} )
		).toBeInTheDocument();
		expect( outsideButton ).toHaveFocus();
	} );

	it( 'should render setup guidance when no account is connected', async () => {
		mockApiFetch.mockResolvedValue(
			createAccountResponse( {
				id: '',
				connected: false,
				working: false,
				can_process_payments: false,
				test_mode: false,
			} )
		);

		render( <WooPaymentsAccountSettings /> );

		expect(
			await screen.findByRole( 'heading', { name: 'Set up WooPayments' } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Connect an account to start accepting payments.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Set up WooPayments' } )
		).toHaveAttribute(
			'href',
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=/woopayments/onboarding'
		);
		expect( screen.queryByText( 'acct_123' ) ).not.toBeInTheDocument();
	} );

	it( 'should render a connected test drive account', async () => {
		mockApiFetch.mockResolvedValue(
			createAccountResponse( {
				working: false,
				can_process_payments: false,
				test_drive: true,
				sandbox: true,
			} )
		);

		render( <WooPaymentsAccountSettings /> );

		expect(
			await screen.findByRole( 'heading', {
				name: 'Connected account',
			} )
		).toBeInTheDocument();
		expect( screen.getByText( 'Test drive' ) ).toHaveClass(
			'woopayments-account-settings__badge'
		);
		expect( screen.getByText( 'acct_123' ) ).toBeInTheDocument();
		expect( screen.getByText( 'USD' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Payments need attention' )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Cannot process payments' )
		).toBeInTheDocument();
	} );

	it( 'should render a connected sandbox account that can process payments', async () => {
		mockApiFetch.mockResolvedValue(
			createAccountResponse( {
				test_drive: false,
				sandbox: true,
			} )
		);

		render( <WooPaymentsAccountSettings /> );

		expect( await screen.findByText( 'Sandbox' ) ).toHaveClass(
			'woopayments-account-settings__badge'
		);
		expect( screen.getByText( 'Payments ready' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Can process payments' )
		).toBeInTheDocument();
	} );

	it( 'should render a connected live account', async () => {
		mockApiFetch.mockResolvedValue(
			createAccountResponse( {
				mode: 'live',
				default_currency: 'EUR',
				test_mode: false,
				live: true,
			} )
		);

		render( <WooPaymentsAccountSettings /> );

		expect( await screen.findByText( 'Live' ) ).toHaveClass(
			'woopayments-account-settings__badge'
		);
		expect( screen.getByText( 'EUR' ) ).toBeInTheDocument();
	} );
} );
