import { beforeEach, describe, expect, it, vi } from 'vitest';

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
vi.mock( '../../../context/drawer-context', () => {
	const mock = {
		useFulfillmentDrawerContext: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../action-buttons/save-draft-button', () => {
	const mock = () => (
		<button data-testid="save-draft-button">Save as Draft</button>
	);
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '../../action-buttons/fulfill-items-button', () => {
	const mock = () => (
		<button data-testid="fulfill-items-button">Fulfill Items</button>
	);
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '../item-selector', () => {
	const mock = () => <div data-testid="item-selector" />;
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '../../customer-notification-form', () => {
	const mock = () => (
		<div data-testid="fulfillment-customer-notification-form" />
	);
	return {
		default: mock,
		...mock,
	};
} );
vi.mock( '../../../context/fulfillment-context', () => {
	const mock = {
		FulfillmentProvider: ( { children } ) => (
			<div data-testid="fulfillment-provider">{ children }</div>
		),
		useFulfillmentContext: vi.fn( () => ( {
			order: {
				id: 1,
				currency: 'USD',
				line_items: [],
			},
			fulfillment: null,
			notifyCustomer: true,
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../../utils/order-utils', () => {
	const mock = {
		getItemsNotInAnyFulfillment: vi.fn( () => [] ),
		spreadItems: vi.fn( () => [] ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'NewFulfillmentForm', () => {
	const mockContext = {
		order: null,
		fulfillments: [],
		openSection: 'order',
	};
	beforeEach( () => {
		vi.clearAllMocks();
		useFulfillmentDrawerContext.mockReturnValue( mockContext );
	} );
	it( 'renders nothing when order is null', () => {
		mockContext.order = null;
		const { container } = render( <NewFulfillmentForm /> );
		expect( container.firstChild ).toBeNull();
	} );
	it( 'renders nothing when there are no remaining items', async () => {
		mockContext.order = {
			id: 1,
			currency: 'USD',
			line_items: [],
		};
		(
			await import( '../../../utils/order-utils' )
		).getItemsNotInAnyFulfillment.mockReturnValue( [] );
		const { container } = render( <NewFulfillmentForm /> );
		expect( container.firstChild ).toBeNull();
	} );
	it( 'renders the form when there are remaining items', async () => {
		mockContext.order = {
			id: 1,
			currency: 'USD',
			line_items: [],
		};
		(
			await import( '../../../utils/order-utils' )
		).getItemsNotInAnyFulfillment.mockReturnValue( [
			{
				id: 1,
				name: 'Item 1',
				selection: [
					{
						index: 0,
						checked: true,
					},
				],
			},
		] );
		render( <NewFulfillmentForm /> );
		expect( screen.getByText( 'Order Items' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'item-selector' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'save-draft-button' ) ).toBeInTheDocument();
		expect(
			screen.getByTestId( 'fulfill-items-button' )
		).toBeInTheDocument();
	} );
} );
