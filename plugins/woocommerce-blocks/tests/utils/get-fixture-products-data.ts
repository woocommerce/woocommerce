/**
 * Internal dependencies
 */
import { Products } from '../e2e/fixtures/fixture-data';

/**
 * Get products data by key from fixtures.
 */
export const getFixtureProductsData = ( key: string ) => {
	return Products()
		.map( ( product ) => product[ key ] )
		.filter( Boolean );
};
