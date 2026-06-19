/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { WooPaymentsOverviewPage } from '../overview/page';
import {
	getWooPaymentsDepositsOverview,
	getWooPaymentsRecentDeposits,
	submitWooPaymentsInstantDeposit,
} from '../overview/data';

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

jest.mock( '~/woopayments/settings/account-settings', () => ( {
	WooPaymentsAccountSettings: () => <div>WooPayments account settings</div>,
} ) );

jest.mock( '../../promotions/spotlight', () => ( {
	SpotlightPromotion: () => <div>Spotlight promotion</div>,
} ) );

jest.mock( '../overview/data', () => ( {
	getWooPaymentsDepositsOverview: jest.fn(),
	getWooPaymentsRecentDeposits: jest.fn(),
	submitWooPaymentsInstantDeposit: jest.fn(),
} ) );

const mockGetOverview = getWooPaymentsDepositsOverview as jest.MockedFunction<
	typeof getWooPaymentsDepositsOverview
>;
const mockGetRecent = getWooPaymentsRecentDeposits as jest.MockedFunction<
	typeof getWooPaymentsRecentDeposits
>;
const mockSubmitInstantPayout =
	submitWooPaymentsInstantDeposit as jest.MockedFunction<
		typeof submitWooPaymentsInstantDeposit
	>;

const createDeferred = < T, >() => {
	let resolve!: ( value: T ) => void;
	let reject!: ( error: Error ) => void;
	const promise = new Promise< T >( ( resolvePromise, rejectPromise ) => {
		resolve = resolvePromise;
		reject = rejectPromise;
	} );

	return { promise, resolve, reject };
};

describe( 'WooPaymentsOverviewPage', () => {
	beforeEach( () => {
		mockGetOverview.mockReset();
		mockGetRecent.mockReset();
		mockSubmitInstantPayout.mockReset();
		mockCreateSuccessNotice.mockClear();
		mockCreateErrorNotice.mockClear();
		Object.defineProperty( window, 'wcSettings', {
			configurable: true,
			value: {
				adminUrl: 'https://example.com/wp-admin/',
			},
		} );
	} );

	it( 'loads recent payout history when overview loading fails', async () => {
		const overviewRequest =
			createDeferred<
				Awaited< ReturnType< typeof getWooPaymentsDepositsOverview > >
			>();
		const recentRequest =
			createDeferred<
				Awaited< ReturnType< typeof getWooPaymentsRecentDeposits > >
			>();

		mockGetOverview.mockReturnValue( overviewRequest.promise );
		mockGetRecent.mockReturnValue( recentRequest.promise );

		const recentResponse = {
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
		};

		render( <WooPaymentsOverviewPage /> );

		await act( async () => {
			overviewRequest.reject( new Error( 'Overview failed.' ) );
			await overviewRequest.promise.catch( () => undefined );
		} );

		await act( async () => {
			recentRequest.resolve( recentResponse );
			await recentRequest.promise;
		} );

		expect( screen.getByText( 'Spotlight promotion' ) ).toBeInTheDocument();

		expect(
			await screen.findByText( 'Overview failed.' )
		).toBeInTheDocument();
		expect(
			await screen.findByText( 'Dispatch date' )
		).toBeInTheDocument();
		expect( mockGetRecent ).toHaveBeenCalledWith( '' );
		expect( screen.getAllByText( '$10.00' ).length ).toBeGreaterThan( 0 );
	} );

	it( 'reloads recent payouts when the selected balance currency changes', async () => {
		const overviewRequest =
			createDeferred<
				Awaited< ReturnType< typeof getWooPaymentsDepositsOverview > >
			>();
		const initialRecentRequest =
			createDeferred<
				Awaited< ReturnType< typeof getWooPaymentsRecentDeposits > >
			>();
		const eurRecentRequest =
			createDeferred<
				Awaited< ReturnType< typeof getWooPaymentsRecentDeposits > >
			>();

		mockGetOverview.mockReturnValue( overviewRequest.promise );
		mockGetRecent
			.mockReturnValueOnce( initialRecentRequest.promise )
			.mockReturnValueOnce( eurRecentRequest.promise );

		const overviewResponse = {
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
			account: {
				default_currency: 'usd',
				deposits_enabled: true,
				deposits_schedule: {
					interval: 'weekly',
					weekly_anchor: 'monday',
				},
				completed_waiting_period: true,
				default_external_accounts: [],
			},
			deposit: {
				last_paid: [],
			},
		};
		const initialRecentResponse = {
			data: [],
			total_count: 0,
		};
		const eurRecentResponse = {
			data: [
				{
					id: 'po_eur',
					date: 1781740800000,
					type: 'deposit',
					amount: 3000,
					status: 'paid',
					bankAccount: 'TEST BANK **** 1234 (EUR)',
					currency: 'eur',
				},
			],
			total_count: 1,
		};

		render( <WooPaymentsOverviewPage /> );

		await act( async () => {
			overviewRequest.resolve( overviewResponse );
			await overviewRequest.promise;
		} );

		await act( async () => {
			initialRecentRequest.resolve( initialRecentResponse );
			await initialRecentRequest.promise;
		} );

		expect(
			await screen.findByText( 'No recent payouts.' )
		).toBeInTheDocument();
		expect( mockGetRecent ).toHaveBeenCalledWith( 'usd' );

		await userEvent.selectOptions(
			await screen.findByRole( 'combobox', { name: 'Balance currency' } ),
			'eur'
		);

		await act( async () => {
			eurRecentRequest.resolve( eurRecentResponse );
			await eurRecentRequest.promise;
		} );

		expect(
			await screen.findByRole( 'link', {
				name: 'View payout po_eur details',
			} )
		).toBeInTheDocument();
		expect( mockGetRecent ).toHaveBeenCalledWith( 'eur' );
		expect( mockGetRecent ).toHaveBeenCalledTimes( 2 );
		expect( screen.getAllByText( '€30.00' ).length ).toBeGreaterThan( 0 );
	} );

	it( 'refreshes overview data after an instant payout is submitted', async () => {
		const initialOverview = {
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
			account: {
				default_currency: 'usd',
				deposits_enabled: true,
				deposits_schedule: {
					interval: 'weekly',
					weekly_anchor: 'monday',
				},
				completed_waiting_period: true,
				default_external_accounts: [],
			},
			deposit: {
				last_paid: [],
			},
		};
		const refreshedOverview = {
			...initialOverview,
			balance: {
				available: [ { amount: 100, currency: 'usd' } ],
				pending: [ { amount: 250, currency: 'usd' } ],
				instant: [],
			},
		};

		mockGetOverview
			.mockResolvedValueOnce( initialOverview )
			.mockResolvedValueOnce( refreshedOverview );
		mockGetRecent
			.mockResolvedValueOnce( {
				data: [],
				total_count: 0,
			} )
			.mockResolvedValueOnce( {
				data: [
					{
						id: 'po_instant',
						date: 1781740800000,
						type: 'instant',
						amount: 900,
						status: 'in_transit',
						bankAccount: 'TEST BANK **** 1234 (USD)',
						currency: 'usd',
					},
				],
				total_count: 1,
			} );
		mockSubmitInstantPayout.mockResolvedValue( {
			id: 'po_instant',
			date: 1781740800000,
			type: 'instant',
			amount: 900,
			status: 'in_transit',
			currency: 'usd',
		} );

		render( <WooPaymentsOverviewPage /> );

		await userEvent.click(
			await screen.findByRole( 'button', { name: 'Get $9.00 now' } )
		);
		await userEvent.click(
			await screen.findByRole( 'button', { name: 'Pay out $8.86 now' } )
		);

		expect( mockSubmitInstantPayout ).toHaveBeenCalledWith( 'usd' );
		await screen.findByRole( 'link', {
			name: 'View payout po_instant details',
		} );
		expect( mockGetOverview ).toHaveBeenCalledTimes( 2 );
		expect( mockGetRecent ).toHaveBeenCalledTimes( 2 );
		expect(
			screen.queryByText( /Get \$9\.00 via instant payout/ )
		).not.toBeInTheDocument();
	} );
} );
