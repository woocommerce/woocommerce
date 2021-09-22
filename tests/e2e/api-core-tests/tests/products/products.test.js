/**
 * Internal dependencies
 */
const { productsApi } = require('../../endpoints/products');

/**
 * Tests for the WooCommerce Products API.
 *
 * @group api
 * @group products
 *
 */
 describe('Products API tests', () => {

	describe( 'List all products', () => {

		it('defaults', async () => {
			const result = await productsApi.listAll.products();
			expect( result.statusCode ).toEqual( 200 );
			expect( result.headers['x-wp-total'] ).toEqual( '18' );
			expect( result.headers['x-wp-totalpages'] ).toEqual( '2' );
		});

	} );

});
