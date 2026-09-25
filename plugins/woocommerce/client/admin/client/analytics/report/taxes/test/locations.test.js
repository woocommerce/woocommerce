/**
 * External dependencies
 */
import { resolveSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getLocationLabels, locationsAutocompleter } from '../locations';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	resolveSelect: jest.fn(),
} ) );

const countries = [
	{ code: 'DE', name: 'Germany', states: [] },
	{
		code: 'US',
		name: 'United States (US)',
		states: [
			{ code: 'CA', name: 'California' },
			{ code: 'NY', name: 'New York' },
		],
	},
];

// The module builds the list once and holds on to it, so every test here reads the same
// countries. A test needing different ones has to run in its own module registry.
const getCountries = jest.fn( () => Promise.resolve( countries ) );

describe( 'Taxes report locations', () => {
	beforeEach( () => {
		resolveSelect.mockReturnValue( { getCountries } );
	} );

	it( 'offers every country and every state of that country', async () => {
		const options = await locationsAutocompleter.options();

		expect( options.map( ( option ) => option.key ) ).toEqual( [
			'DE',
			'US',
			'US:CA',
			'US:NY',
		] );
	} );

	it( 'names a state alongside its country code', async () => {
		const options = await locationsAutocompleter.options();
		const california = options.find( ( option ) => option.key === 'US:CA' );

		expect( california.label ).toBe( 'California (US)' );
	} );

	it( 'matches a state by its own name and by its country code', async () => {
		const options = await locationsAutocompleter.options();
		const california = options.find( ( option ) => option.key === 'US:CA' );

		expect(
			locationsAutocompleter.getOptionKeywords( california )
		).toEqual( [ 'US:CA', 'California' ] );
	} );

	it( 'reads back the labels of a filter restored from the URL', async () => {
		expect( await getLocationLabels( 'US:CA,DE' ) ).toEqual( [
			{ key: 'DE', label: 'Germany' },
			{ key: 'US:CA', label: 'California (US)' },
		] );
	} );

	it( 'reads the countries once and answers the rest from the list it built', async () => {
		await locationsAutocompleter.options();
		await locationsAutocompleter.options();
		await getLocationLabels( 'DE' );

		expect( getCountries ).toHaveBeenCalledTimes( 1 );
	} );
} );
