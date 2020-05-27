/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { Layout } from '../layout';

// Rendering <StatsOverview /> breaks tests.
jest.mock( 'homepage/stats-overview', () => jest.fn().mockReturnValue( null ) );

// We aren't testing the <TaskList /> component here.
jest.mock( 'task-list', () => jest.fn().mockReturnValue( '[TaskList]' ) );

describe( 'Homepage Layout', () => {
	it( 'should show TaskList placeholder when loading', () => {
		const { container } = render(
			<Layout
				requestingTaskList
				taskListHidden={ false }
				query={ {} }
			/>
		);

		const placeholder = container.querySelector( '.woocommerce-task-card.is-loading' );
		expect( placeholder ).not.toBeNull();
	} );

	it( 'should show TaskList inline', async () => {
		const { container } = render(
			<Layout
				requestingTaskList={ false }
				taskListHidden={ false }
				query={ {} }
			/>
		);

		// Expect that we're rendering the "full" home screen (with columns).
		const columns = container.querySelector( '.woocommerce-homepage-column' );
		expect( columns ).not.toBeNull();

		// Expect that the <TaskList /> is there too.
		const taskList = await screen.findByText( '[TaskList]' )
		expect( taskList ).toBeDefined();
	} );

	it( 'should render TaskList alone when on task', async () => {
		const { container } = render(
			<Layout
				requestingTaskList={ false }
				taskListHidden={ false }
				query={ {
					task: 'products',
				} }
			/>
		);

		// Expect that we're NOT rendering the "full" home screen (with columns).
		const columns = container.querySelector( '.woocommerce-homepage-column' );
		expect( columns ).toBeNull();

		// Expect that the <TaskList /> is there though.
		const taskList = await screen.findByText( '[TaskList]' )
		expect( taskList ).toBeDefined();
	} );

	it( 'should not show TaskList when user has hidden', () => {
		render(
			<Layout
				requestingTaskList={ false }
				taskListComplete={ false }
				taskListHidden
				query={ {} }
			/>
		);

		const taskList = screen.queryByText( '[TaskList]' )
		expect( taskList ).toBeNull();
	} );

	it( 'should not show TaskList when it is complete', () => {
		render(
			<Layout
				requestingTaskList={ false }
				taskListComplete
				taskListHidden={ false }
				query={ {} }
			/>
		);

		const taskList = screen.queryByText( '[TaskList]' )
		expect( taskList ).toBeNull();
	} );
} );
