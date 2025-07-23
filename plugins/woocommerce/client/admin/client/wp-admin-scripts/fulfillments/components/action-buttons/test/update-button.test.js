/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import '../../../test-helper/global-mock';
import UpdateButton from '../update-button';
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

describe( 'UpdateButton component', () => {
	beforeEach( () => {
		// Reset mocks
		jest.clearAllMocks();

		// Default mock implementations
		useDispatch.mockReturnValue( { updateFulfillment: jest.fn() } );
		useFulfillmentContext.mockReturnValue( {
			order: { id: 123 },
			fulfillment: {
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
			},
		} );
	} );

	it( 'should render button with correct text', () => {
		render( <UpdateButton setError={ setError } /> );
		expect( screen.getByText( 'Update' ) ).toBeInTheDocument();
	} );

	it( 'should call updateFulfillment when button is clicked', async () => {
		const mockUpdateFulfillment = jest.fn( () => Promise.resolve() );
		useDispatch.mockReturnValue( {
			updateFulfillment: mockUpdateFulfillment,
		} );

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

		render( <UpdateButton setError={ setError } /> );
		fireEvent.click( screen.getByText( 'Update' ) );

		expect( await mockUpdateFulfillment ).toHaveBeenCalledWith(
			123,
			mockFulfillment,
			true
		);
	} );

	it( 'should not call updateFulfillment when fulfillment is undefined', () => {
		const mockUpdateFulfillment = jest.fn();
		useDispatch.mockReturnValue( {
			updateFulfillment: mockUpdateFulfillment,
		} );

		useFulfillmentContext.mockReturnValue( {
			order: { id: 123 },
			fulfillment: undefined,
		} );

		render( <UpdateButton setError={ setError } /> );
		fireEvent.click( screen.getByText( 'Update' ) );

		expect( mockUpdateFulfillment ).not.toHaveBeenCalled();
	} );
} );
