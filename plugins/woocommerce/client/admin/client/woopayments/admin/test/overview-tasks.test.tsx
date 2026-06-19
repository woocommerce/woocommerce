/**
 * Internal dependencies
 */
import {
	buildOverviewTasks,
	formatTaskCurrency,
	getVisibleOverviewTasks,
	isDisputeDueWithinDays,
} from '../overview/components/overview-tasks';

const DAY_IN_MS = 24 * 60 * 60 * 1000;
const NOW = new Date( '2026-06-19T12:00:00.000Z' ).getTime();

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

const createDispute = ( overrides: Record< string, unknown > = {} ) => ( {
	id: 'dp_test',
	dispute_id: 'dp_test',
	charge_id: 'ch_test',
	amount: 1000,
	currency: 'usd',
	evidence_due_by: NOW + DAY_IN_MS,
	...overrides,
} );

describe( 'overview task builders', () => {
	beforeEach( () => {
		jest.spyOn( Date, 'now' ).mockReturnValue( NOW );
		Object.defineProperty( window, 'wcSettings', {
			configurable: true,
			value: {
				adminUrl: 'https://example.com/wp-admin/',
			},
		} );
	} );

	afterEach( () => {
		jest.restoreAllMocks();
	} );

	it( 'builds an incomplete setup task that routes to native onboarding', () => {
		const shell = createShell( {
			account_status: {
				...createShell().account_status,
				status: 'restricted',
				details_submitted: false,
			},
			show_update_details_task: true,
		} );

		const task = buildOverviewTasks( {
			shell,
			disputes: [],
			onOpenUpdateBusinessDetails: jest.fn(),
			onActivatePayments: jest.fn(),
		} )[ 0 ];

		expect( task ).toMatchObject( {
			key: 'complete-setup',
			title: 'Finish setting up WooPayments',
			actionLabel: 'Finish setup',
		} );
		expect( task.href ).toContain( 'path=%2Fwoopayments%2Fonboarding' );
		expect( task.href ).toContain( 'source=wcpay-finish-setup-task' );
		expect( task.href ).toContain( 'from=WCPAY_OVERVIEW' );
	} );

	it( 'builds a restricted-soon update task that opens the details modal', () => {
		const onOpenUpdateBusinessDetails = jest.fn();
		const shell = createShell( {
			account_status: {
				...createShell().account_status,
				status: 'restricted_soon',
				current_deadline: Math.floor( ( NOW + DAY_IN_MS ) / 1000 ),
				requirements: {
					errors: [
						{
							code: 'verification_document_missing_front',
							reason: 'Upload the front of the document.',
						},
					],
				},
			},
			show_update_details_task: true,
		} );

		const task = buildOverviewTasks( {
			shell,
			disputes: [],
			onOpenUpdateBusinessDetails,
			onActivatePayments: jest.fn(),
		} )[ 0 ];

		expect( task ).toMatchObject( {
			key: 'update-business-details',
			title: 'Update WooPayments business details',
			actionLabel: 'Update',
		} );
		expect( task.content ).toContain( 'Update by' );

		task.onClick?.();

		expect( onOpenUpdateBusinessDetails ).toHaveBeenCalledWith( shell );
	} );

	it( 'filters generic requirement errors from the update details task content', () => {
		const shell = createShell( {
			account_status: {
				...createShell().account_status,
				status: 'restricted_soon',
				current_deadline: Math.floor( ( NOW + DAY_IN_MS ) / 1000 ),
				requirements: {
					errors: [
						{
							code: 'invalid_value_other',
							reason: 'Generic Stripe requirement.',
						},
						{
							code: 'verification_document_missing_front',
							reason: 'Upload the front of the document.',
						},
					],
				},
			},
			show_update_details_task: true,
		} );

		const task = buildOverviewTasks( {
			shell,
			disputes: [],
			onOpenUpdateBusinessDetails: jest.fn(),
			onActivatePayments: jest.fn(),
		} )[ 0 ];

		expect( task.actionLabel ).toBe( 'Update' );
		expect( task.content ).toContain(
			'The uploaded file was missing the front of the document.'
		);
		expect( task.content ).not.toContain( 'Generic Stripe requirement.' );
	} );

	it( 'uses the more-details action when multiple requirement errors remain visible', () => {
		const shell = createShell( {
			account_status: {
				...createShell().account_status,
				status: 'restricted_soon',
				current_deadline: Math.floor( ( NOW + DAY_IN_MS ) / 1000 ),
				requirements: {
					errors: [
						{
							code: 'verification_document_missing_front',
						},
						{
							code: 'verification_document_missing_back',
						},
					],
				},
			},
			show_update_details_task: true,
		} );

		const task = buildOverviewTasks( {
			shell,
			disputes: [],
			onOpenUpdateBusinessDetails: jest.fn(),
			onActivatePayments: jest.fn(),
		} )[ 0 ];

		expect( task ).toMatchObject( {
			key: 'update-business-details',
			actionLabel: 'More details',
		} );
		expect( task.content ).toContain( 'Update by' );
		expect( task.content ).not.toContain(
			'The uploaded file was missing the front of the document.'
		);
	} );

	it( 'builds an urgent single dispute task that links to the transaction details route', () => {
		const task = buildOverviewTasks( {
			shell: createShell(),
			disputes: [ createDispute() ],
			onOpenUpdateBusinessDetails: jest.fn(),
			onActivatePayments: jest.fn(),
		} )[ 0 ];

		expect( task ).toMatchObject( {
			key: 'dispute-resolution-task-dp_test',
			title: 'Respond to a dispute for $10.00',
			actionLabel: 'Respond now',
			level: 1,
		} );
		expect( task.href ).toContain(
			'path=%2Fwoopayments%2Ftransactions%2Fdetails'
		);
		expect( task.href ).toContain( 'id=ch_test' );
	} );

	it( 'builds a multiple-dispute task that links to the awaiting-response dispute list', () => {
		const task = buildOverviewTasks( {
			shell: createShell(),
			disputes: [
				createDispute( { dispute_id: 'dp_one', amount: 1000 } ),
				createDispute( { dispute_id: 'dp_two', amount: 2500 } ),
			],
			onOpenUpdateBusinessDetails: jest.fn(),
			onActivatePayments: jest.fn(),
		} )[ 0 ];

		expect( task ).toMatchObject( {
			key: 'dispute-resolution-task-dp_one-dp_two',
			title: 'Respond to 2 active disputes for a total of $35.00',
			actionLabel: 'See disputes',
		} );
		expect( task.href ).toContain( 'path=%2Fwoopayments%2Fdisputes' );
		expect( task.href ).toContain( 'filter=awaiting_response' );
	} );

	it( 'builds a go-live task that dispatches the native activate-payments event', () => {
		const onActivatePayments = jest.fn();
		const shell = createShell( {
			account: {
				...createShell().account,
				live: false,
				test_drive: true,
				test_mode_onboarding: true,
				dev_mode: false,
			},
		} );

		const task = buildOverviewTasks( {
			shell,
			disputes: [],
			onOpenUpdateBusinessDetails: jest.fn(),
			onActivatePayments,
		} )[ 0 ];

		expect( task ).toMatchObject( {
			key: 'go-live-payments',
			title: 'Activate payments',
		} );

		task.onClick?.();

		expect( onActivatePayments ).toHaveBeenCalled();
	} );

	it( 'filters dismissed, deleted, and currently snoozed tasks', () => {
		const tasks = [
			{ key: 'visible', title: 'Visible' },
			{ key: 'dismissed', title: 'Dismissed' },
			{ key: 'deleted', title: 'Deleted' },
			{ key: 'snoozed', title: 'Snoozed' },
		];

		expect(
			getVisibleOverviewTasks(
				tasks,
				{
					dismissed_todo_tasks: [ 'dismissed' ],
					deleted_todo_tasks: [ 'deleted' ],
					remind_me_later_todo_tasks: {
						snoozed: NOW + DAY_IN_MS,
					},
				},
				NOW
			)
		).toEqual( [ tasks[ 0 ] ] );
	} );

	it( 'detects disputes due within the requested day window', () => {
		expect( isDisputeDueWithinDays( createDispute(), 7, NOW ) ).toBe(
			true
		);
		expect(
			isDisputeDueWithinDays(
				createDispute( { evidence_due_by: NOW + 8 * DAY_IN_MS } ),
				7,
				NOW
			)
		).toBe( false );
	} );

	it( 'formats dispute task currency amounts', () => {
		expect( formatTaskCurrency( 1000, 'usd' ) ).toBe( '$10.00' );
	} );
} );
