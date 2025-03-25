/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';
import { useSelect } from '@wordpress/data';
import { TaskType } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { useTaskListsState } from '../use-tasklists-state';
import { getAdminSetting } from '~/utils/admin-settings';

// Mock dependencies
jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true,
		...originalModule,
		useSelect: jest.fn(),
	};
} );
jest.mock( '~/utils/admin-settings' );

describe( 'useTaskListsState', () => {
	// Setup mock data
	const mockSetupTask: TaskType = {
		id: 'mock-task',
		title: 'Mock Task',
		canView: true,
		isComplete: false,
		isDismissed: false,
		time: '1 minute',
		content: 'Mock content',
		additionalInfo: '',
		actionLabel: 'Mock action',
		isVisible: true,
		level: 3,
		parentId: '',
		isDismissable: true,
		isSnoozed: false,
		isSnoozeable: true,
		isDisabled: false,
		snoozedUntil: 0,
		isActioned: false,
		isVisited: false,
		eventPrefix: 'mock_task',
		recordViewEvent: true,
	};

	const mockSetupTaskList = {
		id: 'setup',
		title: 'Setup',
		isHidden: false,
		isComplete: false,
		tasks: [ mockSetupTask ],
	};

	const mockExtendedTaskList = {
		id: 'extended',
		title: 'Extended',
		isHidden: false,
		isComplete: false,
		tasks: [ mockSetupTask ],
	};

	beforeEach( () => {
		// Reset all mocks before each test
		jest.clearAllMocks();
		( useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( () => ( {
				getTaskList: jest.fn(),
				hasFinishedResolution: () => true,
			} ) )
		);
		( getAdminSetting as jest.Mock ).mockImplementation( ( setting ) => {
			if ( setting === 'visibleTaskListIds' ) return [];
			if ( setting === 'completedTaskListIds' ) return [];
			return [];
		} );
	} );

	it( 'should return default state when no task lists are visible', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( ( setting ) => {
			if ( setting === 'visibleTaskListIds' ) return [];
			if ( setting === 'completedTaskListIds' ) return [];
			return [];
		} );

		const { result } = renderHook( () => useTaskListsState() );

		expect( result.current ).toEqual( {
			requestingTaskListOptions: false,
			setupTaskListHidden: true,
			setupTaskListComplete: false,
			setupTaskListActive: false,
			setupTasksCount: undefined,
			setupTasksCompleteCount: undefined,
			thingsToDoNextCount: undefined,
		} );
	} );

	it( 'should return setup task list state when only setup is visible and not completed', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( ( setting ) => {
			if ( setting === 'visibleTaskListIds' ) return [ 'setup' ];
			if ( setting === 'completedTaskListIds' ) return [];
			return [];
		} );
		( useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( () => ( {
				getTaskList: () => mockSetupTaskList,
				hasFinishedResolution: () => true,
			} ) )
		);

		const { result } = renderHook( () => useTaskListsState() );

		expect( result.current ).toEqual( {
			requestingTaskListOptions: false,
			setupTaskListHidden: false,
			setupTaskListComplete: false,
			setupTaskListActive: true,
			setupTasksCount: 1,
			setupTasksCompleteCount: 0,
			thingsToDoNextCount: undefined,
		} );
	} );

	it( 'should return extended task list state when only extended is visible and not completed', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( ( setting ) => {
			if ( setting === 'visibleTaskListIds' ) return [ 'extended' ];
			if ( setting === 'completedTaskListIds' ) return [];
			return [];
		} );
		( useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( () => ( {
				getTaskList: () => mockExtendedTaskList,
				hasFinishedResolution: () => true,
			} ) )
		);

		const { result } = renderHook( () =>
			useTaskListsState( {
				setupTasklist: false,
				extendedTaskList: true,
			} )
		);

		expect( result.current ).toEqual( {
			requestingTaskListOptions: false,
			setupTaskListHidden: true,
			setupTaskListComplete: false,
			setupTaskListActive: false,
			setupTasksCount: undefined,
			setupTasksCompleteCount: undefined,
			thingsToDoNextCount: 1,
		} );
	} );

	it( 'should return full state when both task lists are visible and not completed', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( ( setting ) => {
			if ( setting === 'visibleTaskListIds' )
				return [ 'setup', 'extended' ];
			if ( setting === 'completedTaskListIds' ) return [];
			return [];
		} );
		( useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( () => ( {
				getTaskList: ( id: string ) =>
					id === 'setup' ? mockSetupTaskList : mockExtendedTaskList,
				hasFinishedResolution: () => true,
			} ) )
		);

		const { result } = renderHook( () => useTaskListsState() );

		expect( result.current ).toEqual( {
			requestingTaskListOptions: false,
			setupTaskListHidden: false,
			setupTaskListComplete: false,
			setupTaskListActive: true,
			setupTasksCount: 1,
			setupTasksCompleteCount: 0,
			thingsToDoNextCount: 1,
		} );
	} );

	it( 'should handle loading state correctly', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( ( setting ) => {
			if ( setting === 'visibleTaskListIds' )
				return [ 'setup', 'extended' ];
			if ( setting === 'completedTaskListIds' ) return [];
			return [];
		} );
		( useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( () => ( {
				getTaskList: () => null,
				hasFinishedResolution: () => false,
			} ) )
		);

		const { result } = renderHook( () => useTaskListsState() );

		expect( result.current.requestingTaskListOptions ).toBe( true );
	} );

	it( 'should handle completed task lists correctly', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( ( setting ) => {
			if ( setting === 'visibleTaskListIds' )
				return [ 'setup', 'extended' ];
			if ( setting === 'completedTaskListIds' ) return [ 'setup' ];
			return [];
		} );
		( useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( () => ( {
				getTaskList: () => ( {
					...mockSetupTaskList,
					isHidden: true,
				} ),
				hasFinishedResolution: () => true,
			} ) )
		);

		const { result } = renderHook( () => useTaskListsState() );

		expect( result.current ).toEqual( {
			requestingTaskListOptions: false,
			setupTaskListHidden: false,
			setupTaskListComplete: true,
			setupTaskListActive: false,
			setupTasksCount: undefined,
			setupTasksCompleteCount: undefined,
			thingsToDoNextCount: 0,
		} );
	} );

	it( 'should respect the options parameter when task lists are completed', () => {
		( getAdminSetting as jest.Mock ).mockImplementation( ( setting ) => {
			if ( setting === 'visibleTaskListIds' )
				return [ 'setup', 'extended' ];
			if ( setting === 'completedTaskListIds' )
				return [ 'setup', 'extended' ];
			return [];
		} );
		( useSelect as jest.Mock ).mockImplementation( ( callback ) =>
			callback( () => ( {
				getTaskList: () => ( {
					...mockSetupTaskList,
					isHidden: true,
				} ),
				hasFinishedResolution: () => true,
			} ) )
		);

		const { result } = renderHook( () =>
			useTaskListsState( {
				setupTasklist: false,
				extendedTaskList: false,
			} )
		);

		expect( result.current ).toEqual( {
			requestingTaskListOptions: false,
			setupTaskListHidden: false,
			setupTaskListComplete: true,
			setupTaskListActive: false,
			setupTasksCount: undefined,
			setupTasksCompleteCount: undefined,
			thingsToDoNextCount: undefined,
		} );
	} );
} );
