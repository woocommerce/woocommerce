import {
	afterEach,
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type MockInstance,
} from 'vitest';
const { mockGetTaskListsByIds } = vi.hoisted( () => {
	const mockGetTaskListsByIds = vi.fn();
	return {
		mockGetTaskListsByIds,
	};
} );

// Mock problematic imports before importing the module under test.
vi.mock(
	'@wordpress/edit-site/build-module/components/sidebar-navigation-item',
	() => {
		const mock = {
			__esModule: true,
			default: () => null,
		};
		return Object.defineProperties(
			{
				default: mock,
			},
			Object.getOwnPropertyDescriptors( mock )
		);
	}
);
vi.mock( '@woocommerce/navigation', () => {
	const mock = {
		getNewPath: vi.fn(),
		navigateTo: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/hooks', () => {
	const mock = {
		applyFilters: vi.fn( ( _filter, value ) => value ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/tracks', () => {
	const mock = {
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/onboarding', () => {
	const mock = {
		accessTaskReferralStorage: vi.fn( () => ( {
			setWithExpiry: vi.fn(),
		} ) ),
		createStorageUtils: vi.fn( () => ( {
			getWithExpiry: vi.fn( () => [] ),
			setWithExpiry: vi.fn(),
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/settings', () => {
	const mock = {
		getAdminLink: vi.fn( ( path ) => path ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/settings-payments/utils', () => {
	const mock = {
		recordPaymentsOnboardingEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

// Mock the entire @woocommerce/data module to avoid complex initialization.
vi.mock( '@woocommerce/data', () => {
	const mock = {
		onboardingStore: 'onboarding-store',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

// Create a mock function for resolveSelect's chain.

vi.mock( '@wordpress/data', () => {
	const mock = {
		resolveSelect: vi.fn( () => ( {
			getTaskListsByIds: mockGetTaskListsByIds,
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

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
	let consoleErrorSpy: MockInstance;
	beforeEach( () => {
		vi.clearAllMocks();
		// Spy on console.error to verify error logging.
		consoleErrorSpy = vi
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
