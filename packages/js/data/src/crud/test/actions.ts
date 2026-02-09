/**
 * Internal dependencies
 */
import { createDispatchActions } from '../actions';

const selectors = createDispatchActions( {
	resourceName: 'Product',
	namespace: '/products',
} );

describe( 'crud selectors', () => {
	it( 'should return methods for the default actions', () => {
		expect( Object.keys( selectors ).length ).toEqual( 3 );
		expect( selectors ).toHaveProperty( 'createProduct' );
		expect( selectors ).toHaveProperty( 'deleteProduct' );
		expect( selectors ).toHaveProperty( 'updateProduct' );
	} );
} );
