/**
 * External dependencies
 */
import { render, findByText, queryByTestId } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import TaskDashboard from '../index.js';

jest.mock( '@wordpress/api-fetch' );

// Mock the tasks.
jest.mock( '../tasks', () => {
	return {
		getAllTasks: jest.fn().mockImplementation( () => [
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
		] ),
	};
} );

describe( 'TaskDashboard', () => {
	afterEach( () => jest.clearAllMocks() );

	it( 'renders a dismiss button for tasks that are optional and incomplete', async () => {
		apiFetch.mockResolvedValue( {} );

		const { container } = render( <TaskDashboard query={ {} } /> );

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
} );
