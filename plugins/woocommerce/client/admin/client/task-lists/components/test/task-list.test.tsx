/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';
import { TaskType } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { TaskList } from '../task-list';
import { TaskListItemProps } from '../task-list-item';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );
jest.mock( '../task-list-item', () => ( {
	TaskListItem: ( props: TaskListItemProps ) => (
		<div>{ props.task.title }</div>
	),
} ) );
jest.mock( '../task-list-menu', () => ( {
	TaskListMenu: jest
		.fn()
		.mockImplementation( () => <div>task_list_menu</div> ),
} ) );
jest.mock( '@woocommerce/components', () => ( {
	Badge: jest
		.fn()
		.mockImplementation( ( { count } ) => <div>Count:{ count }</div> ),
} ) );
jest.mock( '@woocommerce/admin-layout', () => {
	const mockContext = {
		layoutPath: [ 'home' ],
		layoutString: 'home',
		extendLayout: () => {},
		isDescendantOf: () => false,
	};
	return {
		...jest.requireActual( '@woocommerce/admin-layout' ),
		useLayoutContext: jest.fn().mockReturnValue( mockContext ),
		useExtendLayout: jest.fn().mockReturnValue( mockContext ),
	};
} );

const tasks: { [ key: string ]: TaskType[] } = {
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
		jest.clearAllMocks();
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
		const dismissedTask = [ { ...tasks.setup[ 0 ], isDismissed: true } ];
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
} );
