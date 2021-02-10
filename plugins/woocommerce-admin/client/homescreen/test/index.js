/**
 * External dependencies
 */
import { render, screen, within } from '@testing-library/react';
import { useUserPreferences } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { Layout } from '../layout';

// Rendering <StatsOverview /> breaks tests.
jest.mock( 'homescreen/stats-overview', () =>
	jest.fn().mockReturnValue( '[StatsOverview]' )
);

// We aren't testing the <TaskList /> component here.
jest.mock( 'task-list', () => jest.fn().mockReturnValue( '[TaskList]' ) );

// We aren't testing the <InboxPanel /> component here.
jest.mock( 'inbox-panel', () => jest.fn().mockReturnValue( '[InboxPanel]' ) );

jest.mock( '../../store-management-links', () => ( {
	StoreManagementLinks: jest.fn().mockReturnValue( '[StoreManagementLinks]' ),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUserPreferences: jest.fn().mockReturnValue( {} ),
} ) );

// We aren't testing the <ActivityPanel /> component here.
jest.mock( '../activity-panel', () => ( {
	ActivityPanel: jest.fn().mockReturnValue( '[ActivityPanel]' ),
} ) );

describe( 'Homescreen Layout', () => {
	it( 'should show TaskList placeholder when loading', () => {
		const { container } = render(
			<Layout
				requestingTaskList
				bothTaskListsHidden={ false }
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		const placeholder = container.querySelector(
			'.woocommerce-task-card.is-loading'
		);
		expect( placeholder ).not.toBeNull();
	} );

	it( 'should show TaskList inline', () => {
		const { container } = render(
			<Layout
				requestingTaskList={ false }
				bothTaskListsHidden={ false }
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
		const taskList = screen.queryByText( /\[TaskList\]/ );
		expect( taskList ).toBeDefined();
	} );

	it( 'should render TaskList alone when on task', () => {
		const { container } = render(
			<Layout
				requestingTaskList={ false }
				bothTaskListsHidden={ false }
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
		const taskList = screen.queryByText( '[TaskList]' );
		expect( taskList ).toBeDefined();
	} );

	it( 'should not show TaskList when user has hidden', () => {
		render(
			<Layout
				requestingTaskList={ false }
				bothTaskListsHidden
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
				bothTaskListsHidden
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
				bothTaskListsHidden={ false }
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		const quickLinks = screen.queryByText( '[QuickLinks]' );
		expect( quickLinks ).toBeDefined();
	} );

	it( 'should default to layout option value', () => {
		useUserPreferences.mockReturnValue( {
			homepage_layout: '',
		} );
		const { container } = render(
			<Layout
				defaultHomescreenLayout="two_columns"
				requestingTaskList={ false }
				bothTaskListsHidden={ false }
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		// Expect that we're rendering the two-column home screen.
		// The user doesn't have a preference set, but the component received a prop
		// defaulting to the two column view.
		expect(
			container.getElementsByClassName(
				'woocommerce-homescreen two-columns'
			)
		).toHaveLength( 1 );
	} );

	it( 'should falback to single column layout', () => {
		useUserPreferences.mockReturnValue( {
			homepage_layout: '',
		} );
		const { container } = render(
			<Layout
				requestingTaskList={ false }
				bothTaskListsHidden={ false }
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		// Expect that we're rendering the single column home screen.
		// If a default layout prop isn't specified, the default falls back to single column view.
		const homescreen = container.getElementsByClassName(
			'woocommerce-homescreen'
		);
		expect( homescreen ).toHaveLength( 1 );

		expect( homescreen[ 0 ] ).not.toHaveClass( 'two-columns' );
	} );

	it( 'switches to two column layout based on user preference', () => {
		useUserPreferences.mockReturnValue( {
			homepage_layout: 'two_columns',
		} );
		const { container } = render(
			<Layout
				requestingTaskList={ false }
				bothTaskListsHidden={ false }
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		// Expect that we're rendering the two-column home screen.
		expect(
			container.getElementsByClassName(
				'woocommerce-homescreen two-columns'
			)
		).toHaveLength( 1 );
	} );

	it( 'should display the correct blocks in each column', () => {
		useUserPreferences.mockReturnValue( {
			homepage_layout: 'two_columns',
		} );
		const { container } = render(
			<Layout
				requestingTaskList={ false }
				bothTaskListsHidden={ false }
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		const columns = container.getElementsByClassName(
			'woocommerce-homescreen-column'
		);
		expect( columns ).toHaveLength( 2 );
		const firstColumn = columns[ 0 ];
		const secondColumn = columns[ 1 ];

		expect(
			within( firstColumn ).getByText( /\[TaskList\]/ )
		).toBeInTheDocument();
		expect(
			within( firstColumn ).getByText( /\[InboxPanel\]/ )
		).toBeInTheDocument();
		expect(
			within( secondColumn ).queryByText( /\[TaskList\]/ )
		).toBeNull();
		expect(
			within( secondColumn ).queryByText( /\[InboxPanel\]/ )
		).toBeNull();

		expect(
			within( secondColumn ).getByText( /\[StatsOverview\]/ )
		).toBeInTheDocument();
		expect(
			within( firstColumn ).queryByText( /\[StatsOverview\]/ )
		).toBeNull();
	} );

	it( 'should display the correct blocks in each column when task list is hidden', () => {
		useUserPreferences.mockReturnValue( {
			homepage_layout: 'two_columns',
		} );
		const { container } = render(
			<Layout
				requestingTaskList={ false }
				bothTaskListsHidden={ false }
				isTaskListHidden={ true }
				query={ {} }
				updateOptions={ () => {} }
			/>
		);

		const columns = container.getElementsByClassName(
			'woocommerce-homescreen-column'
		);
		expect( columns ).toHaveLength( 2 );
		const firstColumn = columns[ 0 ];
		const secondColumn = columns[ 1 ];

		expect(
			within( firstColumn ).getByText( /\[TaskList\]/ )
		).toBeInTheDocument();
		expect(
			within( firstColumn ).getByText( /\[InboxPanel\]/ )
		).toBeInTheDocument();
		expect(
			within( secondColumn ).queryByText( /\[TaskList\]/ )
		).toBeNull();
		expect(
			within( secondColumn ).queryByText( /\[InboxPanel\]/ )
		).toBeNull();

		expect(
			within( secondColumn ).getByText( /\[StatsOverview\]/ )
		).toBeInTheDocument();
		expect(
			within( secondColumn ).getByText( /\[StoreManagementLinks\]/ )
		).toBeInTheDocument();
		expect(
			within( firstColumn ).queryByText( /\[StatsOverview\]/ )
		).toBeNull();
	} );
} );
