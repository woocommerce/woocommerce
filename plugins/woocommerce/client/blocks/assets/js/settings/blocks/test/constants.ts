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

// The constants are computed when the module loads, so every case needs a fresh
// module registry seeded with its own settings.
const loadConstants = ( settings: Record< string, unknown > ): Constants => {
	window.wcSettings = { ...window.wcSettings, ...settings };
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
			countries: {
				US: 'United States (US)',
				GB: 'United Kingdom (UK)',
			},
			countryData,
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
			const { ALLOWED_COUNTRIES, SHIPPING_COUNTRIES } = loadConstants( {
				countries,
				countryData,
			} );

			expect( ALLOWED_COUNTRIES ).toEqual( { US: '' } );
			expect( SHIPPING_COUNTRIES ).toEqual( { US: '' } );
		}
	);

	it( 'falls back to an empty name when the entry is not a string', () => {
		const { ALLOWED_COUNTRIES } = loadConstants( {
			countries: { US: 42 },
			countryData,
		} );

		expect( ALLOWED_COUNTRIES ).toEqual( { US: '' } );
	} );
} );

describe( 'store page constants', () => {
	const originalSettings = window.wcSettings;

	const storePages = {
		terms: {
			id: 7,
			title: 'Terms and conditions',
			permalink: 'https://example.com/terms-and-conditions/',
		},
		privacy: {
			id: 8,
			title: 'Privacy policy',
			permalink: 'https://example.com/privacy-policy/',
		},
	};

	afterEach( () => {
		window.wcSettings = originalSettings;
	} );

	it( 'takes the terms link and name from the terms store page', () => {
		const { TERMS_URL, TERMS_PAGE_NAME } = loadConstants( { storePages } );

		expect( TERMS_URL ).toBe( 'https://example.com/terms-and-conditions/' );
		expect( TERMS_PAGE_NAME ).toBe( 'Terms and conditions' );
	} );

	it( 'takes the privacy link and name from the privacy store page', () => {
		const { PRIVACY_URL, PRIVACY_PAGE_NAME } = loadConstants( {
			storePages,
		} );

		expect( PRIVACY_URL ).toBe( 'https://example.com/privacy-policy/' );
		expect( PRIVACY_PAGE_NAME ).toBe( 'Privacy policy' );
	} );
} );
