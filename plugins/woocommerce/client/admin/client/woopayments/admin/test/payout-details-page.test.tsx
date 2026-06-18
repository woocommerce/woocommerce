/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { WooPaymentsPayouts } from '../payouts';
import { WooPaymentsPayoutDetailsPage } from '../payout-details';
import {
	getWooPaymentsDeposit,
	getWooPaymentsDeposits,
} from '../overview/data';
import { getWooPaymentsTransactionsSummary } from '../money-movement/data';

jest.mock( '../overview/data', () => ( {
	getWooPaymentsDeposit: jest.fn(),
	getWooPaymentsDeposits: jest.fn(),
} ) );

jest.mock( '../money-movement/data', () => ( {
	getWooPaymentsTransactionsSummary: jest.fn(),
} ) );

const mockGetDeposit = getWooPaymentsDeposit as jest.MockedFunction<
	typeof getWooPaymentsDeposit
>;
const mockGetDeposits = getWooPaymentsDeposits as jest.MockedFunction<
	typeof getWooPaymentsDeposits
>;
const mockGetTransactionsSummary =
	getWooPaymentsTransactionsSummary as jest.MockedFunction<
		typeof getWooPaymentsTransactionsSummary
	>;

describe( 'WooPayments payout details admin surface', () => {
	beforeEach( () => {
		window.wcSettings = {
			adminUrl: 'http://example.com/wp-admin',
		};
		mockGetDeposit.mockReset();
		mockGetDeposits.mockReset();
		mockGetTransactionsSummary.mockReset();
	} );

	it( 'links payout history rows to native payout details', async () => {
		mockGetDeposits.mockResolvedValue( {
			total_count: 1,
			data: [
				{
					id: 'po_test',
					date: '2026-06-18',
					type: 'deposit',
					amount: 12500,
					status: 'paid',
					currency: 'usd',
				},
			],
		} );

		render( <WooPaymentsPayouts /> );

		const detailsLink = await screen.findByRole( 'link', {
			name: 'Jun 18, 2026 - view payout details for po_test',
		} );
		expect( detailsLink ).toHaveAttribute(
			'href',
			'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fpayouts%2Fdetails&id=po_test'
		);
	} );

	it( 'loads and renders payout detail data', async () => {
		mockGetDeposit.mockResolvedValue( {
			id: 'po_test',
			date: '2026-06-18',
			type: 'deposit',
			amount: 12500,
			status: 'paid',
			bankAccount: 'STRIPE TEST BANK **** 6789',
			bank_reference_key: 'REF123',
			currency: 'usd',
			automatic: true,
		} );
		mockGetTransactionsSummary.mockResolvedValue( {
			count: 3,
			total: 14000,
			fees: 1500,
			net: 12500,
			currency: 'usd',
		} );

		render(
			<MemoryRouter
				initialEntries={ [ '/woopayments/payouts/details?id=po_test' ] }
			>
				<WooPaymentsPayoutDetailsPage />
			</MemoryRouter>
		);

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading payout details…'
		);
		expect(
			screen.getByRole( 'heading', { name: 'Payout details' } )
		).toBeInTheDocument();
		expect( mockGetDeposit ).toHaveBeenCalledWith( 'po_test' );
		expect( mockGetTransactionsSummary ).toHaveBeenCalledWith( {
			deposit_id: 'po_test',
		} );
		expect(
			await screen.findByText( 'STRIPE TEST BANK **** 6789' )
		).toBeInTheDocument();
		expect( screen.getByText( 'REF123' ) ).toBeInTheDocument();
		expect( screen.getAllByText( '$125.00' ) ).toHaveLength( 2 );
		expect( screen.getByText( '3' ) ).toBeInTheDocument();
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Payout details loaded.'
		);
	} );

	it( 'announces payout detail errors', async () => {
		mockGetDeposit.mockRejectedValue( new Error( 'Payout unavailable.' ) );
		mockGetTransactionsSummary.mockResolvedValue( {} );

		render(
			<MemoryRouter
				initialEntries={ [ '/woopayments/payouts/details?id=po_test' ] }
			>
				<WooPaymentsPayoutDetailsPage />
			</MemoryRouter>
		);

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			'Payout unavailable.'
		);
	} );

	it( 'requires a payout ID before loading details', async () => {
		render(
			<MemoryRouter initialEntries={ [ '/woopayments/payouts/details' ] }>
				<WooPaymentsPayoutDetailsPage />
			</MemoryRouter>
		);

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			'A payout ID is required.'
		);
		expect( mockGetDeposit ).not.toHaveBeenCalled();
		expect( mockGetTransactionsSummary ).not.toHaveBeenCalled();
	} );
} );
