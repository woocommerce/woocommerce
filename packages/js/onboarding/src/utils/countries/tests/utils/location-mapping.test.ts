/* eslint-disable camelcase */

/**
 * Internal dependencies
 */
import { getMappingRegion } from '../../location-mapping';

describe( 'getMappingRegion', () => {
	it( 'should return null for an empty location', () => {
		expect( getMappingRegion( {} ) ).toBeNull();
	} );

	it( 'should return null for a location that is not in the mapping', () => {
		expect(
			getMappingRegion( { country_short: 'US', region: 'California' } )
		).toBeNull();
	} );

	it( 'should return null for a location with no region', () => {
		expect( getMappingRegion( { country_short: 'PH' } ) ).toBeNull();
	} );

	it( 'should return the region for a location that is in the mapping with a region', () => {
		expect(
			getMappingRegion( {
				country_short: 'PH',
				region: 'National Capital Region',
			} )
		).toBe( 'Metro Manila' );
	} );

	it( 'should return the region for a location that is in the mapping with a city', () => {
		expect(
			getMappingRegion( {
				country_short: 'IT',
				region: 'Lazio',
				city: 'Rome',
			} )
		).toBe( 'Roma' );
	} );
} );
