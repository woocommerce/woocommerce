/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useSelect } from '@wordpress/data';
import {
	activityPanelStore,
	ordersStore,
	productsStore,
	useUser,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { ActivityPanel } from '../';
import { getAllPanels } from '../panels';

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true,
		...originalModule,
		useSelect: jest.fn().mockReturnValue( {
			isTaskListHidden: false,
		} ),
	};
} );

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUser: jest.fn().mockReturnValue( {
		currentUserCan: () => true,
	} ),
} ) );

// Mock the panels.
jest.mock( '../panels', () => {
	return {
		getAllPanels: jest.fn().mockImplementation( () => [
			{
				id: 'custom-panel-1',
				title: 'custom-panel-1',
				count: 10000,
				initialOpen: true,
				panel: <span>Custom panel 1</span>,
				collapsible: true,
			},
			{
				id: 'custom-panel-2',
				title: 'custom-panel-2',
				count: 20000,
				initialOpen: false,
				panel: <span>Custom panel 2</span>,
				collapsible: true,
			},
		] ),
	};
} );

// Mock the order statuses.
jest.mock( '../orders/utils', () => {
	return {
		getOrderStatuses: jest.fn().mockImplementation( () => [ 'status' ] ),
	};
} );

describe( 'ActivityPanel', () => {
	beforeEach( () => {
		getAllPanels.mockClear();
		useSelect.mockReturnValue( {
			isTaskListHidden: false,
		} );
		useUser.mockReturnValue( {
			currentUserCan: () => true,
		} );
	} );

	it( 'should render a panel with two rows', () => {
		render( <ActivityPanel /> );
		expect( screen.getByText( 'custom-panel-1' ) ).not.toBeNull();
		expect( screen.getByText( 'custom-panel-2' ) ).not.toBeNull();
	} );

	it( 'should render one visible panel and one hidden panel', () => {
		render( <ActivityPanel /> );
		expect( screen.queryByText( 'Custom panel 1' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Custom panel 2' )
		).not.toBeInTheDocument();
	} );

	it( 'should render the count of unread items', () => {
		render( <ActivityPanel /> );
		expect( screen.queryByText( '10000' ) ).toBeInTheDocument();
		expect( screen.queryByText( '20000' ) ).toBeInTheDocument();
	} );

	it( 'should not render panels when loadingOrderAndProductCount is true', () => {
		useSelect.mockReturnValue( {
			isTaskListHidden: false,
			loadingOrderAndProductCount: true,
		} );
		render( <ActivityPanel /> );
		expect( screen.queryByText( 'custom-panel-1' ) ).toBeNull();
		expect( screen.queryByText( 'custom-panel-2' ) ).toBeNull();
	} );

	it( 'should record activity_panel_open Tracks event when panel is opened', async () => {
		useSelect.mockReturnValue( {
			isTaskListHidden: false,
		} );
		const { getByText } = render( <ActivityPanel /> );
		await userEvent.click( getByText( 'custom-panel-2' ) );
		expect( recordEvent ).toHaveBeenCalledWith( 'activity_panel_open', {
			tab: 'custom-panel-2',
		} );
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

			if ( store === ordersStore ) {
				return {
					getOrdersTotalCount: jest.fn().mockReturnValue( 0 ),
					hasFinishedResolution: jest.fn().mockReturnValue( true ),
				};
			}

			if ( store === productsStore ) {
				return {
					getProductsTotalCount: jest.fn().mockReturnValue( 0 ),
					hasFinishedResolution: jest.fn().mockReturnValue( true ),
				};
			}

			return {};
		} );

		useUser.mockReturnValue( {
			currentUserCan: () => canManageWooCommerce,
		} );
		useSelect.mockImplementation( ( mapSelect ) => mapSelect( select ) );

		render( <ActivityPanel /> );

		expect( getActivityPanelCounts ).toHaveBeenCalledTimes( expectedCalls );
	} );

	it.each( [
		{
			description: 'an order manager only requests the orders count',
			capabilities: [ 'read_private_shop_orders' ],
			expectedCountsCalls: 0,
			expectsManageReviews: false,
			expectsOrders: true,
			expectsProducts: false,
			expectsUpdateStock: false,
		},
		{
			description: 'a manage-only role only requests the panel counts',
			capabilities: [ 'manage_woocommerce' ],
			expectedCountsCalls: 1,
			expectsManageReviews: false,
			expectsOrders: false,
			expectsProducts: false,
			expectsUpdateStock: false,
		},
		{
			description:
				'an order manager with product read access still skips products, whose panels need the counts',
			capabilities: [
				'read_private_shop_orders',
				'read_private_products',
			],
			expectedCountsCalls: 0,
			expectsManageReviews: false,
			expectsOrders: true,
			expectsProducts: false,
			expectsUpdateStock: false,
		},
		{
			description:
				'a manage role with product read access skips products when it cannot use their panels',
			capabilities: [ 'manage_woocommerce', 'read_private_products' ],
			expectedCountsCalls: 1,
			expectsManageReviews: false,
			expectsOrders: false,
			expectsProducts: false,
			expectsUpdateStock: false,
		},
		{
			description:
				'a review manager requests counts and products without order access',
			capabilities: [
				'manage_woocommerce',
				'read_private_products',
				'moderate_comments',
				'edit_products',
			],
			expectedCountsCalls: 1,
			expectsManageReviews: true,
			expectsOrders: false,
			expectsProducts: true,
			expectsUpdateStock: false,
		},
		{
			description:
				'a stock manager requests counts, orders, and products',
			capabilities: [
				'manage_woocommerce',
				'read_private_shop_orders',
				'read_private_products',
				'edit_product',
				'edit_others_products',
				'edit_published_products',
			],
			expectedCountsCalls: 1,
			expectsManageReviews: false,
			expectsOrders: true,
			expectsProducts: true,
			expectsUpdateStock: true,
		},
		{
			description:
				'a stock manager without variation edit access skips products',
			capabilities: [
				'manage_woocommerce',
				'read_private_shop_orders',
				'read_private_products',
				'edit_others_products',
				'edit_published_products',
			],
			expectedCountsCalls: 1,
			expectsManageReviews: false,
			expectsOrders: true,
			expectsProducts: false,
			expectsUpdateStock: false,
		},
		{
			description: 'a full merchant role requests everything',
			capabilities: [
				'manage_woocommerce',
				'read_private_shop_orders',
				'read_private_products',
				'moderate_comments',
				'edit_products',
				'edit_product',
				'edit_others_products',
				'edit_published_products',
			],
			expectedCountsCalls: 1,
			expectsManageReviews: true,
			expectsOrders: true,
			expectsProducts: true,
			expectsUpdateStock: true,
		},
	] )(
		'$description',
		( {
			capabilities,
			expectedCountsCalls,
			expectsManageReviews,
			expectsOrders,
			expectsProducts,
			expectsUpdateStock,
		} ) => {
			const getActivityPanelCounts = jest.fn().mockReturnValue( {} );
			const getOrdersTotalCount = jest.fn().mockReturnValue( 1 );
			const getProductsTotalCount = jest.fn().mockReturnValue( 0 );
			const select = jest.fn( ( store ) => {
				if ( store === activityPanelStore ) {
					return { getActivityPanelCounts };
				}

				if ( store === ordersStore ) {
					return {
						getOrdersTotalCount,
						hasFinishedResolution: jest
							.fn()
							.mockReturnValue( true ),
					};
				}

				if ( store === productsStore ) {
					return {
						getProductsTotalCount,
						hasFinishedResolution: jest
							.fn()
							.mockReturnValue( true ),
					};
				}

				return {};
			} );

			useUser.mockReturnValue( {
				currentUserCan: ( capability ) =>
					capabilities.includes( capability ),
			} );
			useSelect.mockImplementation( ( mapSelect ) =>
				mapSelect( select )
			);

			render( <ActivityPanel /> );

			expect( getActivityPanelCounts ).toHaveBeenCalledTimes(
				expectedCountsCalls
			);
			expect( getOrdersTotalCount.mock.calls.length > 0 ).toBe(
				expectsOrders
			);
			expect( getProductsTotalCount.mock.calls.length > 0 ).toBe(
				expectsProducts
			);
			expect( getAllPanels ).toHaveBeenCalledWith(
				expect.objectContaining( {
					canManageReviews: expectsManageReviews,
					canUpdateStock: expectsUpdateStock,
				} )
			);
		}
	);
} );
