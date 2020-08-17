/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { Layout } from '../layout';

// Rendering <StatsOverview /> breaks tests.
jest.mock( 'homescreen/stats-overview', () =>
	jest.fn().mockReturnValue( null )
);

// We aren't testing the <TaskList /> component here.
jest.mock( 'task-list', () => jest.fn().mockReturnValue( '[TaskList]' ) );

// We aren't testing the <InboxPanel /> component here.
jest.mock( 'header/activity-panel/panels/inbox', () =>
	jest.fn().mockReturnValue( '[InboxPanel]' )
);

// We aren't testing the <QuickLinks /> component here.
jest.mock( 'quick-links', () => jest.fn().mockReturnValue( '[QuickLinks]' ) );

describe( 'Homescreen Layout', () => {
	it( 'should show TaskList placeholder when loading', () => {
		const { container } = render(
			<Layout
				requestingTaskList
				taskListHidden={ false }
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		const placeholder = container.querySelector(
			'.woocommerce-task-card.is-loading'
		);
		expect( placeholder ).not.toBeNull();
	} );

	it( 'should show TaskList inline', async () => {
		const { container } = render(
			<Layout
				requestingTaskList={ false }
				taskListHidden={ false }
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		// Expect that we're rendering the "full" home screen (with columns).
		const columns = container.querySelector(
			'.woocommerce-homescreen-column'
		);
		expect( columns ).not.toBeNull();

		// Expect that the <TaskList /> is there too.
		const taskList = await screen.findByText( '[TaskList]' );
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
				updateOptions={ () => {} }
			/>
		);

		// Expect that we're NOT rendering the "full" home screen (with columns).
		const columns = container.querySelector(
			'.woocommerce-homescreen-column'
		);
		expect( columns ).toBeNull();

		// Expect that the <TaskList /> is there though.
		const taskList = await screen.findByText( '[TaskList]' );
		expect( taskList ).toBeDefined();
	} );

	it( 'should not show TaskList when user has hidden', () => {
		render(
			<Layout
				requestingTaskList={ false }
				taskListComplete={ false }
				taskListHidden
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		const taskList = screen.queryByText( '[TaskList]' );
		expect( taskList ).toBeNull();
	} );

	it( 'should not show TaskList when it is complete', () => {
		render(
			<Layout
				requestingTaskList={ false }
				taskListComplete
				taskListHidden={ false }
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		const taskList = screen.queryByText( '[TaskList]' );
		expect( taskList ).toBeNull();
	} );

	it( 'should show QuickLinks when user has hidden TaskList', () => {
		render(
			<Layout
				requestingTaskList={ false }
				taskListComplete={ false }
				taskListHidden
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		const quickLinks = screen.queryByText( '[QuickLinks]' );
		expect( quickLinks ).toBeDefined();
	} );

	it( 'should show QuickLinks when TaskList is complete', () => {
		render(
			<Layout
				requestingTaskList={ false }
				taskListComplete
				taskListHidden={ false }
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		const quickLinks = screen.queryByText( '[QuickLinks]' );
		expect( quickLinks ).toBeDefined();
	} );
} );
