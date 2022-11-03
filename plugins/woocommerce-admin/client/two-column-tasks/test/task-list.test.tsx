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

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );
jest.mock( '@woocommerce/experimental', () => ( {
	TaskItem: ( props: { title: string } ) => <div>{ props.title }</div>,
	useSlot: jest.fn(),
	List: jest.fn().mockImplementation( ( { children } ) => children ),
} ) );
jest.mock( '@woocommerce/components', () => ( {
	Card: jest.fn().mockImplementation( ( { children } ) => children ),
	Badge: jest
		.fn()
		.mockImplementation( ( { count } ) => <div>Count:{ count }</div> ),
	EllipsisMenu: jest
		.fn()
		.mockImplementation( () => <div>task_list_menu</div> ),
} ) );
jest.mock( '../task-headers', () => ( {
	optional: () => <div>optional_header</div>,
	required: () => <div>required_header</div>,
	completed: () => <div>completed_header</div>,
} ) );

const tasks: { [ key: string ]: TaskType[] } = {
	setup: [
		{
			id: 'optional',
			title: 'This task is optional',
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
		},
		{
			id: 'required',
			title: 'This task is required',
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
		},
		{
			id: 'completed',
			title: 'This task is completed',
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
		},
	],
	extension: [
		{
			id: 'extension',
			title: 'This task is an extension',
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
				eventName="tasklist"
				tasks={ [] }
				title="List title"
				query={ {} }
				isComplete={ false }
				isHidden={ false }
				eventPrefix={ '' }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
				isVisible={ true }
			/>
		);
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith( 'tasklist_view', {
			context: 'root',
			number_tasks: 0,
			store_connected: null,
		} );
	} );

	it( 'should trigger tasklist_view event on initial render for setup task list with eventPrefix if eventName is undefined', () => {
		render(
			<TaskList
				id="setup"
				tasks={ [] }
				title="List title"
				query={ {} }
				isComplete={ false }
				isHidden={ false }
				eventPrefix={ 'tasklist_' }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
				isVisible={ true }
			/>
		);
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith( 'tasklist_view', {
			context: 'root',
			number_tasks: 0,
			store_connected: null,
		} );
	} );

	it( 'should trigger {id}_tasklist_view event on initial render for setup task list if id is not setup', () => {
		render(
			<TaskList
				id="extended"
				eventName="extended_tasklist"
				tasks={ [] }
				title="List title"
				query={ {} }
				isComplete={ false }
				isHidden={ false }
				eventPrefix={ '' }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
				isVisible={ true }
			/>
		);
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith( 'extended_tasklist_view', {
			context: 'root',
			number_tasks: 0,
			store_connected: null,
		} );
	} );

	it( 'should render the task header of the first uncompleted task', () => {
		const { queryByText } = render(
			<TaskList
				id="extended"
				tasks={ [ ...tasks.setup ] }
				title="List title"
				query={ {} }
				isComplete={ false }
				isHidden={ false }
				eventPrefix={ '' }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
				isVisible={ true }
			/>
		);
		expect( queryByText( 'optional_header' ) ).toBeInTheDocument();
	} );

	it( 'should render all tasks', () => {
		const { queryByText } = render(
			<TaskList
				id="extended"
				tasks={ [ ...tasks.setup ] }
				title="List title"
				query={ {} }
				isComplete={ false }
				isHidden={ false }
				eventPrefix={ '' }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
				isVisible={ true }
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
				id="extended"
				tasks={ dismissedTask }
				title="List title"
				query={ {} }
				isComplete={ false }
				isHidden={ false }
				eventPrefix={ '' }
				displayProgressHeader={ false }
				keepCompletedTaskList="no"
				isVisible={ true }
			/>
		);
		expect(
			queryByText( dismissedTask[ 0 ].title )
		).not.toBeInTheDocument();
	} );
} );
