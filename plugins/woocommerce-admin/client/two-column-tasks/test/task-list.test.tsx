/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { TaskList } from '../task-list';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );
jest.mock( '@woocommerce/experimental', () => ( {
	TaskItem: ( props ) => <div>{ props.title }</div>,
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

const tasks = {
	setup: [
		{
			id: 'optional',
			title: 'This task is optional',
			isComplete: false,
			visible: true,
			time: '1 minute',
			isDismissable: true,
			type: 'setup',
			action: 'CTA (optional)',
			content: 'This is the optional task content',
			additionalInfo: 'This is the task additional info',
			expandable: true,
			expanded: true,
		},
		{
			id: 'required',
			title: 'This task is required',
			container: null,
			isComplete: false,
			visible: true,
			time: '1 minute',
			isDismissable: false,
			type: 'setup',
			action: 'CTA (required)',
			actionLabel: 'This is the action label',
			content: 'This is the required task content',
			expandable: false,
		},
		{
			id: 'completed',
			title: 'This task is completed',
			container: null,
			isComplete: true,
			visible: true,
			time: '1 minute',
			isDismissable: true,
			type: 'setup',
		},
	],
	extension: [
		{
			id: 'extension',
			title: 'This task is an extension',
			container: null,
			isComplete: false,
			visible: true,
			time: '1 minute',
			isDismissable: true,
			type: 'extension',
			action: 'CTA (extension)',
			content: 'This is the extension task content',
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
			/>
		);
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith( 'tasklist_view', {
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
			/>
		);
		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith( 'extended_tasklist_view', {
			number_tasks: 0,
			store_connected: null,
		} );
	} );

	it( 'should render the task header of the first uncompleted task', () => {
		const { queryByText } = render(
			<TaskList
				tasks={ [ ...tasks.setup ] }
				title="List title"
				query={ {} }
			/>
		);
		const incompleteCount = tasks.setup.filter(
			( task ) => ! task.isComplete
		).length;
		expect( queryByText( 'optional_header' ) ).toBeInTheDocument();
	} );

	it( 'should render all tasks', () => {
		const { queryByText } = render(
			<TaskList
				tasks={ [ ...tasks.setup ] }
				title="List title"
				query={ {} }
			/>
		);
		for ( const task of tasks.setup ) {
			expect( queryByText( task.title ) ).toBeInTheDocument();
		}
	} );

	it( 'should not display isDismissed tasks', () => {
		const dismissedTask = [ { ...tasks.setup[ 0 ], isDismissed: true } ];
		const { queryByText } = render(
			<TaskList tasks={ dismissedTask } title="List title" query={ {} } />
		);
		expect(
			queryByText( dismissedTask[ 0 ].title )
		).not.toBeInTheDocument();
	} );

	it( 'should not display isSnoozed tasks', () => {
		const dismissedTask = [
			{
				...tasks.setup[ 0 ],
				isSnoozed: true,
				snoozedUntil: Date.now() + 10000,
			},
		];
		const { queryByText } = render(
			<TaskList tasks={ dismissedTask } title="List title" query={ {} } />
		);
		expect(
			queryByText( dismissedTask[ 0 ].title )
		).not.toBeInTheDocument();
	} );

	it( 'should display a snoozed task if snoozedUntil passed the current timestamp', () => {
		const dismissedTask = [
			{
				...tasks.setup[ 0 ],
				isSnoozed: true,
				snoozedUntil: Date.now() - 1000,
			},
		];
		const { queryByText } = render(
			<TaskList tasks={ dismissedTask } title="List title" query={ {} } />
		);
		expect( queryByText( dismissedTask[ 0 ].title ) ).toBeInTheDocument();
	} );
} );
