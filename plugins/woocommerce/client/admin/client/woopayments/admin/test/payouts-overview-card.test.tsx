/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { PayoutsOverviewCard } from '../overview/components/payouts-overview-card';
import type {
	WooPaymentsDeposit,
	WooPaymentsDepositsOverview,
} from '../overview/types';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

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
				amount: 0,
				currency: 'usd',
			},
		],
		instant: [],
	},
	account: {
		default_currency: 'usd',
		deposits_enabled: true,
		deposits_blocked: false,
		deposits_schedule: {
			delay_days: 7,
			interval: 'weekly',
			weekly_anchor: 'monday',
		},
		completed_waiting_period: true,
		minimum_scheduled_deposit_amounts: {
			usd: 500,
		},
		default_external_accounts: [],
	},
	deposit: {
		last_paid: [],
	},
	...overrides,
} );

const createDeposit = (
	overrides: Partial< WooPaymentsDeposit > = {}
): WooPaymentsDeposit => ( {
	id: 'po_test',
	date: 1781740800000,
	type: 'deposit',
	amount: 1000,
	status: 'paid',
	bankAccount: 'TEST BANK **** 1234 (USD)',
	currency: 'usd',
	automatic: true,
	fee: 0,
	fee_percentage: 0,
	created: 1781740800,
	...overrides,
} );

describe( 'PayoutsOverviewCard', () => {
	beforeEach( () => {
		jest.mocked( recordEvent ).mockClear();
		Object.defineProperty( window, 'wcSettings', {
			configurable: true,
			value: {
				adminUrl: 'https://example.com/wp-admin/',
			},
		} );
	} );

	it( 'renders the loading state', () => {
		render(
			<PayoutsOverviewCard
				isLoading
				errorMessage={ null }
				overview={ null }
				recentPayouts={ [] }
			/>
		);

		expect(
			screen.getByRole( 'heading', { name: 'Payouts' } )
		).toBeInTheDocument();
		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'Loading payouts…'
		);
		expect( screen.getByRole( 'region' ) ).toHaveAttribute(
			'aria-busy',
			'true'
		);
	} );

	it( 'hides the card for a new account with no pending or available funds', () => {
		const { container } = render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview( {
					balance: {
						available: [ { amount: 0, currency: 'usd' } ],
						pending: [ { amount: 0, currency: 'usd' } ],
						instant: [],
					},
					account: {
						...createOverview().account,
						completed_waiting_period: false,
					},
				} ) }
				recentPayouts={ [] }
			/>
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'shows waiting-period guidance when pending funds exist for a new account', () => {
		render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview( {
					balance: {
						available: [ { amount: 0, currency: 'usd' } ],
						pending: [ { amount: 1500, currency: 'usd' } ],
						instant: [],
					},
					account: {
						...createOverview().account,
						completed_waiting_period: false,
					},
				} ) }
				recentPayouts={ [] }
			/>
		);

		expect(
			screen.getByText( /standard 7-day waiting period/i )
		).toBeInTheDocument();
		expect( screen.getByText( '$15.00' ) ).toBeInTheDocument();
	} );

	it( 'shows suspended-payouts guidance without schedule actions', () => {
		render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview( {
					account: {
						...createOverview().account,
						deposits_enabled: false,
						deposits_blocked: true,
					},
				} ) }
				recentPayouts={ [ createDeposit() ] }
			/>
		);

		expect(
			screen.getByText( /Your payouts are temporarily suspended/i )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'link', { name: /change payout schedule/i } )
		).not.toBeInTheDocument();
	} );

	it( 'keeps failed-payout recovery visible when payouts are suspended', () => {
		render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview( {
					account: {
						...createOverview().account,
						account_link: 'https://example.com/account-link',
						deposits_enabled: false,
						deposits_blocked: true,
						default_external_accounts: [
							{
								currency: 'usd',
								status: 'errored',
							},
						],
					},
				} ) }
				recentPayouts={ [ createDeposit() ] }
			/>
		);

		expect(
			screen.getByText( /Your payouts are temporarily suspended/i )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: 'update your bank account details',
			} )
		).toBeInTheDocument();
	} );

	it( 'shows minimum-payout and negative-balance notices', () => {
		const { rerender } = render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview( {
					balance: {
						available: [ { amount: 100, currency: 'usd' } ],
						pending: [ { amount: 0, currency: 'usd' } ],
						instant: [],
					},
				} ) }
				recentPayouts={ [] }
			/>
		);

		expect(
			screen.getByText(
				/Payouts are paused while your available funds balance remains below \$5.00/i
			)
		).toBeInTheDocument();

		rerender(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview( {
					balance: {
						available: [ { amount: -100, currency: 'usd' } ],
						pending: [ { amount: 0, currency: 'usd' } ],
						instant: [],
					},
				} ) }
				recentPayouts={ [] }
			/>
		);

		expect(
			screen.getByText( /WooPayments balance remains negative/i )
		).toBeInTheDocument();
	} );

	it( 'shows failed-payout account recovery when the selected bank account is errored', () => {
		render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview( {
					account: {
						...createOverview().account,
						account_link: 'https://example.com/account-link',
						default_external_accounts: [
							{
								currency: 'usd',
								status: 'errored',
							},
						],
					},
				} ) }
				recentPayouts={ [] }
			/>
		);

		expect(
			screen.getByText(
				/Payouts are currently paused because a recent payout failed/i
			)
		).toBeInTheDocument();

		const updateLink = screen.getByRole( 'link', {
			name: 'update your bank account details',
		} );
		expect( updateLink ).toHaveAttribute(
			'href',
			'https://example.com/account-link?from=WCPAY_PAYOUTS&source=wcpay-payout-failure-notice'
		);

		fireEvent.click( updateLink );

		expect( recordEvent ).toHaveBeenCalledWith(
			'wcpay_account_details_link_clicked',
			{
				from: 'WCPAY_PAYOUTS',
				source: 'wcpay-payout-failure-notice',
			}
		);
	} );

	it( 'keeps overview-derived payout details visible when the recent-payout list fails', () => {
		render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage="Unable to load recent payouts."
				overview={ createOverview() }
				recentPayouts={ [] }
			/>
		);

		expect(
			screen.getByText(
				'Available funds are automatically dispatched every Monday.'
			)
		).toBeInTheDocument();
		expect( screen.getAllByText( '$10.00' ).length ).toBeGreaterThan( 0 );
		expect( screen.getByRole( 'alert' ) ).toHaveTextContent(
			'Unable to load recent payouts.'
		);
	} );

	it( 'announces when there are no recent payouts', () => {
		render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview() }
				recentPayouts={ [] }
			/>
		);

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'No recent payouts.'
		);
	} );

	it( 'keeps the payout status region mounted from loading to loaded', () => {
		const { rerender } = render(
			<PayoutsOverviewCard
				isLoading
				errorMessage={ null }
				overview={ null }
				recentPayouts={ [] }
			/>
		);
		const statusRegion = screen.getByRole( 'status' );

		rerender(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview() }
				recentPayouts={ [] }
			/>
		);

		expect( screen.getByRole( 'status' ) ).toBe( statusRegion );
		expect( statusRegion ).toHaveTextContent( 'No recent payouts.' );
	} );

	it( 'keeps recent payout history visible when overview data is unavailable', () => {
		render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ null }
				recentPayouts={ [ createDeposit() ] }
			/>
		);

		expect(
			screen.getByRole( 'heading', { name: 'Payouts' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'Dispatch date' ) ).toBeInTheDocument();
		expect( screen.getAllByText( '$10.00' ).length ).toBeGreaterThan( 0 );
	} );

	it( 'renders recent payout rows and tracks history navigation', () => {
		render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview() }
				recentPayouts={ [ createDeposit() ] }
			/>
		);

		expect( screen.getByText( 'Dispatch date' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Status' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Amount' ) ).toBeInTheDocument();
		expect( screen.getAllByText( '$10.00' ).length ).toBeGreaterThan( 0 );
		expect( screen.getByText( 'Paid' ) ).toHaveClass(
			'woocommerce-woopayments-overview__status-chip',
			'woocommerce-woopayments-overview__status-chip--paid'
		);

		expect(
			screen.getByRole( 'link', { name: 'View payout po_test details' } )
		).toHaveAttribute(
			'href',
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fpayouts%2Fdetails&id=po_test'
		);

		const historyLink = screen.getByRole( 'link', {
			name: 'View full payout history',
		} );
		expect( historyLink ).toHaveAttribute(
			'href',
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fpayouts'
		);

		fireEvent.click( historyLink );

		expect( recordEvent ).toHaveBeenCalledWith(
			'wcpay_overview_deposits_view_history_click'
		);
	} );

	it( 'uses reference payout schedule copy for daily and weekly payouts', () => {
		const { rerender } = render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview( {
					account: {
						...createOverview().account,
						deposits_schedule: {
							interval: 'daily',
						},
					},
				} ) }
				recentPayouts={ [] }
			/>
		);

		expect(
			screen.getByText(
				'Available funds are automatically dispatched every day.'
			)
		).toBeInTheDocument();

		rerender(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview() }
				recentPayouts={ [] }
			/>
		);

		expect(
			screen.getByText(
				'Available funds are automatically dispatched every Monday.'
			)
		).toBeInTheDocument();
	} );

	it( 'uses reference payout schedule copy for monthly and month-end payouts', () => {
		const { rerender } = render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview( {
					account: {
						...createOverview().account,
						deposits_schedule: {
							interval: 'monthly',
							monthly_anchor: 15,
						},
					},
				} ) }
				recentPayouts={ [] }
			/>
		);

		expect(
			screen.getByText(
				'Available funds are automatically dispatched on the 15th of every month.'
			)
		).toBeInTheDocument();

		rerender(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview( {
					account: {
						...createOverview().account,
						deposits_schedule: {
							interval: 'monthly',
							monthly_anchor: 31,
						},
					},
				} ) }
				recentPayouts={ [] }
			/>
		);

		expect(
			screen.getByText(
				'Available funds are automatically dispatched on the last day of every month.'
			)
		).toBeInTheDocument();
	} );

	it( 'keeps payout schedule help copy visible', () => {
		render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview() }
				recentPayouts={ [] }
			/>
		);

		expect(
			screen.getByText(
				/The timing and amount of your payouts may vary due to several factors\./
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: /payout schedule guide/ } )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/woopayments/payouts/payout-schedule/'
		);
	} );

	it( 'shows the reference no-funds notice when funds are still pending', () => {
		render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview( {
					balance: {
						available: [ { amount: 0, currency: 'usd' } ],
						pending: [ { amount: 1500, currency: 'usd' } ],
						instant: [],
					},
				} ) }
				recentPayouts={ [] }
			/>
		);

		expect(
			screen.getByText( /You have no funds available/i )
		).toBeInTheDocument();
		expect( screen.getByRole( 'link', { name: /Why\?/ } ) ).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/woopayments/payouts/payout-schedule/#pending-funds'
		);
	} );

	it( 'renders change payout schedule when the schedule can be changed', () => {
		render(
			<PayoutsOverviewCard
				isLoading={ false }
				errorMessage={ null }
				overview={ createOverview() }
				recentPayouts={ [ createDeposit() ] }
			/>
		);

		const scheduleLink = screen.getByRole( 'link', {
			name: 'Change payout schedule',
		} );
		expect( scheduleLink ).toHaveAttribute(
			'href',
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fsettings#payout-schedule'
		);

		fireEvent.click( scheduleLink );

		expect( recordEvent ).toHaveBeenCalledWith(
			'wcpay_overview_deposits_change_schedule_click'
		);
	} );
} );
