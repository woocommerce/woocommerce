// Mock problematic imports before importing the module under test.
jest.mock(
	'@wordpress/edit-site/build-module/components/sidebar-navigation-item',
	() => ( {
		__esModule: true,
		default: () => null,
	} )
);

jest.mock( '@woocommerce/navigation', () => ( {
	getNewPath: jest.fn(),
	navigateTo: jest.fn(),
} ) );

jest.mock( '@wordpress/hooks', () => ( {
	applyFilters: jest.fn( ( _filter, value ) => value ),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/onboarding', () => ( {
	accessTaskReferralStorage: jest.fn( () => ( {
		setWithExpiry: jest.fn(),
	} ) ),
	createStorageUtils: jest.fn( () => ( {
		getWithExpiry: jest.fn( () => [] ),
		setWithExpiry: jest.fn(),
	} ) ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: jest.fn( ( path ) => path ),
} ) );

jest.mock( '~/settings-payments/utils', () => ( {
	recordPaymentsOnboardingEvent: jest.fn(),
} ) );

// Mock the entire @woocommerce/data module to avoid complex initialization.
jest.mock( '@woocommerce/data', () => ( {
	onboardingStore: 'onboarding-store',
} ) );

// Create a mock function for resolveSelect's chain.
const mockGetTaskListsByIds = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	resolveSelect: jest.fn( () => ( {
		getTaskListsByIds: mockGetTaskListsByIds,
	} ) ),
} ) );

/**
 * Internal dependencies
 */
import { getPaymentsTaskFromLysTasklist } from '../tasklist';

/**
 * TaskType interface for tests.
 * Matches the structure from @woocommerce/data.
 */
interface TaskType {
	id: string;
	parentId: string;
	title: string;
	content: string;
	isComplete: boolean;
	time: string;
	actionLabel?: string;
	actionUrl?: string;
	isVisible: boolean;
	isDismissable: boolean;
	isDismissed: boolean;
	isSnoozeable: boolean;
	isSnoozed: boolean;
	snoozedUntil: number;
	canView: boolean;
	isActioned: boolean;
	eventPrefix: string;
	level: 1 | 2 | 3;
	isDisabled: boolean;
	additionalInfo: string;
	isVisited: boolean;
	isInProgress: boolean;
	inProgressLabel: string;
	recordViewEvent: boolean;
	additionalData?: Record< string, unknown >;
}

/**
 * Creates a mock TaskType object with default values.
 *
 * @param overrides - Partial TaskType to override default values.
 * @return A complete mock TaskType object.
 */
const createMockTask = ( overrides: Partial< TaskType > = {} ): TaskType => ( {
	id: 'test-task',
	parentId: '',
	title: 'Test Task',
	content: '',
	isComplete: false,
	time: '5 minutes',
	actionLabel: 'Start',
	actionUrl: 'https://example.com/task',
	isVisible: true,
	isDismissable: false,
	isDismissed: false,
	isSnoozeable: false,
	isSnoozed: false,
	snoozedUntil: 0,
	canView: true,
	isActioned: false,
	eventPrefix: 'test',
	level: 1,
	isDisabled: false,
	additionalInfo: '',
	isVisited: false,
	isInProgress: false,
	inProgressLabel: '',
	recordViewEvent: false,
	...overrides,
} );

/**
 * Creates a mock tasklist array for getTaskListsByIds.
 *
 * @param tasks - The tasks to include in the tasklist.
 * @return A mock tasklist array.
 */
const createMockTasklistResponse = ( tasks: TaskType[] ) => [
	{
		id: 'setup',
		title: 'Setup',
		isHidden: false,
		isVisible: true,
		isComplete: false,
		eventPrefix: 'tasklist',
		displayProgressHeader: true,
		keepCompletedTaskList: 'no' as const,
		tasks,
	},
];

describe( 'getPaymentsTaskFromLysTasklist', () => {
	let consoleErrorSpy: jest.SpyInstance;

	beforeEach( () => {
		jest.clearAllMocks();
		// Spy on console.error to verify error logging.
		consoleErrorSpy = jest
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );
	} );

	afterEach( () => {
		consoleErrorSpy.mockRestore();
	} );

	describe( 'successful retrieval', () => {
		it( 'returns the payments task when fullLysTaskList contains a task with id "payments"', async () => {
			const paymentsTask = createMockTask( {
				id: 'payments',
				title: 'Set up payments',
			} );
			const otherTask = createMockTask( {
				id: 'shipping',
				title: 'Set up shipping',
			} );

			mockGetTaskListsByIds.mockResolvedValue(
				createMockTasklistResponse( [ paymentsTask, otherTask ] )
			);

			const result = await getPaymentsTaskFromLysTasklist();

			expect( result ).toEqual( paymentsTask );
			expect( consoleErrorSpy ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'invalid tasklist data', () => {
		// Note: When tasks is not an array, getLysTasklist() throws an error
		// when trying to call .filter() on it. The error is caught by the
		// try/catch in getPaymentsTaskFromLysTasklist and logged.
		it( 'returns undefined and logs an error when tasks is not an array', async () => {
			// Return a tasklist where tasks is not an array.
			mockGetTaskListsByIds.mockResolvedValue( [
				{
					id: 'setup',
					title: 'Setup',
					isHidden: false,
					isVisible: true,
					isComplete: false,
					eventPrefix: 'tasklist',
					displayProgressHeader: true,
					keepCompletedTaskList: 'no' as const,
					tasks: 'not-an-array',
				},
			] );

			const result = await getPaymentsTaskFromLysTasklist();

			expect( result ).toBeUndefined();
			// Error is caught from getLysTasklist when it tries to filter.
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				'Error fetching payments task:',
				expect.any( TypeError )
			);
		} );

		it( 'returns undefined and logs an error when tasks is null', async () => {
			mockGetTaskListsByIds.mockResolvedValue( [
				{
					id: 'setup',
					title: 'Setup',
					isHidden: false,
					isVisible: true,
					isComplete: false,
					eventPrefix: 'tasklist',
					displayProgressHeader: true,
					keepCompletedTaskList: 'no' as const,
					tasks: null,
				},
			] );

			const result = await getPaymentsTaskFromLysTasklist();

			expect( result ).toBeUndefined();
			// Error is caught from getLysTasklist when it tries to filter.
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				'Error fetching payments task:',
				expect.any( TypeError )
			);
		} );

		it( 'returns undefined and logs an error when tasks is undefined', async () => {
			mockGetTaskListsByIds.mockResolvedValue( [
				{
					id: 'setup',
					title: 'Setup',
					isHidden: false,
					isVisible: true,
					isComplete: false,
					eventPrefix: 'tasklist',
					displayProgressHeader: true,
					keepCompletedTaskList: 'no' as const,
					tasks: undefined,
				},
			] );

			const result = await getPaymentsTaskFromLysTasklist();

			expect( result ).toBeUndefined();
			// Error is caught from getLysTasklist when it tries to filter.
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				'Error fetching payments task:',
				expect.any( TypeError )
			);
		} );
	} );

	describe( 'payments task absent', () => {
		it( 'returns undefined when the payments task is absent from fullLysTaskList', async () => {
			const shippingTask = createMockTask( {
				id: 'shipping',
				title: 'Set up shipping',
			} );
			const taxTask = createMockTask( {
				id: 'tax',
				title: 'Set up tax',
			} );

			mockGetTaskListsByIds.mockResolvedValue(
				createMockTasklistResponse( [ shippingTask, taxTask ] )
			);

			const result = await getPaymentsTaskFromLysTasklist();

			expect( result ).toBeUndefined();
			expect( consoleErrorSpy ).not.toHaveBeenCalled();
		} );

		it( 'returns undefined when fullLysTaskList is an empty array', async () => {
			mockGetTaskListsByIds.mockResolvedValue(
				createMockTasklistResponse( [] )
			);

			const result = await getPaymentsTaskFromLysTasklist();

			expect( result ).toBeUndefined();
			expect( consoleErrorSpy ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'error handling', () => {
		it( 'returns undefined and logs an error when getLysTasklist throws', async () => {
			const testError = new Error( 'Network error' );
			mockGetTaskListsByIds.mockRejectedValue( testError );

			const result = await getPaymentsTaskFromLysTasklist();

			expect( result ).toBeUndefined();
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				'Error fetching payments task:',
				testError
			);
		} );

		it( 'returns undefined and logs an error when getLysTasklist throws a non-Error value', async () => {
			mockGetTaskListsByIds.mockRejectedValue( 'String error' );

			const result = await getPaymentsTaskFromLysTasklist();

			expect( result ).toBeUndefined();
			expect( consoleErrorSpy ).toHaveBeenCalledWith(
				'Error fetching payments task:',
				'String error'
			);
		} );
	} );
} );
