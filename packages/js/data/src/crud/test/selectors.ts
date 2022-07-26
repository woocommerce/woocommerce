/**
 * Internal dependencies
 */
import { createSelectors } from '../selectors';

const selectors = createSelectors( {
	resourceName: 'Product',
	pluralResourceName: 'Products',
	namespace: '',
} );

describe( 'crud selectors', () => {
	it( 'should return methods for the default selectors', () => {
		expect( Object.keys( selectors ).length ).toEqual( 7 );
		expect( selectors ).toHaveProperty( 'getProduct' );
		expect( selectors ).toHaveProperty( 'getProducts' );
		expect( selectors ).toHaveProperty( 'getProductError' );
		expect( selectors ).toHaveProperty( 'getProductsError' );
		expect( selectors ).toHaveProperty( 'getProductCreateError' );
		expect( selectors ).toHaveProperty( 'getProductDeleteError' );
		expect( selectors ).toHaveProperty( 'getProductUpdateError' );
	} );
} );
