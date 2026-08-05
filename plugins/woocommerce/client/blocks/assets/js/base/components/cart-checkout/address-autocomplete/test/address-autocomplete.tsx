/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { AddressAutocomplete } from '../address-autocomplete';

describe( 'Address Autocomplete Component', () => {
	it( 'should render a ValidatedTextInput with correct props', () => {
		const mockOnChange = jest.fn();
		const { container } = render(
			<AddressAutocomplete
				addressType="billing"
				onChange={ mockOnChange }
				id="billing_address_1"
				value="123 Main St"
				label="Address"
			/>
		);

		// Check that the container has the correct class
		expect(
			container.querySelector(
				'.wc-block-components-address-autocomplete-container'
			)
		).toBeInTheDocument();

		// Check that the input is rendered with correct id
		const input = container.querySelector( '#billing_address_1' );
		expect( input ).toBeInTheDocument();
		expect( input ).toHaveValue( '123 Main St' );
	} );

	it( 'should render for shipping address type', () => {
		const mockOnChange = jest.fn();
		const { container } = render(
			<AddressAutocomplete
				addressType="shipping"
				onChange={ mockOnChange }
				id="shipping_address_1"
				value="456 Oak Ave"
				label="Shipping Address"
			/>
		);

		// Check that the input is rendered with correct id and value
		const input = container.querySelector( '#shipping_address_1' );
		expect( input ).toBeInTheDocument();
		expect( input ).toHaveValue( '456 Oak Ave' );
	} );

	it( 'should pass through additional props to ValidatedTextInput', () => {
		const mockOnChange = jest.fn();
		const { container } = render(
			<AddressAutocomplete
				addressType="billing"
				onChange={ mockOnChange }
				id="test_input"
				placeholder="Enter address"
				disabled={ true }
				required={ true }
			/>
		);

		const input = container.querySelector( '#test_input' );
		expect( input ).toBeInTheDocument();
		expect( input ).toHaveAttribute( 'placeholder', 'Enter address' );
		expect( input ).toBeDisabled();
		expect( input ).toBeRequired();
	} );

	it( 'Creates mutation observer that watches data-disable-autocomplete attribute', () => {
		const { container } = render(
			<AddressAutocomplete
				addressType="billing"
				onChange={ () => {} }
				id="billing_address_1"
				autoComplete="street-address"
			/>
		);

		const input = container.querySelector(
			'#billing_address_1'
		) as HTMLInputElement;
		expect( input ).toBeTruthy();

		// Initially should have data-disable-autocomplete attribute set to "off"
		expect( input.getAttribute( 'data-disable-autocomplete' ) ).toBe(
			'off'
		);

		// Manually trigger the mutation observer by changing the attribute
		// This simulates what would happen when searchValue state changes
		input.setAttribute( 'data-disable-autocomplete', 'on' );

		// Give the mutation observer time to react
		return waitFor( () => {
			expect( input.getAttribute( 'autocomplete' ) ).toBe( 'none' );
			expect( input.getAttribute( 'data-1p-ignore' ) ).toBe( 'true' );
		} ).then( () => {
			// Now set it back to 'off'
			input.setAttribute( 'data-disable-autocomplete', 'off' );

			// Wait for mutation observer to restore original attributes
			return waitFor( () => {
				expect( input.getAttribute( 'autocomplete' ) ).toBe(
					'street-address'
				);
				expect( input.hasAttribute( 'data-1p-ignore' ) ).toBe( false );
			} );
		} );
	} );
} );
