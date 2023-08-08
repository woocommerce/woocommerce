/**
 * Internal dependencies
 */
import { Products } from '../e2e-jest/fixtures/fixture-data';

/**
 * Get products data by key from fixtures.
 */
export const getFixtureProductsData = ( key = '' ) => {
	if ( ! key ) {
		return Products();
	}
	return Products()
		.map( ( product ) => product[ key ] )
		.filter( Boolean );
};
