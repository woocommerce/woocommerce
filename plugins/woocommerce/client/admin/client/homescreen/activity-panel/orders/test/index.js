import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen, act } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import OrdersPanel from '../';
vi.mock( '@wordpress/data', async () => {
	// Require the original module to not be mocked...
	const originalModule = await vi.importActual( '@wordpress/data' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		__esModule: true,
		// Use it when dealing with esModules
		...originalModule,
		useSelect: vi.fn().mockReturnValue( {} ),
	} );
} );
vi.mock( '~/utils/admin-settings', async () => {
	const mock = {
		...( await vi.importActual( '~/utils/admin-settings' ) ),
		getAdminSetting: vi.fn().mockReturnValue( {
			currencySymbols: {
				EUR: '&euro;',
				USD: '&#36;',
			},
		} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/settings', async () => {
	const mock = {
		...( await vi.importActual( '@woocommerce/settings' ) ),
		getAdminLink: vi.fn().mockReturnValue( '' ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
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
	it( 'should record activity_panel_orders_orders_begin_fulfillment Tracks event when order is clicked', async () => {
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
		await act( async () => {
			userEvent.click( getByText( '0 products' ) );
		} );
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
				[
					5,
					{
						id: 5,
						user_id: 5,
						name: 'Registered Customer',
					},
				],
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
