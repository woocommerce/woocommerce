/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
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
			fulfillment: { id: 456 },
		} );
	} );

	it( 'should render button with correct text', () => {
		render( <RemoveButton setError={ setError } /> );
		expect( screen.getByText( 'Remove' ) ).toBeInTheDocument();
	} );

	it( 'should call deleteFulfillment when button is clicked', async () => {
		const mockDeleteFulfillment = jest.fn( () => Promise.resolve() );
		useDispatch.mockReturnValue( {
			deleteFulfillment: mockDeleteFulfillment,
		} );

		useFulfillmentContext.mockReturnValue( {
			order: { id: 123 },
			fulfillment: { id: 456 },
		} );

		render( <RemoveButton setError={ setError } /> );

		fireEvent.click( screen.getByText( 'Remove' ) );

		expect( await mockDeleteFulfillment ).toHaveBeenCalledWith( 123, 456 );
	} );

	it( 'should not call deleteFulfillment when fulfillment is undefined', () => {
		const mockDeleteFulfillment = jest.fn();
		useDispatch.mockReturnValue( {
			deleteFulfillment: mockDeleteFulfillment,
		} );

		useFulfillmentContext.mockReturnValue( {
			order: { id: 123 },
			fulfillment: undefined,
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
			},
		} );

		render( <RemoveButton setError={ setError } /> );
		fireEvent.click( screen.getByText( 'Remove' ) );

		expect( mockDeleteFulfillment ).not.toHaveBeenCalled();
	} );
} );
