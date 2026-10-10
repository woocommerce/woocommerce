/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import '../../../test-helper/global-mock';
import NewFulfillmentForm from '../new-fulfillment-form';
import { useFulfillmentDrawerContext } from '../../../context/drawer-context';

jest.mock( '../../../context/drawer-context', () => ( {
	useFulfillmentDrawerContext: jest.fn(),
} ) );
jest.mock( '../../action-buttons/save-draft-button', () => () => (
	<button data-testid="save-draft-button">Save as Draft</button>
) );
jest.mock( '../../action-buttons/fulfill-items-button', () => () => (
	<button data-testid="fulfill-items-button">Fulfill Items</button>
) );
jest.mock( '../../action-buttons/pickup-buttons', () => ( {
	ReadyForPickupButton: () => (
		<button data-testid="ready-for-pickup-button">Ready for pickup</button>
	),
	PickedUpButton: () => (
		<button data-testid="picked-up-button">Mark picked up</button>
	),
} ) );
jest.mock( '../../shipment-form', () => () => (
	<div data-testid="shipment-form" />
) );
jest.mock( '../item-selector', () => () => (
	<div data-testid="item-selector" />
) );
jest.mock( '../../customer-notification-form', () => () => (
	<div data-testid="fulfillment-customer-notification-form" />
) );

jest.mock( '../../../context/fulfillment-context', () => ( {
	FulfillmentProvider: ( { children } ) => (
		<div data-testid="fulfillment-provider">{ children }</div>
	),
	useFulfillmentContext: jest.fn( () => ( {
		order: { id: 1, currency: 'USD', line_items: [] },
		fulfillment: null,
		notifyCustomer: true,
	} ) ),
} ) );

jest.mock( '../../../utils/order-utils', () => ( {
	getItemsNotInAnyFulfillment: jest.fn( () => [] ),
	getOrderPickupLocation: jest.fn( () => null ),
	spreadItems: jest.fn( () => [] ),
} ) );

describe( 'NewFulfillmentForm', () => {
	const mockContext = {
		order: null,
		fulfillments: [],
		openSection: 'order',
	};

	beforeEach( () => {
		jest.clearAllMocks();
		useFulfillmentDrawerContext.mockReturnValue( mockContext );
	} );

	it( 'renders nothing when order is null', () => {
		mockContext.order = null;
		const { container } = render( <NewFulfillmentForm /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders nothing when there are no remaining items', () => {
		mockContext.order = { id: 1, currency: 'USD', line_items: [] };
		require( '../../../utils/order-utils' ).getItemsNotInAnyFulfillment.mockReturnValue(
			[]
		);
		const { container } = render( <NewFulfillmentForm /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders the form when there are remaining items', () => {
		mockContext.order = { id: 1, currency: 'USD', line_items: [] };
		require( '../../../utils/order-utils' ).getItemsNotInAnyFulfillment.mockReturnValue(
			[
				{
					id: 1,
					name: 'Item 1',
					selection: [ { index: 0, checked: true } ],
				},
			]
		);

		render( <NewFulfillmentForm /> );

		expect( screen.getByText( 'Order Items' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'item-selector' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'save-draft-button' ) ).toBeInTheDocument();
		expect(
			screen.getByTestId( 'fulfill-items-button' )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'shipment-form' ) ).toBeInTheDocument();
		expect(
			screen.queryByTestId( 'ready-for-pickup-button' )
		).not.toBeInTheDocument();
	} );

	it( 'renders the pickup steps instead of shipping for a local pickup order', () => {
		mockContext.order = { id: 1, currency: 'USD', line_items: [] };
		const orderUtils = require( '../../../utils/order-utils' );
		orderUtils.getItemsNotInAnyFulfillment.mockReturnValue( [
			{
				id: 1,
				name: 'Item 1',
				selection: [ { index: 0, checked: true } ],
			},
		] );
		orderUtils.getOrderPickupLocation.mockReturnValue( {
			name: 'Main Street store',
			address: '123 Main Street, Austin, TX 78701',
			details: 'Ask at the counter.',
		} );

		render( <NewFulfillmentForm /> );

		expect( screen.getByText( 'Pickup Information' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Main Street store' ) ).toBeInTheDocument();
		expect(
			screen.getByText( '123 Main Street, Austin, TX 78701' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Ask at the counter.' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'save-draft-button' ) ).toBeInTheDocument();
		expect(
			screen.getByTestId( 'ready-for-pickup-button' )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'picked-up-button' ) ).toBeInTheDocument();
		expect(
			screen.queryByTestId( 'fulfill-items-button' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByTestId( 'shipment-form' )
		).not.toBeInTheDocument();
	} );
} );
