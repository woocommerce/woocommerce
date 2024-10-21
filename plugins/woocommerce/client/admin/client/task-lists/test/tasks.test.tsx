/**
 * External dependencies
 */
import { render, act, cleanup, waitFor } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { TaskLists } from '../task-lists';
import { TaskProps } from '../components/task';
import { TaskListProps } from '../components/task-list';
import { TaskListProps as SetupTaskListProps } from '../setup-task-list/setup-task-list';

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useDispatch: jest.fn().mockReturnValue( {} ),
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

jest.mock( '@woocommerce/explat' );
jest.mock( '@woocommerce/tracks' );

jest.mock( '../components/task-list', () => ( {
	TaskList: ( { id }: TaskListProps ) => <div>task-list:{ id }</div>,
} ) );

jest.mock( '../setup-task-list', () => ( {
	SetupTaskList: ( { id }: SetupTaskListProps ) => (
		<div>setup-task-list:{ id }</div>
	),
} ) );

jest.mock( '../components/task', () => ( {
	Task: ( { query }: TaskProps ) => <div>task:{ query.task }</div>,
} ) );

jest.mock( '../components/placeholder', () => ( {
	TasksPlaceholder: () => <div>task-placeholder</div>,
} ) );

jest.mock( '~/activity-panel/display-options', () => ( {
	DisplayOption: ( { children }: { children: React.ReactNode } ) => (
		<div>{ children } </div>
	),
} ) );

describe( 'Task', () => {
	const hideTaskList = jest.fn();
	const updateOptions = jest.fn();
	beforeEach( () => {
		jest.clearAllMocks();
		( useDispatch as jest.Mock ).mockImplementation( () => ( {
			hideTaskList,
			updateOptions,
		} ) );
		( useSelect as jest.Mock ).mockImplementation( () => ( {
			isResolving: false,
			taskLists: [
				{
					id: 'main',
					eventPrefix: 'main_tasklist_',
					isVisible: true,
					tasks: [ { id: 'main-task-1' }, { id: 'main-task-2' } ],
				},
				{ id: 'extended', isVisible: true, tasks: [] },
			],
		} ) );
	} );

	afterEach( () => {
		cleanup();
	} );

	it( 'should render if no current task and finished resolving', () => {
		const { queryByText } = render(
			<div>
				<TaskLists query={ {} } />
			</div>
		);
		waitFor( () => {
			expect( queryByText( 'task-list:main' ) ).toBeInTheDocument();
			expect( queryByText( 'task-list:extended' ) ).toBeInTheDocument();
			expect(
				queryByText( 'setup-task-list:setup' )
			).not.toBeInTheDocument();
		} );
	} );

	it( 'should render the task component if query has an existing task', () => {
		const { queryByText } = render(
			<div>
				<TaskLists query={ { task: 'main-task-1' } } />
			</div>
		);
		expect( queryByText( 'task:main-task-1' ) ).toBeInTheDocument();
	} );

	it( 'should not render anything if query has task, but task does not exist', () => {
		const { queryByText } = render(
			<div>
				<TaskLists query={ { task: 'main-task-random' } } />
			</div>
		);
		expect(
			queryByText( 'task:main-task-random' )
		).not.toBeInTheDocument();
		expect( queryByText( 'task-list:main' ) ).not.toBeInTheDocument();
	} );

	it( 'should render the placeholder if isResolving is true', () => {
		( useSelect as jest.Mock ).mockImplementation( () => ( {
			isResolving: true,
		} ) );
		const { queryByText } = render(
			<div>
				<TaskLists query={ {} } />
			</div>
		);
		expect( queryByText( 'task-placeholder' ) ).toBeInTheDocument();
	} );

	it( 'should show a menu item with Show things to do next if task list has isToggleable set to true', () => {
		( useSelect as jest.Mock ).mockImplementation( () => ( {
			isResolving: false,
			taskLists: [
				{
					id: 'main',
					eventPrefix: 'main_tasklist_',
					isVisible: true,
					isToggleable: true,
					isHidden: false,
					tasks: [ { id: 'main-task-1' }, { id: 'main-task-2' } ],
				},
			],
		} ) );
		const { queryByText } = render(
			<div>
				<TaskLists query={ {} } />
			</div>
		);
		expect( queryByText( 'Show things to do next' ) ).toBeInTheDocument();
	} );

	describe( 'toggle list', () => {
		it( 'should trigger hide track when clicking Show things to do next button', () => {
			( useSelect as jest.Mock ).mockImplementation( () => ( {
				isResolving: false,
				taskLists: [
					{
						id: 'main',
						eventPrefix: 'main_tasklist_',
						isVisible: true,
						isToggleable: true,
						isHidden: false,
						tasks: [ { id: 'main-task-1' }, { id: 'main-task-2' } ],
					},
				],
			} ) );
			const { getByText } = render(
				<div>
					<TaskLists query={ {} } />
				</div>
			);
			act( () => {
				userEvent.click( getByText( 'Show things to do next' ) );
			} );
			expect( recordEvent ).toHaveBeenCalledWith(
				'main_tasklist_hide',
				{}
			);
			expect( hideTaskList ).toHaveBeenCalledWith( 'main' );
		} );

		it( 'should trigger show track when toggling task list when isHidden was true', () => {
			( useSelect as jest.Mock ).mockImplementation( () => ( {
				isResolving: false,
				taskLists: [
					{
						id: 'main',
						eventPrefix: 'main_tasklist_',
						isVisible: true,
						isToggleable: true,
						isHidden: true,
						tasks: [ { id: 'main-task-1' }, { id: 'main-task-2' } ],
					},
				],
			} ) );
			const { getByText } = render(
				<div>
					<TaskLists query={ {} } />
				</div>
			);
			act( () => {
				userEvent.click( getByText( 'Show things to do next' ) );
			} );
			expect( recordEvent ).toHaveBeenCalledWith(
				'main_tasklist_show',
				{}
			);
			expect( hideTaskList ).toHaveBeenCalledWith( 'main' );
		} );
	} );
} );
