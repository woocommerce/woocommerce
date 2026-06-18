/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { AccountBalancesCard } from '../overview/components/account-balances-card';
import type { WooPaymentsDepositsOverview } from '../overview/types';

const createOverview = (
	overrides: Partial< WooPaymentsDepositsOverview > = {}
): WooPaymentsDepositsOverview => ( {
	balance: {
		available: [
			{
				amount: 1000,
				currency: 'usd',
			},
		],
		pending: [
			{
				amount: 250,
				currency: 'usd',
			},
		],
		instant: [],
	},
	account: {
		default_currency: 'usd',
	},
	deposit: {
		last_paid: [],
	},
	...overrides,
} );

describe( 'AccountBalancesCard', () => {
	it( 'announces loading state from a stable status region', () => {
		render(
			<AccountBalancesCard
				isLoading
				errorMessage={ null }
				overview={ null }
			/>
		);

		expect(
			screen.getByRole( 'heading', { name: 'Balance' } )
		).toBeInTheDocument();
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading balance…'
		);
		expect( screen.getByRole( 'region' ) ).toHaveAttribute(
			'aria-busy',
			'true'
		);
	} );

	it( 'announces errors from a stable alert region', () => {
		render(
			<AccountBalancesCard
				isLoading={ false }
				errorMessage="Unable to load balance."
				overview={ null }
			/>
		);

		expect( screen.getByRole( 'alert' ) ).toHaveTextContent(
			'Unable to load balance.'
		);
		expect( screen.getByRole( 'region' ) ).toHaveAttribute(
			'aria-busy',
			'false'
		);
	} );

	it( 'renders balance totals when overview data is available', () => {
		render(
			<AccountBalancesCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview() }
			/>
		);

		expect( screen.getByText( 'Available' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Pending' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Total' ) ).toBeInTheDocument();
		expect( screen.getByText( '$12.50' ) ).toBeInTheDocument();
	} );
} );
