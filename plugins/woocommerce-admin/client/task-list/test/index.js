/**
 * External dependencies
 */
import {
	act,
	render,
	findByText,
	fireEvent,
	queryByTestId,
	waitFor,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';
import { SlotFillProvider } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useExperiment } from '@woocommerce/explat';

/**
 * Internal dependencies
 */
import TaskDashboard from '../';
import { TaskList } from '../task-list';
import { getAllTasks } from '../tasks';
import { DisplayOption } from '../../header/activity-panel/display-options';

jest.mock( '@wordpress/api-fetch' );
jest.mock( '../tasks', () => ( {
	...jest.requireActual( '../tasks' ),
	recordTaskViewEvent: jest.fn(),
	getAllTasks: jest.fn(),
} ) );
jest.mock( '../../dashboard/components/cart-modal', () => null );
jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
jest.mock( '@woocommerce/data', () => ( {} ) );
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn().mockReturnValue( {
		profileItems: {},
	} ),
	useDispatch: jest.fn(),
} ) );
jest.mock( '@woocommerce/explat', () => ( {
	useExperiment: jest.fn().mockReturnValue( [ false, {} ] ),
} ) );

const TASK_LIST_HEADING = 'Get ready to start selling';
const EXTENDED_TASK_LIST_HEADING = 'Things to do next';

describe( 'TaskDashboard and TaskList', () => {
	const updateOptions = jest.fn();
	const createNotice = jest.fn();
	const originalClientHeight = Object.getOwnPropertyDescriptor(
		global.HTMLElement.prototype,
		'clientHeight'
	);

	beforeEach( () => {
		useDispatch.mockImplementation( () => ( {
			updateOptions,
			createNotice,
			installAndActivatePlugins: jest.fn(),
		} ) );
		Object.defineProperty( global.HTMLElement.prototype, 'clientHeight', {
			configurable: true,
			value: 100,
		} );
	} );
	afterEach( () => {
		jest.clearAllMocks();
		if ( originalClientHeight ) {
			Object.defineProperty(
				global.HTMLElement.prototype,
				'offsetHeight',
				originalClientHeight
			);
		}
	} );
	const MockTask = () => {
		return <div>mock task</div>;
	};
	const tasks = {
		setup: [
			{
				key: 'optional',
				title: 'This task is optional',
				container: <MockTask />,
				completed: false,
				visible: true,
				time: '1 minute',
				isDismissable: true,
				type: 'setup',
				action: 'CTA (optional)',
				content: 'This is the optional task content',
			},
			{
				key: 'required',
				title: 'This task is required',
				container: null,
				completed: false,
				visible: true,
				time: '1 minute',
				isDismissable: false,
				type: 'setup',
				action: 'CTA (required)',
				content: 'This is the required task content',
			},
			{
				key: 'completed',
				title: 'This task is completed',
				container: null,
				completed: true,
				visible: true,
				time: '1 minute',
				isDismissable: true,
				type: 'setup',
			},
		],
		extension: [
			{
				key: 'extension',
				title: 'This task is an extension',
				container: null,
				completed: false,
				visible: true,
				time: '1 minute',
				isDismissable: true,
				type: 'extension',
				action: 'CTA (extension)',
				content: 'This is the extension task content',
			},
		],
	};
	const shorterTasksList = [
		{
			key: 'completed-1',
			title: 'This task is completed',
			container: null,
			completed: true,
			visible: true,
			time: '1 minute',
			isDismissable: true,
			type: 'setup',
		},
		{
			key: 'completed-2',
			title: 'This task is completed',
			container: null,
			completed: true,
			visible: true,
			time: '1 minute',
			isDismissable: true,
			type: 'setup',
		},
	];
	const notVisibleTask = {
		key: 'not-visible',
		title: 'This task is not visible',
		container: <MockTask />,
		completed: false,
		visible: false,
		time: '2 minute',
		isDismissable: true,
	};
	const completedExtensionTask = {
		key: 'extension2',
		title: 'This completed task is an extension',
		container: null,
		completed: true,
		visible: true,
		time: '2 minutes',
		isDismissable: true,
		type: 'extension',
	};
	const defaultTaskListProps = {
		name: 'task_list',
		eventName: 'tasklist',
		query: {},
		dismissedTasks: [],
		remindMeLaterTasks: {},
		trackedCompletedTasks: shorterTasksList,
		tasks: shorterTasksList,
	};

	it( 'renders the "Finish setup" and "Things to do next" tasks lists', async () => {
		apiFetch.mockResolvedValue( {} );
		getAllTasks.mockReturnValue( tasks );
		const { container } = render( <TaskDashboard query={ {} } /> );

		// Wait for the setup task list to render.
		expect(
			await findByText( container, TASK_LIST_HEADING )
		).toBeDefined();

		// Wait for the extension task list to render.
		expect(
			await findByText( container, EXTENDED_TASK_LIST_HEADING )
		).toBeDefined();
	} );

	it( 'should show TaskList placeholder when loading', () => {
		useSelect.mockImplementation( () => ( {
			isResolving: true,
		} ) );
		getAllTasks.mockReturnValue( tasks );

		const { container } = render(
			<TaskDashboard
				requestingTaskList
				bothTaskListsHidden={ false }
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		const placeholder = container.querySelector(
			'.woocommerce-task-card.is-loading'
		);
		expect( placeholder ).toBeInTheDocument();
	} );

	it( 'renders a dismiss button for tasks that are optional and incomplete', async () => {
		apiFetch.mockResolvedValue( {} );
		getAllTasks.mockReturnValue( tasks );
		const { container } = render( <TaskDashboard query={ {} } /> );

		// The `optional` task has a dismiss button.
		expect(
			queryByTestId( container, 'optional-dismiss-button' )
		).toBeDefined();

		// The `required` task does not have a dismiss button.
		expect(
			queryByTestId( container, 'required-dismiss-button' )
		).toBeNull();

		// The `completed` task does not have a dismiss button.
		expect(
			queryByTestId( container, 'completed-dismiss-button' )
		).toBeNull();
	} );

	it( 'renders the selected task', async () => {
		apiFetch.mockResolvedValue( {} );
		getAllTasks.mockReturnValue( tasks );
		const { container } = render(
			<TaskDashboard query={ { task: 'optional' } } />
		);

		// Wait for the task to render.
		expect( await findByText( container, 'mock task' ) ).toBeDefined();
	} );

	it( 'renders only the extended task list', () => {
		useSelect.mockImplementation( () => ( {
			dismissedTasks: [],
			isSetupTaskListHidden: true,
			profileItems: {},
		} ) );
		apiFetch.mockResolvedValue( {} );
		getAllTasks.mockReturnValue( tasks );
		const { queryByText } = render( <TaskDashboard query={ {} } /> );

		expect( queryByText( TASK_LIST_HEADING ) ).toBeNull();

		expect( queryByText( EXTENDED_TASK_LIST_HEADING ) ).not.toBeNull();
	} );

	it( 'renders only the extended task list with expansion', () => {
		useSelect.mockImplementation( () => ( {
			dismissedTasks: [],
			isSetupTaskListHidden: true,
			profileItems: {},
		} ) );
		apiFetch.mockResolvedValue( {} );
		getAllTasks.mockReturnValue( {
			setup: [],
			extension: [ ...tasks.setup, ...tasks.extension ],
		} );
		const { queryByText } = render( <TaskDashboard query={ {} } /> );

		expect( queryByText( TASK_LIST_HEADING ) ).toBeNull();

		expect( queryByText( EXTENDED_TASK_LIST_HEADING ) ).not.toBeNull();
		const taskLength = tasks.setup.length + tasks.extension.length;
		expect(
			queryByText( `Show ${ taskLength - 2 } more tasks.` )
		).toBeInTheDocument();
	} );

	it( 'invokes onComplete callback when supplied', () => {
		apiFetch.mockResolvedValue( {} );
		const onComplete = jest.fn();
		act( () => {
			render(
				<TaskList
					{ ...defaultTaskListProps }
					tasks={ shorterTasksList }
					onComplete={ onComplete }
				/>
			);
		} );

		expect( onComplete ).toHaveBeenCalled();
	} );

	it( 'invokes onHide callback when supplied', () => {
		apiFetch.mockResolvedValue( {} );
		const onHide = jest.fn();
		const { getByRole } = render(
			<TaskList { ...defaultTaskListProps } onHide={ onHide } />
		);

		expect( onHide ).not.toHaveBeenCalled();

		userEvent.click( getByRole( 'button', { name: 'Task List Options' } ) );
		userEvent.click( getByRole( 'button', { name: 'Hide this' } ) );

		expect( onHide ).toHaveBeenCalled();
	} );

	it( 'sets homescreen layout default when dismissed', async () => {
		useSelect.mockImplementation( () => ( {
			dismissedTasks: [],
			isSetupTaskListHidden: false,
			isExtendedTaskListHidden: true,
			profileItems: {},
		} ) );
		apiFetch.mockResolvedValue( {} );
		getAllTasks.mockReturnValue( tasks );
		const { container, getByRole } = render(
			<TaskDashboard query={ {} } />
		);

		// Wait for the setup task list to render.
		expect(
			await findByText( container, TASK_LIST_HEADING )
		).toBeDefined();

		userEvent.click( getByRole( 'button', { name: 'Task List Options' } ) );
		userEvent.click( getByRole( 'button', { name: 'Hide this' } ) );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_default_homepage_layout: 'two_columns',
		} );
	} );

	it( 'sets homescreen layout default when completed', () => {
		useSelect.mockImplementation( () => ( {
			dismissedTasks: [],
			isSetupTaskListHidden: false,
			isExtendedTaskListHidden: true,
			profileItems: {},
		} ) );
		apiFetch.mockResolvedValue( {} );
		getAllTasks.mockReturnValue( { setup: shorterTasksList } );

		act( () => {
			render( <TaskDashboard query={ {} } /> );
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_default_homepage_layout: 'two_columns',
		} );
	} );

	it( 'hides the setup task list if there are no visible tasks', () => {
		apiFetch.mockResolvedValue( {} );
		const { setup } = tasks;
		const { queryByText } = render(
			<TaskList
				{ ...defaultTaskListProps }
				name="task_list"
				eventName="tasklist"
				dismissedTasks={ [ 'optional', 'required', 'completed' ] }
				isComplete={ false }
				trackedCompletedTasks={ [] }
				tasks={ [ ...setup, notVisibleTask ] }
			/>
		);

		expect( queryByText( TASK_LIST_HEADING ) ).toBeNull();
	} );

	it( 'hides the extended task list if there are no visible tasks', () => {
		apiFetch.mockResolvedValue( {} );
		const { extension } = tasks;
		const { queryByText } = render(
			<TaskList
				{ ...defaultTaskListProps }
				name="extended_task_list"
				eventName="extended_tasklist"
				dismissedTasks={ [ 'extension' ] }
				isComplete={ false }
				trackedCompletedTasks={ [] }
				tasks={ [ ...extension, notVisibleTask ] }
			/>
		);

		expect( queryByText( EXTENDED_TASK_LIST_HEADING ) ).toBeNull();
	} );

	it( 'sets setup tasks list as completed', () => {
		useSelect.mockImplementation( () => ( {
			dismissedTasks: [],
			isSetupTaskListHidden: false,
			isExtendedTaskListHidden: true,
			profileItems: {},
		} ) );
		apiFetch.mockResolvedValue( {} );
		getAllTasks.mockReturnValue( { setup: shorterTasksList } );

		act( () => {
			render( <TaskList { ...defaultTaskListProps } /> );
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_task_list_complete: 'yes',
		} );
	} );

	it( 'sets extended tasks list as completed', () => {
		apiFetch.mockResolvedValue( {} );
		act( () => {
			render(
				<TaskList
					{ ...defaultTaskListProps }
					name="extended_task_list"
					eventName="extended_tasklist"
					dismissedTasks={ [] }
					isComplete={ false }
					trackedCompletedTasks={ [] }
					tasks={ shorterTasksList }
				/>
			);
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_extended_task_list_complete: 'yes',
		} );
	} );

	it( 'sets setup tasks list (with only dismissed tasks) as completed', () => {
		apiFetch.mockResolvedValue( {} );
		const { setup } = tasks;
		act( () => {
			render(
				<TaskList
					{ ...defaultTaskListProps }
					dismissedTasks={ [ 'optional', 'required', 'completed' ] }
					isComplete={ false }
					trackedCompletedTasks={ [] }
					tasks={ setup }
				/>
			);
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_task_list_complete: 'yes',
		} );
	} );

	it( 'sets extended tasks list (with only dismissed tasks) as completed', () => {
		apiFetch.mockResolvedValue( {} );
		const { extension } = tasks;
		act( () => {
			render(
				<TaskList
					{ ...defaultTaskListProps }
					name="extended_task_list"
					eventName="extended_tasklist"
					dismissedTasks={ [ 'extension' ] }
					isComplete={ false }
					trackedCompletedTasks={ [] }
					tasks={ extension }
				/>
			);
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_extended_task_list_complete: 'yes',
		} );
	} );

	it( 'sets setup tasks list as incomplete', () => {
		apiFetch.mockResolvedValue( {} );
		const { setup } = tasks;
		act( () => {
			render(
				<TaskList
					{ ...defaultTaskListProps }
					dismissedTasks={ [] }
					isComplete={ true }
					tasks={ [ ...setup ] }
				/>
			);
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_task_list_complete: 'no',
		} );
	} );

	it( 'sets extended tasks list as incomplete', () => {
		apiFetch.mockResolvedValue( {} );
		const { extension } = tasks;
		act( () => {
			render(
				<TaskList
					{ ...defaultTaskListProps }
					name="extended_task_list"
					eventName="extended_tasklist"
					dismissedTasks={ [] }
					isComplete={ true }
					tasks={ extension }
				/>
			);
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_extended_task_list_complete: 'no',
		} );
	} );

	it( 'adds an untracked completed task', () => {
		apiFetch.mockResolvedValue( {} );
		const { setup, extension } = tasks;
		act( () => {
			render(
				<TaskList
					{ ...defaultTaskListProps }
					dismissedTasks={ [] }
					trackedCompletedTasks={ [] }
					tasks={ [ ...setup, ...extension ] }
				/>
			);
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_task_list_tracked_completed_tasks: [ 'completed' ],
		} );
	} );

	it( 'removes an incomplete but already tracked task from tracked tasks list', () => {
		apiFetch.mockResolvedValue( {} );
		const { setup, extension } = tasks;
		act( () => {
			render(
				<TaskList
					{ ...defaultTaskListProps }
					trackedCompletedTasks={ [ 'completed', 'extension' ] }
					tasks={ [ ...setup, ...extension ] }
				/>
			);
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_task_list_tracked_completed_tasks: [ 'completed' ],
		} );
	} );

	it( 'adds an untracked completed task and removes an incomplete but already tracked task from tracked tasks list', () => {
		apiFetch.mockResolvedValue( {} );
		const { setup, extension } = tasks;
		act( () => {
			render(
				<TaskList
					{ ...defaultTaskListProps }
					trackedCompletedTasks={ [ 'extension' ] }
					tasks={ [ ...setup, ...extension ] }
				/>
			);
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_task_list_tracked_completed_tasks: [ 'completed' ],
		} );
	} );

	it( 'does not add untracked completed (but dismissed) tasks', () => {
		apiFetch.mockResolvedValue( {} );
		act( () => {
			render(
				<TaskList
					{ ...defaultTaskListProps }
					dismissedTasks={ [ 'completed-1' ] }
					trackedCompletedTasks={ [] }
					tasks={ shorterTasksList }
				/>
			);
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_task_list_tracked_completed_tasks: [ 'completed-2' ],
		} );
	} );

	it( 'dismisses a task', async () => {
		apiFetch.mockResolvedValue( {} );
		const { extension } = tasks;
		const { getByText, getByTitle } = render(
			<TaskList
				{ ...defaultTaskListProps }
				name="extended_task_list"
				eventName="extended_tasklist"
				dismissedTasks={ [] }
				isComplete={ false }
				trackedCompletedTasks={ [] }
				tasks={ extension }
			/>
		);

		fireEvent.click( getByTitle( 'Task Options' ) );
		await waitFor( () => {
			expect( getByText( 'Dismiss' ) ).toBeInTheDocument();
		} );
		fireEvent.click( getByText( 'Dismiss' ) );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_task_list_dismissed_tasks: [ 'extension' ],
		} );
	} );

	it( 'calls the "onDismiss" callback after dismissing a task', async () => {
		apiFetch.mockResolvedValue( {} );
		const callback = jest.fn();
		const { extension } = tasks;
		extension[ 0 ].onDismiss = callback;
		const { getByText, getByTitle } = render(
			<TaskList
				{ ...defaultTaskListProps }
				isComplete={ false }
				trackedCompletedTasks={ [] }
				tasks={ extension }
				name={ 'extended_task_list' }
			/>
		);

		fireEvent.click( getByTitle( 'Task Options' ) );
		await waitFor( () => {
			expect( getByText( 'Dismiss' ) ).toBeInTheDocument();
		} );
		fireEvent.click( getByText( 'Dismiss' ) );
		expect( callback ).toHaveBeenCalledWith();
	} );

	it( 'sorts the extended task list tasks by completion status', () => {
		useSelect.mockImplementation( () => ( {
			dismissedTasks: [],
			isSetupTaskListHidden: true,
			profileItems: {},
		} ) );
		apiFetch.mockResolvedValue( {} );
		getAllTasks.mockReturnValue( {
			extension: [ completedExtensionTask, ...tasks.extension ],
		} );
		const { queryByText, container } = render(
			<TaskDashboard query={ {} } />
		);

		const visibleTasks = container.querySelectorAll( 'li' );
		expect( queryByText( EXTENDED_TASK_LIST_HEADING ) ).not.toBeNull();

		expect( visibleTasks ).toHaveLength( 2 );
		expect( visibleTasks[ 0 ] ).toHaveTextContent(
			'This task is an extension'
		);
		expect( visibleTasks[ 1 ] ).toHaveTextContent(
			'This completed task is an extension'
		);
	} );

	it( 'correctly toggles the extension task list', () => {
		const { getByText } = render(
			<SlotFillProvider>
				<DisplayOption.Slot />
				<TaskDashboard query={ {} } />
			</SlotFillProvider>
		);

		// Verify the task list is initially shown.
		expect(
			getByText( 'Show things to do next', { selector: 'button' } )
		).toBeChecked();
		// Toggle it off.
		fireEvent.click(
			getByText( 'Show things to do next', { selector: 'button' } )
		);

		expect( recordEvent ).toHaveBeenCalledWith( 'extended_tasklist_hide' );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_extended_task_list_hidden: 'yes',
		} );
	} );

	it( 'setup task list renders normal items in experiment control', () => {
		apiFetch.mockResolvedValue( {} );
		getAllTasks.mockReturnValue( tasks );
		useSelect.mockImplementation( () => ( {
			dismissedTasks: [],
			isSetupTaskListHidden: false,
			isExtendedTaskListHidden: true,
			profileItems: {},
		} ) );
		act( () => {
			const { queryByText } = render( <TaskDashboard query={ {} } /> );
			expect(
				queryByText( 'This is the optional task content' )
			).not.toHaveClass(
				'woocommerce-task-list__item-expandable-content-appear'
			);
		} );
	} );

	it( 'setup task list renders expandable items in experiment variant', async () => {
		apiFetch.mockResolvedValue( {} );
		getAllTasks.mockReturnValue( tasks );
		useSelect.mockImplementation( () => ( {
			dismissedTasks: [],
			isSetupTaskListHidden: false,
			isExtendedTaskListHidden: true,
			profileItems: {},
		} ) );
		useExperiment.mockReturnValue( [
			false,
			{
				variationName: 'treatment',
			},
		] );
		await act( async () => {
			const { container, queryByText } = render(
				<TaskDashboard query={ {} } />
			);

			// Expect the first incomplete task to be expanded
			expect(
				(
					await findByText(
						container,
						'This is the optional task content'
					)
				 ).parentElement.style.maxHeight
			).not.toBe( '0' );

			// Expect the second not to be.
			expect(
				queryByText( 'This is the required task content' ).parentElement
					.style.maxHeight
			).toBe( '0' );
		} );
	} );

	describe( 'getVisibleTasks', () => {
		it( 'should filter out tasks that are not visible', async () => {
			apiFetch.mockResolvedValue( {} );
			const { extension } = tasks;
			const { queryByText } = render(
				<TaskList
					{ ...defaultTaskListProps }
					isComplete={ false }
					trackedCompletedTasks={ [] }
					tasks={ [ ...extension, notVisibleTask ] }
					name={ 'extended_task_list' }
				/>
			);

			expect( queryByText( extension[ 0 ].title ) ).toBeInTheDocument();
			expect(
				queryByText( notVisibleTask.title )
			).not.toBeInTheDocument();
		} );

		it( 'should filter out dismissed tasks', async () => {
			apiFetch.mockResolvedValue( {} );
			const { extension, setup } = tasks;
			const { queryByText } = render(
				<TaskList
					{ ...defaultTaskListProps }
					isComplete={ false }
					trackedCompletedTasks={ [] }
					dismissedTasks={ [ extension[ 0 ].key ] }
					tasks={ [ ...extension, ...setup ] }
					name={ 'extended_task_list' }
				/>
			);

			expect(
				queryByText( extension[ 0 ].title )
			).not.toBeInTheDocument();
		} );

		it( 'should filter out tasks that are postponed using remind me later', async () => {
			apiFetch.mockResolvedValue( {} );
			const { extension, setup } = tasks;
			const timestamp = Date.now();
			const { queryByText } = render(
				<TaskList
					{ ...defaultTaskListProps }
					isComplete={ false }
					trackedCompletedTasks={ [] }
					remindMeLaterTasks={ {
						[ extension[ 0 ].key ]: timestamp + 1000 * 60 * 60,
					} }
					tasks={ [ ...extension, ...setup ] }
					name={ 'extended_task_list' }
				/>
			);

			expect(
				queryByText( extension[ 0 ].title )
			).not.toBeInTheDocument();
		} );

		it( 'should include tasks that had been postponed, but are past the snooze timestamp now', async () => {
			apiFetch.mockResolvedValue( {} );
			const { extension, setup } = tasks;
			const timestamp = Date.now();
			const { queryByText } = render(
				<TaskList
					{ ...defaultTaskListProps }
					isComplete={ false }
					trackedCompletedTasks={ [] }
					remindMeLaterTasks={ {
						[ extension[ 0 ].key ]: timestamp - 1000 * 60,
					} }
					tasks={ [ ...extension, ...setup ] }
					name={ 'extended_task_list' }
				/>
			);

			expect( queryByText( extension[ 0 ].title ) ).toBeInTheDocument();
		} );
	} );
} );
