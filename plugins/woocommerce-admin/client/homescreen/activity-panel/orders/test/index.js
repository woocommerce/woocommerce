/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import OrdersPanel from '../';

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

describe( 'OrdersPanel', () => {
	it( 'should render an empty order card', () => {
		useSelect.mockReturnValue( {
			orders: [],
			isError: false,
			isRequesting: false,
		} );
		render( <OrdersPanel orderStatuses={ [] } unreadOrdersCount={ 0 } /> );
		expect(
			screen.queryByText( 'You’ve fulfilled all your orders' )
		).toBeInTheDocument();
	} );
} );
