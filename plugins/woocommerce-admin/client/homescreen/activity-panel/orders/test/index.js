/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import OrdersPanel from '../';

describe( 'OrdersPanel', () => {
	it( 'should render an empty order card', () => {
		render(
			<OrdersPanel
				countUnreadOrders={ 0 }
				isError={ false }
				isRequesting={ false }
				orderStatuses={ [] }
			/>
		);
		expect(
			screen.queryByText( 'You’ve fulfilled all your orders' )
		).toBeInTheDocument();
	} );
} );
