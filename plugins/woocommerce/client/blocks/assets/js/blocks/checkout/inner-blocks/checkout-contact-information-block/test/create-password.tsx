/**
 * External dependencies
 */
import { render, screen, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { checkoutStore } from '@woocommerce/block-data';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import CreatePassword from '../create-password';

describe( 'CreatePassword', () => {
	let user: ReturnType< typeof userEvent.setup >;

	beforeEach( () => {
		user = userEvent.setup();
		jest.clearAllMocks();
		dispatch( checkoutStore ).__internalSetCustomerPassword( '' );
	} );

	it( 'renders the password input field', () => {
		render( <CreatePassword /> );

		expect(
			screen.getByLabelText( 'Create a password' )
		).toBeInTheDocument();
		expect( screen.getByLabelText( 'Create a password' ) ).toHaveAttribute(
			'type',
			'password'
		);
		expect( screen.getByLabelText( 'Create a password' ) ).toHaveAttribute(
			'required'
		);
	} );

	// Note, this test does not use zxcvbn - it is not mocked or bundled in the test environment.
	it( 'shows the password strength meter when typing', async () => {
		render( <CreatePassword /> );
		const input = screen.getByLabelText( 'Create a password' );

		// Initially, the meter should be hidden
		const meter = screen.getByRole( 'meter', {
			name: 'Password strength',
		} );
		expect(
			meter.closest( '.wc-block-components-password-strength' )
		).toHaveClass( 'hidden' );

		// Type a password
		await act( async () => {
			await user.clear( input );
			await user.type( input, 'g' );
		} );

		// The meter should now be visible
		await waitFor( () => {
			expect(
				meter.closest( '.wc-block-components-password-strength' )
			).not.toHaveClass( 'hidden' );
		} );

		// Check that the strength value is displayed
		await waitFor( () => {
			expect(
				screen.getByText( /Password Strength: Too weak/i )
			).toBeInTheDocument();
		} );

		await act( async () => {
			await user.clear( input );
			await user.type( input, 'test12!' );
		} );
		await waitFor( () => {
			expect(
				screen.getByText( /Password Strength: Weak/i )
			).toBeInTheDocument();
		} );

		await act( async () => {
			await user.clear( input );
			await user.type( input, 'med len 1' );
		} );
		await waitFor( () => {
			expect(
				screen.getByText( /Password Strength: Medium/i )
			).toBeInTheDocument();
		} );

		await act( async () => {
			await user.clear( input );
			await user.type( input, '!StrongPass!2' );
		} );
		await waitFor( () => {
			expect(
				screen.getByText( /Password Strength: Strong/i )
			).toBeInTheDocument();
		} );

		await act( async () => {
			await user.clear( input );
			await user.type( input, 'This$IsA%Super()Strong935:Pa5W0Rd' );
		} );
		await waitFor( () => {
			expect(
				screen.getByText( /Password Strength: Very strong/i )
			).toBeInTheDocument();
		} );
	} );

	it( 'does not show a warning after a strong password is pasted in', async () => {
		render( <CreatePassword /> );
		const input = screen.getByLabelText( 'Create a password' );

		// Initially, the meter should be hidden
		const meter = screen.getByRole( 'meter', {
			name: 'Password strength',
		} );
		expect(
			meter.closest( '.wc-block-components-password-strength' )
		).toHaveClass( 'hidden' );

		// Type a password
		await act( async () => {
			await user.clear( input );
			await user.click( input );
			await user.paste( 'This$IsA%Super()Strong935:Pa5W0Rd' );
		} );
		await waitFor( () => {
			expect(
				screen.getByText( /Password Strength: Very strong/i )
			).toBeInTheDocument();
		} );
		act( () => input.blur() );

		await waitFor( () => {
			expect(
				screen.getByText( /Password Strength: Very strong/i )
			).toBeInTheDocument();
		} );
		await waitFor( () => {
			expect(
				screen.queryByText( /Please create a stronger password/i )
			).not.toBeInTheDocument();
		} );
	} );
} );
