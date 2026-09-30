/**
 * External dependencies
 */
import type { CountryData } from '@woocommerce/types';

type Constants = typeof import('../constants');

const countryData: Record< string, CountryData > = {
	US: {
		allowBilling: true,
		allowShipping: true,
		states: {},
		locale: {} as CountryData[ 'locale' ],
	},
	GB: {
		allowBilling: false,
		allowShipping: false,
		states: {},
		locale: {} as CountryData[ 'locale' ],
	},
};

// The country maps are computed when the module loads, so every case needs a
// fresh module registry seeded with its own `countries` setting.
const loadConstants = ( countries: unknown ): Constants => {
	window.wcSettings = { ...window.wcSettings, countries, countryData };
	let constants: Constants | null = null;
	jest.isolateModules( () => {
		constants = require( '../constants' );
	} );
	if ( ! constants ) {
		throw new Error( 'Constants module did not load.' );
	}
	return constants;
};

describe( 'country constants', () => {
	const originalSettings = window.wcSettings;

	afterEach( () => {
		window.wcSettings = originalSettings;
	} );

	it( 'maps allowed countries to their names', () => {
		const { ALLOWED_COUNTRIES, SHIPPING_COUNTRIES } = loadConstants( {
			US: 'United States (US)',
			GB: 'United Kingdom (UK)',
		} );

		expect( ALLOWED_COUNTRIES ).toEqual( { US: 'United States (US)' } );
		expect( SHIPPING_COUNTRIES ).toEqual( { US: 'United States (US)' } );
	} );

	it.each( [
		[ 'null', null ],
		[ 'an array', [] ],
		[ 'a string', 'broken' ],
	] )(
		'does not throw when the countries setting is %s',
		( _label, countries ) => {
			const { ALLOWED_COUNTRIES, SHIPPING_COUNTRIES } =
				loadConstants( countries );

			expect( ALLOWED_COUNTRIES ).toEqual( { US: '' } );
			expect( SHIPPING_COUNTRIES ).toEqual( { US: '' } );
		}
	);

	it( 'falls back to an empty name when the entry is not a string', () => {
		const { ALLOWED_COUNTRIES } = loadConstants( { US: 42 } );

		expect( ALLOWED_COUNTRIES ).toEqual( { US: '' } );
	} );
} );
