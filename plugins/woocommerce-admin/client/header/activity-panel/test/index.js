/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ActivityPanel } from '../';

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

	it( 'should render help tab when on single task', async () => {
		render(
			<ActivityPanel
				requestingTaskListOptions={ false }
				taskListComplete={ false }
				taskListHidden={ false }
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
				taskListComplete={ false }
				taskListHidden={ false }
				query={ {
					task: 'products',
					path: '/customers',
				} }
			/>
		);

		// Expect that "Help" tab is absent.
		expect( screen.queryByText( 'Help' ) ).toBeNull();
	} );

	it( 'should not render help tab when TaskList is hidden', () => {
		render(
			<ActivityPanel
				requestingTaskListOptions={ false }
				taskListComplete={ false }
				taskListHidden
				query={ {
					task: 'products',
				} }
			/>
		);

		// Expect that "Help" tab is absent.
		expect( screen.queryByText( 'Help' ) ).toBeNull();
	} );

	it( 'should not render help tab when TaskList is complete', () => {
		render(
			<ActivityPanel
				requestingTaskListOptions={ false }
				taskListComplete
				taskListHidden={ false }
				query={ {
					task: 'products',
				} }
			/>
		);

		// Expect that "Help" tab is absent.
		expect( screen.queryByText( 'Help' ) ).toBeNull();
	} );

	it( 'should only render the store setup link when TaskList is not complete', () => {
		const { queryByText, rerender } = render(
			<ActivityPanel
				requestingTaskListOptions={ false }
				taskListComplete={ false }
				taskListHidden={ false }
				query={ {
					task: 'products',
				} }
			/>
		);

		expect( queryByText( 'Store Setup' ) ).toBeDefined();

		rerender(
			<ActivityPanel
				requestingTaskListOptions={ false }
				taskListComplete
				taskListHidden={ false }
				query={ {
					task: 'products',
				} }
			/>
		);

		expect( queryByText( 'Store Setup' ) ).toBeNull();
	} );
} );
