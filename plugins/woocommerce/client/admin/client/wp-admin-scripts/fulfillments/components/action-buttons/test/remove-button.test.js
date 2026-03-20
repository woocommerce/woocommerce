/**
 * External dependencies
 */
import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
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

jest.mock( '../../../context/drawer-context', () => ( {
	useFulfillmentDrawerContext: jest.fn( () => ( {
		setIsEditing: jest.fn(),
		setOpenSection: jest.fn(),
	} ) ),
} ) );

jest.mock( '@wordpress/components', () => ( {
	Button: ( { onClick, children, disabled, isBusy, ...props } ) => {
		// Filter out custom WordPress props that shouldn't be on DOM elements
		const { variant, __next40pxDefaultSize, ...domProps } = props;
		return (
			<button
				onClick={ onClick }
				disabled={ disabled || isBusy }
				{ ...domProps }
			>
				{ children }
			</button>
		);
	},
	Modal: ( { title, onRequestClose, children } ) => (
		<div role="dialog" aria-labelledby="modal-title">
			<h1 id="modal-title">{ title }</h1>
			{ children }
			<button onClick={ onRequestClose }>Close</button>
		</div>
	),
	ToggleControl: React.forwardRef( ( { checked, onChange }, ref ) => (
		<input
			ref={ ref }
			type="checkbox"
			checked={ checked }
			onChange={ ( e ) => onChange( e.target.checked ) }
		/>
	) ),
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

		await waitFor( () => {
			expect( mockDeleteFulfillment ).toHaveBeenCalledWith(
				123,
				456,
				true
			);
		} );
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
			screen.getByRole( 'button', {
				name: 'Remove fulfillment',
			} )
		);

		await waitFor( () => {
			expect( mockDeleteFulfillment ).toHaveBeenCalledWith(
				123,
				456,
				true
			);
		} );
	} );

	describe( 'Accessibility', () => {
		it( 'should not have redundant aria-label overriding visible text', () => {
			render( <RemoveButton setError={ setError } /> );

			const button = screen.getByRole( 'button' );
			expect( button ).not.toHaveAttribute( 'aria-label' );
		} );

		it( 'should have aria-describedby with unique prefix', () => {
			render( <RemoveButton setError={ setError } /> );

			const button = screen.getByRole( 'button' );
			expect( button.getAttribute( 'aria-describedby' ) ).toMatch(
				/^remove-button-description/
			);
		} );

		it( 'should have hidden description for screen readers', () => {
			render( <RemoveButton setError={ setError } /> );

			const description = screen.getByText(
				'Deletes this fulfillment permanently'
			);
			expect( description ).toBeInTheDocument();
			expect( description.getAttribute( 'id' ) ).toMatch(
				/^remove-button-description/
			);
			expect( description ).toHaveClass( 'screen-reader-text' );
		} );

		it( 'should update button text when executing', () => {
			const mockDeleteFulfillment = jest.fn(
				() => new Promise( ( resolve ) => setTimeout( resolve, 100 ) )
			);
			useDispatch.mockReturnValue( {
				deleteFulfillment: mockDeleteFulfillment,
			} );

			render( <RemoveButton setError={ setError } /> );
			const button = screen.getByRole( 'button' );

			fireEvent.click( button );

			// Check that the button text updates during execution
			expect( screen.getByText( 'Removing…' ) ).toBeInTheDocument();
			expect( button ).toBeDisabled();
		} );

		describe( 'Modal Accessibility', () => {
			beforeEach( () => {
				useFulfillmentContext.mockReturnValue( {
					order: { id: 123 },
					fulfillment: { id: 456, is_fulfilled: true },
					notifyCustomer: true,
				} );
			} );

			it( 'should have proper modal title', () => {
				render( <RemoveButton setError={ setError } /> );
				fireEvent.click( screen.getByText( 'Remove' ) );

				expect(
					screen.getByRole( 'heading', {
						name: 'Remove fulfillment',
					} )
				).toBeInTheDocument();
			} );

			it( 'should have accessible cancel button in modal', () => {
				render( <RemoveButton setError={ setError } /> );
				fireEvent.click( screen.getByText( 'Remove' ) );

				const cancelButton = screen.getByRole( 'button', {
					name: 'Cancel removal and close dialog',
				} );
				expect( cancelButton ).toBeInTheDocument();
				expect( cancelButton ).toHaveAttribute(
					'aria-label',
					'Cancel removal and close dialog'
				);
			} );

			it( 'should have accessible confirm button in modal with visible text', () => {
				render( <RemoveButton setError={ setError } /> );
				fireEvent.click( screen.getByText( 'Remove' ) );

				const confirmButton = screen.getByRole( 'button', {
					name: 'Remove fulfillment',
				} );
				expect( confirmButton ).toBeInTheDocument();
				expect( confirmButton ).not.toHaveAttribute( 'aria-label' );
			} );

			it( 'should update modal button states when executing deletion', async () => {
				const mockDeleteFulfillment = jest.fn(
					() =>
						new Promise( ( resolve ) => setTimeout( resolve, 100 ) )
				);
				useDispatch.mockReturnValue( {
					deleteFulfillment: mockDeleteFulfillment,
				} );

				render( <RemoveButton setError={ setError } /> );
				fireEvent.click( screen.getByText( 'Remove' ) );

				const confirmButton = screen.getByRole( 'button', {
					name: 'Remove fulfillment',
				} );

				fireEvent.click( confirmButton );

				// The button text should update immediately
				expect( screen.getByText( 'Removing…' ) ).toBeInTheDocument();
			} );
		} );

		it( 'should be keyboard accessible', () => {
			render( <RemoveButton setError={ setError } /> );

			const button = screen.getByRole( 'button' );
			button.focus();
			expect( button.ownerDocument.activeElement ).toBe( button );
		} );
	} );
} );
