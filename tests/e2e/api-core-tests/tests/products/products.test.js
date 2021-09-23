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
 describe( 'Products API tests', () => {

	describe( 'List all products', () => {

		it( 'defaults', async () => {
			const result = await productsApi.listAll.products();
			expect( result.statusCode ).toEqual( 200 );
			expect( result.headers['x-wp-total'] ).toEqual( '18' );
			expect( result.headers['x-wp-totalpages'] ).toEqual( '2' );
		} );

		it( 'pagination', async () => {
			const pageSize = 4;
			const page1 = await productsApi.listAll.products( {
				per_page: pageSize,
			} );
			const page2 = await productsApi.listAll.products( {
				per_page: pageSize,
				page: 2,
			} );
			expect( page1.statusCode ).toEqual( 200 );
			expect( page2.statusCode ).toEqual( 200 );

			// Verify total page count.
			expect( page1.headers['x-wp-total'] ).toEqual( '18' );
			expect( page1.headers['x-wp-totalpages'] ).toEqual( '5' );
			
			// Verify we get pageSize'd arrays.
			expect( Array.isArray( page1.body ) ).toBe( true );
			expect( Array.isArray( page2.body ) ).toBe( true );
			expect( page1.body ).toHaveLength( pageSize );
			expect( page2.body ).toHaveLength( pageSize );

			// Ensure all of the product IDs are unique (no page overlap).
			const allProductIds = page1.body.concat( page2.body ).reduce( ( acc, product ) => {
				acc[ product.id ] = 1;
				return acc;
			}, {} );
			expect( Object.keys( allProductIds ) ).toHaveLength( pageSize * 2 );

			// Verify that offset takes precedent over page number.
			const page2Offset = await productsApi.listAll.products( {
				per_page: pageSize,
				page: 2,
				offset: 5,
			} );
			// The offset pushes the result set 1 product past the start of page 2.
			expect( page2Offset.body ).toEqual(
				expect.not.arrayContaining( [
					expect.objectContaining( { id: page2.body[0].id } )
				] )
			);
			expect( page2Offset.body[0].id ).toEqual( page2.body[1].id );

			// Verify the last page only has 2 products as we expect.
			const page5 = await productsApi.listAll.products( {
				per_page: pageSize,
				page: 5,
			} );
			expect( Array.isArray( page5.body ) ).toBe( true );
			expect( page5.body ).toHaveLength( 2 );

			// Verify a page outside the total page count is empty.
			const page6 = await productsApi.listAll.products( {
				per_page: pageSize,
				page: 6,
			} );
			expect( Array.isArray( page6.body ) ).toBe( true );
			expect( page6.body ).toHaveLength( 0 );
		} );

		it( 'search', async () => {
			// Match in the short description.
			const result1 = await productsApi.listAll.products( {
				search: 'external'
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( 1 );
			expect( result1.body[0].name ).toBe( 'WordPress Pennant' );
	
			// Match in the product name.
			const result2 = await productsApi.listAll.products( {
				search: 'pocket'
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toHaveLength( 1 );
			expect( result2.body[0].name ).toBe( 'Hoodie with Pocket' );
		} );

		it( 'inclusion / exclusion', async () => {
			const allProducts = await productsApi.listAll.products( {
				per_page: 20,
			} );
			expect( allProducts.statusCode ).toEqual( 200 );
			const allProductIds = allProducts.body.map( product => product.id );
			expect( allProductIds ).toHaveLength( 18 );

			const productsToFilter = [
				allProductIds[2],
				allProductIds[4],
				allProductIds[7],
				allProductIds[13],
			];

			const included = await productsApi.listAll.products( {
				per_page: 20,
				include: productsToFilter.join( ',' ),
			} );
			expect( included.statusCode ).toEqual( 200 );
			expect( included.body ).toHaveLength( productsToFilter.length );
			expect( included.body ).toEqual(
				expect.arrayContaining(
					productsToFilter.map( id => expect.objectContaining( { id } ) )
				)
			);

			const excluded = await productsApi.listAll.products( {
				per_page: 20,
				exclude: productsToFilter.join( ',' ),
			} );
			expect( excluded.statusCode ).toEqual( 200 );
			expect( excluded.body ).toHaveLength( 18 - productsToFilter.length );
			expect( excluded.body ).toEqual(
				expect.not.arrayContaining(
					productsToFilter.map( id => expect.objectContaining( { id } ) )
				)
			);

		} );

		it( 'slug', async () => {
			// Match by slug.
			const result1 = await productsApi.listAll.products( {
				slug: 't-shirt-with-logo'
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( 1 );
			expect( result1.body[0].slug ).toBe( 't-shirt-with-logo' );
	
			// No matches
			const result2 = await productsApi.listAll.products( {
				slug: 'no-product-with-this-slug'
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toHaveLength( 0 );
		} );

		it( 'sku', async () => {
			// Match by SKU.
			const result1 = await productsApi.listAll.products( {
				sku: 'woo-sunglasses'
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( 1 );
			expect( result1.body[0].sku ).toBe( 'woo-sunglasses' );
	
			// No matches
			const result2 = await productsApi.listAll.products( {
				sku: 'no-product-with-this-sku'
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toHaveLength( 0 );
		} );

		it( 'type', async () => {
			const result1 = await productsApi.listAll.products( {
				type: 'simple'
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.headers['x-wp-total'] ).toEqual( '14' );
	
			const result2 = await productsApi.listAll.products( {
				type: 'external'
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toHaveLength( 1 );
			expect( result2.body[0].name ).toBe( 'WordPress Pennant' );

			const result3 = await productsApi.listAll.products( {
				type: 'variable'
			} );
			expect( result3.statusCode ).toEqual( 200 );
			expect( result3.body ).toHaveLength( 2 );

			const result4 = await productsApi.listAll.products( {
				type: 'grouped'
			} );
			expect( result4.statusCode ).toEqual( 200 );
			expect( result4.body ).toHaveLength( 1 );
			expect( result4.body[0].name ).toBe( 'Logo Collection' );
		} );

		it( 'featured', async () => {
			const featured = [
				expect.objectContaining( { name: 'Hoodie with Zipper' } ),
				expect.objectContaining( { name: 'Hoodie with Pocket' } ),
				expect.objectContaining( { name: 'Sunglasses' } ),
				expect.objectContaining( { name: 'Cap' } ),
				expect.objectContaining( { name: 'V-Neck T-Shirt' } ),
			];

			const result1 = await productsApi.listAll.products( {
				featured: true,
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( featured.length );
			expect( result1.body ).toEqual( expect.arrayContaining( featured ) );

			const result2 = await productsApi.listAll.products( {
				featured: false,
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toEqual( expect.not.arrayContaining( featured ) );
		} );
	} );

} );
