/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import type { ShippingAddress } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import ShippingCalculatorAddress from '../address';

const address: ShippingAddress = {
	first_name: '',
	last_name: '',
	company: '',
	address_1: '',
	address_2: '',
	city: '',
	state: '',
	postcode: '',
	country: '',
	phone: '',
};

const addressFields: Array< 'country' | 'city' | 'state' | 'postcode' > = [
	'country',
	'city',
	'state',
	'postcode',
];

describe( 'ShippingCalculatorAddress', () => {
	it( 'should show inline validation errors when the form is submitted with empty fields', () => {
		render(
			<ShippingCalculatorAddress
				address={ address }
				onUpdate={ jest.fn() }
				onCancel={ jest.fn() }
				addressFields={ addressFields }
			/>
		);

		// Submit form with empty fields
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Check delivery options' } )
		);

		// Verify validation errors are shown
		const errorMessages = [
			'Please enter a valid city',
			'Please enter a valid state/county',
			'Please enter a valid postal code',
		];
		errorMessages.forEach( ( message ) => {
			expect( screen.getByText( message ) ).toBeInTheDocument();
		} );

		// Fill in all required fields
		const fields = {
			City: 'Vienna',
			'State/County': 'Vienna',
			'Postal code': '1010',
		};
		Object.entries( fields ).forEach( ( [ label, value ] ) => {
			fireEvent.change( screen.getByRole( 'textbox', { name: label } ), {
				target: { value },
			} );
		} );

		// Submit form with filled fields
		fireEvent.click(
			screen.getByRole( 'button', { name: 'Check delivery options' } )
		);

		// Verify validation errors are cleared
		errorMessages.forEach( ( message ) => {
			expect( screen.queryByText( message ) ).not.toBeInTheDocument();
		} );
	} );
} );
