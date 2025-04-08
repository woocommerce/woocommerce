/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { SlotFillProvider } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useSlot } from '@woocommerce/experimental';
import { TaskType } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { TaskListItem } from '../task-list-item';

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );
	return {
		...originalModule,
		useDispatch: jest.fn(),
	};
} );
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

const mockDispatch = {
	createNotice: jest.fn(),
	dismissTask: jest.fn(),
	snoozeTask: jest.fn(),
	undoDismissTask: jest.fn(),
	undoSnoozeTask: jest.fn(),
	visitedTask: jest.fn(),
	invalidateResolutionForStoreSelector: jest.fn(),
};
( useDispatch as jest.Mock ).mockReturnValue( mockDispatch );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/data', () => {
	const originalModule = jest.requireActual( '@woocommerce/data' );
	return {
		...originalModule,
		useUserPreferences: jest.fn().mockReturnValue( {
			task_list_tracked_started_tasks: 0,
			updateUserPreferences: jest.fn(),
		} ),
	};
} );
jest.mock( '@woocommerce/experimental', () => {
	const originalModule = jest.requireActual( '@woocommerce/experimental' );
	return {
		...originalModule,
		useSlot: jest.fn(),
		TaskItem: jest
			.fn()
			.mockImplementation(
				( { title, onSnooze, onDismiss, onClick } ) => (
					<div>
						<button onClick={ onClick }>{ title }</button>
						{ onSnooze && (
							<button onClick={ onSnooze } name="Snooze">
								Snooze
							</button>
						) }
						{ onDismiss && (
							<button onClick={ onDismiss } name="Dismiss">
								Dismiss
							</button>
						) }
					</div>
				)
			),
	};
} );
jest.mock( '@woocommerce/navigation', () => {
	return {
		getPersistedQuery: jest.fn().mockReturnValue( {} ),
		navigateTo: jest.fn(),
		getNewPath: jest.fn(),
		addHistoryListener: jest.fn(),
	};
} );

const task: TaskType = {
	id: 'optional',
	title: 'This task is optional',
	badge: 'Optional badge',
	isComplete: false,
	time: '1 minute',
	isDismissable: true,
	isSnoozeable: true,
	content: 'This is the optional task content',
	additionalInfo: 'This is the task additional info',
	parentId: '',
	isDismissed: false,
	isSnoozed: false,
	isVisible: true,
	isDisabled: false,
	snoozedUntil: 0,
	isVisited: false,
	canView: true,
	isActioned: false,
	eventPrefix: '',
	level: 0,
	recordViewEvent: false,
};

describe( 'TaskListItem', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render the default task list item', () => {
		const { queryByText } = render(
			<TaskListItem
				task={ { ...task } }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
			/>
		);
		expect( queryByText( task.title ) ).toBeInTheDocument();
	} );

	it( 'should not record view event on render if recordViewEvent is false', () => {
		render(
			<TaskListItem
				task={ { ...task, recordViewEvent: false } }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
			/>
		);

		expect( recordEvent ).toHaveBeenCalledTimes( 0 );
	} );

	it( 'should record view event on render if recordViewEvent is true', () => {
		render(
			<TaskListItem
				task={ { ...task, recordViewEvent: true } }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
			/>
		);

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith( 'tasklist_item_view', {
			context: 'home',
			is_complete: task.isComplete,
			task_name: task.id,
		} );
	} );

	it( 'should call dismissTask and trigger a notice when dismissing a task', () => {
		const { getByRole } = render(
			<TaskListItem
				task={ { ...task } }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
			/>
		);
		act( () => {
			userEvent.click( getByRole( 'button', { name: 'Dismiss' } ) );
		} );
		expect( mockDispatch.dismissTask ).toHaveBeenCalledWith( task.id );
		expect( mockDispatch.createNotice ).toHaveBeenCalled();
		expect( mockDispatch.createNotice.mock.calls[ 0 ][ 0 ] ).toEqual(
			'success'
		);
		expect( mockDispatch.createNotice.mock.calls[ 0 ][ 1 ] ).toEqual(
			'Task dismissed'
		);
	} );

	it( 'should trigger tasklist_click event when clicking tasklist item', () => {
		render(
			<TaskListItem
				task={ { ...task } }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
			/>
		);
		act( () => {
			userEvent.click( screen.getByText( task.title ) );
		} );

		expect( recordEvent ).toHaveBeenCalledWith( 'tasklist_click', {
			context: 'home',
			task_name: task.id,
		} );
	} );

	it( 'should not call dismissTask when isDismissable is set to false', () => {
		const { queryByRole } = render(
			<TaskListItem
				task={ { ...task, isDismissable: false } }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
			/>
		);
		expect(
			queryByRole( 'button', { name: 'Dismiss' } )
		).not.toBeInTheDocument();
	} );

	it( 'should call snoozeTask and trigger a notice when snoozing a task', () => {
		const { getByRole } = render(
			<TaskListItem
				task={ { ...task } }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
			/>
		);
		act( () => {
			userEvent.click( getByRole( 'button', { name: 'Snooze' } ) );
		} );
		expect( mockDispatch.snoozeTask ).toHaveBeenCalledWith( task.id );
		expect( mockDispatch.createNotice ).toHaveBeenCalled();
		expect( mockDispatch.createNotice.mock.calls[ 0 ][ 0 ] ).toEqual(
			'success'
		);
		expect( mockDispatch.createNotice.mock.calls[ 0 ][ 1 ] ).toEqual(
			'Task postponed until tomorrow'
		);
	} );

	it( 'should not call snoozeTask when isSnoozeable is set to false', () => {
		const { queryByRole } = render(
			<TaskListItem
				task={ { ...task, isSnoozeable: false } }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
			/>
		);
		expect(
			queryByRole( 'button', { name: 'Snooze' } )
		).not.toBeInTheDocument();
	} );

	it( 'should not render task if slotfill is registered for id', () => {
		( useSlot as jest.Mock ).mockReturnValue( { fills: [ 'test' ] } );
		const { queryByText } = render(
			<SlotFillProvider>
				<div>
					<TaskListItem
						task={ { ...task, id: 'test' } }
						isExpandable={ false }
						isExpanded={ false }
						setExpandedTask={ () => {} }
					/>
				</div>
			</SlotFillProvider>
		);
		expect( queryByText( task.title ) ).not.toBeInTheDocument();
	} );
} );
