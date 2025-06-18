/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import '../../../test-helper/global-mock';
import FulfillItemsButton from '../fulfill-items-button';
import { useFulfillmentContext } from '../../../context/fulfillment-context';

const setError = jest.fn();

// Mock dependencies
jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );
	return {
		...originalModule,
		useDispatch: jest.fn( () => {} ),
	};
} );

jest.mock( '../../../context/fulfillment-context', () => ( {
	useFulfillmentContext: jest.fn(),
} ) );

describe( 'FulfillItemsButton component', () => {
	beforeEach( () => {
		// Reset mocks
		jest.clearAllMocks();

		// Default mock implementations
		useDispatch.mockReturnValue( { saveFulfillment: jest.fn() } );
		useFulfillmentContext.mockReturnValue( {
			order: { id: 123 },
			fulfillment: { id: 456 },
			notifyCustomer: true,
		} );
	} );

	it( 'should render button with correct text', () => {
		render( <FulfillItemsButton setError={ setError } /> );
		expect( screen.getByText( 'Fulfill items' ) ).toBeInTheDocument();
	} );

	it( 'should call saveFulfillment when button is clicked', async () => {
		const mockSaveFulfillment = jest.fn( () => Promise.resolve() );
		useDispatch.mockReturnValue( { saveFulfillment: mockSaveFulfillment } );

		const mockFulfillment = {
			id: 456,
			meta_data: [
				{
					id: 1,
					key: '_items',
					value: [
						{
							id: 1,
							name: 'Item 1',
							quantity: 2,
						},
						{
							id: 2,
							name: 'Item 2',
							quantity: 3,
						},
					],
				},
			],
		};
		useFulfillmentContext.mockReturnValue( {
			order: { id: 123 },
			fulfillment: mockFulfillment,
			notifyCustomer: true,
		} );

		render( <FulfillItemsButton setError={ setError } /> );
		fireEvent.click( screen.getByText( 'Fulfill items' ) );

		expect( mockFulfillment.is_fulfilled ).toBe( true );
		expect( mockFulfillment.status ).toBe( 'fulfilled' );
		expect( await mockSaveFulfillment ).toHaveBeenCalledWith(
			123,
			mockFulfillment,
			true
		);
	} );

	it( 'should not call saveFulfillment when fulfillment is undefined', () => {
		const mockSaveFulfillment = jest.fn();
		useDispatch.mockReturnValue( { saveFulfillment: mockSaveFulfillment } );

		useFulfillmentContext.mockReturnValue( {
			order: { id: 123 },
			fulfillment: undefined,
			notifyCustomer: true,
		} );

		render( <FulfillItemsButton setError={ setError } /> );
		fireEvent.click( screen.getByText( 'Fulfill items' ) );

		expect( mockSaveFulfillment ).not.toHaveBeenCalled();
	} );
} );
