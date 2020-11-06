/**
 * External dependencies
 */
import { act, render, findByText, queryByTestId } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { TaskDashboard } from '../index.js';
import { getAllTasks } from '../tasks';

jest.mock( '@wordpress/api-fetch' );
jest.mock( '../tasks' );

describe( 'TaskDashboard', () => {
	afterEach( () => jest.clearAllMocks() );

	it( 'renders a dismiss button for tasks that are optional and incomplete', async () => {
		apiFetch.mockResolvedValue( {} );
		getAllTasks.mockReturnValue( [
			{
				key: 'optional',
				title: 'This task is optional',
				container: null,
				completed: false,
				visible: true,
				time: '1 minute',
				isDismissable: true,
			},
			{
				key: 'required',
				title: 'This task is required',
				container: null,
				completed: false,
				visible: true,
				time: '1 minute',
				isDismissable: false,
			},
			{
				key: 'completed',
				title: 'This task is completed',
				container: null,
				completed: true,
				visible: true,
				time: '1 minute',
				isDismissable: true,
			},
		] );

		const { container } = render(
			<TaskDashboard
				dismissedTasks={ [] }
				profileItems={ {} }
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		// Wait for the task list to render.
		expect( await findByText( container, 'Finish setup' ) ).toBeDefined();

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

	it( 'sets homescreen layout default when dismissed', () => {
		const updateOptions = jest.fn();
		const { getByRole } = render(
			<TaskDashboard
				dismissedTasks={ [] }
				profileItems={ {} }
				query={ {} }
				updateOptions={ updateOptions }
			/>
		);

		userEvent.click( getByRole( 'button', { name: 'Task List Options' } ) );
		userEvent.click( getByRole( 'button', { name: 'Hide this' } ) );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_task_list_hidden: 'yes',
			woocommerce_task_list_prompt_shown: true,
			woocommerce_default_homepage_layout: 'two_columns',
		} );
	} );

	it( 'sets homescreen layout default when completed', () => {
		apiFetch.mockResolvedValue( {} );
		// Mock all tasks as completed.
		getAllTasks.mockReturnValue( [
			{
				key: 'optional',
				title: 'This task is optional',
				container: null,
				completed: true,
				visible: true,
				time: '1 minute',
				isDismissable: true,
			},
			{
				key: 'required',
				title: 'This task is required',
				container: null,
				completed: true,
				visible: true,
				time: '1 minute',
				isDismissable: false,
			},
			{
				key: 'completed',
				title: 'This task is completed',
				container: null,
				completed: true,
				visible: true,
				time: '1 minute',
				isDismissable: true,
			},
		] );
		const updateOptions = jest.fn();
		act( () => {
			render(
				<TaskDashboard
					dismissedTasks={ [] }
					isTaskListComplete={ false }
					profileItems={ {} }
					query={ {} }
					updateOptions={ updateOptions }
				/>
			);
		} );

		expect( updateOptions ).toHaveBeenCalledWith( {
			woocommerce_task_list_complete: 'yes',
			woocommerce_default_homepage_layout: 'two_columns',
		} );
	} );
} );
