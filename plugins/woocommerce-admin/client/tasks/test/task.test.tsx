/**
 * External dependencies
 */
import { render, act } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';
import userEvent from '@testing-library/user-event';
import { getHistory } from '@woocommerce/navigation';
import { WooOnboardingTask } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import { Task } from '../task';

jest.mock( '@wordpress/data' );

jest.mock( '@woocommerce/navigation', () => ( {
	getHistory: jest.fn(),
	getNewPath: () => 'new-path',
} ) );

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
				<Task query={ { task: 'test' } } />
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
				<Task query={ { task: 'test' } } />
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
