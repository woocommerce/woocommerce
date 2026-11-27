/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { Status } from '../settings-email-listing-status';

describe( 'Status', () => {
	it( 'shows the status label without a warning by default', () => {
		render( <Status slug="disabled" /> );

		expect( screen.getByText( 'Inactive' ) ).toBeInTheDocument();
		expect( screen.queryByRole( 'button' ) ).not.toBeInTheDocument();
	} );

	it( 'shows a focusable warning labelled with the reason', () => {
		render(
			<Status
				slug="disabled"
				warning="New customers need this email to set their password and sign in."
			/>
		);

		expect(
			screen.getByRole( 'button', {
				name: 'New customers need this email to set their password and sign in.',
			} )
		).toBeInTheDocument();
		expect( screen.getByText( 'Inactive' ) ).toBeInTheDocument();
	} );

	it( 'hides the warning once the email is enabled', () => {
		render(
			<Status
				slug="enabled"
				warning="New customers need this email to set their password and sign in."
			/>
		);

		expect( screen.queryByRole( 'button' ) ).not.toBeInTheDocument();
		expect( screen.getByText( 'Active' ) ).toBeInTheDocument();
	} );
} );
