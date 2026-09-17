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

describe( 'Taxes report locations', () => {
	beforeEach( () => {
		resolveSelect.mockReturnValue( {
			getCountries: () => Promise.resolve( countries ),
		} );
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

	it( 'names a state alongside its country', async () => {
		const options = await locationsAutocompleter.options();
		const california = options.find( ( option ) => option.key === 'US:CA' );

		expect( california.label ).toBe( 'California, United States (US)' );
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
			{ key: 'US:CA', label: 'California, United States (US)' },
		] );
	} );
} );
