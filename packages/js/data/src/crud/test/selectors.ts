/**
 * Internal dependencies
 */
import { createSelectors } from '../selectors';

const selectors = createSelectors( {
	resourceName: 'Product',
	pluralResourceName: 'Products',
} );

describe( 'crud selectors', () => {
	it( 'should return methods for the default selectors', () => {
		expect( Object.keys( selectors ).length ).toEqual( 4 );
		expect( selectors ).toHaveProperty( 'getProduct' );
		expect( selectors ).toHaveProperty( 'getProducts' );
		expect( selectors ).toHaveProperty( 'getProductError' );
		expect( selectors ).toHaveProperty( 'getProductsError' );
	} );
} );
