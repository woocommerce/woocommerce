import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen, within } from '@testing-library/react';
import { useUserPreferences } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { Layout } from '../layout';
import { isFeatureEnabled } from '~/utils/features';
vi.mock( '../stats-overview', () => {
	const mock = vi.fn().mockReturnValue( <div>[StatsOverview]</div> );
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '../../inbox-panel', () => {
	const mock = vi.fn().mockReturnValue( <div>[InboxPanel]</div> );
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '../../store-management-links', () => {
	const mock = {
		StoreManagementLinks: vi
			.fn()
			.mockReturnValue( <div>[StoreManagementLinks]</div> ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../activity-panel', () => {
	const mock = {
		ActivityPanel: vi.fn().mockReturnValue( <div>[ActivityPanel]</div> ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/data', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/data' ) ),
		useUserPreferences: vi.fn().mockReturnValue( {} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '~/utils/features', () => {
	const mock = {
		isFeatureEnabled: vi.fn().mockReturnValue( true ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/element', async () => {
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...( await vi.importActual( '@wordpress/element' ) ),
		Suspense: ( { children } ) => <div>{ children }</div>,
		// It's not easy to mock a React.lazy component, since we only use one in this component, this mocks lazy to return a mocked <TaskList>
		lazy: () => () => <div>[TaskList]</div>,
	} );
} );
describe( 'Homescreen Layout', () => {
	beforeEach( () => {
		isFeatureEnabled.mockReturnValue( true );
	} );
	it( 'should show TaskList inline', () => {
		const { container } = render(
			<Layout
				requestingTaskList={ false }
				taskListComplete
				hasTaskList={ true }
				query={ {
					page: 'wc-admin',
				} }
				updateOptions={ () => {} }
			/>
		);

		// Expect that we're rendering the "full" home screen (with columns).
		const columns = container.querySelector(
			'.woocommerce-homescreen-column'
		);
		expect( columns ).toBeInTheDocument();

		// Expect that the <TaskList /> is there too.
		const taskList = screen.getByText( '[TaskList]' );
		expect( taskList ).toBeInTheDocument();
	} );
	it( 'should render TaskList alone when on task', () => {
		const { container } = render(
			<Layout
				requestingTaskList={ false }
				hasTaskList={ true }
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
		expect( columns ).not.toBeInTheDocument();

		// Expect that the <TaskList /> is there though.
		const taskList = screen.queryByText( '[TaskList]' );
		expect( taskList ).toBeInTheDocument();
	} );
	it( 'should not show TaskList when user has hidden', () => {
		render(
			<Layout
				requestingTaskList={ false }
				hasTaskList={ false }
				query={ {
					page: 'wc-admin',
				} }
				updateOptions={ () => {} }
			/>
		);
		const taskList = screen.queryByText( '[TaskList]' );
		expect( taskList ).not.toBeInTheDocument();
	} );
	it( 'should show StoreManagementLinks when TaskList is complete, even if the task list is not hidden', () => {
		render(
			<Layout
				requestingTaskList={ false }
				hasTaskList={ true }
				taskListComplete
				query={ {
					page: 'wc-admin',
				} }
				updateOptions={ () => {} }
			/>
		);
		const storeManagementLinks = screen.queryByText(
			'[StoreManagementLinks]'
		);
		expect( storeManagementLinks ).toBeInTheDocument();
	} );
	it( 'should default to layout option value', () => {
		useUserPreferences.mockReturnValue( {
			homepage_layout: '',
		} );
		const { container } = render(
			<Layout
				defaultHomescreenLayout="two_columns"
				requestingTaskList={ false }
				hasTaskList={ true }
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
	it( 'should fallback to single column layout', () => {
		useUserPreferences.mockReturnValue( {
			homepage_layout: '',
		} );
		const { container } = render(
			<Layout
				requestingTaskList={ false }
				hasTaskList={ true }
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
				hasTaskList={ true }
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
				taskListComplete
				hasTaskList={ true }
				query={ {
					page: 'wc-admin',
				} }
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
			within( firstColumn ).getByText( '[TaskList]' )
		).toBeInTheDocument();
		expect(
			within( firstColumn ).getByText( '[InboxPanel]' )
		).toBeInTheDocument();
		expect(
			within( secondColumn ).queryByText( '[TaskList]' )
		).not.toBeInTheDocument();
		expect(
			within( secondColumn ).queryByText( '[InboxPanel]' )
		).not.toBeInTheDocument();
		expect(
			within( secondColumn ).getByText( '[StatsOverview]' )
		).toBeInTheDocument();
		expect(
			within( firstColumn ).queryByText( '[StatsOverview]' )
		).not.toBeInTheDocument();
	} );
	it( 'should display the correct blocks in each column when task list is hidden', () => {
		useUserPreferences.mockReturnValue( {
			homepage_layout: 'two_columns',
		} );
		const { container } = render(
			<Layout
				requestingTaskList={ false }
				taskListComplete
				hasTaskList={ true }
				isTaskListHidden={ true }
				query={ {
					page: 'wc-admin',
				} }
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
			within( firstColumn ).getByText( '[TaskList]' )
		).toBeInTheDocument();
		expect(
			within( firstColumn ).getByText( '[InboxPanel]' )
		).toBeInTheDocument();
		expect(
			within( secondColumn ).queryByText( '[TaskList]' )
		).not.toBeInTheDocument();
		expect(
			within( secondColumn ).queryByText( '[InboxPanel]' )
		).not.toBeInTheDocument();
		expect(
			within( secondColumn ).getByText( '[StatsOverview]' )
		).toBeInTheDocument();
		expect(
			within( secondColumn ).getByText( '[StoreManagementLinks]' )
		).toBeInTheDocument();
		expect(
			within( firstColumn ).queryByText( '[StatsOverview]' )
		).not.toBeInTheDocument();
	} );
} );
