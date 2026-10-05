import { beforeEach, describe, expect, it, vi } from 'vitest';

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
const setError = vi.fn();

// Mock dependencies
vi.mock( '@wordpress/data', async () => {
	const originalModule = await vi.importActual( '@wordpress/data' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		...originalModule,
		useDispatch: vi.fn( () => {} ),
	} );
} );
vi.mock( '../../../context/fulfillment-context', () => {
	const mock = {
		useFulfillmentContext: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
describe( 'UpdateButton component', () => {
	beforeEach( () => {
		// Reset mocks
		vi.clearAllMocks();

		// Default mock implementations
		useDispatch.mockReturnValue( {
			updateFulfillment: vi.fn(),
		} );
		useFulfillmentContext.mockReturnValue( {
			order: {
				id: 123,
			},
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
			notifyCustomer: true,
			setNotifyCustomer: vi.fn(),
			customerNote: '',
			setCustomerNote: vi.fn(),
		} );
	} );
	it( 'should render button with correct text', () => {
		render( <UpdateButton setError={ setError } /> );
		expect( screen.getByText( 'Update' ) ).toBeInTheDocument();
	} );
	it( 'should call updateFulfillment when button is clicked', async () => {
		const mockUpdateFulfillment = vi.fn( () => Promise.resolve() );
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
			order: {
				id: 123,
			},
			fulfillment: mockFulfillment,
			notifyCustomer: true,
			customerNote: 'Test note',
			setCustomerNote: vi.fn(),
		} );
		render( <UpdateButton setError={ setError } /> );
		fireEvent.click( screen.getByText( 'Update' ) );
		await waitFor( () => {
			expect( mockUpdateFulfillment ).toHaveBeenCalledWith(
				123,
				mockFulfillment,
				true,
				'Test note'
			);
		} );
	} );
	it( 'should pass empty customer note when notifyCustomer is false', async () => {
		const mockUpdateFulfillment = vi.fn( () => Promise.resolve() );
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
					],
				},
			],
		};
		useFulfillmentContext.mockReturnValue( {
			order: {
				id: 123,
			},
			fulfillment: mockFulfillment,
			notifyCustomer: false,
			customerNote: 'This note should not be sent',
			setCustomerNote: vi.fn(),
		} );
		render( <UpdateButton setError={ setError } /> );
		fireEvent.click( screen.getByText( 'Update' ) );
		await waitFor( () => {
			expect( mockUpdateFulfillment ).toHaveBeenCalledWith(
				123,
				mockFulfillment,
				false,
				''
			);
		} );
	} );
	it( 'should not call updateFulfillment when fulfillment is undefined', () => {
		const mockUpdateFulfillment = vi.fn();
		useDispatch.mockReturnValue( {
			updateFulfillment: mockUpdateFulfillment,
		} );
		useFulfillmentContext.mockReturnValue( {
			order: {
				id: 123,
			},
			fulfillment: undefined,
			customerNote: '',
			setCustomerNote: vi.fn(),
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
			const mockUpdateFulfillment = vi.fn(
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
