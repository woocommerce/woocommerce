import {
	afterEach,
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type Mock,
} from 'vitest';

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
vi.mock( '@wordpress/data', async () => {
	// Require the original module to not be mocked...
	const originalModule = await vi.importActual( '@wordpress/data' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		__esModule: true,
		// Use it when dealing with esModules
		...originalModule,
		useDispatch: vi.fn().mockReturnValue( {} ),
		useSelect: vi.fn().mockReturnValue( {} ),
	} );
} );
vi.mock( '@woocommerce/explat' );
vi.mock( '@woocommerce/tracks' );
vi.mock( '../components/task-list', () => {
	const mock = {
		TaskList: ( { id }: TaskListProps ) => <div>task-list:{ id }</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../setup-task-list', () => {
	const mock = {
		SetupTaskList: ( { id }: SetupTaskListProps ) => (
			<div>setup-task-list:{ id }</div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../components/task', () => {
	const mock = {
		Task: ( { query }: TaskProps ) => <div>task:{ query.task }</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../components/placeholder', () => {
	const mock = {
		TasksPlaceholder: () => <div>task-placeholder</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/activity-panel/display-options', () => {
	const mock = {
		DisplayOption: ( { children }: { children: React.ReactNode } ) => (
			<div>{ children } </div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'Task', () => {
	const hideTaskList = vi.fn();
	const updateOptions = vi.fn();
	beforeEach( () => {
		vi.clearAllMocks();
		( useDispatch as Mock ).mockImplementation( () => ( {
			hideTaskList,
			updateOptions,
		} ) );
		( useSelect as Mock ).mockImplementation( () => ( {
			isResolving: false,
			taskLists: [
				{
					id: 'main',
					eventPrefix: 'main_tasklist_',
					isVisible: true,
					tasks: [
						{
							id: 'main-task-1',
						},
						{
							id: 'main-task-2',
						},
					],
				},
				{
					id: 'extended',
					isVisible: true,
					tasks: [],
				},
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
				<TaskLists
					query={ {
						task: 'main-task-1',
					} }
				/>
			</div>
		);
		expect( queryByText( 'task:main-task-1' ) ).toBeInTheDocument();
	} );
	it( 'should not render anything if query has task, but task does not exist', () => {
		const { queryByText } = render(
			<div>
				<TaskLists
					query={ {
						task: 'main-task-random',
					} }
				/>
			</div>
		);
		expect(
			queryByText( 'task:main-task-random' )
		).not.toBeInTheDocument();
		expect( queryByText( 'task-list:main' ) ).not.toBeInTheDocument();
	} );
	it( 'should render the placeholder if isResolving is true', () => {
		( useSelect as Mock ).mockImplementation( () => ( {
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
		( useSelect as Mock ).mockImplementation( () => ( {
			isResolving: false,
			taskLists: [
				{
					id: 'main',
					eventPrefix: 'main_tasklist_',
					isVisible: true,
					isToggleable: true,
					isHidden: false,
					tasks: [
						{
							id: 'main-task-1',
						},
						{
							id: 'main-task-2',
						},
					],
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
			( useSelect as Mock ).mockImplementation( () => ( {
				isResolving: false,
				taskLists: [
					{
						id: 'main',
						eventPrefix: 'main_tasklist_',
						isVisible: true,
						isToggleable: true,
						isHidden: false,
						tasks: [
							{
								id: 'main-task-1',
							},
							{
								id: 'main-task-2',
							},
						],
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
			( useSelect as Mock ).mockImplementation( () => ( {
				isResolving: false,
				taskLists: [
					{
						id: 'main',
						eventPrefix: 'main_tasklist_',
						isVisible: true,
						isToggleable: true,
						isHidden: true,
						tasks: [
							{
								id: 'main-task-1',
							},
							{
								id: 'main-task-2',
							},
						],
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
