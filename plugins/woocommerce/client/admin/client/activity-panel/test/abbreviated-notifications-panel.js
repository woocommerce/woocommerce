/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { AbbreviatedNotificationsPanel } from '../panels/inbox/abbreviated-notifications-panel';

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

describe( 'Inbox', () => {
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
		useSelect.mockImplementation( () => ( {
			stockNoticesCount: 4,
			reviewsToModerateCount: 3,
			ordersToProcessCount: 2,
			isExtendedTaskListHidden: true,
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
		useSelect.mockImplementation( () => ( {
			isExtendedTaskListHidden: false,
		} ) );
		const { getByText } = render(
			<AbbreviatedNotificationsPanel thingsToDoNextCount={ 1 } />
		);
		expect( getByText( 'Things to do next' ) ).toBeDefined();
		expect( getByText( 'You have 1 new thing to do' ) ).toBeDefined();
	} );
	it( 'shows plural copy for the `Things to do next` notification panel', () => {
		const { getByText } = render(
			<AbbreviatedNotificationsPanel thingsToDoNextCount={ 5 } />
		);
		expect( getByText( 'Things to do next' ) ).toBeDefined();
		expect( getByText( 'You have 5 new things to do' ) ).toBeDefined();
	} );
	it( 'shows the `Orders to fulfill` notification panel, with 2 thing to do', () => {
		useSelect.mockImplementation( () => ( {
			ordersToProcessCount: 2,
			isSetupTaskListHidden: true,
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
			isSetupTaskListHidden: true,
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
			isSetupTaskListHidden: true,
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
		useSelect.mockImplementation( () => ( {
			stockNoticesCount: 4,
			reviewsToModerateCount: 3,
			ordersToProcessCount: 2,
			isSetupTaskListHidden: true,
		} ) );
		const { getByText } = render(
			<AbbreviatedNotificationsPanel thingsToDoNextCount={ 1 } />
		);
		expect( getByText( 'Things to do next' ) ).toBeDefined();
		expect( getByText( 'Orders to fulfill' ) ).toBeDefined();
		expect( getByText( 'Reviews to moderate' ) ).toBeDefined();
		expect( getByText( 'Inventory to review' ) ).toBeDefined();
	} );
} );
