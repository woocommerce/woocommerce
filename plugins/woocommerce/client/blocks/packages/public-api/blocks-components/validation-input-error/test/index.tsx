/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import { dispatch } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { ValidationInputError } from '..';

describe( 'ValidationInputError', () => {
	afterEach( () => {
		act( () => {
			dispatch( validationStore ).clearValidationErrors();
		} );
	} );

	it( 'uses the given id for a message passed directly', () => {
		render(
			<ValidationInputError
				errorMessage="Coupon rejected"
				id="coupon-error"
			/>
		);

		expect( screen.getByRole( 'alert' ) ).toHaveTextContent(
			'Coupon rejected'
		);
		expect( document.getElementById( 'coupon-error' ) ).toHaveTextContent(
			'Coupon rejected'
		);
	} );

	it( 'keeps the store-derived id when no id is given', () => {
		act( () => {
			dispatch( validationStore ).setValidationErrors( {
				email: { message: 'Enter a valid email', hidden: false },
			} );
		} );

		render(
			<ValidationInputError propertyName="email" elementId="email" />
		);

		expect(
			document.getElementById( 'validate-error-email' )
		).toHaveTextContent( 'Enter a valid email' );
	} );

	it( 'renders nothing without a message', () => {
		const { container } = render(
			<ValidationInputError propertyName="missing" />
		);

		expect( container ).toBeEmptyDOMElement();
	} );
} );
