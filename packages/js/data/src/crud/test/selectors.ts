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
		expect( Object.keys( selectors ).length ).toEqual( 7 );
		expect( selectors ).toHaveProperty( 'getProduct' );
		expect( selectors ).toHaveProperty( 'getProducts' );
		expect( selectors ).toHaveProperty( 'getProductError' );
		expect( selectors ).toHaveProperty( 'getProductsError' );
		expect( selectors ).toHaveProperty( 'getCreateProductError' );
		expect( selectors ).toHaveProperty( 'getDeleteProductError' );
		expect( selectors ).toHaveProperty( 'getUpdateProductError' );
	} );
} );
