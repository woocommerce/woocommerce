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
	} );
} );
