/**
 * External dependencies
 */
import { render, act } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';
import userEvent from '@testing-library/user-event';
import { getHistory } from '@woocommerce/navigation';
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { TaskType } from '@woocommerce/data';

const task: TaskType = {
	id: 'optional',
	title: 'Test',
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

/**
 * Internal dependencies
 */
import { Task } from '../task';

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useDispatch: jest.fn(),
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

jest.mock( '@woocommerce/navigation', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@woocommerce/navigation' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		getPersistedQuery: jest.fn().mockReturnValue( {} ),
		getHistory: jest.fn(),
		getNewPath: () => 'new-path',
	};
} );

jest.mock( '@woocommerce/onboarding', () => ( {
	WooOnboardingTask: {
		Slot: jest.fn(),
	},
} ) );

describe( 'Task', () => {
	const invalidateResolutionForStoreSelector = jest.fn();
	const optimisticallyCompleteTask = jest.fn();
	beforeEach( () => {
		( useDispatch as jest.Mock ).mockImplementation( () => ( {
			invalidateResolutionForStoreSelector,
			optimisticallyCompleteTask,
		} ) );
		( WooOnboardingTask.Slot as jest.Mock ).mockImplementation(
			( { id, fillProps } ) => (
				<div>
					{ id }
					<button onClick={ fillProps.onComplete } name="complete">
						complete
					</button>
				</div>
			)
		);
	} );

	it( 'should pass the task name as id to the OnboardingTask.Slot', () => {
		const { queryByText } = render(
			<div>
				<Task query={ { task: 'test' } } task={ task } />
			</div>
		);
		expect( queryByText( 'test' ) ).toBeInTheDocument();
	} );

	it( 'should update history and invalidate store selector onComplete', () => {
		const historyPushMock = jest.fn();
		( getHistory as jest.Mock ).mockImplementation( () => {
			return {
				push: historyPushMock,
			};
		} );
		const { getByRole } = render(
			<div>
				<Task query={ { task: 'test' } } task={ task } />
			</div>
		);
		act( () => {
			userEvent.click( getByRole( 'button', { name: 'complete' } ) );
		} );
		expect( optimisticallyCompleteTask ).toHaveBeenCalledWith( 'test' );
		expect( invalidateResolutionForStoreSelector ).toHaveBeenCalledWith(
			'getTaskLists'
		);
		expect( historyPushMock ).toHaveBeenCalledWith( 'new-path' );
	} );
} );
