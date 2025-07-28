/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import '../../../test-helper/global-mock';
import RemoveButton from '../remove-button';
import { useFulfillmentContext } from '../../../context/fulfillment-context';

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

const setError = jest.fn();

describe( 'RemoveButton component', () => {
	beforeEach( () => {
		// Reset mocks
		jest.clearAllMocks();

		// Default mock implementations
		useDispatch.mockReturnValue( {
			deleteFulfillment: jest.fn(),
		} );

		useFulfillmentContext.mockReturnValue( {
			order: { id: 123 },
			fulfillment: { id: 456, is_fulfilled: false },
			notifyCustomer: true,
		} );
	} );

	it( 'should render button with correct text', () => {
		render( <RemoveButton setError={ setError } /> );
		expect( screen.getByText( 'Remove' ) ).toBeInTheDocument();
	} );

	it( 'should not call deleteFulfillment when fulfillment is undefined', () => {
		const mockDeleteFulfillment = jest.fn();
		useDispatch.mockReturnValue( {
			deleteFulfillment: mockDeleteFulfillment,
		} );

		useFulfillmentContext.mockReturnValue( {
			order: { id: 123 },
			fulfillment: undefined,
			notifyCustomer: true,
		} );

		render( <RemoveButton setError={ setError } /> );

		fireEvent.click( screen.getByText( 'Remove' ) );

		expect( mockDeleteFulfillment ).not.toHaveBeenCalled();
	} );

	it( 'should not call deleteFulfillment when fulfillment has no id', () => {
		const mockDeleteFulfillment = jest.fn();
		useDispatch.mockReturnValue( {
			deleteFulfillment: mockDeleteFulfillment,
		} );

		useFulfillmentContext.mockReturnValue( {
			order: { id: 123 },
			fulfillment: {
				/* no id */
				is_fulfilled: false,
			},
			notifyCustomer: true,
		} );

		render( <RemoveButton setError={ setError } /> );
		fireEvent.click( screen.getByText( 'Remove' ) );

		expect( mockDeleteFulfillment ).not.toHaveBeenCalled();
	} );

	it( 'should call deleteFulfillment when button is clicked on unfulfilled fulfillment', async () => {
		const mockDeleteFulfillment = jest.fn( () => Promise.resolve() );
		useDispatch.mockReturnValue( {
			deleteFulfillment: mockDeleteFulfillment,
		} );

		useFulfillmentContext.mockReturnValue( {
			order: { id: 123 },
			fulfillment: { id: 456, is_fulfilled: false },
			notifyCustomer: true,
		} );

		render( <RemoveButton setError={ setError } /> );

		fireEvent.click( screen.getByText( 'Remove' ) );

		expect( await mockDeleteFulfillment ).toHaveBeenCalledWith(
			123,
			456,
			true
		);
	} );

	it( 'should open confirmation modal when button is clicked on fulfilled fulfillment', async () => {
		const mockDeleteFulfillment = jest.fn( () => Promise.resolve() );
		useDispatch.mockReturnValue( {
			deleteFulfillment: mockDeleteFulfillment,
		} );

		useFulfillmentContext.mockReturnValue( {
			order: { id: 123 },
			fulfillment: { id: 456, is_fulfilled: true },
			notifyCustomer: true,
		} );

		render( <RemoveButton setError={ setError } /> );

		fireEvent.click( screen.getByText( 'Remove' ) );

		expect(
			screen.getByText(
				'Are you sure you want to remove this fulfillment?'
			)
		).toBeInTheDocument();

		expect( mockDeleteFulfillment ).not.toHaveBeenCalled();

		// Simulate confirmation
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Remove fulfillment' } )
		);
		expect( await mockDeleteFulfillment ).toHaveBeenCalledWith(
			123,
			456,
			true
		);
	} );
} );
