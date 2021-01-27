/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ActivityPanel } from '../';

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUserPreferences: () => ( {
		updateUserPreferences: () => {},
	} ),
} ) );

// We aren't testing the <DisplayOptions /> component here.
jest.mock( '../display-options', () => ( {
	DisplayOptions: jest.fn().mockReturnValue( '[DisplayOptions]' ),
} ) );

jest.mock( '../highlight-tooltip', () => ( {
	HighlightTooltip: jest.fn().mockReturnValue( '[HighlightTooltip]' ),
} ) );

describe( 'Activity Panel', () => {
	it( 'should render inbox tab on embedded pages', () => {
		render( <ActivityPanel isEmbedded query={ {} } /> );

		expect( screen.getByText( 'Inbox' ) ).toBeDefined();
	} );

	it( 'should render inbox tab if not on home screen', () => {
		render(
			<ActivityPanel
				getHistory={ () => ( {
					location: {
						pathname: '/customers',
					},
				} ) }
				query={ {} }
			/>
		);

		expect( screen.getByText( 'Inbox' ) ).toBeDefined();
	} );

	it( 'should not render inbox tab on home screen', () => {
		render( <ActivityPanel query={ {} } /> );

		expect( screen.queryByText( 'Inbox' ) ).toBeNull();
	} );

	it( 'should not render help tab if not on home screen', () => {
		render(
			<ActivityPanel
				getHistory={ () => ( {
					location: {
						pathname: '/customers',
					},
				} ) }
				query={ {} }
			/>
		);

		expect( screen.queryByText( 'Help' ) ).toBeNull();
	} );

	it( 'should render help tab if on home screen', () => {
		render(
			<ActivityPanel
				getHistory={ () => ( {
					location: {
						pathname: '/',
					},
				} ) }
				query={ {} }
			/>
		);

		expect( screen.getByText( 'Help' ) ).toBeDefined();
	} );

	it( 'should render help tab before options load', async () => {
		render(
			<ActivityPanel
				requestingTaskListOptions
				query={ {
					task: 'products',
				} }
			/>
		);

		const tabs = await screen.findAllByRole( 'tab' );

		// Expect that the only tab is "Help".
		expect( tabs ).toHaveLength( 1 );
		expect( screen.getByText( 'Help' ) ).toBeDefined();
	} );

	it( 'should not render help tab when not on main route', () => {
		render(
			<ActivityPanel
				requestingTaskListOptions={ false }
				setupTaskListComplete={ false }
				setupTaskListHidden={ false }
				getHistory={ () => ( {
					location: {
						pathname: '/customers',
					},
				} ) }
				query={ {
					task: 'products',
					path: '/customers',
				} }
			/>
		);

		// Expect that "Help" tab is absent.
		expect( screen.queryByText( 'Help' ) ).toBeNull();
	} );

	it( 'should render display options if on home screen', () => {
		render(
			<ActivityPanel
				getHistory={ () => ( {
					location: {
						pathname: '/',
					},
				} ) }
				query={ {} }
			/>
		);

		expect( screen.getByText( '[DisplayOptions]' ) ).toBeDefined();
	} );

	it( 'should only render the store setup link when TaskList is not complete', () => {
		const { queryByText, rerender } = render(
			<ActivityPanel
				requestingTaskListOptions={ false }
				setupTaskListComplete={ false }
				setupTaskListHidden={ false }
				query={ {
					task: 'products',
				} }
			/>
		);

		expect( queryByText( 'Store Setup' ) ).toBeDefined();

		rerender(
			<ActivityPanel
				requestingTaskListOptions={ false }
				setupTaskListComplete
				setupTaskListHidden={ false }
				query={ {
					task: 'products',
				} }
			/>
		);

		expect( queryByText( 'Store Setup' ) ).toBeNull();
	} );

	it( 'should not render the store setup link when on the home screen and TaskList is not complete', () => {
		const { queryByText } = render(
			<ActivityPanel
				requestingTaskListOptions={ false }
				setupTaskListComplete={ false }
				setupTaskListHidden={ false }
				getHistory={ () => ( {
					location: {
						pathname: '/',
					},
				} ) }
				query={ {
					task: '',
				} }
			/>
		);

		expect( queryByText( 'Store Setup' ) ).toBeNull();
	} );

	it( 'should render the store setup link when on embedded pages and TaskList is not complete', () => {
		const { getByText } = render(
			<ActivityPanel
				requestingTaskListOptions={ false }
				setupTaskListComplete={ false }
				setupTaskListHidden={ false }
				isEmbedded
				query={ {} }
			/>
		);

		expect( getByText( 'Store Setup' ) ).toBeInTheDocument();
	} );

	describe( 'help panel tooltip', () => {
		it( 'should render highlight tooltip when task count is at-least 2, task is not completed, and tooltip not shown yet', () => {
			const { getByText } = render(
				<ActivityPanel
					requestingTaskListOptions={ false }
					setupTaskListComplete={ false }
					setupTaskListHidden={ false }
					userPreferencesData={ {
						task_list_tracked_started_tasks: { payment: 2 },
					} }
					trackedCompletedTasks={ [] }
					helpPanelHighlightShown="no"
					isEmbedded
					query={ { task: 'payment' } }
				/>
			);

			expect( getByText( '[HighlightTooltip]' ) ).toBeInTheDocument();
		} );

		it( 'should not render highlight tooltip when task is not visited more then once', () => {
			render(
				<ActivityPanel
					requestingTaskListOptions={ false }
					setupTaskListComplete={ false }
					setupTaskListHidden={ false }
					userPreferencesData={ {
						task_list_tracked_started_tasks: { payment: 1 },
					} }
					trackedCompletedTasks={ [] }
					isEmbedded
					query={ { task: 'payment' } }
				/>
			);

			expect( screen.queryByText( '[HighlightTooltip]' ) ).toBeNull();

			render(
				<ActivityPanel
					requestingTaskListOptions={ false }
					setupTaskListComplete={ false }
					setupTaskListHidden={ false }
					userPreferencesData={ {
						task_list_tracked_started_tasks: {},
					} }
					trackedCompletedTasks={ [] }
					isEmbedded
					query={ { task: 'payment' } }
				/>
			);

			expect( screen.queryByText( '[HighlightTooltip]' ) ).toBeNull();
		} );

		it( 'should not render highlight tooltip when task is visited twice, but completed already', () => {
			const { queryByText } = render(
				<ActivityPanel
					requestingTaskListOptions={ false }
					setupTaskListComplete={ false }
					setupTaskListHidden={ false }
					userPreferencesData={ {
						task_list_tracked_started_tasks: { payment: 2 },
					} }
					trackedCompletedTasks={ [ 'payment' ] }
					isEmbedded
					query={ { task: 'payment' } }
				/>
			);

			expect( queryByText( '[HighlightTooltip]' ) ).toBeNull();
		} );

		it( 'should not render highlight tooltip when task is visited twice, not completed, but already shown', () => {
			const { queryByText } = render(
				<ActivityPanel
					requestingTaskListOptions={ false }
					setupTaskListComplete={ false }
					setupTaskListHidden={ false }
					userPreferencesData={ {
						task_list_tracked_started_tasks: { payment: 2 },
						help_panel_highlight_shown: 'yes',
					} }
					trackedCompletedTasks={ [] }
					isEmbedded
					query={ { task: 'payment' } }
				/>
			);

			expect( queryByText( '[HighlightTooltip]' ) ).toBeNull();
		} );
	} );
} );
