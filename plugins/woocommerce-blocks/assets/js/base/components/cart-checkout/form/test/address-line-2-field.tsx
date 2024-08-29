/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';
import AddressLine2Field from '@woocommerce/base-components/cart-checkout/form/address-line-2-field';
import { useState } from '@wordpress/element';

describe( 'Address Line 2 Component', () => {
	it( 'Renders a hidden field which disappears as soon as text is entered (autofill functionality)', async () => {
		// Render in a wrapper so we can track the value is sent correctly from hidden to visible input.
		const FieldWrapper = () => {
			const [ value, setValue ] = useState( '' );
			return (
				<AddressLine2Field
					field={ {
						index: 0,
						key: 'address_2',
						required: false,
						label: 'Address 2',
						optionalLabel: 'Optional Address 2',
						type: 'text',
						hidden: false,
						autocomplete: 'address-line2',
					} }
					value={ value }
					onChange={ ( _: string, newValue: string ) => {
						setValue( newValue );
					} }
				/>
			);
		};
		render( <FieldWrapper /> );
		const hiddenInput = screen.getByLabelText( 'Address 2' );
		expect( hiddenInput ).toBeInTheDocument();
		expect( hiddenInput ).toHaveAttribute( 'aria-hidden', 'true' );
		fireEvent.change( hiddenInput, { target: { value: '123' } } );
		expect( hiddenInput ).not.toBeInTheDocument();
		const visibleInput = screen.getByLabelText( 'Optional Address 2' );
		expect( visibleInput ).toBeInTheDocument();
		expect( visibleInput ).toHaveValue( '123' );
	} );
} );
