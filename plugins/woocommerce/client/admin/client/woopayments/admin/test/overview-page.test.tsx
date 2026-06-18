/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { WooPaymentsOverviewPage } from '../overview/page';
import {
	getWooPaymentsDepositsOverview,
	getWooPaymentsRecentDeposits,
} from '../overview/data';

jest.mock( '~/woopayments/settings/account-settings', () => ( {
	WooPaymentsAccountSettings: () => <div>WooPayments account settings</div>,
} ) );

jest.mock( '../overview/data', () => ( {
	getWooPaymentsDepositsOverview: jest.fn(),
	getWooPaymentsRecentDeposits: jest.fn(),
} ) );

const mockGetOverview = getWooPaymentsDepositsOverview as jest.MockedFunction<
	typeof getWooPaymentsDepositsOverview
>;
const mockGetRecent = getWooPaymentsRecentDeposits as jest.MockedFunction<
	typeof getWooPaymentsRecentDeposits
>;

describe( 'WooPaymentsOverviewPage', () => {
	beforeEach( () => {
		mockGetOverview.mockReset();
		mockGetRecent.mockReset();
		Object.defineProperty( window, 'wcSettings', {
			configurable: true,
			value: {
				adminUrl: 'https://example.com/wp-admin/',
			},
		} );
	} );

	it( 'loads recent payout history when overview loading fails', async () => {
		mockGetOverview.mockRejectedValue( new Error( 'Overview failed.' ) );
		mockGetRecent.mockResolvedValue( {
			data: [
				{
					id: 'po_test',
					date: 1781740800000,
					type: 'deposit',
					amount: 1000,
					status: 'paid',
					bankAccount: 'TEST BANK **** 1234 (USD)',
					currency: 'usd',
				},
			],
			total_count: 1,
		} );

		render( <WooPaymentsOverviewPage /> );

		await waitFor( () => {
			expect( mockGetRecent ).toHaveBeenCalledWith( '' );
		} );

		expect( screen.getByText( 'Overview failed.' ) ).toBeInTheDocument();
		expect(
			await screen.findByText( 'Dispatch date' )
		).toBeInTheDocument();
		expect( screen.getAllByText( '$10.00' ).length ).toBeGreaterThan( 0 );
	} );
} );
