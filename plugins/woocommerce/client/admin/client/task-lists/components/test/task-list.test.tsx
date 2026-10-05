import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { act, fireEvent, render, waitFor } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';
import { TaskType } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { TaskList } from '../task-list';
import { TaskListItemProps } from '../task-list-item';
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
vi.mock( '../task-list-item', () => {
	const mock = {
		TaskListItem: ( props: TaskListItemProps ) => {
			return (
				<>
					<button onClick={ props.trackClick }>
						{ props.task.title }
					</button>
					{ props.showSkipAction && (
						<button
							disabled={ props.isSkipDisabled }
							onClick={ () =>
								void props.onTaskSkip?.( props.task )
							}
						>
							Skip { props.task.title }
						</button>
					) }
				</>
			);
		},
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../task-list-menu', () => {
	const mock = {
		TaskListMenu: vi
			.fn()
			.mockImplementation( () => <div>task_list_menu</div> ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/components', () => {
	const mock = {
		Badge: vi
			.fn()
			.mockImplementation( ( { count } ) => <div>Count:{ count }</div> ),
		H: vi
			.fn()
			.mockImplementation( ( { children } ) => <h2>{ children }</h2> ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/admin-layout', async () => {
	const mockContext = {
		layoutPath: [ 'home' ],
		layoutString: 'home',
		extendLayout: () => {},
		isDescendantOf: () => false,
	};
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...( await vi.importActual( '@woocommerce/admin-layout' ) ),
		useLayoutContext: vi.fn().mockReturnValue( mockContext ),
		useExtendLayout: vi.fn().mockReturnValue( mockContext ),
	} );
} );
vi.mock( '@wordpress/data', async () => {
	const originalModule = await vi.importActual( '@wordpress/data' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...originalModule,
		useDispatch: vi.fn(),
	} );
} );
const mockDispatch = {
	createNotice: vi.fn(),
	dismissTask: vi.fn(),
	undoDismissTask: vi.fn(),
};
( useDispatch as Mock ).mockReturnValue( mockDispatch );
const tasks: {
	[ key: string ]: TaskType[];
} = {
	setup: [
		{
			id: 'optional',
			title: 'This task is optional',
			badge: '',
			isComplete: false,
			isVisible: true,
			time: '1 minute',
			isDismissable: true,
			content: 'This is the optional task content',
			isDismissed: false,
			isSnoozed: false,
			isSnoozeable: false,
			isDisabled: false,
			snoozedUntil: 0,
			isVisited: false,
			parentId: '',
			additionalInfo: '',
			canView: true,
			isActioned: false,
			eventPrefix: '',
			level: 0,
			recordViewEvent: false,
		},
		{
			id: 'required',
			title: 'This task is required',
			badge: '',
			isComplete: false,
			isVisible: true,
			time: '1 minute',
			isDismissable: false,
			actionLabel: 'This is the action label',
			content: 'This is the required task content',
			isDismissed: false,
			isSnoozed: false,
			isSnoozeable: false,
			isDisabled: false,
			snoozedUntil: 0,
			isVisited: false,
			parentId: '',
			additionalInfo: '',
			canView: true,
			isActioned: false,
			eventPrefix: '',
			level: 0,
			recordViewEvent: false,
		},
		{
			id: 'completed',
			title: 'This task is completed',
			badge: '',
			isComplete: true,
			isVisible: true,
			time: '1 minute',
			isDismissable: true,
			isDismissed: false,
			isSnoozed: false,
			isSnoozeable: false,
			isDisabled: false,
			snoozedUntil: 0,
			isVisited: false,
			content: '',
			parentId: '',
			additionalInfo: '',
			canView: true,
			isActioned: false,
			eventPrefix: '',
			level: 0,
			recordViewEvent: false,
		},
	],
	extension: [
		{
			id: 'extension',
			title: 'This task is an extension',
			badge: '',
			isComplete: false,
			isVisible: true,
			time: '1 minute',
			isDismissable: true,
			content: 'This is the extension task content',
			isDismissed: false,
			isSnoozed: false,
			isSnoozeable: false,
			isDisabled: false,
			snoozedUntil: 0,
			isVisited: false,
			parentId: '',
			additionalInfo: '',
			canView: true,
			isActioned: false,
			eventPrefix: '',
			level: 0,
			recordViewEvent: false,
		},
	],
};
describe( 'TaskList', () => {
	beforeEach( () => {
		vi.clearAllMocks();
		mockDispatch.dismissTask.mockResolvedValue( undefined );
		mockDispatch.undoDismissTask.mockResolvedValue( undefined );
	} );
	it( 'should trigger tasklist_view event on initial render for setup task list', () => {
		render(
			<TaskList
				id="setup"
				eventPrefix="tasklist_"
				tasks={ [] }
				title="List title"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith( 'tasklist_view', {
			context: 'home',
			number_tasks: 0,
			store_connected: null,
		} );
	} );
	it( 'should trigger {id}_tasklist_view event on initial render for setup task list if id is not setup', () => {
		render(
			<TaskList
				id="extended"
				eventPrefix="extended_tasklist_"
				tasks={ [] }
				title="List title"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith( 'extended_tasklist_view', {
			context: 'home',
			number_tasks: 0,
			store_connected: null,
		} );
	} );
	it( 'should render the task title and incomplete task number', () => {
		const { queryByText } = render(
			<TaskList
				id="setup"
				eventPrefix="tasklist_"
				tasks={ [ ...tasks.setup ] }
				title="List title"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		const incompleteCount = tasks.setup.filter(
			( task ) => ! task.isComplete
		).length;
		expect( queryByText( 'List title' ) ).toBeInTheDocument();
		expect( queryByText( 'Count:' + incompleteCount ) ).toBeInTheDocument();
	} );
	it( 'should render all tasks', () => {
		const { queryByText } = render(
			<TaskList
				id="setup"
				eventPrefix="tasklist_"
				tasks={ [ ...tasks.setup ] }
				title="List title"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		for ( const task of tasks.setup ) {
			expect( queryByText( task.title ) ).toBeInTheDocument();
		}
	} );
	it( 'should not display isDismissed tasks', () => {
		const dismissedTask = [
			{
				...tasks.setup[ 0 ],
				isDismissed: true,
			},
		];
		const { queryByText } = render(
			<TaskList
				id="setup"
				eventPrefix="tasklist_"
				tasks={ dismissedTask }
				title="List title"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		expect(
			queryByText( dismissedTask[ 0 ].title )
		).not.toBeInTheDocument();
	} );
	it( 'should render an empty state for extended task list when all tasks are dismissed', () => {
		const dismissedTask = [
			{
				...tasks.extension[ 0 ],
				isDismissed: true,
			},
		];
		const { queryByText } = render(
			<TaskList
				id="extended"
				eventPrefix="extended_tasklist_"
				tasks={ dismissedTask }
				title="Things to do next"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		expect( queryByText( "You're all caught up" ) ).toBeInTheDocument();
		expect(
			queryByText(
				"You've completed all the things to do next. Watch this space for more recommendations."
			)
		).toBeInTheDocument();
	} );
	it( 'should restore a skipped task when dismissing fails', async () => {
		mockDispatch.dismissTask.mockRejectedValueOnce(
			new Error( 'Unable to dismiss task' )
		);
		const { getByRole, queryByText } = render(
			<TaskList
				id="extended"
				eventPrefix="extended_tasklist_"
				tasks={ [ ...tasks.extension ] }
				title="Things to do next"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		fireEvent.click(
			getByRole( 'button', {
				name: `Skip ${ tasks.extension[ 0 ].title }`,
			} )
		);
		await waitFor( () => {
			expect(
				queryByText( tasks.extension[ 0 ].title )
			).toBeInTheDocument();
		} );
		expect( mockDispatch.createNotice ).toHaveBeenCalledWith(
			'error',
			'There was a problem skipping this task. Please try again.'
		);
	} );
	it( 'should keep a skipped task removed when undo fails', async () => {
		mockDispatch.undoDismissTask.mockRejectedValueOnce(
			new Error( 'Unable to restore task' )
		);
		const { getByRole, queryByText } = render(
			<TaskList
				id="extended"
				eventPrefix="extended_tasklist_"
				tasks={ [ ...tasks.extension ] }
				title="Things to do next"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		fireEvent.click(
			getByRole( 'button', {
				name: `Skip ${ tasks.extension[ 0 ].title }`,
			} )
		);
		await waitFor( () => {
			expect(
				getByRole( 'button', {
					name: 'Undo',
				} )
			).toBeEnabled();
		} );
		fireEvent.click(
			getByRole( 'button', {
				name: 'Undo',
			} )
		);
		await waitFor( () => {
			expect( mockDispatch.createNotice ).toHaveBeenCalledWith(
				'error',
				'There was a problem restoring this task. Please try again.'
			);
		} );
		expect(
			getByRole( 'button', {
				name: 'Undo',
			} )
		).toBeEnabled();
		expect(
			queryByText( tasks.extension[ 0 ].title )
		).not.toBeInTheDocument();
	} );
	it( 'should pass the task list id when skipping and restoring a task', async () => {
		const { getByRole } = render(
			<TaskList
				id="extended"
				eventPrefix="extended_tasklist_"
				tasks={ [ ...tasks.extension ] }
				title="Things to do next"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		fireEvent.click(
			getByRole( 'button', {
				name: `Skip ${ tasks.extension[ 0 ].title }`,
			} )
		);
		await waitFor( () => {
			expect( mockDispatch.dismissTask ).toHaveBeenCalledWith(
				tasks.extension[ 0 ].id,
				'extended'
			);
		} );
		fireEvent.click(
			getByRole( 'button', {
				name: 'Undo',
			} )
		);
		await waitFor( () => {
			expect( mockDispatch.undoDismissTask ).toHaveBeenCalledWith(
				tasks.extension[ 0 ].id,
				'extended'
			);
		} );
	} );
	it( 'should serialize Skip and Undo requests for the same task', async () => {
		let resolveDismissTask: () => void;
		let resolveUndoDismissTask: () => void;
		const dismissTaskRequest = new Promise< void >( ( resolve ) => {
			resolveDismissTask = resolve;
		} );
		const undoDismissTaskRequest = new Promise< void >( ( resolve ) => {
			resolveUndoDismissTask = resolve;
		} );
		mockDispatch.dismissTask.mockReturnValueOnce( dismissTaskRequest );
		mockDispatch.undoDismissTask.mockReturnValueOnce(
			undoDismissTaskRequest
		);
		const { getByRole } = render(
			<TaskList
				id="extended"
				eventPrefix="extended_tasklist_"
				tasks={ [ ...tasks.extension ] }
				title="Things to do next"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		fireEvent.click(
			getByRole( 'button', {
				name: `Skip ${ tasks.extension[ 0 ].title }`,
			} )
		);
		expect( mockDispatch.dismissTask ).toHaveBeenCalledTimes( 1 );
		const undoButton = getByRole( 'button', {
			name: 'Undo',
		} );
		expect( undoButton ).toBeDisabled();
		fireEvent.click( undoButton );
		expect( mockDispatch.undoDismissTask ).not.toHaveBeenCalled();
		await act( async () => {
			resolveDismissTask!();
			await dismissTaskRequest;
		} );
		await waitFor( () => {
			expect(
				getByRole( 'button', {
					name: 'Undo',
				} )
			).toBeEnabled();
		} );
		fireEvent.click(
			getByRole( 'button', {
				name: 'Undo',
			} )
		);
		expect( mockDispatch.undoDismissTask ).toHaveBeenCalledTimes( 1 );
		const skipButton = getByRole( 'button', {
			name: `Skip ${ tasks.extension[ 0 ].title }`,
		} );
		expect( skipButton ).toBeDisabled();
		fireEvent.click( skipButton );
		expect( mockDispatch.dismissTask ).toHaveBeenCalledTimes( 1 );
		await act( async () => {
			resolveUndoDismissTask!();
			await undoDismissTaskRequest;
		} );
		await waitFor( () => {
			expect(
				getByRole( 'button', {
					name: `Skip ${ tasks.extension[ 0 ].title }`,
				} )
			).toBeEnabled();
		} );
	} );
	it( 'should render an empty state for extended task list when all tasks are completed', () => {
		const completedTask = [
			{
				...tasks.extension[ 0 ],
				isComplete: true,
			},
		];
		const { queryByText } = render(
			<TaskList
				id="extended"
				eventPrefix="extended_tasklist_"
				tasks={ completedTask }
				title="Things to do next"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ true }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		expect(
			queryByText( completedTask[ 0 ].title )
		).not.toBeInTheDocument();
		expect( queryByText( "You're all caught up" ) ).toBeInTheDocument();
	} );
	it( 'should treat any task list ID starting with extended as an extended list', () => {
		const completedTask = [
			{
				...tasks.extension[ 0 ],
				isComplete: true,
			},
		];
		const { queryByText } = render(
			<TaskList
				id="extended_foo"
				eventPrefix="extended_foo_tasklist_"
				tasks={ completedTask }
				title="Things to do next"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ true }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		expect(
			queryByText( completedTask[ 0 ].title )
		).not.toBeInTheDocument();
		expect( queryByText( "You're all caught up" ) ).toBeInTheDocument();
	} );
	it( 'should offer the skip action on a task list ID starting with extended', () => {
		const { getByRole } = render(
			<TaskList
				id="extended_foo"
				eventPrefix="extended_foo_tasklist_"
				tasks={ [ ...tasks.extension ] }
				title="Things to do next"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		expect(
			getByRole( 'button', {
				name: `Skip ${ tasks.extension[ 0 ].title }`,
			} )
		).toBeInTheDocument();
	} );
	it( 'should render the skipped task placeholder as a list item', async () => {
		mockDispatch.dismissTask.mockResolvedValueOnce( undefined );
		const { getByRole, getAllByRole } = render(
			<TaskList
				id="extended"
				eventPrefix="extended_tasklist_"
				tasks={ [ ...tasks.extension ] }
				title="Things to do next"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		await act( async () => {
			fireEvent.click(
				getByRole( 'button', {
					name: `Skip ${ tasks.extension[ 0 ].title }`,
				} )
			);
		} );
		await waitFor( () => {
			expect(
				getAllByRole( 'listitem' ).some( ( item ) =>
					item.classList.contains(
						'woocommerce-task-list__item--dismissed'
					)
				)
			).toBe( true );
		} );
	} );
	it( 'should fire extended tasklist task clicked event when a task is clicked', () => {
		const { getByRole } = render(
			<TaskList
				id="extended"
				eventPrefix="extended_tasklist_"
				tasks={ [ ...tasks.extension ] }
				title="List title"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		( recordEvent as Mock ).mockClear();
		fireEvent.click(
			getByRole( 'button', {
				name: tasks.extension[ 0 ].title,
			} )
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'extended_tasklist_task_click',
			{
				task_name: 'extension',
				task_complete: false,
				task_dismissed: false,
				context: 'home',
			}
		);
	} );
	it( 'should include task_complete when a completed task is clicked', () => {
		const { getByRole } = render(
			<TaskList
				id="setup"
				eventPrefix="extended_tasklist_"
				tasks={ [ tasks.setup[ 2 ] ] }
				title="List title"
				query={ {} }
				isVisible={ true }
				isHidden={ false }
				isComplete={ false }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
			/>
		);
		( recordEvent as Mock ).mockClear();
		fireEvent.click(
			getByRole( 'button', {
				name: tasks.setup[ 2 ].title,
			} )
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'extended_tasklist_task_click',
			expect.objectContaining( {
				task_name: 'completed',
				task_complete: true,
			} )
		);
	} );
} );
