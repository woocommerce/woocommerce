/**
 * External dependencies
 */
import { render, act, cleanup } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';
import { useExperiment } from '@woocommerce/explat';
import { recordEvent } from '@woocommerce/tracks';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { Tasks } from '../tasks';

jest.mock( '@wordpress/data' );
jest.mock( '@woocommerce/explat' );
jest.mock( '@woocommerce/tracks' );

jest.mock( '../task-list', () => ( {
	TaskList: ( { id } ) => <div>task-list:{ id }</div>,
} ) );

jest.mock( '../task', () => ( {
	Task: ( { query } ) => <div>task:{ query.task }</div>,
} ) );

jest.mock( '../placeholder', () => ( {
	TasksPlaceholder: () => <div>task-placeholder</div>,
} ) );

jest.mock( '~/activity-panel/display-options', () => ( {
	DisplayOption: ( { children } ) => <div>{ children } </div>,
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
					isVisible: true,
					tasks: [ { id: 'main-task-1' }, { id: 'main-task-2' } ],
				},
				{ id: 'extended', isVisible: true, tasks: [] },
			],
		} ) );
		( useExperiment as jest.Mock ).mockImplementation( () => [
			false,
			'control',
		] );
	} );

	afterEach( () => {
		cleanup();
	} );

	it( 'should render if no current task and finished resolving', () => {
		const { queryByText } = render(
			<div>
				<Tasks query={ {} } />
			</div>
		);
		expect( queryByText( 'task-list:main' ) ).toBeInTheDocument();
		expect( queryByText( 'task-list:extended' ) ).toBeInTheDocument();
	} );

	it( 'should render the task component if query has an existing task', () => {
		const { queryByText } = render(
			<div>
				<Tasks query={ { task: 'main-task-1' } } />
			</div>
		);
		expect( queryByText( 'task:main-task-1' ) ).toBeInTheDocument();
	} );

	it( 'should not render anything if query has task, but task does not exist', () => {
		const { queryByText } = render(
			<div>
				<Tasks query={ { task: 'main-task-random' } } />
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
				<Tasks query={ {} } />
			</div>
		);
		expect( queryByText( 'task-placeholder' ) ).toBeInTheDocument();
	} );

	it( 'should render the placeholder if isLoadingExperiment is true', () => {
		( useExperiment as jest.Mock ).mockImplementation( () => [
			true,
			'control',
		] );
		const { queryByText } = render(
			<div>
				<Tasks query={ {} } />
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
					isVisible: true,
					isToggleable: true,
					isHidden: false,
					tasks: [ { id: 'main-task-1' }, { id: 'main-task-2' } ],
				},
			],
		} ) );
		const { queryByText } = render(
			<div>
				<Tasks query={ {} } />
			</div>
		);
		expect( queryByText( 'Show things to do next' ) ).toBeInTheDocument();
	} );

	it( 'should class updateOptions with default data on render', () => {
		render(
			<div>
				<Tasks query={ {} } />
			</div>
		);
		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_task_list_prompt_shown: true,
		} );
	} );

	describe( 'toggle list', () => {
		it( 'should trigger hide track when clicking Show things to do next button', () => {
			( useSelect as jest.Mock ).mockImplementation( () => ( {
				isResolving: false,
				taskLists: [
					{
						id: 'main',
						isVisible: true,
						isToggleable: true,
						isHidden: false,
						tasks: [ { id: 'main-task-1' }, { id: 'main-task-2' } ],
					},
				],
			} ) );
			const { getByText } = render(
				<div>
					<Tasks query={ {} } />
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
						isVisible: true,
						isToggleable: true,
						isHidden: true,
						tasks: [ { id: 'main-task-1' }, { id: 'main-task-2' } ],
					},
				],
			} ) );
			const { getByText } = render(
				<div>
					<Tasks query={ {} } />
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
