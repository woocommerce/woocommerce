/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { AddressAutocomplete } from '@woocommerce/base-components/cart-checkout/address-autocomplete/address-autocomplete';

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
} );
