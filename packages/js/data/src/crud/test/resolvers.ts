/**
 * Internal dependencies
 */
import { createResolvers } from '../resolvers';

const resolvers = createResolvers( {
	storeName: 'wc/products',
	resourceName: 'Product',
	pluralResourceName: 'Products',
	namespace: '/products',
} );

describe( 'crud resolvers', () => {
	it( 'should return methods for the default resolvers', () => {
		expect( Object.keys( resolvers ).length ).toEqual( 3 );
		expect( resolvers ).toHaveProperty( 'getProduct' );
		expect( resolvers ).toHaveProperty( 'getProducts' );
		expect( resolvers ).toHaveProperty( 'getProductsTotalCount' );
	} );
} );
