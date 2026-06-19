/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { AccountBalancesCard } from '../overview/components/account-balances-card';
import type { WooPaymentsDepositsOverview } from '../overview/types';

const mockCreateSuccessNotice = jest.fn();
const mockCreateErrorNotice = jest.fn();

jest.mock( '@wordpress/data', () => {
	const actual = jest.requireActual( '@wordpress/data' );

	return {
		...actual,
		dispatch: jest.fn( ( storeName ) => {
			if ( storeName === 'core/notices' ) {
				return {
					createSuccessNotice: mockCreateSuccessNotice,
					createErrorNotice: mockCreateErrorNotice,
				};
			}

			return actual.dispatch( storeName );
		} ),
	};
} );

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
	beforeEach( () => {
		mockCreateSuccessNotice.mockClear();
		mockCreateErrorNotice.mockClear();
		Object.defineProperty( window, 'wcSettings', {
			configurable: true,
			value: {
				adminUrl: 'https://example.com/wp-admin/',
			},
		} );
	} );

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
				selectedCurrency="usd"
				onCurrencyChange={ jest.fn() }
			/>
		);

		expect( screen.getByText( 'Available funds' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Total balance' ) ).toBeInTheDocument();
		expect( screen.getByText( '$12.50' ) ).toBeInTheDocument();
	} );

	it( 'lets merchants switch the balance currency', async () => {
		const onCurrencyChange = jest.fn();
		const overview = createOverview( {
			balance: {
				available: [
					{ amount: 1000, currency: 'usd' },
					{ amount: 2500, currency: 'eur' },
				],
				pending: [
					{ amount: 250, currency: 'usd' },
					{ amount: 500, currency: 'eur' },
				],
				instant: [],
			},
		} );
		const { rerender } = render(
			<AccountBalancesCard
				isLoading={ false }
				errorMessage={ null }
				overview={ overview }
				selectedCurrency="usd"
				onCurrencyChange={ onCurrencyChange }
			/>
		);

		expect(
			screen.getByRole( 'combobox', { name: 'Balance currency' } )
		).toHaveValue( 'usd' );
		expect( screen.getByText( '$12.50' ) ).toBeInTheDocument();

		await userEvent.selectOptions(
			screen.getByRole( 'combobox', { name: 'Balance currency' } ),
			'eur'
		);

		expect( onCurrencyChange ).toHaveBeenCalledWith( 'eur' );

		rerender(
			<AccountBalancesCard
				isLoading={ false }
				errorMessage={ null }
				overview={ overview }
				selectedCurrency="eur"
				onCurrencyChange={ onCurrencyChange }
			/>
		);

		expect( screen.getByText( '€30.00' ) ).toBeInTheDocument();
		expect( screen.getByText( '€25.00' ) ).toBeInTheDocument();
	} );

	it( 'preserves the reference balance help copy', () => {
		render(
			<AccountBalancesCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview() }
				selectedCurrency="usd"
				onCurrencyChange={ jest.fn() }
			/>
		);

		expect(
			screen.getByText(
				/Total balance combines both pending funds \(transactions under processing\) and available funds \(ready for payout\)\./
			)
		).toBeInTheDocument();
		expect(
			screen.getByText(
				/Available funds have completed processing and are ready to be dispatched to your bank account\./
			)
		).toBeInTheDocument();
	} );

	it( 'opens the instant payout modal and submits the native action', async () => {
		const submitInstantPayout = jest.fn().mockResolvedValue( {
			id: 'po_instant',
			date: 1781740800000,
			type: 'instant',
			amount: 900,
			status: 'in_transit',
			currency: 'usd',
		} );
		render(
			<AccountBalancesCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview( {
					balance: {
						available: [ { amount: 1000, currency: 'usd' } ],
						pending: [ { amount: 250, currency: 'usd' } ],
						instant: [
							{
								amount: 900,
								currency: 'usd',
								fee: 14,
								net: 886,
								fee_percentage: 1.5,
							},
						],
					},
				} ) }
				selectedCurrency="usd"
				onCurrencyChange={ jest.fn() }
				onInstantPayoutSubmit={ submitInstantPayout }
			/>
		);

		const notice = screen.getByText(
			'Get $9.00 via instant payout. Funds are typically in your bank account within 30 mins. Fee: 1.5%.'
		);
		expect(
			notice.closest(
				'.woocommerce-woopayments-overview__instant-payout'
			)
		).not.toHaveAttribute( 'role', 'status' );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Get $9.00 now' } )
		);

		expect(
			screen.getByRole( 'dialog', { name: 'Instant payout' } )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Balance available for instant payout:' )
		).toBeInTheDocument();
		expect( screen.getByText( '1.5% service fee:' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Net payout amount:' ) ).toBeInTheDocument();

		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Pay out $8.86 now' } )
			);
		} );

		await waitFor( () =>
			expect( submitInstantPayout ).toHaveBeenCalledWith( 'usd' )
		);
		await waitFor( () =>
			expect(
				screen.queryByRole( 'dialog', { name: 'Instant payout' } )
			).not.toBeInTheDocument()
		);
		expect( mockCreateSuccessNotice ).toHaveBeenCalledWith(
			'Instant payout for $9.00 in transit.',
			{
				actions: [
					{
						label: 'View details',
						url: 'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fpayouts%2Fdetails&id=po_instant',
					},
				],
			}
		);
		expect( mockCreateErrorNotice ).not.toHaveBeenCalled();
	} );
} );
