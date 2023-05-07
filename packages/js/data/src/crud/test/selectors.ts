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
		expect( Object.keys( selectors ).length ).toEqual( 10 );
		expect( selectors ).toHaveProperty( 'getProduct' );
		expect( selectors ).toHaveProperty( 'getProducts' );
		expect( selectors ).toHaveProperty( 'getProductsTotalCount' );
		expect( selectors ).toHaveProperty( 'getProductError' );
		expect( selectors ).toHaveProperty( 'getProductsError' );
		expect( selectors ).toHaveProperty( 'getProductCreateError' );
		expect( selectors ).toHaveProperty( 'getProductDeleteError' );
		expect( selectors ).toHaveProperty( 'getProductUpdateError' );
		expect( selectors ).toHaveProperty( 'hasFinishedRequest' );
		expect( selectors ).toHaveProperty( 'isRequesting' );
	} );
} );
