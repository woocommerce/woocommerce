/**
 * Regression test for woocommerce#60542.
 *
 * Block checkout core field customizations applied via the
 * `woocommerce_get_country_locale_default` filter (and the ordering/visibility
 * of additional checkout fields injected into the default locale) must be
 * applied on first render — before the customer chooses a country.
 *
 * The fix exposes a `default` entry in COUNTRY_LOCALE and uses it as a fallback
 * in prepareFormFields when no country has been selected yet.
 */

jest.mock( '@woocommerce/block-settings', () => ( {
	COUNTRY_LOCALE: {
		default: {
			last_name: { hidden: true, required: false },
			first_name: { label: 'Full name', index: 5 },
			'my-plugin/hello': { index: 11 },
		},
		US: {
			postcode: { label: 'ZIP Code' },
		},
	},
} ) );

/**
 * Internal dependencies
 */
import prepareFormFields from '../prepare-form-fields';

const defaultFields = {
	first_name: {
		label: 'First name',
		required: true,
		hidden: false,
		index: 10,
	},
	last_name: {
		label: 'Last name',
		required: true,
		hidden: false,
		index: 20,
	},
	postcode: {
		label: 'Postal code',
		required: true,
		hidden: false,
		index: 90,
	},
	'my-plugin/hello': {
		label: "Hey, I'm the first field!",
		required: false,
		hidden: false,
		index: 25,
	},
} as never;

const ADDRESS_KEYS = [
	'first_name',
	'last_name',
	'postcode',
	'my-plugin/hello',
] as never;

describe( 'prepareFormFields', () => {
	it( 'applies the default locale when no country is selected', () => {
		const fields = prepareFormFields( ADDRESS_KEYS, defaultFields, '' );

		const byKey = Object.fromEntries(
			fields.map( ( f ) => [ f.key, f ] )
		);

		// last_name hidden by woocommerce_get_country_locale_default.
		expect( byKey.last_name.hidden ).toBe( true );
		expect( byKey.last_name.required ).toBe( false );

		// first_name relabelled and its index pulled forward via the default
		// locale's `priority` value.
		expect( byKey.first_name.label ).toBe( 'Full name' );
		expect( byKey.first_name.index ).toBe( 5 );

		// Additional field ordering applied via the default locale.
		expect( byKey[ 'my-plugin/hello' ].index ).toBe( 11 );
	} );

	it( 'lets a selected country locale override the default locale', () => {
		const fields = prepareFormFields( ADDRESS_KEYS, defaultFields, 'US' );
		const byKey = Object.fromEntries(
			fields.map( ( f ) => [ f.key, f ] )
		);

		// Default-locale customizations still apply where the country locale
		// is silent.
		expect( byKey.last_name.hidden ).toBe( true );
		expect( byKey.first_name.label ).toBe( 'Full name' );

		// Country-specific override wins for postcode.
		expect( byKey.postcode.label ).toBe( 'ZIP Code' );
	} );

	it( 'returns fields ordered by the resolved index', () => {
		const fields = prepareFormFields( ADDRESS_KEYS, defaultFields, '' );
		const keysInOrder = fields.map( ( f ) => f.key );

		// first_name index 5, my-plugin/hello index 11, last_name index 20, postcode index 90.
		expect( keysInOrder ).toEqual( [
			'first_name',
			'my-plugin/hello',
			'last_name',
			'postcode',
		] );
	} );
} );
