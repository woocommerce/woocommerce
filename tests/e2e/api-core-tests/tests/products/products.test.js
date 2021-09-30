/**
 * Internal dependencies
 */
const { createSampleProducts } = require( '../../data/products' );
const { productsApi } = require('../../endpoints/products');
const { getRequest } = require( '../../utils/request' );

/**
 * Tests for the WooCommerce Products API.
 *
 * @group api
 * @group products
 *
 */
 describe( 'Products API tests', () => {

	beforeAll( async () => {
		await createSampleProducts();
	}, 7000 );

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

		it( 'categories', async () => {
			const accessory = [
				expect.objectContaining( { name: 'Beanie' } ),
			]
			const hoodies = [
				expect.objectContaining( { name: 'Hoodie with Zipper' } ),
				expect.objectContaining( { name: 'Hoodie with Pocket' } ),
				expect.objectContaining( { name: 'Hoodie with Logo' } ),
				expect.objectContaining( { name: 'Hoodie' } ),
			];

			// Get the Clothing category ID from the Logo Collection product.
			const result1 = await productsApi.listAll.products( {
				sku: 'logo-collection',
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( 1 );
			const clothingCatId = result1.body[0].categories[0].id;

			// Verify that subcategories are included.
			const result2 = await productsApi.listAll.products( {
				per_page: 20,
				category: clothingCatId,
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toEqual( expect.arrayContaining( accessory ) );
			expect( result2.body ).toEqual( expect.arrayContaining( hoodies ) );

			// Get the Hoodie category ID.
			const result3 = await productsApi.listAll.products( {
				sku: 'woo-hoodie-with-zipper',
			} );
			expect( result3.statusCode ).toEqual( 200 );
			expect( result3.body ).toHaveLength( 1 );
			const hoodieCatId = result3.body[0].categories[0].id;

			// Verify sibling categories are not.
			const result4 = await productsApi.listAll.products( {
				category: hoodieCatId,
			} );
			expect( result4.statusCode ).toEqual( 200 );
			expect( result4.body ).toEqual( expect.not.arrayContaining( accessory ) );
			expect( result4.body ).toEqual( expect.arrayContaining( hoodies ) );
		} );

		it( 'on sale', async () => {
			const onSale = [
				expect.objectContaining( { name: 'Beanie with Logo' } ),
				expect.objectContaining( { name: 'Hoodie with Pocket' } ),
				expect.objectContaining( { name: 'Single' } ),
				expect.objectContaining( { name: 'Cap' } ),
				expect.objectContaining( { name: 'Belt' } ),
				expect.objectContaining( { name: 'Beanie' } ),
				expect.objectContaining( { name: 'Hoodie' } ),
			];

			const result1 = await productsApi.listAll.products( {
				on_sale: true,
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( onSale.length );
			expect( result1.body ).toEqual( expect.arrayContaining( onSale ) );

			const result2 = await productsApi.listAll.products( {
				on_sale: false,
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toEqual( expect.not.arrayContaining( onSale ) );
		} );

		it( 'price', async () => {
			const result1 = await productsApi.listAll.products( {
				min_price: 21,
				max_price: 28,
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( 1 );
			expect( result1.body[0].name ).toBe( 'Long Sleeve Tee' );
			expect( result1.body[0].price ).toBe( '25' );

			const result2 = await productsApi.listAll.products( {
				max_price: 5,
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toHaveLength( 1 );
			expect( result2.body[0].name ).toBe( 'Single' );
			expect( result2.body[0].price ).toBe( '2' );

			const result3 = await productsApi.listAll.products( {
				min_price: 5,
				order: 'asc',
				orderby: 'price',
			} );
			expect( result3.statusCode ).toEqual( 200 );
			expect( result3.body ).toEqual(
				expect.not.arrayContaining( [
					expect.objectContaining( { name: 'Single' } )
				] )
			);
		} );

		it( 'before / after', async () => {
			const before = [
				expect.objectContaining( { name: 'Album' } ),
				expect.objectContaining( { name: 'Single' } ),
				expect.objectContaining( { name: 'T-Shirt with Logo' } ),
				expect.objectContaining( { name: 'Beanie with Logo' } ),
			];
			const after = [
				expect.objectContaining( { name: 'Hoodie' } ),
				expect.objectContaining( { name: 'V-Neck T-Shirt' } ),
			];

			const result1 = await productsApi.listAll.products( {
				before: '2021-09-05T15:50:19',
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( before.length );
			expect( result1.body ).toEqual( expect.arrayContaining( before ) );

			const result2 = await productsApi.listAll.products( {
				after: '2021-09-18T15:50:18',
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toEqual( expect.not.arrayContaining( before ) );
			expect( result2.body ).toHaveLength( after.length );
			expect( result2.body ).toEqual( expect.arrayContaining( after ) );
		} );

		it( 'attributes', async () => {
			const { body: attributes } = await getRequest( 'products/attributes' );
			const color = attributes.find( attr => attr.name === 'Color' );
			const { body: colorTerms } = await getRequest( `products/attributes/${ color.id }/terms` );
			const red = colorTerms.find( term => term.name === 'Red' );

			const redProducts = [
				expect.objectContaining( { name: 'V-Neck T-Shirt' } ),
				expect.objectContaining( { name: 'Hoodie' } ),
				expect.objectContaining( { name: 'Beanie' } ),
				expect.objectContaining( { name: 'Beanie with Logo' } ),
			];

			const result = await productsApi.listAll.products( {
				attribute: 'pa_color',
				attribute_term: red.id,
			} );

			expect( result.statusCode ).toEqual( 200 );
			expect( result.body ).toHaveLength( redProducts.length );
			expect( result.body ).toEqual( expect.arrayContaining( redProducts ) );
		} );

		it( 'status', async () => {
			const result1 = await productsApi.listAll.products( {
				status: 'pending'
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( 1 );
			expect( result1.body[0].name ).toBe( 'Polo' );

			const result2 = await productsApi.listAll.products( {
				status: 'draft'
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toHaveLength( 0 );
		} );

		it( 'shipping class', async () => {
			const { body: shippingClasses } = await getRequest( 'products/shipping_classes' );
			const freight = shippingClasses.find( c => c.name === 'Freight' );

			const result = await productsApi.listAll.products( {
				shipping_class: freight.id,
			} );
			expect( result.statusCode ).toEqual( 200 );
			expect( result.body ).toHaveLength( 1 );
			expect( result.body[0].name ).toBe( 'Long Sleeve Tee' );
		} );

		it( 'tax class', async () => {
			const result = await productsApi.listAll.products( {
				tax_class: 'reduced-rate',
			} );
			expect( result.statusCode ).toEqual( 200 );
			expect( result.body ).toHaveLength( 1 );
			expect( result.body[0].name ).toBe( 'Sunglasses' );
		} );

		it( 'stock status', async () => {
			const result = await productsApi.listAll.products( {
				stock_status: 'onbackorder',
			} );
			expect( result.statusCode ).toEqual( 200 );
			expect( result.body ).toHaveLength( 1 );
			expect( result.body[0].name ).toBe( 'T-Shirt' );
		} );

		it( 'tags', async () => {
			const { body: tags } = await getRequest( 'products/tags' );
			const cool = tags.find( attr => attr.name === 'Cool' );

			const coolProducts = [
				expect.objectContaining( { name: 'Sunglasses' } ),
				expect.objectContaining( { name: 'Hoodie with Pocket' } ),
				expect.objectContaining( { name: 'Beanie' } ),
			];

			const result = await productsApi.listAll.products( {
				tag: cool.id,
			} );

			expect( result.statusCode ).toEqual( 200 );
			expect( result.body ).toHaveLength( coolProducts.length );
			expect( result.body ).toEqual( expect.arrayContaining( coolProducts ) );
		} );
	} );
} );
