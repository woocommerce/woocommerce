import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';
import { TaskType } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { SetupTaskList } from '../setup-task-list';
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
vi.mock( '@woocommerce/experimental', () => {
	const mock = {
		TaskItem: ( props: { title: string } ) => <div>{ props.title }</div>,
		useSlot: vi.fn(),
		List: vi.fn().mockImplementation( ( { children } ) => children ),
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
		Card: vi.fn().mockImplementation( ( { children } ) => children ),
		Badge: vi
			.fn()
			.mockImplementation( ( { count } ) => <div>Count:{ count }</div> ),
		EllipsisMenu: vi
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
vi.mock( '../components/task-headers', () => {
	const mock = {
		taskHeaders: {
			optional: () => <div>optional_header</div>,
			required: () => <div>required_header</div>,
			completed: () => <div>completed_header</div>,
		},
		DefaultTaskHeader: () => <div>default_header</div>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/data', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/data' ) ),
		useUserPreferences: vi.fn().mockReturnValue( {
			updateUserPreferences: vi.fn(),
		} ),
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
			recordViewEvent: true,
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
	} );
	it( 'should trigger tasklist_view event on initial render for setup task list', () => {
		render(
			<SetupTaskList
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
			context: 'home',
			number_tasks: 0,
			store_connected: null,
		} );
	} );
	it( 'should trigger tasklist_view event on initial render for setup task list with eventPrefix if eventName is undefined', () => {
		render(
			<SetupTaskList
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
			context: 'home',
			number_tasks: 0,
			store_connected: null,
		} );
	} );
	it( 'should trigger {id}_tasklist_view event on initial render for setup task list if id is not setup', () => {
		render(
			<SetupTaskList
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
			context: 'home',
			number_tasks: 0,
			store_connected: null,
		} );
	} );
	it( 'should render the task header of the first uncompleted task', () => {
		const { queryByText } = render(
			<SetupTaskList
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
			<SetupTaskList
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
		const dismissedTask = [
			{
				...tasks.setup[ 0 ],
				isDismissed: true,
			},
		];
		const { queryByText } = render(
			<SetupTaskList
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
	it( 'should fall back to the DefaultTaskHeader for a task that has an image but no dedicated header or slot fill', () => {
		const thirdPartyTask = {
			...tasks.extension[ 0 ],
			imageUrl: 'https://example.com/custom-illustration.png',
			imageAlt: 'Custom illustration',
		};
		const { queryByText } = render(
			<SetupTaskList
				id="extended"
				tasks={ [ thirdPartyTask ] }
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
		expect( queryByText( 'default_header' ) ).toBeInTheDocument();
	} );
	it( 'should not render any task header for a task without an image, dedicated header, or slot fill', () => {
		const { queryByText } = render(
			<SetupTaskList
				id="extended"
				tasks={ [ ...tasks.extension ] }
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
		expect( queryByText( 'default_header' ) ).not.toBeInTheDocument();
	} );
	it( 'should prefer a dedicated task header over the DefaultTaskHeader even when the task has an image', () => {
		const taskWithImage = {
			...tasks.setup[ 0 ],
			imageUrl: 'https://example.com/custom-illustration.png',
			imageAlt: 'Custom illustration',
		};
		const { queryByText } = render(
			<SetupTaskList
				id="extended"
				tasks={ [ taskWithImage ] }
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
		expect( queryByText( 'default_header' ) ).not.toBeInTheDocument();
	} );
} );
