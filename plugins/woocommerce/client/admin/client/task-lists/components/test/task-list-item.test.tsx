import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { SlotFillProvider } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { TaskItem, useSlot } from '@woocommerce/experimental';
import { WooOnboardingTaskListItem } from '@woocommerce/onboarding';
import { TaskType } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { navigateTo } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { TaskListItem } from '../task-list-item';
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
const mockDispatch = {
	createNotice: vi.fn(),
	dismissTask: vi.fn(),
	snoozeTask: vi.fn(),
	undoDismissTask: vi.fn(),
	undoSnoozeTask: vi.fn(),
	visitedTask: vi.fn(),
	invalidateResolutionForStoreSelector: vi.fn(),
};
( useDispatch as Mock ).mockReturnValue( mockDispatch );
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
vi.mock( '@woocommerce/data', async () => {
	const originalModule = await vi.importActual( '@woocommerce/data' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...originalModule,
		useUserPreferences: vi.fn().mockReturnValue( {
			task_list_tracked_started_tasks: 0,
			updateUserPreferences: vi.fn(),
		} ),
	} );
} );
vi.mock( '@woocommerce/experimental', async () => {
	const originalModule = await vi.importActual( '@woocommerce/experimental' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...originalModule,
		useSlot: vi.fn(),
		TaskItem: vi
			.fn()
			.mockImplementation(
				( {
					title,
					onSnooze,
					onDismiss,
					onClick,
					secondaryAction,
				} ) => (
					// ExperimentalListItem gives the row a button role that
					// treats Enter as a click, so the mock does the same to
					// keep keyboard coverage meaningful.
					<div
						role="button"
						tabIndex={ 0 }
						onKeyDown={ ( event: React.KeyboardEvent ) => {
							if ( event.key === 'Enter' ) {
								onClick?.( event );
							}
						} }
					>
						<button onClick={ onClick }>{ title }</button>
						{ secondaryAction }
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
	} );
} );
vi.mock( '@woocommerce/navigation', () => {
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		getPersistedQuery: vi.fn().mockReturnValue( {} ),
		navigateTo: vi.fn(),
		getNewPath: vi.fn(),
		addHistoryListener: vi.fn(),
	} );
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
		vi.clearAllMocks();
		mockDispatch.dismissTask.mockResolvedValue( undefined );
	} );
	it( 'should render the default task list item', () => {
		const { queryByText } = render(
			<TaskListItem
				task={ {
					...task,
				} }
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
				task={ {
					...task,
					recordViewEvent: false,
				} }
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
				task={ {
					...task,
					recordViewEvent: true,
				} }
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
				task={ {
					...task,
				} }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
			/>
		);
		act( () => {
			userEvent.click(
				getByRole( 'button', {
					name: 'Dismiss',
				} )
			);
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
				task={ {
					...task,
				} }
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
	it( 'should call trackClick when clicking tasklist item', () => {
		const trackClick = vi.fn();
		render(
			<TaskListItem
				task={ {
					...task,
				} }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
				trackClick={ trackClick }
			/>
		);
		act( () => {
			userEvent.click( screen.getByText( task.title ) );
		} );
		expect( trackClick ).toHaveBeenCalledTimes( 1 );
	} );
	it( 'should call trackClick before expanding expandable tasklist item', () => {
		const trackClick = vi.fn();
		const setExpandedTask = vi.fn();
		render(
			<TaskListItem
				task={ {
					...task,
				} }
				isExpandable={ true }
				isExpanded={ false }
				setExpandedTask={ setExpandedTask }
				trackClick={ trackClick }
			/>
		);
		act( () => {
			userEvent.click( screen.getByText( task.title ) );
		} );
		expect( trackClick ).toHaveBeenCalledTimes( 1 );
		expect( setExpandedTask ).toHaveBeenCalledWith( task.id );
		expect( trackClick.mock.invocationCallOrder[ 0 ] ).toBeLessThan(
			setExpandedTask.mock.invocationCallOrder[ 0 ]
		);
	} );
	it( 'should not call dismissTask when isDismissable is set to false', () => {
		const { queryByRole } = render(
			<TaskListItem
				task={ {
					...task,
					isDismissable: false,
				} }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
			/>
		);
		expect(
			queryByRole( 'button', {
				name: 'Dismiss',
			} )
		).not.toBeInTheDocument();
	} );
	it( 'should request a task skip through the parent callback', async () => {
		const onTaskSkip = vi.fn().mockResolvedValue( undefined );
		const { getByRole } = render(
			<TaskListItem
				task={ {
					...task,
				} }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
				showSkipAction={ true }
				onTaskSkip={ onTaskSkip }
			/>
		);
		await act( async () => {
			await userEvent.click(
				getByRole( 'button', {
					name: 'Skip',
				} )
			);
		} );
		expect( onTaskSkip ).toHaveBeenCalledWith( task );
		expect( mockDispatch.dismissTask ).not.toHaveBeenCalled();
	} );
	it( 'should not navigate to the task when Skip is activated with Enter', async () => {
		const onTaskSkip = vi.fn().mockResolvedValue( undefined );
		const { getByRole } = render(
			<TaskListItem
				task={ {
					...task,
				} }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
				showSkipAction={ true }
				onTaskSkip={ onTaskSkip }
			/>
		);
		await act( async () => {
			getByRole( 'button', {
				name: 'Skip',
			} ).focus();
		} );
		await act( async () => {
			await userEvent.keyboard( '{Enter}' );
		} );
		expect( onTaskSkip ).toHaveBeenCalledWith( task );
		expect( navigateTo ).not.toHaveBeenCalled();
	} );
	it( 'should disable Skip while a task request is pending', async () => {
		const onTaskSkip = vi.fn().mockResolvedValue( undefined );
		const { getByRole } = render(
			<TaskListItem
				task={ {
					...task,
				} }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
				showSkipAction={ true }
				isSkipDisabled={ true }
				onTaskSkip={ onTaskSkip }
			/>
		);
		const skipButton = getByRole( 'button', {
			name: 'Skip',
		} );
		expect( skipButton ).toBeDisabled();
		await act( async () => {
			await userEvent.click( skipButton );
		} );
		expect( onTaskSkip ).not.toHaveBeenCalled();
	} );
	it( 'should call snoozeTask and trigger a notice when snoozing a task', () => {
		const { getByRole } = render(
			<TaskListItem
				task={ {
					...task,
				} }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
			/>
		);
		act( () => {
			userEvent.click(
				getByRole( 'button', {
					name: 'Snooze',
				} )
			);
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
				task={ {
					...task,
					isSnoozeable: false,
				} }
				isExpandable={ false }
				isExpanded={ false }
				setExpandedTask={ () => {} }
			/>
		);
		expect(
			queryByRole( 'button', {
				name: 'Snooze',
			} )
		).not.toBeInTheDocument();
	} );
	it( 'should not render task if slotfill is registered for id', () => {
		( useSlot as Mock ).mockReturnValue( {
			fills: [ 'test' ],
		} );
		const { queryByText } = render(
			<SlotFillProvider>
				<div>
					<TaskListItem
						task={ {
							...task,
							id: 'test',
						} }
						isExpandable={ false }
						isExpanded={ false }
						setExpandedTask={ () => {} }
					/>
				</div>
			</SlotFillProvider>
		);
		expect( queryByText( task.title ) ).not.toBeInTheDocument();
	} );
	it( 'should hand the skip action to a fill that composes its own TaskItem', async () => {
		( useSlot as Mock ).mockReturnValue( {
			fills: [ 'test' ],
		} );
		const onTaskSkip = vi.fn().mockResolvedValue( undefined );
		const extendedTask = {
			...task,
			id: 'test',
		};
		const { getByRole, queryByRole } = render(
			<SlotFillProvider>
				<WooOnboardingTaskListItem id="test">
					{ (
						fillProps: React.ComponentProps< typeof TaskItem >
					) => <TaskItem { ...fillProps } title={ task.title } /> }
				</WooOnboardingTaskListItem>
				<TaskListItem
					task={ extendedTask }
					isExpandable={ false }
					isExpanded={ false }
					setExpandedTask={ () => {} }
					showSkipAction={ true }
					onTaskSkip={ onTaskSkip }
				/>
			</SlotFillProvider>
		);

		// Extended lists drop Dismiss, so the fill has to receive Skip in its
		// place rather than losing both actions.
		expect(
			queryByRole( 'button', {
				name: 'Dismiss',
			} )
		).toBeNull();
		await act( async () => {
			await userEvent.click(
				getByRole( 'button', {
					name: 'Skip',
				} )
			);
		} );
		expect( onTaskSkip ).toHaveBeenCalledWith( extendedTask );
	} );
} );
