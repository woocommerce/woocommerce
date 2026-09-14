/**
 * External dependencies
 */
import { act, fireEvent, render, screen } from '@testing-library/react';
import { validationStore } from '@woocommerce/block-data';
import { dispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ValidatedCheckboxControl from '../validated-checkbox-control';

describe( 'ValidatedCheckboxControl', () => {
	const label = 'Test required checkbox';
	const errorMessage = 'Please check the box or you will be unable to order';

	const renderUnchecked = ( props = {} ) =>
		render(
			<ValidatedCheckboxControl
				id="required-checkbox"
				label={ label }
				required
				checked={ false }
				onChange={ () => void 0 }
				{ ...props }
			/>
		);

	it( "Shows the field's own error message once errors are revealed", async () => {
		renderUnchecked( { errorMessage } );

		// Validation runs on mount but starts hidden, which is what a shopper sees
		// before they try to place the order.
		expect( screen.queryByText( errorMessage ) ).not.toBeInTheDocument();

		await act( async () => {
			dispatch( validationStore ).showAllValidationErrors();
		} );

		expect( screen.getByText( errorMessage ) ).toBeInTheDocument();
	} );

	it( 'Clears the error once the shopper checks the box', async () => {
		// The two cases above render with checked fixed at false, so they only cover
		// the reveal. The clear runs through a different path entirely --
		// onChange -> validateInput( false ) -> clearValidationError -- and a
		// regression there leaves a blocking error on screen after the shopper has
		// already fixed the problem. Drive it through a stateful parent, the way
		// checkout does, so the checkbox actually ends up checked.
		const StatefulCheckbox = () => {
			const [ checked, setChecked ] = useState( false );

			return (
				<ValidatedCheckboxControl
					id="required-checkbox"
					label={ label }
					required
					checked={ checked }
					onChange={ setChecked }
					errorMessage={ errorMessage }
				/>
			);
		};

		render( <StatefulCheckbox /> );

		await act( async () => {
			dispatch( validationStore ).showAllValidationErrors();
		} );
		expect( screen.getByText( errorMessage ) ).toBeInTheDocument();

		fireEvent.click( screen.getByRole( 'checkbox' ) );

		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
		expect( screen.queryByText( errorMessage ) ).not.toBeInTheDocument();
	} );

	it( 'Falls back to the generated message when the field supplies none', async () => {
		renderUnchecked();

		await act( async () => {
			dispatch( validationStore ).showAllValidationErrors();
		} );

		// Without this the test above would pass on any message at all, rather than
		// on the one the field asked for.
		expect( screen.queryByText( errorMessage ) ).not.toBeInTheDocument();
		expect(
			screen.getByText( 'Please check this box if you want to proceed.' )
		).toBeInTheDocument();
	} );
} );
