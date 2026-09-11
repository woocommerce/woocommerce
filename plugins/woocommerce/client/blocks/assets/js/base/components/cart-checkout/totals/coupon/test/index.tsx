/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { dispatch, select } from '@wordpress/data';
import { checkoutStore, validationStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { TotalsCoupon } from '..';

const ERROR_ID = 'wc-block-components-totals-coupon__error-coupon';

describe( 'TotalsCoupon', () => {
	describe( 'Rejected coupons', () => {
		it( 'shows the message from a rejected submit and keeps the validation store empty', async () => {
			const user = userEvent.setup();
			const message =
				'Coupon code "5fixedcheckout" has already been applied.';
			const mockOnSubmit = jest
				.fn()
				.mockRejectedValue( new Error( message ) );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			const couponInput = screen.getByLabelText( 'Enter code' );

			await act( async () => {
				await user.type( couponInput, '5fixedcheckout' );
			} );
			await act( async () => {
				await user.click(
					screen.getByRole( 'button', { name: 'Apply' } )
				);
			} );

			expect( mockOnSubmit ).toHaveBeenCalledWith( '5fixedcheckout' );
			expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
				message
			);

			// The input points at the message so assistive tech can read it.
			expect( document.getElementById( ERROR_ID ) ).toHaveTextContent(
				message
			);
			expect( couponInput ).toHaveAttribute( 'aria-invalid', 'true' );
			expect( couponInput ).toHaveAttribute(
				'aria-describedby',
				ERROR_ID
			);
			expect( couponInput ).toHaveAttribute(
				'aria-errormessage',
				ERROR_ID
			);
			expect(
				couponInput.closest( '.wc-block-components-text-input' )
			).toHaveClass( 'has-error' );

			// The shopper can correct the code without retyping it.
			expect( couponInput ).toHaveValue( '5fixedcheckout' );
			expect( couponInput ).toHaveFocus();

			// The failure must not enter the store that blocks checkout.
			expect( select( validationStore ).hasValidationErrors() ).toBe(
				false
			);
		} );

		it.each( [
			'Usage limit for coupon "limited_coupon" has been reached.',
			'Coupons are disabled.',
			'"nope" is an invalid coupon code.',
		] )( 'shows the server message "%s"', async ( message ) => {
			const user = userEvent.setup();
			const mockOnSubmit = jest
				.fn()
				.mockRejectedValue( new Error( message ) );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			await act( async () => {
				await user.type( screen.getByLabelText( 'Enter code' ), 'x' );
			} );
			await act( async () => {
				await user.click(
					screen.getByRole( 'button', { name: 'Apply' } )
				);
			} );

			expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
				message
			);
		} );

		it( 'clears the message when the code is edited', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest
				.fn()
				.mockRejectedValue( new Error( 'Invalid coupon code' ) );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			const couponInput = screen.getByLabelText( 'Enter code' );

			await act( async () => {
				await user.type( couponInput, 'bad' );
			} );
			await act( async () => {
				await user.click(
					screen.getByRole( 'button', { name: 'Apply' } )
				);
			} );
			expect( await screen.findByRole( 'alert' ) ).toBeInTheDocument();

			await act( async () => {
				await user.type( couponInput, '2' );
			} );

			expect( screen.queryByRole( 'alert' ) ).not.toBeInTheDocument();
			expect( couponInput ).toHaveAttribute( 'aria-invalid', 'false' );
			expect( couponInput ).not.toHaveAttribute( 'aria-describedby' );
		} );

		it( 'clears the message when a later submit succeeds', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest
				.fn()
				.mockRejectedValueOnce( new Error( 'Invalid coupon code' ) )
				.mockResolvedValueOnce( true );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			await act( async () => {
				await user.type( screen.getByLabelText( 'Enter code' ), 'bad' );
			} );
			await act( async () => {
				await user.click(
					screen.getByRole( 'button', { name: 'Apply' } )
				);
			} );
			expect( await screen.findByRole( 'alert' ) ).toBeInTheDocument();

			await act( async () => {
				await user.click(
					screen.getByRole( 'button', { name: 'Apply' } )
				);
			} );

			await waitFor( () => {
				expect(
					screen.queryByLabelText( 'Enter code' )
				).not.toBeInTheDocument();
			} );
			expect( screen.queryByRole( 'alert' ) ).not.toBeInTheDocument();
		} );

		it( 'clears the message when the checkout leaves the idle state', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest
				.fn()
				.mockRejectedValue( new Error( 'Invalid coupon code' ) );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			await act( async () => {
				await user.type( screen.getByLabelText( 'Enter code' ), 'bad' );
			} );
			await act( async () => {
				await user.click(
					screen.getByRole( 'button', { name: 'Apply' } )
				);
			} );
			expect( await screen.findByRole( 'alert' ) ).toBeInTheDocument();

			// Placing the order moves the checkout out of idle.
			act( () => {
				dispatch( checkoutStore ).__internalSetProcessing();
			} );

			await waitFor( () => {
				expect( screen.queryByRole( 'alert' ) ).not.toBeInTheDocument();
			} );

			act( () => {
				dispatch( checkoutStore ).__internalSetIdle();
			} );
		} );
	} );

	describe( 'API Response Scenarios', () => {
		it( 'handles successful coupon application', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest.fn().mockResolvedValue( true );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			// Find the coupon input and apply button
			const couponInput = screen.getByLabelText( 'Enter code' );
			const applyButton = screen.getByRole( 'button', { name: 'Apply' } );

			// Enter a coupon code
			await act( async () => {
				await user.type( couponInput, '5fixedcheckout' );
			} );

			// Submit the coupon
			await act( async () => {
				await user.click( applyButton );
			} );

			// Verify the API was called with the correct coupon code
			expect( mockOnSubmit ).toHaveBeenCalledWith( '5fixedcheckout' );

			// Wait for the success flow to complete
			await waitFor( () => {
				// Input should be cleared on success and form should be hidden
				expect(
					screen.queryByLabelText( 'Enter code' )
				).not.toBeInTheDocument();
			} );
		} );

		it( 'handles coupon application failure with focus on input', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest.fn().mockResolvedValue( false );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			const couponInput = screen.getByLabelText( 'Enter code' );
			const applyButton = screen.getByRole( 'button', { name: 'Apply' } );

			// Enter an invalid coupon code
			await act( async () => {
				await user.type( couponInput, 'invalid_coupon' );
			} );

			// Submit the coupon
			await act( async () => {
				await user.click( applyButton );
			} );

			// Verify the API was called
			expect( mockOnSubmit ).toHaveBeenCalledWith( 'invalid_coupon' );

			// Wait for the failure flow to complete
			await waitFor( () => {
				// Input should retain its value on failure
				expect( couponInput ).toHaveValue( 'invalid_coupon' );
			} );

			// Input should be focused for retry
			expect( couponInput ).toHaveFocus();
		} );
	} );

	describe( 'Loading States', () => {
		it( 'shows loading state while coupon is being applied', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest.fn().mockResolvedValue( undefined );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
					isLoading={ true }
				/>
			);

			const couponInput = screen.getByLabelText( 'Enter code' );

			// When loading, button has different attributes, select by class
			const applyButton = screen.getByText( 'Apply' ).closest( 'button' );

			// Enter coupon code
			await act( async () => {
				await user.type( couponInput, 'test_coupon' );
			} );

			// Verify button is disabled while loading
			expect( applyButton ).toBeDisabled();

			// Verify loading mask is shown
			expect(
				screen.getByText( 'Applying coupon…' )
			).toBeInTheDocument();

			// Verify spinner is shown in button
			expect( applyButton ).toHaveClass(
				'wc-block-components-totals-coupon__button--loading'
			);

			// Submit should not work while loading (pointer events disabled)
			// We just verify the button is disabled, no need to click
			expect( mockOnSubmit ).not.toHaveBeenCalled();
		} );

		it( 'enables button when input has value and not loading', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest.fn().mockResolvedValue( true );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
					isLoading={ false }
				/>
			);

			const couponInput = screen.getByLabelText( 'Enter code' );
			const applyButton = screen.getByRole( 'button', { name: 'Apply' } );

			// Initially button should be disabled
			expect( applyButton ).toBeDisabled();

			// Type in the input
			await act( async () => {
				await user.type( couponInput, 'test' );
			} );

			// Button should now be enabled
			expect( applyButton ).not.toBeDisabled();
		} );

		it( 'disables button when input is empty', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest.fn().mockResolvedValue( true );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			const couponInput = screen.getByLabelText( 'Enter code' );
			const applyButton = screen.getByRole( 'button', { name: 'Apply' } );

			// Type something first
			await act( async () => {
				await user.type( couponInput, 'test' );
			} );

			expect( applyButton ).not.toBeDisabled();

			// Clear the input
			await act( async () => {
				await user.clear( couponInput );
			} );

			// Button should be disabled again
			expect( applyButton ).toBeDisabled();
		} );
	} );

	describe( 'Multiple Coupon Scenarios', () => {
		it( 'allows applying multiple different coupons sequentially', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest.fn().mockResolvedValue( true );

			const { rerender } = render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ false }
				/>
			);

			// First coupon application
			const openButton = screen.getByText( 'Add coupons' );
			await act( async () => {
				await user.click( openButton );
			} );

			let couponInput = screen.getByLabelText( 'Enter code' );
			let applyButton = screen.getByRole( 'button', { name: 'Apply' } );

			await act( async () => {
				await user.type( couponInput, '5fixedcheckout' );
			} );

			await act( async () => {
				await user.click( applyButton );
			} );

			expect( mockOnSubmit ).toHaveBeenCalledWith( '5fixedcheckout' );

			// Simulate form closing after successful application
			rerender(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ false }
				/>
			);

			// Second coupon application
			const openButton2 = screen.getByText( 'Add coupons' );
			await act( async () => {
				await user.click( openButton2 );
			} );

			couponInput = screen.getByLabelText( 'Enter code' );
			applyButton = screen.getByRole( 'button', { name: 'Apply' } );

			await act( async () => {
				await user.type( couponInput, '50percoffcheckout' );
			} );

			await act( async () => {
				await user.click( applyButton );
			} );

			expect( mockOnSubmit ).toHaveBeenCalledWith( '50percoffcheckout' );
			expect( mockOnSubmit ).toHaveBeenCalledTimes( 2 );
		} );
	} );

	describe( 'Form Interaction', () => {
		it( 'toggles coupon form visibility when clicking add coupons button', async () => {
			const user = userEvent.setup();

			render( <TotalsCoupon instanceId="coupon" /> );

			// Initially form should be closed
			expect(
				screen.queryByLabelText( 'Enter code' )
			).not.toBeInTheDocument();

			// Click to open form
			const openButton = screen.getByText( 'Add coupons' );
			await act( async () => {
				await user.click( openButton );
			} );

			// Form should now be visible
			expect( screen.getByLabelText( 'Enter code' ) ).toBeInTheDocument();
		} );

		it( 'focuses on input when form opens', async () => {
			const user = userEvent.setup();

			render( <TotalsCoupon instanceId="coupon" /> );

			const openButton = screen.getByText( 'Add coupons' );
			await act( async () => {
				await user.click( openButton );
			} );

			const couponInput = screen.getByLabelText( 'Enter code' );
			expect( couponInput ).toHaveFocus();
		} );

		it( 'starts with form visible when displayCouponForm is true', () => {
			render(
				<TotalsCoupon instanceId="coupon" displayCouponForm={ true } />
			);

			expect( screen.getByLabelText( 'Enter code' ) ).toBeInTheDocument();
		} );

		it( 'handles form submission via enter key', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest.fn().mockResolvedValue( true );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			const couponInput = screen.getByLabelText( 'Enter code' );

			await act( async () => {
				await user.type( couponInput, 'test_coupon{enter}' );
			} );

			expect( mockOnSubmit ).toHaveBeenCalledWith( 'test_coupon' );
		} );
	} );

	describe( 'Edge Cases', () => {
		it( 'handles onSubmit returning undefined', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest.fn().mockReturnValue( undefined );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			const couponInput = screen.getByLabelText( 'Enter code' );
			const applyButton = screen.getByRole( 'button', { name: 'Apply' } );

			await act( async () => {
				await user.type( couponInput, 'test_coupon' );
			} );

			await act( async () => {
				await user.click( applyButton );
			} );

			expect( mockOnSubmit ).toHaveBeenCalledWith( 'test_coupon' );
			// Should not crash when onSubmit returns undefined
		} );

		it( 'handles whitespace in coupon codes', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest.fn().mockResolvedValue( true );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			const couponInput = screen.getByLabelText( 'Enter code' );
			const applyButton = screen.getByRole( 'button', { name: 'Apply' } );

			await act( async () => {
				await user.type( couponInput, '  test_coupon  ' );
			} );

			await act( async () => {
				await user.click( applyButton );
			} );

			// Should pass the coupon code as entered (trimming typically happens server-side)
			expect( mockOnSubmit ).toHaveBeenCalledWith( '  test_coupon  ' );
		} );

		it( 'handles special characters in coupon codes', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest.fn().mockResolvedValue( true );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			const couponInput = screen.getByLabelText( 'Enter code' );
			const applyButton = screen.getByRole( 'button', { name: 'Apply' } );

			await act( async () => {
				await user.type( couponInput, '$5 off' );
			} );

			await act( async () => {
				await user.click( applyButton );
			} );

			expect( mockOnSubmit ).toHaveBeenCalledWith( '$5 off' );
		} );
	} );
} );
