/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { allSettings } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import CountryInput from '../country-input';

const allowedCountries = {
	AT: 'Austria',
	US: 'United States (US)',
} as const;

const defaultProps = {
	id: 'shipping-country',
	label: 'Country/Region',
	countries: allowedCountries,
	onChange: jest.fn(),
};

describe( 'CountryInput', () => {
	beforeEach( () => {
		allSettings.countries = {
			...allowedCountries,
			GB: 'United Kingdom (UK)',
		};
	} );

	afterEach( () => {
		allSettings.countries = [];
		jest.clearAllMocks();
	} );

	it( 'renders the allowed countries as options', () => {
		render( <CountryInput { ...defaultProps } value="US" /> );

		expect(
			screen.getByRole( 'option', { name: 'United States (US)' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'option', { name: 'Austria' } )
		).toBeInTheDocument();
	} );

	it( 'shows the selected country as a disabled option when it is not in the allowed list', () => {
		render( <CountryInput { ...defaultProps } value="GB" /> );

		const unavailableOption = screen.getByRole( 'option', {
			name: 'United Kingdom (UK)',
		} ) as HTMLOptionElement;

		expect( unavailableOption ).toBeInTheDocument();
		expect( unavailableOption.disabled ).toBe( true );
		expect( unavailableOption.selected ).toBe( true );
		expect( screen.getByLabelText( 'Country/Region' ) ).toHaveValue( 'GB' );
	} );

	it( 'does not add an extra option when the selected country is allowed', () => {
		render( <CountryInput { ...defaultProps } value="US" /> );

		// Placeholder + the two allowed countries only.
		expect( screen.getAllByRole( 'option' ) ).toHaveLength( 3 );
	} );

	it( 'inserts the unavailable country in alphabetical position', () => {
		render( <CountryInput { ...defaultProps } value="GB" /> );

		const optionLabels = screen
			.getAllByRole( 'option' )
			.map( ( option ) => option.textContent );

		expect( optionLabels ).toEqual( [
			'Select a country/region',
			'Austria',
			'United Kingdom (UK)',
			'United States (US)',
		] );
	} );

	it( 'falls back to the country code when the full country list has no name for it', () => {
		render( <CountryInput { ...defaultProps } value="XX" /> );

		expect(
			screen.getByRole( 'option', { name: 'XX' } )
		).toBeInTheDocument();
	} );

	it( 'removes the unavailable option once an allowed country is selected', () => {
		const { rerender } = render(
			<CountryInput { ...defaultProps } value="GB" />
		);

		expect(
			screen.getByRole( 'option', { name: 'United Kingdom (UK)' } )
		).toBeInTheDocument();

		rerender( <CountryInput { ...defaultProps } value="US" /> );

		expect(
			screen.queryByRole( 'option', { name: 'United Kingdom (UK)' } )
		).not.toBeInTheDocument();
		expect( screen.getByLabelText( 'Country/Region' ) ).toHaveValue( 'US' );
	} );
} );
