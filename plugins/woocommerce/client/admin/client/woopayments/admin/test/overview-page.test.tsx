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
	getWooPaymentsOverviewDisputes,
	getWooPaymentsOverviewShell,
	getWooPaymentsRecentDeposits,
	submitWooPaymentsInstantDeposit,
} from '../overview/data';
import { saveOption } from '../../settings/data/actions';

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
	getWooPaymentsOverviewDisputes: jest.fn(),
	getWooPaymentsOverviewShell: jest.fn(),
	getWooPaymentsRecentDeposits: jest.fn(),
	submitWooPaymentsInstantDeposit: jest.fn(),
} ) );

jest.mock( '../../settings/data/actions', () => ( {
	saveOption: jest.fn(),
} ) );

const mockGetOverview = getWooPaymentsDepositsOverview as jest.MockedFunction<
	typeof getWooPaymentsDepositsOverview
>;
const mockGetShell = getWooPaymentsOverviewShell as jest.MockedFunction<
	typeof getWooPaymentsOverviewShell
>;
const mockGetOverviewDisputes =
	getWooPaymentsOverviewDisputes as jest.MockedFunction<
		typeof getWooPaymentsOverviewDisputes
	>;
const mockGetRecent = getWooPaymentsRecentDeposits as jest.MockedFunction<
	typeof getWooPaymentsRecentDeposits
>;
const mockSubmitInstantPayout =
	submitWooPaymentsInstantDeposit as jest.MockedFunction<
		typeof submitWooPaymentsInstantDeposit
	>;
const mockSaveOption = saveOption as jest.MockedFunction< typeof saveOption >;

const createDeferred = < T, >() => {
	let resolve!: ( value: T ) => void;
	let reject!: ( error: Error ) => void;
	const promise = new Promise< T >( ( resolvePromise, rejectPromise ) => {
		resolve = resolvePromise;
		reject = rejectPromise;
	} );

	return { promise, resolve, reject };
};

const createShell = ( overrides: Record< string, unknown > = {} ) => ( {
	account: {
		id: 'acct_test',
		mode: 'live',
		connected: true,
		working: true,
		can_process_payments: true,
		details_submitted: true,
		test_mode: false,
		test_mode_onboarding: false,
		dev_mode: false,
		test_drive: false,
		sandbox: false,
		live: true,
	},
	account_status: {
		status: 'complete',
		current_deadline: 0,
		past_due: false,
		account_link: 'https://connect.example/update',
		requirements: {
			errors: [],
		},
		details_submitted: true,
		payments_enabled: true,
		deposits_enabled: true,
	},
	show_update_details_task: false,
	disputes_awaiting_response_count: null,
	overview_tasks_visibility: {
		dismissed_todo_tasks: [],
		deleted_todo_tasks: [],
		remind_me_later_todo_tasks: {},
	},
	is_connection_success_modal_dismissed: false,
	wpcom_reconnect_url: '',
	urls: {
		overview_page: '',
		settings: '',
		onboarding: '',
		setup: '',
	},
	...overrides,
} );

const createDepositsOverview = () => ( {
	balance: {
		available: [ { amount: 1000, currency: 'usd' } ],
		pending: [ { amount: 250, currency: 'usd' } ],
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
} );

describe( 'WooPaymentsOverviewPage', () => {
	beforeEach( () => {
		mockGetOverview.mockReset();
		mockGetShell.mockReset();
		mockGetOverviewDisputes.mockReset();
		mockGetRecent.mockReset();
		mockSubmitInstantPayout.mockReset();
		mockSaveOption.mockReset();
		mockCreateSuccessNotice.mockClear();
		mockCreateErrorNotice.mockClear();
		mockGetShell.mockResolvedValue( createShell() );
		mockGetOverviewDisputes.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );
		mockSaveOption.mockResolvedValue( undefined );
		Object.defineProperty( window, 'wcSettings', {
			configurable: true,
			value: {
				adminUrl: 'https://example.com/wp-admin/',
			},
		} );
		window.history.pushState( {}, '', '/' );
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

	it( 'renders query notices, tasks, spotlight, and financial cards in shell order', async () => {
		window.history.pushState(
			{},
			'',
			'/wp-admin/admin.php?wcpay-login-error=1'
		);
		mockGetShell.mockResolvedValue(
			createShell( {
				account_status: {
					...createShell().account_status,
					status: 'restricted',
					details_submitted: false,
				},
				show_update_details_task: true,
			} )
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		const { container } = render( <WooPaymentsOverviewPage /> );

		await screen.findByText( 'Finish setting up WooPayments' );
		await screen.findByRole( 'heading', { name: 'Balance' } );

		const text = container.textContent || '';
		const noticeIndex = text.indexOf(
			'There was a problem redirecting you to the account dashboard. Please try again.'
		);
		const taskIndex = text.indexOf( 'Finish setting up WooPayments' );
		const spotlightIndex = text.indexOf( 'Spotlight promotion' );
		const balanceIndex = text.indexOf( 'Balance' );

		expect( noticeIndex ).toBeGreaterThanOrEqual( 0 );
		expect( taskIndex ).toBeGreaterThan( noticeIndex );
		expect( spotlightIndex ).toBeGreaterThan( taskIndex );
		expect( balanceIndex ).toBeGreaterThan( spotlightIndex );
	} );

	it( 'does not fetch disputes when the shell reports no actionable disputes', async () => {
		mockGetShell.mockResolvedValue(
			createShell( {
				account_status: {
					...createShell().account_status,
					status: 'restricted',
					details_submitted: false,
				},
				show_update_details_task: true,
				disputes_awaiting_response_count: 0,
			} )
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		expect(
			await screen.findByText( 'Finish setting up WooPayments' )
		).toBeInTheDocument();
		expect( mockGetOverviewDisputes ).not.toHaveBeenCalled();
	} );

	it( 'fails closed when the overview shell cannot be loaded', async () => {
		mockGetShell.mockRejectedValue( new Error( 'Shell failed.' ) );
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		expect(
			await screen.findByText( 'Spotlight promotion' )
		).toBeInTheDocument();
		expect(
			await screen.findByRole( 'heading', { name: 'Balance' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'heading', { name: 'Things to do' } )
		).not.toBeInTheDocument();
		expect( screen.queryByText( 'Shell failed.' ) ).not.toBeInTheDocument();
	} );

	it( 'persists connection success modal dismissal', async () => {
		window.history.pushState(
			{},
			'',
			'/wp-admin/admin.php?wcpay-connection-success=1'
		);
		mockGetShell.mockResolvedValue(
			createShell( {
				account: {
					...createShell().account,
					can_process_payments: true,
				},
				account_status: {
					...createShell().account_status,
					deposits_enabled: true,
					payments_enabled: true,
				},
				is_connection_success_modal_dismissed: false,
			} )
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		expect(
			await screen.findByRole( 'heading', {
				name: "You're ready to accept payments!",
			} )
		).toBeInTheDocument();

		await userEvent.click(
			screen.getByRole( 'button', {
				name: 'Dismiss',
			} )
		);

		expect( mockSaveOption ).toHaveBeenCalledWith(
			'wcpay_connection_success_modal_dismissed',
			true
		);
		expect(
			screen.queryByRole( 'heading', {
				name: "You're ready to accept payments!",
			} )
		).not.toBeInTheDocument();
	} );
} );
