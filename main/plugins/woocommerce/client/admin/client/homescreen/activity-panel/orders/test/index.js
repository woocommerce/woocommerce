/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

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

jest.mock( '~/utils/admin-settings', () => ( {
	...jest.requireActual( '~/utils/admin-settings' ),
	getAdminSetting: jest.fn().mockReturnValue( {
		currencySymbols: {
			EUR: '&euro;',
			USD: '&#36;',
		},
	} ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getAdminLink: jest.fn().mockReturnValue( '' ),
} ) );

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

	it( 'should record activity_panel_orders_orders_begin_fulfillment Tracks event when order is clicked', () => {
		useSelect.mockReturnValue( {
			orders: [
				{
					total: 123,
					id: 1,
					number: 1,
				},
			],
			isError: false,
			isRequesting: false,
		} );
		const { getByText } = render(
			<OrdersPanel orderStatuses={ [] } unreadOrdersCount={ 1 } />
		);
		userEvent.click( getByText( '0 products' ) );
		expect( recordEvent ).toHaveBeenCalledWith(
			'activity_panel_orders_orders_begin_fulfillment',
			{}
		);
	} );

	it( 'should format order total correctly with the same currency as store currency', () => {
		useSelect.mockReturnValue( {
			orders: [
				{
					total: 123,
					id: 1,
					number: 1,
					currency: 'USD',
				},
			],
			isError: false,
			isRequesting: false,
		} );
		const { getByText } = render(
			<OrdersPanel orderStatuses={ [] } unreadOrdersCount={ 1 } />
		);
		expect( getByText( '$123.00' ) ).toBeInTheDocument();
	} );

	it( 'should show order total correctly with a different currency from store currency', () => {
		useSelect.mockReturnValue( {
			orders: [
				{
					total: 123,
					id: 1,
					number: 1,
					currency: 'EUR',
				},
			],
			isError: false,
			isRequesting: false,
		} );

		const { getByText } = render(
			<OrdersPanel orderStatuses={ [] } unreadOrdersCount={ 1 } />
		);
		expect( getByText( '€123.00' ) ).toBeInTheDocument();
	} );

	it( 'should show order total correctly with a currency not in currencySymbols', () => {
		useSelect.mockReturnValue( {
			orders: [
				{
					total: 123,
					id: 1,
					number: 1,
					currency: 'BTC',
				},
			],
			isError: false,
			isRequesting: false,
		} );

		const { getByText } = render(
			<OrdersPanel orderStatuses={ [] } unreadOrdersCount={ 1 } />
		);
		expect( getByText( 'BTC123' ) ).toBeInTheDocument();
	} );

	it( 'should show the billing name for a guest order', () => {
		useSelect.mockReturnValue( {
			orders: [
				{
					total: 123,
					id: 1,
					number: 1,
					customer_id: 0,
					billing: {
						first_name: 'Jane',
						last_name: 'Doe',
					},
				},
			],
			isError: false,
			isRequesting: false,
		} );

		render( <OrdersPanel orderStatuses={ [] } unreadOrdersCount={ 1 } /> );
		expect( screen.getByText( 'Jane Doe' ) ).toBeInTheDocument();
	} );

	it( 'should show no name for a guest order with a blank billing name', () => {
		useSelect.mockReturnValue( {
			orders: [
				{
					total: 123,
					id: 1,
					number: 1,
					customer_id: 0,
					billing: {
						first_name: '',
						last_name: '',
					},
				},
			],
			isError: false,
			isRequesting: false,
		} );

		render( <OrdersPanel orderStatuses={ [] } unreadOrdersCount={ 1 } /> );
		expect( screen.getByText( 'Order #1' ) ).toBeInTheDocument();
	} );

	it( 'should prefer the registered customer name over the billing name', () => {
		useSelect.mockReturnValue( {
			orders: [
				{
					total: 123,
					id: 1,
					number: 1,
					customer_id: 5,
					billing: {
						first_name: 'Jane',
						last_name: 'Doe',
					},
				},
			],
			customerItems: new Map( [
				[ 5, { id: 5, user_id: 5, name: 'Registered Customer' } ],
			] ),
			isError: false,
			isRequesting: false,
		} );

		render( <OrdersPanel orderStatuses={ [] } unreadOrdersCount={ 1 } /> );
		expect( screen.getByText( 'Registered Customer' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Jane Doe' ) ).not.toBeInTheDocument();
	} );

	it( 'should strip angle brackets from a guest billing name', () => {
		useSelect.mockReturnValue( {
			orders: [
				{
					total: 123,
					id: 1,
					number: 1,
					customer_id: 0,
					billing: {
						first_name: '<b>script</b>',
						last_name: 'Doe',
					},
				},
			],
			isError: false,
			isRequesting: false,
		} );

		render( <OrdersPanel orderStatuses={ [] } unreadOrdersCount={ 1 } /> );
		expect( screen.getByText( 'bscript/b Doe' ) ).toBeInTheDocument();
		expect( screen.queryByText( /</ ) ).not.toBeInTheDocument();
	} );
} );
