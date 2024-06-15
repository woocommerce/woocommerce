/* eslint-disable camelcase */
/* eslint-disable no-undef */

/**
 * Internal dependencies
 */
import { findCountryOption, getCountry } from '../../';
import { countryStateOptions } from './country-options';
import { locations } from './locations';

describe( 'findCountryOption', () => {
	it( 'should return null on undefined location', () => {
		const location = undefined;
		expect( findCountryOption( countryStateOptions, location ) ).toEqual(
			null
		);
	} );

	it( 'should return null when not found', () => {
		const location = { country_short: 'US' };
		expect( findCountryOption( countryStateOptions, location ) ).toBeNull();
	} );

	it( 'should ignore accents for comparison', () => {
		const location = {
			city: 'Malaga',
			region: 'Andalucia',
			country_short: 'ES',
		};
		expect( findCountryOption( countryStateOptions, location ) ).toEqual( {
			key: 'ES:MA',
			label: 'Spain — Málaga',
		} );
	} );

	it.each( [
		{
			location: { country_short: 'TW' },
			expected: {
				key: 'TW',
				label: 'Taiwan',
			},
		},
		{
			location: { country_short: 'US', region: 'California' },
			expected: {
				key: 'US:CA',
				label: 'United States (US) — California',
			},
		},
		{
			location: { country_short: 'ES', region: 'Madrid, Comunidad de' },
			expected: {
				key: 'ES:M',
				label: 'Spain — Madrid',
			},
		},
		{
			location: {
				country_short: 'AR',
				region: 'Ciudad Autonoma de Buenos Aires',
			},
			expected: {
				key: 'AR:C',
				label: 'Argentina — Ciudad Autónoma de Buenos Aires',
			},
		},
		{
			location: {
				country_short: 'IT',
				region: 'Lazio',
				city: 'Rome',
			},
			expected: {
				key: 'IT:RM',
				label: 'Italy — Roma',
			},
		},
		{
			location: {
				country_short: 'PH',
				region: 'National Capital Region',
				city: 'Makati',
			},
			expected: {
				key: 'PH:00',
				label: 'Philippines — Metro Manila',
			},
		},
	] )(
		'should return the country option for location $expected',
		( { location, expected } ) => {
			expect(
				findCountryOption( countryStateOptions, location, 0.4 )
			).toEqual( expected );
		}
	);

	it( 'should return a country option for > 98% locations', () => {
		let matchCount = 0;
		locations.forEach( ( location ) => {
			if ( findCountryOption( countryStateOptions, location, 0.4 ) ) {
				matchCount++;
			}
		} );
		expect( matchCount / locations.length ).toBeGreaterThan( 0.98 );
	} );
} );

describe( 'getCountry', () => {
	it( 'should return null on undefined location', () => {
		// @ts-expect-error -- tests undefined
		expect( getCountry( undefined ) ).toEqual( undefined );
	} );

	it( 'should return country when country string without state is passed', () => {
		expect( getCountry( 'SG' ) ).toEqual( 'SG' );
	} );

	it( 'should return country when country string with state is passed', () => {
		expect( getCountry( 'AU:VIC' ) ).toEqual( 'AU' );
	} );
} );
