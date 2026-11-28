/**
 * Internal dependencies
 */
import { getCountryStateOptions } from '../country';

describe( 'getCountryStateOptions', () => {
	it( 'should return an empty array when passed an empty array of countries', () => {
		const result = getCountryStateOptions( [] );
		expect( result ).toEqual( [] );
	} );

	it( 'should return an array of CountryStateOption objects when passed an array of countries with states', () => {
		const countries = [
			{
				code: 'US',
				name: 'United States',
				states: [
					{ code: 'CA', name: 'California' },
					{ code: 'NY', name: 'New York' },
				],
			},
			{
				code: 'AU',
				name: 'Australia',
				states: [
					{
						code: 'NSW',
						name: 'New South Wales',
					},
					{
						code: 'VIC',
						name: 'Victoria',
					},
				],
			},
		];
		const expected = [
			{ key: 'US:CA', label: 'United States — California' },
			{ key: 'US:NY', label: 'United States — New York' },
			{ key: 'AU:NSW', label: 'Australia — New South Wales' },
			{ key: 'AU:VIC', label: 'Australia — Victoria' },
		];
		const result = getCountryStateOptions( countries );
		expect( result ).toEqual( expected );
	} );

	it( 'should return an array of CountryStateOption objects with correct key and label properties when passed an array of countries with and without states', () => {
		const countries = [
			{
				code: 'US',
				name: 'United States',
				states: [ { code: 'CA', name: 'California' } ],
			},
			{
				code: 'GB',
				name: 'United Kingdom',
				states: [],
			},
			{
				code: 'AU',
				name: 'Australia',
				states: [
					{
						code: 'NSW',
						name: 'New South Wales',
					},
					{
						code: 'VIC',
						name: 'Victoria',
					},
				],
			},
		];
		const expected = [
			{ key: 'US:CA', label: 'United States — California' },
			{ key: 'GB', label: 'United Kingdom' },
			{ key: 'AU:NSW', label: 'Australia — New South Wales' },
			{ key: 'AU:VIC', label: 'Australia — Victoria' },
		];
		const result = getCountryStateOptions( countries );
		expect( result ).toEqual( expected );
	} );
} );
