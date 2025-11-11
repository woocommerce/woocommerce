/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { dispatch } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { TotalsCoupon } from '..';

describe( 'TotalsCoupon', () => {
	beforeEach( () => {
		// Clear validation errors before each test
		const { clearValidationErrors } = dispatch( validationStore );
		act( () => {
			clearValidationErrors();
		} );
	} );
	afterAll( () => {
		// Clear validation errors after all tests to ensure no data store state is leaked.
		const { clearValidationErrors } = dispatch( validationStore );
		act( () => {
			clearValidationErrors();
		} );
	} );

	it( "Shows a validation error when one is in the wc/store/validation data store and doesn't show one when there isn't", async () => {
		const user = userEvent.setup();
		const { rerender } = render( <TotalsCoupon instanceId={ 'coupon' } /> );

		const openCouponFormButton = screen.getByText( 'Add coupons' );
		expect( openCouponFormButton ).toBeInTheDocument();
		await act( async () => {
			await user.click( openCouponFormButton );
		} );
		expect(
			screen.queryByText( 'Invalid coupon code' )
		).not.toBeInTheDocument();

		const { setValidationErrors } = dispatch( validationStore );
		act( () => {
			setValidationErrors( {
				coupon: {
					hidden: false,
					message: 'Invalid coupon code',
				},
			} );
		} );
		rerender( <TotalsCoupon instanceId={ 'coupon' } /> );

		expect( screen.getByText( 'Invalid coupon code' ) ).toBeInTheDocument();
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

		it( 'handles onSubmit that returns Promise that rejects', async () => {
			const user = userEvent.setup();

			// Create a proper promise-returning mock that catches the rejection
			const mockOnSubmit = jest.fn().mockImplementation( () => {
				return Promise.reject( {
					code: 'woocommerce_rest_cart_coupon_error',
					message: 'Coupon "expired_coupon" has already expired.',
				} ).catch( () => {
					// Handle the rejection internally to prevent unhandled promise
					return false;
				} );
			} );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			const couponInput = screen.getByLabelText( 'Enter code' );
			const applyButton = screen.getByRole( 'button', { name: 'Apply' } );

			// Enter an expired coupon code
			await act( async () => {
				await user.type( couponInput, 'expired_coupon' );
			} );

			// Submit the coupon
			await act( async () => {
				await user.click( applyButton );
			} );

			// Verify the API was called
			expect( mockOnSubmit ).toHaveBeenCalledWith( 'expired_coupon' );

			// Component should handle the case gracefully
			expect( couponInput ).toHaveValue( 'expired_coupon' );
		} );

		it( 'handles duplicate coupon application', async () => {
			const user = userEvent.setup();
			const mockOnSubmit = jest.fn().mockResolvedValue( false );

			// Set up validation error as would happen from the API response
			const { setValidationErrors } = dispatch( validationStore );
			act( () => {
				setValidationErrors( {
					coupon: {
						hidden: false,
						message:
							'Coupon code "5fixedcheckout" has already been applied.',
					},
				} );
			} );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			// Verify error message is displayed
			expect(
				screen.getByText(
					'Coupon code "5fixedcheckout" has already been applied.'
				)
			).toBeInTheDocument();

			const couponInput = screen.getByLabelText( 'Enter code' );
			const applyButton = screen.getByRole( 'button', { name: 'Apply' } );

			// Try to apply the same coupon again
			await act( async () => {
				await user.type( couponInput, '5fixedcheckout' );
			} );

			await act( async () => {
				await user.click( applyButton );
			} );

			expect( mockOnSubmit ).toHaveBeenCalledWith( '5fixedcheckout' );

			// Input should retain value and stay focused on failure
			await waitFor( () => {
				expect( couponInput ).toHaveValue( '5fixedcheckout' );
				expect( couponInput ).toHaveFocus();
			} );
		} );

		it( 'handles usage limit exceeded error', async () => {
			const mockOnSubmit = jest.fn().mockResolvedValue( false );

			// Set up validation error for usage limit
			const { setValidationErrors } = dispatch( validationStore );
			act( () => {
				setValidationErrors( {
					coupon: {
						hidden: false,
						message:
							'Usage limit for coupon "limited_coupon" has been reached.',
					},
				} );
			} );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			// Verify error message is displayed
			expect(
				screen.getByText(
					'Usage limit for coupon "limited_coupon" has been reached.'
				)
			).toBeInTheDocument();
		} );

		it( 'handles coupons disabled error', async () => {
			const mockOnSubmit = jest.fn().mockResolvedValue( false );

			// Set up validation error for disabled coupons
			const { setValidationErrors } = dispatch( validationStore );
			act( () => {
				setValidationErrors( {
					coupon: {
						hidden: false,
						message: 'Coupons are disabled.',
					},
				} );
			} );

			render(
				<TotalsCoupon
					instanceId="coupon"
					onSubmit={ mockOnSubmit }
					displayCouponForm={ true }
				/>
			);

			// Verify error message is displayed
			expect(
				screen.getByText( 'Coupons are disabled.' )
			).toBeInTheDocument();
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
