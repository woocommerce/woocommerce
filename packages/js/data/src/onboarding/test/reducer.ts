/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer, { defaultState } from '../reducer';
import TYPES from '../action-types';
import { TaskListType, TaskType } from '../types';

const profileItems = {
	business_extensions: [],
	completed: false,
	industry: null,
	number_employees: null,
	other_platform: null,
	other_platform_name: '',
	product_count: null,
	product_types: null,
	revenue: null,
	selling_venues: null,
	setup_client: false,
	skipped: true,
	theme: null,
	wccom_connected: null,
	is_agree_marketing: null,
	store_email: null,
};

const paymentMethods = [
	{
		id: '',
		content: '',
		plugins: [],
		title: '',
		category_additional: [],
		category_other: [],
		image: '',
	},
];

const task = ( id: string, overrides = {} ) =>
	( {
		id,
		title: id,
		content: '',
		parentId: '',
		isComplete: false,
		isDismissable: true,
		isDismissed: false,
		isSnoozed: false,
		isInProgress: false,
		inProgressLabel: '',
		isVisible: true,
		isSnoozeable: false,
		isDisabled: false,
		snoozedUntil: 0,
		time: '',
		isVisited: false,
		additionalInfo: '',
		canView: true,
		isActioned: false,
		eventPrefix: '',
		level: 3,
		recordViewEvent: false,
		...overrides,
	} ) as TaskType;

const taskList = ( id: string, tasks: TaskType[] ) =>
	( {
		id,
		title: id,
		isHidden: false,
		isVisible: true,
		isComplete: false,
		tasks,
		eventPrefix: '',
		displayProgressHeader: false,
		keepCompletedTaskList: 'no',
	} ) as TaskListType;

// `payments` exists in both the setup and extended lists, so an unscoped
// update would dismiss the setup task too.
const taskListsWithSharedTaskId = {
	setup: taskList( 'setup', [ task( 'payments' ) ] ),
	extended: taskList( 'extended', [ task( 'payments' ) ] ),
};

describe( 'plugins reducer', () => {
	it( 'should return a default state', () => {
		// @ts-expect-error - we're testing the default state
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
	} );

	it( 'should handle SET_PROFILE_ITEMS', () => {
		const state = reducer(
			{
				profileItems,
				profileProgress: {},
				freeExtensions: [],
				taskLists: {},
				paymentMethods: [],
				productTypes: {},
				emailPrefill: '',
				errors: {},
				requesting: {},
				jetpackAuthUrls: {},
			},
			{
				type: TYPES.SET_PROFILE_ITEMS,
				profileItems: { is_agree_marketing: true },
				replace: false,
			}
		);

		expect( state.profileItems.is_agree_marketing ).toBe( true );
	} );

	it( 'should handle SET_PROFILE_ITEMS with replace', () => {
		const state = reducer(
			{
				profileItems,
				profileProgress: {},
				freeExtensions: [],
				taskLists: {},
				paymentMethods: [],
				productTypes: {},
				emailPrefill: '',
				errors: {},
				requesting: {},
				jetpackAuthUrls: {},
			},
			{
				type: TYPES.SET_PROFILE_ITEMS,
				profileItems: { is_agree_marketing: true },
				replace: true,
			}
		);

		expect( state.profileItems ).not.toHaveProperty( 'store_email' );
		expect( state.profileItems ).toHaveProperty( 'is_agree_marketing' );
		expect( state.profileItems.is_agree_marketing ).toBe( true );
	} );

	it( 'should handle GET_PAYMENT_METHODS_SUCCESS', () => {
		const state = reducer(
			// @ts-expect-error - we're only testing paymentMethods
			{
				paymentMethods,
			},
			{
				type: TYPES.GET_PAYMENT_METHODS_SUCCESS,
				paymentMethods: [ { image_72x72: 'changed' } ],
			}
		);

		expect( state.paymentMethods[ 0 ] ).not.toHaveProperty(
			'previousItem'
		);
		expect( state.paymentMethods[ 0 ] ).toHaveProperty( 'image_72x72' );
		expect( state.paymentMethods[ 0 ].image_72x72 ).toBe( 'changed' );
	} );

	it( 'should handle SET_ERROR', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_ERROR,
			selector: 'getProfileItems',
			error: { code: 'error' },
		} );

		/* eslint-disable dot-notation */
		// @ts-expect-error we're asserting error properties
		expect( state.errors[ 'getProfileItems' ].code ).toBe( 'error' );
		/* eslint-enable dot-notation */
	} );

	it( 'should handle SET_IS_REQUESTING', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_IS_REQUESTING,
			selector: 'updateProfileItems',
			isRequesting: true,
		} );

		/* eslint-disable dot-notation */
		expect( state.requesting[ 'updateProfileItems' ] ).toBeTruthy();
		/* eslint-enable dot-notation */
	} );

	it( 'should only dismiss the task in the supplied task list', () => {
		const state = reducer(
			{ ...defaultState, taskLists: taskListsWithSharedTaskId },
			{
				type: TYPES.DISMISS_TASK_REQUEST,
				taskId: 'payments',
				taskListId: 'extended',
			}
		);

		expect( state.taskLists.extended.tasks[ 0 ].isDismissed ).toBe( true );
		expect( state.taskLists.setup.tasks[ 0 ].isDismissed ).toBe( false );
	} );

	it( 'should only restore the task in the supplied task list', () => {
		const dismissed = {
			setup: taskList( 'setup', [
				task( 'payments', { isDismissed: true } ),
			] ),
			extended: taskList( 'extended', [
				task( 'payments', { isDismissed: true } ),
			] ),
		};

		const state = reducer(
			{ ...defaultState, taskLists: dismissed },
			{
				type: TYPES.UNDO_DISMISS_TASK_SUCCESS,
				task: { id: 'payments', isDismissed: false },
				taskListId: 'extended',
			}
		);

		expect( state.taskLists.extended.tasks[ 0 ].isDismissed ).toBe( false );
		expect( state.taskLists.setup.tasks[ 0 ].isDismissed ).toBe( true );
	} );

	it( 'should dismiss the task in every list when no task list is supplied', () => {
		const state = reducer(
			{ ...defaultState, taskLists: taskListsWithSharedTaskId },
			{
				type: TYPES.DISMISS_TASK_REQUEST,
				taskId: 'payments',
				taskListId: undefined,
			}
		);

		expect( state.taskLists.extended.tasks[ 0 ].isDismissed ).toBe( true );
		expect( state.taskLists.setup.tasks[ 0 ].isDismissed ).toBe( true );
	} );
} );
