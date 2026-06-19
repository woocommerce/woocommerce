/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { WooPaymentsOverviewPage } from '../overview/page';
import {
	confirmWooPaymentsDisputeReadinessStatementDescriptor,
	createWooPaymentsAccountSession,
	dismissWooPaymentsDisputeReadinessCard,
	getWooPaymentsDepositsOverview,
	getWooPaymentsDisputeReadiness,
	getWooPaymentsOverviewDisputes,
	getWooPaymentsOverviewShell,
	getWooPaymentsRecentDeposits,
	submitWooPaymentsInstantDeposit,
} from '../overview/data';
import { getWooPaymentsCapitalActiveLoanSummary } from '../capital/data';
import { saveOption } from '../../settings/data/actions';

const mockCreateSuccessNotice = jest.fn();
const mockCreateErrorNotice = jest.fn();

jest.mock( '@wordpress/data', () => {
	const actual = jest.requireActual( '@wordpress/data' );
	const notesDispatch = {
		removeNote: jest.fn(),
		triggerNoteAction: jest.fn(),
		updateNote: jest.fn(),
	};
	(
		globalThis as unknown as {
			__mockWooPaymentsNotesDispatch: typeof notesDispatch;
		}
	 ).__mockWooPaymentsNotesDispatch = notesDispatch;

	return {
		...actual,
		dispatch: jest.fn( ( storeName ) => {
			if ( storeName === 'core/notices' ) {
				return {
					createSuccessNotice: mockCreateSuccessNotice,
					createErrorNotice: mockCreateErrorNotice,
				};
			}

			if (
				storeName === 'wc/admin/notes' ||
				( typeof storeName === 'object' &&
					storeName !== null &&
					'name' in storeName &&
					storeName.name === 'wc/admin/notes' )
			) {
				return notesDispatch;
			}

			return actual.dispatch( storeName );
		} ),
		useSelect: jest.fn(),
	};
} );

jest.mock( '~/woopayments/settings/account-settings', () => ( {
	WooPaymentsAccountSettings: ( { headingLevel = 1 } ) => {
		const HeadingTag = `h${ headingLevel }` as keyof JSX.IntrinsicElements;

		return <HeadingTag tabIndex={ -1 }>WooPayments settings</HeadingTag>;
	},
} ) );

jest.mock( '../../promotions/spotlight', () => ( {
	SpotlightPromotion: () => <div>Spotlight promotion</div>,
} ) );

jest.mock( '../overview/data', () => ( {
	confirmWooPaymentsDisputeReadinessStatementDescriptor: jest.fn(),
	createWooPaymentsAccountSession: jest.fn(),
	dismissWooPaymentsDisputeReadinessCard: jest.fn(),
	getWooPaymentsDepositsOverview: jest.fn(),
	getWooPaymentsDisputeReadiness: jest.fn(),
	getWooPaymentsOverviewDisputes: jest.fn(),
	getWooPaymentsOverviewShell: jest.fn(),
	getWooPaymentsRecentDeposits: jest.fn(),
	submitWooPaymentsInstantDeposit: jest.fn(),
} ) );

jest.mock( '../capital/data', () => ( {
	getWooPaymentsCapitalActiveLoanSummary: jest.fn(),
} ) );

jest.mock( '../../settings/data/actions', () => ( {
	saveOption: jest.fn(),
} ) );

jest.mock( 'react-intersection-observer', () => ( {
	useInView: ( options: { onChange?: ( inView: boolean ) => void } = {} ) => {
		options.onChange?.( true );

		return {
			ref: jest.fn(),
			inView: true,
		};
	},
} ) );

const mockConfirmStatementDescriptor =
	confirmWooPaymentsDisputeReadinessStatementDescriptor as jest.MockedFunction<
		typeof confirmWooPaymentsDisputeReadinessStatementDescriptor
	>;
const mockCreateAccountSession =
	createWooPaymentsAccountSession as jest.MockedFunction<
		typeof createWooPaymentsAccountSession
	>;
const mockDismissDisputeReadiness =
	dismissWooPaymentsDisputeReadinessCard as jest.MockedFunction<
		typeof dismissWooPaymentsDisputeReadinessCard
	>;
const mockGetOverview = getWooPaymentsDepositsOverview as jest.MockedFunction<
	typeof getWooPaymentsDepositsOverview
>;
const mockGetDisputeReadiness =
	getWooPaymentsDisputeReadiness as jest.MockedFunction<
		typeof getWooPaymentsDisputeReadiness
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
const mockGetActiveLoanSummary =
	getWooPaymentsCapitalActiveLoanSummary as jest.MockedFunction<
		typeof getWooPaymentsCapitalActiveLoanSummary
	>;
const mockSaveOption = saveOption as jest.MockedFunction< typeof saveOption >;
const mockUseSelect = useSelect as jest.MockedFunction< typeof useSelect >;
const getMockNotesDispatch = () =>
	(
		globalThis as unknown as {
			__mockWooPaymentsNotesDispatch: {
				removeNote: jest.Mock;
				triggerNoteAction: jest.Mock;
				updateNote: jest.Mock;
			};
		}
	 ).__mockWooPaymentsNotesDispatch;

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

const createDisputeReadinessPayload = ( overrides = {} ) => ( {
	overview: {
		enabled: true,
		score: 3,
		total: 4,
		state: 'incomplete',
		isDismissed: false,
		completeSignalIds: [ 'refund_policy', 'support_contact' ],
		incompleteSignalIds: [ 'statement_descriptor', 'terms_and_conditions' ],
		signals: [
			{
				id: 'statement_descriptor',
				status: 'incomplete',
				label: 'Recognizable statement descriptor',
				description:
					'Make sure customers can identify your store on their bank statement.',
				actionLabel: 'Review statement descriptor',
				actionUrl:
					'http://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&path=%2Fwoopayments%2Fsettings',
				reviewPrompt: {
					text: "Your statement descriptor will show up on your customers' bank statements. Does it clearly identify your store?",
					currentDescriptor: 'NATIVE SHOP',
					confirmLabel: 'Looks good',
					updateLabel: 'Update descriptor',
				},
			},
			{
				id: 'terms_and_conditions',
				status: 'incomplete',
				label: 'Clear terms and conditions',
				description:
					'Publish terms customers can find before checkout.',
				actionLabel: 'Add terms',
				actionUrl:
					'http://example.com/wp-admin/admin.php?page=wc-settings&tab=advanced',
			},
		],
		...overrides,
	},
} );

const createActiveLoanSummary = () => ( {
	details: {
		advance_amount: 100000,
		advance_paid_out_at: 1729505500,
		currency: 'usd',
		current_repayment_interval: {
			due_at: 1735294500,
			paid_amount: 20000,
			remaining_amount: 30000,
		},
		fee_amount: 10000,
		paid_amount: 20000,
		remaining_amount: 90000,
		repayments_begin_at: 1730110500,
		withhold_rate: 0.15,
	},
} );

describe( 'WooPaymentsOverviewPage', () => {
	beforeEach( () => {
		mockConfirmStatementDescriptor.mockReset();
		mockCreateAccountSession.mockReset();
		mockDismissDisputeReadiness.mockReset();
		mockGetOverview.mockReset();
		mockGetDisputeReadiness.mockReset();
		mockGetShell.mockReset();
		mockGetOverviewDisputes.mockReset();
		mockGetRecent.mockReset();
		mockSubmitInstantPayout.mockReset();
		mockGetActiveLoanSummary.mockReset();
		mockSaveOption.mockReset();
		mockUseSelect.mockReset();
		mockCreateSuccessNotice.mockClear();
		mockCreateErrorNotice.mockClear();
		getMockNotesDispatch().removeNote.mockReset();
		getMockNotesDispatch().triggerNoteAction.mockReset();
		getMockNotesDispatch().updateNote.mockReset();
		mockUseSelect.mockReturnValue( {
			isError: false,
			isLoading: false,
			notes: [],
		} );
		getMockNotesDispatch().removeNote.mockResolvedValue( {
			id: 10,
			is_deleted: true,
		} );
		mockConfirmStatementDescriptor.mockResolvedValue( {
			overview: {
				enabled: false,
			},
		} );
		mockCreateAccountSession.mockResolvedValue( {
			clientSecret: 'cs_test',
			publishableKey: 'pk_test',
			locale: 'en_US',
		} );
		mockDismissDisputeReadiness.mockResolvedValue( {
			overview: {
				enabled: true,
				isDismissed: true,
			},
		} );
		mockGetDisputeReadiness.mockResolvedValue( {
			overview: {
				enabled: false,
			},
		} );
		mockGetShell.mockResolvedValue( createShell() );
		mockGetOverviewDisputes.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );
		mockGetActiveLoanSummary.mockResolvedValue( {
			details: undefined,
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

	it( 'renders a stable page heading', async () => {
		render( <WooPaymentsOverviewPage /> );

		expect(
			screen.getByRole( 'heading', { name: 'Overview', level: 1 } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', {
				name: 'WooPayments settings',
				level: 2,
			} )
		).toBeInTheDocument();
		await screen.findByRole( 'heading', { name: 'Balance' } );
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
		expect( balanceIndex ).toBeGreaterThan( taskIndex );
		expect( spotlightIndex ).toBeGreaterThan( balanceIndex );
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

	it( 'suppresses the connection success modal during test-mode onboarding', async () => {
		window.history.pushState(
			{},
			'',
			'/wp-admin/admin.php?wcpay-connection-success=1'
		);
		mockGetShell.mockResolvedValue(
			createShell( {
				account: {
					...createShell().account,
					test_mode_onboarding: true,
					can_process_payments: true,
				},
				account_status: {
					...createShell().account_status,
					deposits_enabled: true,
					payments_enabled: true,
				},
			} )
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		expect(
			await screen.findByRole( 'heading', { name: 'Balance' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'heading', {
				name: "You're ready to accept payments!",
			} )
		).not.toBeInTheDocument();
	} );

	it( 'renders account details from the overview shell', async () => {
		mockGetShell.mockResolvedValue(
			createShell( {
				account_details: {
					account_status: {
						text: 'Restricted soon',
						background_color: 'yellow',
					},
					payout_status: {
						text: 'Paused',
						background_color: 'red',
					},
					banner: {
						text: 'Please update your business details.',
						background_color: 'yellow',
						cta_text: 'Update details',
						cta_link: 'https://connect.example/update',
					},
				},
				account_fees: [
					{
						payment_method: 'card',
						label: 'Cards',
						fee: {
							base: {
								currency: 'usd',
								percentage_rate: 2.9,
								fixed_rate: 30,
							},
							discount: [
								{
									percentage_rate: 2.6,
									fixed_rate: 30,
								},
							],
						},
					},
				],
			} )
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		expect(
			await screen.findByRole( 'heading', { name: 'Account details' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'Restricted soon' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Paused' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Please update your business details.' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Cards' ) ).toBeInTheDocument();
	} );

	it( 'fetches the active loan summary when the shell reports an active loan', async () => {
		mockGetShell.mockResolvedValue(
			createShell( {
				account_loans: {
					has_active_loan: true,
				},
			} )
		);
		mockGetActiveLoanSummary.mockResolvedValue( createActiveLoanSummary() );
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		expect(
			await screen.findByRole( 'heading', {
				name: 'Active loan overview',
			} )
		).toBeInTheDocument();
		expect( mockGetActiveLoanSummary ).toHaveBeenCalledTimes( 1 );
		expect(
			screen.getByText( '$200.00 of $1,100.00' )
		).toBeInTheDocument();
	} );

	it( 'does not fetch the active loan summary without an active loan', async () => {
		mockGetShell.mockResolvedValue(
			createShell( {
				account_loans: {
					has_active_loan: false,
				},
			} )
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		expect(
			await screen.findByRole( 'heading', { name: 'Balance' } )
		).toBeInTheDocument();
		expect( mockGetActiveLoanSummary ).not.toHaveBeenCalled();
		expect(
			screen.queryByRole( 'heading', {
				name: 'Active loan overview',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'renders dispute readiness when the shell feature flag and payload allow it', async () => {
		mockGetShell.mockResolvedValue(
			createShell( {
				feature_flags: {
					dispute_readiness_overview: true,
				},
			} )
		);
		mockGetDisputeReadiness.mockResolvedValue(
			createDisputeReadinessPayload()
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		expect(
			await screen.findByRole( 'heading', {
				name: 'Dispute readiness',
			} )
		).toBeInTheDocument();
		expect( mockGetDisputeReadiness ).toHaveBeenCalledTimes( 1 );
		expect(
			screen.getByText( 'Recognizable statement descriptor' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Dismiss dispute readiness' } )
		).toBeInTheDocument();
	} );

	it( 'does not fetch dispute readiness for disconnected accounts', async () => {
		mockGetShell.mockResolvedValue(
			createShell( {
				account: {
					...createShell().account,
					connected: false,
					working: false,
				},
				feature_flags: {
					dispute_readiness_overview: true,
				},
			} )
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		expect(
			await screen.findByRole( 'heading', { name: 'Balance' } )
		).toBeInTheDocument();
		expect( mockGetDisputeReadiness ).not.toHaveBeenCalled();
		expect(
			screen.queryByRole( 'heading', {
				name: 'Dispute readiness',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'hides dispute readiness when the feature flag is disabled', async () => {
		mockGetShell.mockResolvedValue(
			createShell( {
				feature_flags: {
					dispute_readiness_overview: false,
				},
			} )
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		expect(
			await screen.findByRole( 'heading', { name: 'Balance' } )
		).toBeInTheDocument();
		expect( mockGetDisputeReadiness ).not.toHaveBeenCalled();
		expect(
			screen.queryByRole( 'heading', {
				name: 'Dispute readiness',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'dismisses dispute readiness without losing focus', async () => {
		mockGetShell.mockResolvedValue(
			createShell( {
				feature_flags: {
					dispute_readiness_overview: true,
				},
			} )
		);
		mockGetDisputeReadiness.mockResolvedValue(
			createDisputeReadinessPayload()
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		const dismissButton = await screen.findByRole( 'button', {
			name: 'Dismiss dispute readiness',
		} );
		await act( async () => {
			dismissButton.focus();
		} );

		await act( async () => {
			await userEvent.click( dismissButton );
		} );

		expect( mockDismissDisputeReadiness ).toHaveBeenCalledTimes( 1 );
		expect(
			await screen.findByRole( 'status', {
				name: 'Dispute readiness status',
			} )
		).toHaveTextContent( 'Dispute readiness dismissed.' );
		expect(
			screen.queryByRole( 'heading', {
				name: 'Dispute readiness',
			} )
		).not.toBeInTheDocument();
		const balanceHeading = screen.getByRole( 'heading', {
			name: 'Balance',
		} );
		expect( balanceHeading.ownerDocument.activeElement ).toBe(
			balanceHeading
		);
	} );

	it( 'confirms the dispute readiness statement descriptor review', async () => {
		mockGetShell.mockResolvedValue(
			createShell( {
				feature_flags: {
					dispute_readiness_overview: true,
				},
			} )
		);
		mockGetDisputeReadiness.mockResolvedValue(
			createDisputeReadinessPayload()
		);
		mockConfirmStatementDescriptor.mockResolvedValue(
			createDisputeReadinessPayload( {
				score: 4,
				state: 'complete',
				incompleteSignalIds: [],
				signals: [],
			} )
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		await userEvent.click(
			await screen.findByRole( 'button', {
				name: 'Review statement descriptor',
			} )
		);

		expect(
			screen.getByRole( 'dialog', {
				name: 'Review statement descriptor',
			} )
		).toBeInTheDocument();
		expect( screen.getByText( 'NATIVE SHOP' ) ).toBeInTheDocument();

		await act( async () => {
			await userEvent.click(
				screen.getByRole( 'button', { name: 'Looks good' } )
			);
		} );

		expect( mockConfirmStatementDescriptor ).toHaveBeenCalledTimes( 1 );
		expect(
			await screen.findByRole( 'status', {
				name: 'Dispute readiness status',
			} )
		).toHaveTextContent( 'Statement descriptor confirmed.' );
		expect(
			screen.queryByRole( 'dialog', {
				name: 'Review statement descriptor',
			} )
		).not.toBeInTheDocument();
	} );

	it( 'does not move focus after delayed dispute readiness dismissal when focus has moved away', async () => {
		const dismissalRequest =
			createDeferred<
				Awaited<
					ReturnType< typeof dismissWooPaymentsDisputeReadinessCard >
				>
			>();
		mockGetShell.mockResolvedValue(
			createShell( {
				feature_flags: {
					dispute_readiness_overview: true,
				},
			} )
		);
		mockGetDisputeReadiness.mockResolvedValue(
			createDisputeReadinessPayload()
		);
		mockDismissDisputeReadiness.mockReturnValue( dismissalRequest.promise );
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		const dismissButton = await screen.findByRole( 'button', {
			name: 'Dismiss dispute readiness',
		} );
		await userEvent.click( dismissButton );
		screen.getByRole( 'heading', { name: 'WooPayments settings' } ).focus();

		await act( async () => {
			dismissalRequest.resolve( {
				overview: {
					enabled: true,
					isDismissed: true,
				},
			} );
			await dismissalRequest.promise;
		} );

		expect(
			screen.getByRole( 'heading', { name: 'WooPayments settings' } )
				.ownerDocument.activeElement
		).toBe(
			screen.getByRole( 'heading', { name: 'WooPayments settings' } )
		);
	} );

	it( 'does not create embedded account sessions without a mounted embedded notification UI', async () => {
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		expect(
			await screen.findByRole( 'heading', { name: 'Balance' } )
		).toBeInTheDocument();
		expect( mockCreateAccountSession ).not.toHaveBeenCalled();
	} );

	it( 'queries and renders WooPayments inbox notes from the notes store', async () => {
		mockUseSelect.mockReturnValue( {
			isError: false,
			isLoading: false,
			notes: [
				{
					id: 10,
					name: 'wcpay-test-note',
					type: 'info',
					status: 'unactioned',
					title: 'Review your WooPayments account',
					content: '<p>Account action is required.</p>',
					date_created: '2026-06-19T10:00:00',
					date_created_gmt: '2026-06-19T07:00:00',
					actions: [
						{
							id: 20,
							name: 'review',
							label: 'Review',
							query: '',
							status: 'unactioned',
							actioned_text: '',
							nonce_action: null,
							nonce_name: null,
							url: '/wp-admin/admin.php?page=wc-settings',
						},
					],
					is_deleted: false,
					is_read: false,
				},
			],
		} );
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		expect(
			await screen.findByRole( 'heading', { name: 'Inbox' } )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Review your WooPayments account' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'heading', {
				name: 'Review your WooPayments account',
				level: 3,
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Account action is required.' )
		).toBeInTheDocument();
	} );

	it( 'does not query inbox notes before the shell reports an eligible account', async () => {
		mockGetShell.mockResolvedValue(
			createShell( {
				account: {
					...createShell().account,
					connected: false,
					working: false,
				},
			} )
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		expect(
			await screen.findByRole( 'heading', { name: 'Balance' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'heading', { name: 'Inbox' } )
		).not.toBeInTheDocument();
		expect( mockUseSelect ).not.toHaveBeenCalled();
	} );

	it( 'keeps focus on a stable inbox heading after dismissing the only note', async () => {
		const removeRequest = createDeferred< {
			id: number;
			is_deleted: boolean;
		} >();
		mockUseSelect.mockReturnValue( {
			isError: false,
			isLoading: false,
			notes: [
				{
					id: 10,
					name: 'wcpay-test-note',
					type: 'info',
					status: 'unactioned',
					title: 'Review your WooPayments account',
					content: '<p>Account action is required.</p>',
					date_created: '2026-06-19T10:00:00',
					date_created_gmt: '2026-06-19T07:00:00',
					actions: [
						{
							id: 20,
							name: 'review',
							label: 'Review',
							query: '',
							status: 'unactioned',
							actioned_text: '',
							nonce_action: null,
							nonce_name: null,
							url: '/wp-admin/admin.php?page=wc-settings',
						},
					],
					is_deleted: false,
					is_read: false,
				},
			],
		} );
		getMockNotesDispatch().removeNote.mockReturnValue(
			removeRequest.promise
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		await userEvent.click(
			await screen.findByRole( 'button', { name: 'Dismiss' } )
		);
		await userEvent.click(
			screen.getByRole( 'button', { name: "Yes, I'm sure" } )
		);
		mockUseSelect.mockReturnValue( {
			isError: false,
			isLoading: false,
			notes: [],
		} );

		await act( async () => {
			removeRequest.resolve( {
				id: 10,
				is_deleted: true,
			} );
			await removeRequest.promise;
		} );

		const inboxHeading = screen.getByRole( 'heading', { name: 'Inbox' } );
		expect( getMockNotesDispatch().removeNote ).toHaveBeenCalledWith( 10 );
		await waitFor( () =>
			expect( inboxHeading.ownerDocument.activeElement ).toBe(
				inboxHeading
			)
		);
	} );

	it( 'keeps focus on a stable inbox heading after dismissing one of multiple notes', async () => {
		const removeRequest = createDeferred< {
			id: number;
			is_deleted: boolean;
		} >();
		mockUseSelect.mockReturnValue( {
			isError: false,
			isLoading: false,
			notes: [
				{
					id: 10,
					name: 'wcpay-test-note',
					type: 'info',
					status: 'unactioned',
					title: 'Review your WooPayments account',
					content: '<p>Account action is required.</p>',
					date_created: '2026-06-19T10:00:00',
					date_created_gmt: '2026-06-19T07:00:00',
					actions: [
						{
							id: 20,
							name: 'review',
							label: 'Review',
							query: '',
							status: 'unactioned',
							actioned_text: '',
							nonce_action: null,
							nonce_name: null,
							url: '/wp-admin/admin.php?page=wc-settings',
						},
					],
					is_deleted: false,
					is_read: false,
				},
				{
					id: 11,
					name: 'wcpay-second-note',
					type: 'info',
					status: 'unactioned',
					title: 'Finish WooPayments setup',
					content: '<p>Finish your account setup.</p>',
					date_created: '2026-06-19T11:00:00',
					date_created_gmt: '2026-06-19T08:00:00',
					actions: [
						{
							id: 21,
							name: 'finish',
							label: 'Finish setup',
							query: '',
							status: 'unactioned',
							actioned_text: '',
							nonce_action: null,
							nonce_name: null,
							url: '/wp-admin/admin.php?page=wc-settings',
						},
					],
					is_deleted: false,
					is_read: false,
				},
			],
		} );
		getMockNotesDispatch().removeNote.mockReturnValue(
			removeRequest.promise
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		const dismissButtons = await screen.findAllByRole( 'button', {
			name: 'Dismiss',
		} );
		await userEvent.click( dismissButtons[ 0 ] );
		await userEvent.click(
			screen.getByRole( 'button', { name: "Yes, I'm sure" } )
		);
		mockUseSelect.mockReturnValue( {
			isError: false,
			isLoading: false,
			notes: [
				{
					id: 11,
					name: 'wcpay-second-note',
					type: 'info',
					status: 'unactioned',
					title: 'Finish WooPayments setup',
					content: '<p>Finish your account setup.</p>',
					date_created: '2026-06-19T11:00:00',
					date_created_gmt: '2026-06-19T08:00:00',
					actions: [
						{
							id: 21,
							name: 'finish',
							label: 'Finish setup',
							query: '',
							status: 'unactioned',
							actioned_text: '',
							nonce_action: null,
							nonce_name: null,
							url: '/wp-admin/admin.php?page=wc-settings',
						},
					],
					is_deleted: false,
					is_read: false,
				},
			],
		} );

		await act( async () => {
			removeRequest.resolve( {
				id: 10,
				is_deleted: true,
			} );
			await removeRequest.promise;
		} );

		const inboxHeading = screen.getByRole( 'heading', { name: 'Inbox' } );
		expect( getMockNotesDispatch().removeNote ).toHaveBeenCalledWith( 10 );
		await waitFor( () =>
			expect( inboxHeading.ownerDocument.activeElement ).toBe(
				inboxHeading
			)
		);
	} );

	it( 'does not move focus after delayed inbox dismissal when focus has moved away', async () => {
		const removeRequest = createDeferred< {
			id: number;
			is_deleted: boolean;
		} >();
		mockUseSelect.mockReturnValue( {
			isError: false,
			isLoading: false,
			notes: [
				{
					id: 10,
					name: 'wcpay-test-note',
					type: 'info',
					status: 'unactioned',
					title: 'Review your WooPayments account',
					content: '<p>Account action is required.</p>',
					date_created: '2026-06-19T10:00:00',
					date_created_gmt: '2026-06-19T07:00:00',
					actions: [
						{
							id: 20,
							name: 'review',
							label: 'Review',
							query: '',
							status: 'unactioned',
							actioned_text: '',
							nonce_action: null,
							nonce_name: null,
							url: '/wp-admin/admin.php?page=wc-settings',
						},
					],
					is_deleted: false,
					is_read: false,
				},
			],
		} );
		getMockNotesDispatch().removeNote.mockReturnValue(
			removeRequest.promise
		);
		mockGetOverview.mockResolvedValue( createDepositsOverview() );
		mockGetRecent.mockResolvedValue( {
			data: [],
			total_count: 0,
		} );

		render( <WooPaymentsOverviewPage /> );

		await userEvent.click(
			await screen.findByRole( 'button', { name: 'Dismiss' } )
		);
		await userEvent.click(
			screen.getByRole( 'button', { name: "Yes, I'm sure" } )
		);
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Cancel' } )
		);

		const settingsHeading = screen.getByRole( 'heading', {
			name: 'WooPayments settings',
		} );
		settingsHeading.focus();
		mockUseSelect.mockReturnValue( {
			isError: false,
			isLoading: false,
			notes: [],
		} );

		await act( async () => {
			removeRequest.resolve( {
				id: 10,
				is_deleted: true,
			} );
			await removeRequest.promise;
		} );
		await act(
			() =>
				new Promise< void >( ( resolve ) =>
					window.requestAnimationFrame( () => resolve() )
				)
		);

		expect( settingsHeading.ownerDocument.activeElement ).toBe(
			settingsHeading
		);
	} );
} );
