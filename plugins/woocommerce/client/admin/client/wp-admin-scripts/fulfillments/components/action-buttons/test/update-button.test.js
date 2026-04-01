/**
 * External dependencies
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
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

		await waitFor( () => {
			expect( mockUpdateFulfillment ).toHaveBeenCalledWith(
				123,
				mockFulfillment,
				true
			);
		} );
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

	describe( 'Accessibility', () => {
		it( 'should not have redundant aria-label overriding visible text', () => {
			render( <UpdateButton setError={ setError } /> );

			const button = screen.getByRole( 'button' );
			expect( button ).not.toHaveAttribute( 'aria-label' );
		} );

		it( 'should have aria-describedby with unique prefix', () => {
			render( <UpdateButton setError={ setError } /> );

			const button = screen.getByRole( 'button' );
			expect( button.getAttribute( 'aria-describedby' ) ).toMatch(
				/^update-button-description/
			);
		} );

		it( 'should have hidden description for screen readers', () => {
			render( <UpdateButton setError={ setError } /> );

			const description = screen.getByText(
				'Applies changes to the existing fulfillment'
			);
			expect( description ).toBeInTheDocument();
			expect( description.getAttribute( 'id' ) ).toMatch(
				/^update-button-description/
			);
			expect( description ).toHaveClass( 'screen-reader-text' );
		} );

		it( 'should update button text when executing', () => {
			const mockUpdateFulfillment = jest.fn(
				() => new Promise( ( resolve ) => setTimeout( resolve, 100 ) )
			);
			useDispatch.mockReturnValue( {
				updateFulfillment: mockUpdateFulfillment,
			} );

			render( <UpdateButton setError={ setError } /> );
			const button = screen.getByRole( 'button' );

			fireEvent.click( button );

			// Check that the button text updates during execution
			expect( screen.getByText( 'Updating…' ) ).toBeInTheDocument();
			expect( button ).toBeDisabled();
		} );

		it( 'should be keyboard accessible', () => {
			render( <UpdateButton setError={ setError } /> );

			const button = screen.getByRole( 'button' );
			button.focus();
			expect( button.ownerDocument.activeElement ).toBe( button );
		} );
	} );
} );
