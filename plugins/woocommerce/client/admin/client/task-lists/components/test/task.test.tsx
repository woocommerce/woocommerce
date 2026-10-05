import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';

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
vi.mock( '@wordpress/data', async () => {
	// Require the original module to not be mocked...
	const originalModule = await vi.importActual( '@wordpress/data' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		__esModule: true,
		// Use it when dealing with esModules
		...originalModule,
		useDispatch: vi.fn(),
		useSelect: vi.fn().mockReturnValue( {} ),
	} );
} );
vi.mock( '@woocommerce/navigation', async () => {
	// Require the original module to not be mocked...
	const originalModule = await vi.importActual( '@woocommerce/navigation' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		__esModule: true,
		// Use it when dealing with esModules
		...originalModule,
		getPersistedQuery: vi.fn().mockReturnValue( {} ),
		getHistory: vi.fn(),
		getNewPath: () => 'new-path',
	} );
} );
vi.mock( '@woocommerce/onboarding', () => {
	const mock = {
		WooOnboardingTask: {
			Slot: vi.fn(),
		},
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'Task', () => {
	const invalidateResolutionForStoreSelector = vi.fn();
	const optimisticallyCompleteTask = vi.fn();
	beforeEach( () => {
		( useDispatch as Mock ).mockImplementation( () => ( {
			invalidateResolutionForStoreSelector,
			optimisticallyCompleteTask,
		} ) );
		( WooOnboardingTask.Slot as Mock ).mockImplementation(
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
				<Task
					query={ {
						task: 'test',
					} }
					task={ task }
				/>
			</div>
		);
		expect( queryByText( 'test' ) ).toBeInTheDocument();
	} );
	it( 'should update history and invalidate store selector onComplete', () => {
		const historyPushMock = vi.fn();
		( getHistory as Mock ).mockImplementation( () => {
			return {
				push: historyPushMock,
			};
		} );
		const { getByRole } = render(
			<div>
				<Task
					query={ {
						task: 'test',
					} }
					task={ task }
				/>
			</div>
		);
		act( () => {
			userEvent.click(
				getByRole( 'button', {
					name: 'complete',
				} )
			);
		} );
		expect( optimisticallyCompleteTask ).toHaveBeenCalledWith( 'test' );
		expect( invalidateResolutionForStoreSelector ).toHaveBeenCalledWith(
			'getTaskLists'
		);
		expect( historyPushMock ).toHaveBeenCalledWith( 'new-path' );
	} );
} );
