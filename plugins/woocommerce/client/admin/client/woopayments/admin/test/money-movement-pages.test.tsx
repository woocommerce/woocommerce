/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { WooPaymentsDisputesPage } from '../money-movement/disputes-page';
import { WooPaymentsTransactionDetailsPage } from '../money-movement/transaction-details-page';
import { WooPaymentsTransactionsPage } from '../money-movement/transactions-page';
import {
	getWooPaymentsDisputes,
	getWooPaymentsTransaction,
	getWooPaymentsTransactions,
} from '../money-movement/data';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '../money-movement/data', () => ( {
	getWooPaymentsDisputes: jest.fn(),
	getWooPaymentsTransaction: jest.fn(),
	getWooPaymentsTransactions: jest.fn(),
} ) );

const mockGetTransactions = getWooPaymentsTransactions as jest.MockedFunction<
	typeof getWooPaymentsTransactions
>;
const mockGetDisputes = getWooPaymentsDisputes as jest.MockedFunction<
	typeof getWooPaymentsDisputes
>;
const mockGetTransaction = getWooPaymentsTransaction as jest.MockedFunction<
	typeof getWooPaymentsTransaction
>;

describe( 'WooPayments money movement pages', () => {
	beforeEach( () => {
		window.wcSettings = {
			adminUrl: 'http://example.com/wp-admin',
		};
		mockGetTransactions.mockReset();
		mockGetDisputes.mockReset();
		mockGetTransaction.mockReset();
	} );

	it( 'announces loaded transactions and gives row links clear purpose', async () => {
		mockGetTransactions.mockResolvedValue( {
			data: [
				{
					id: 'txn_test',
					type: 'charge',
					date: '2026-06-18',
					amount: 5000,
					currency: 'usd',
				},
			],
		} );

		render( <WooPaymentsTransactionsPage /> );

		expect(
			await screen.findByRole( 'link', {
				name: 'View transaction details for Charge transaction txn_test',
			} )
		).toBeInTheDocument();
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Transactions loaded.'
		);
	} );

	it( 'announces empty transaction results from a stable status region', async () => {
		mockGetTransactions.mockResolvedValue( { data: [] } );

		render( <WooPaymentsTransactionsPage /> );

		expect( await screen.findByRole( 'status' ) ).toHaveTextContent(
			'No transactions found.'
		);
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'No transactions found.'
		);
	} );

	it( 'announces loaded disputes and routes actionable rows to challenge evidence', async () => {
		mockGetDisputes.mockResolvedValue( {
			data: [
				{
					id: 'dp_test',
					charge_id: 'ch_test',
					reason: 'fraudulent',
					status: 'needs_response',
					date: '2026-06-18',
					amount: 5000,
					currency: 'usd',
				},
				{
					id: 'dp_closed',
					charge_id: 'ch_closed',
					reason: 'fraudulent',
					status: 'won',
					date: '2026-06-18',
					amount: 5000,
					currency: 'usd',
				},
			],
		} );

		render( <WooPaymentsDisputesPage /> );

		const challengeLink = await screen.findByRole( 'link', {
			name: 'Challenge Fraudulent dispute dp_test',
		} );
		expect( challengeLink ).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fdisputes%2Fchallenge&id=dp_test'
		);
		expect(
			screen.getByRole( 'link', {
				name: 'View transaction details for Fraudulent dispute dp_closed',
			} )
		).toBeInTheDocument();
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Disputes loaded.'
		);
	} );

	it( 'announces transaction detail loading from a stable status region', () => {
		mockGetTransaction.mockImplementation( () => new Promise( () => {} ) );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=txn_test',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading transaction details…'
		);
	} );

	it( 'announces transaction detail errors from a stable alert region', async () => {
		mockGetTransaction.mockRejectedValue( new Error( 'Provider failed' ) );

		render(
			<MemoryRouter
				initialEntries={ [
					'/woopayments/transactions/details?id=txn_test',
				] }
			>
				<WooPaymentsTransactionDetailsPage />
			</MemoryRouter>
		);

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			'Provider failed'
		);
	} );
} );
