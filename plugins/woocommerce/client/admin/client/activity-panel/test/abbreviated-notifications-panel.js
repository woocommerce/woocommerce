/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { activityPanelStore, useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { AbbreviatedNotificationsPanel } from '../panels/inbox/abbreviated-notifications-panel';
import { isTaskListVisible } from '~/hooks/use-tasklists-state';

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUser: jest.fn().mockReturnValue( {
		currentUserCan: () => true,
	} ),
} ) );

jest.mock( '~/hooks/use-tasklists-state', () => ( {
	isTaskListVisible: jest.fn(),
} ) );

describe( 'Inbox', () => {
	beforeEach( () => {
		useSelect.mockReturnValue( {} );
		useUser.mockReturnValue( {
			currentUserCan: () => true,
		} );
		isTaskListVisible.mockReturnValue( false );
	} );

	it( 'does not show any abbreviated notifications', () => {
		const { queryByText } = render(
			<AbbreviatedNotificationsPanel thingsToDoNextCount={ 0 } />
		);
		expect( queryByText( 'Things to do next' ) ).toBeNull();
		expect( queryByText( 'Orders to fulfill' ) ).toBeNull();
		expect( queryByText( 'Reviews to moderate' ) ).toBeNull();
		expect( queryByText( 'Inventory to review' ) ).toBeNull();
	} );
	it( 'does not show any abbreviated panel when the extended task list is hidden and the setup list is visible', () => {
		isTaskListVisible.mockImplementation( ( id ) => id === 'setup' );
		useSelect.mockImplementation( () => ( {
			stockNoticesCount: 4,
			reviewsToModerateCount: 3,
			ordersToProcessCount: 2,
		} ) );
		const { queryByText } = render(
			<AbbreviatedNotificationsPanel thingsToDoNextCount={ 1 } />
		);
		expect( queryByText( 'Things to do next' ) ).toBeNull();
		expect( queryByText( 'Orders to fulfill' ) ).toBeNull();
		expect( queryByText( 'Reviews to moderate' ) ).toBeNull();
		expect( queryByText( 'Inventory to review' ) ).toBeNull();
	} );
	it( 'shows the `Things to do next` notification panel, with 1 thing to do', () => {
		isTaskListVisible.mockImplementation( ( id ) => id === 'extended' );
		const { getByText } = render(
			<AbbreviatedNotificationsPanel thingsToDoNextCount={ 1 } />
		);
		expect( getByText( 'Things to do next' ) ).toBeDefined();
		expect( getByText( 'You have 1 new thing to do' ) ).toBeDefined();
	} );
	it( 'shows plural copy for the `Things to do next` notification panel', () => {
		isTaskListVisible.mockImplementation( ( id ) => id === 'extended' );
		const { getByText } = render(
			<AbbreviatedNotificationsPanel thingsToDoNextCount={ 5 } />
		);
		expect( getByText( 'Things to do next' ) ).toBeDefined();
		expect( getByText( 'You have 5 new things to do' ) ).toBeDefined();
	} );
	it( 'shows the `Orders to fulfill` notification panel, with 2 thing to do', () => {
		useSelect.mockImplementation( () => ( {
			ordersToProcessCount: 2,
		} ) );
		const { getByText } = render(
			<AbbreviatedNotificationsPanel thingsToDoNextCount={ 0 } />
		);
		expect( getByText( 'Orders to fulfill' ) ).toBeDefined();
		expect( getByText( 'You have 2 orders to fulfill' ) ).toBeDefined();
	} );
	it( 'shows the `Reviews to moderate` notification panel, with 3 thing to do', () => {
		useSelect.mockImplementation( () => ( {
			reviewsToModerateCount: 3,
		} ) );
		const { getByText } = render(
			<AbbreviatedNotificationsPanel thingsToDoNextCount={ 0 } />
		);
		expect( getByText( 'Reviews to moderate' ) ).toBeDefined();
		expect( getByText( 'You have 3 reviews to moderate' ) ).toBeDefined();
	} );
	it( 'shows the `Inventory to review` notification panel', () => {
		useSelect.mockImplementation( () => ( {
			stockNoticesCount: 4,
		} ) );
		const { getByText } = render(
			<AbbreviatedNotificationsPanel thingsToDoNextCount={ 0 } />
		);
		expect( getByText( 'Inventory to review' ) ).toBeDefined();
		expect(
			getByText( 'You have inventory to review and update' )
		).toBeDefined();
	} );
	it( 'shows all the abbreviated notification panels', () => {
		isTaskListVisible.mockImplementation( ( id ) => id === 'extended' );
		useSelect.mockImplementation( () => ( {
			stockNoticesCount: 4,
			reviewsToModerateCount: 3,
			ordersToProcessCount: 2,
		} ) );
		const { getByText } = render(
			<AbbreviatedNotificationsPanel thingsToDoNextCount={ 1 } />
		);
		expect( getByText( 'Things to do next' ) ).toBeDefined();
		expect( getByText( 'Orders to fulfill' ) ).toBeDefined();
		expect( getByText( 'Reviews to moderate' ) ).toBeDefined();
		expect( getByText( 'Inventory to review' ) ).toBeDefined();
	} );

	it.each( [
		{
			description:
				'does not request Activity Panel counts without permission',
			canManageWooCommerce: false,
			expectedCalls: 0,
		},
		{
			description: 'requests Activity Panel counts with permission',
			canManageWooCommerce: true,
			expectedCalls: 1,
		},
	] )( '$description', ( { canManageWooCommerce, expectedCalls } ) => {
		const getActivityPanelCounts = jest.fn().mockReturnValue( {} );
		const select = jest.fn( ( store ) => {
			if ( store === activityPanelStore ) {
				return { getActivityPanelCounts };
			}

			return {};
		} );

		useUser.mockReturnValue( {
			currentUserCan: () => canManageWooCommerce,
		} );
		useSelect.mockImplementation( ( mapSelect ) => mapSelect( select ) );

		render( <AbbreviatedNotificationsPanel thingsToDoNextCount={ 0 } /> );

		expect( getActivityPanelCounts ).toHaveBeenCalledTimes( expectedCalls );
	} );
} );
