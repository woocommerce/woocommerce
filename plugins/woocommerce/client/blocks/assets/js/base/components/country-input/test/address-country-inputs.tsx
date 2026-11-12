/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ShippingCountryInput from '../shipping-country-input';
import BillingCountryInput from '../billing-country-input';

jest.mock( '@woocommerce/block-settings', () => ( {
	...jest.requireActual( '@woocommerce/block-settings' ),
	SHIPPING_COUNTRIES: { ES: 'Spain' },
	COUNTRIES: { ES: 'Spain', US: 'United States (US)' },
} ) );

const defaultProps = {
	id: 'country',
	label: 'Country/Region',
	onChange: jest.fn(),
};

describe( 'Address country inputs', () => {
	it( 'only offers shipping countries even when billing countries are also available', () => {
		render( <ShippingCountryInput { ...defaultProps } value="ES" /> );

		expect( screen.getByRole( 'option', { name: 'Spain' } ) ).toBeEnabled();
		expect(
			screen.queryByRole( 'option', { name: 'United States (US)' } )
		).not.toBeInTheDocument();
	} );

	it( 'keeps an unavailable saved shipping country visible but disabled', () => {
		render( <ShippingCountryInput { ...defaultProps } value="US" /> );

		expect( screen.getByRole( 'option', { name: 'US' } ) ).toBeDisabled();
		expect( screen.getByRole( 'option', { name: 'Spain' } ) ).toBeEnabled();
	} );

	it( 'still offers billing countries outside the shipping area for local pickup', () => {
		render( <BillingCountryInput { ...defaultProps } value="US" /> );

		expect(
			screen.getByRole( 'option', { name: 'United States (US)' } )
		).toBeEnabled();
	} );
} );
