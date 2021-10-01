/**
 * Internal dependencies
 */
const { createSampleData, deleteSampleData } = require( '../../data/products' );
const { productsApi } = require('../../endpoints/products');

/**
 * Tests for the WooCommerce Products API.
 *
 * @group api
 * @group products
 *
 */
 describe( 'Products API tests', () => {

	const PRODUCTS_COUNT = 20;
	let sampleData;

	beforeAll( async () => {
		sampleData = await createSampleData();
	}, 10000 );

	afterAll( async () => {
		await deleteSampleData( sampleData );
	}, 10000 );

	describe( 'List all products', () => {

		it( 'defaults', async () => {
			const result = await productsApi.listAll.products();
			expect( result.statusCode ).toEqual( 200 );
			expect( result.headers['x-wp-total'] ).toEqual( PRODUCTS_COUNT.toString() );
			expect( result.headers['x-wp-totalpages'] ).toEqual( '2' );
		} );

		it( 'pagination', async () => {
			const pageSize = 6;
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
			expect( page1.headers['x-wp-total'] ).toEqual( PRODUCTS_COUNT.toString() );
			expect( page1.headers['x-wp-totalpages'] ).toEqual( '4' );
			
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
				offset: pageSize + 1,
			} );
			// The offset pushes the result set 1 product past the start of page 2.
			expect( page2Offset.body ).toEqual(
				expect.not.arrayContaining( [
					expect.objectContaining( { id: page2.body[0].id } )
				] )
			);
			expect( page2Offset.body[0].id ).toEqual( page2.body[1].id );

			// Verify the last page only has 2 products as we expect.
			const lastPage = await productsApi.listAll.products( {
				per_page: pageSize,
				page: 4,
			} );
			expect( Array.isArray( lastPage.body ) ).toBe( true );
			expect( lastPage.body ).toHaveLength( 2 );

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
			expect( allProductIds ).toHaveLength( PRODUCTS_COUNT );

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
			expect( excluded.body ).toHaveLength( PRODUCTS_COUNT - productsToFilter.length );
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
			expect( result1.headers['x-wp-total'] ).toEqual( '16' );
	
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

			// Verify that subcategories are included.
			const result1 = await productsApi.listAll.products( {
				per_page: 20,
				category: sampleData.categories.clothing.id,
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toEqual( expect.arrayContaining( accessory ) );
			expect( result1.body ).toEqual( expect.arrayContaining( hoodies ) );

			// Verify sibling categories are not.
			const result2 = await productsApi.listAll.products( {
				category: sampleData.categories.hoodies.id,
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toEqual( expect.not.arrayContaining( accessory ) );
			expect( result2.body ).toEqual( expect.arrayContaining( hoodies ) );
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
				expect.objectContaining( { name: 'Parent Product' } ),
				expect.objectContaining( { name: 'Child Product' } ),
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
			const red = sampleData.attributes.colors.find( term => term.name === 'Red' );

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
			const result = await productsApi.listAll.products( {
				shipping_class: sampleData.shippingClasses.freight.id,
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
			const coolProducts = [
				expect.objectContaining( { name: 'Sunglasses' } ),
				expect.objectContaining( { name: 'Hoodie with Pocket' } ),
				expect.objectContaining( { name: 'Beanie' } ),
			];

			const result = await productsApi.listAll.products( {
				tag: sampleData.tags.cool.id,
			} );

			expect( result.statusCode ).toEqual( 200 );
			expect( result.body ).toHaveLength( coolProducts.length );
			expect( result.body ).toEqual( expect.arrayContaining( coolProducts ) );
		} );

		it( 'parent', async () => {
			const result1 = await productsApi.listAll.products( {
				parent: sampleData.hierarchicalProducts.parent.id,
			} );
			expect( result1.statusCode ).toEqual( 200 );
			expect( result1.body ).toHaveLength( 1 );
			expect( result1.body[0].name ).toBe( 'Child Product' );

			const result2 = await productsApi.listAll.products( {
				parent_exclude: sampleData.hierarchicalProducts.parent.id,
			} );
			expect( result2.statusCode ).toEqual( 200 );
			expect( result2.body ).toEqual( expect.not.arrayContaining( [
				expect.objectContaining( { name: 'Child Product' } ),
			] ) );
		} );

		describe( 'orderby', () => {
			const productNamesAsc = [
				'Album',
				'Beanie',
				'Beanie with Logo',
				'Belt',
				'Cap',
				'Child Product',
				'Hoodie',
				'Hoodie with Logo',
				'Hoodie with Pocket',
				'Hoodie with Zipper',
				'Logo Collection',
				'Long Sleeve Tee',
				'Parent Product',
				'Polo',
				'Single',
				'Sunglasses',
				'T-Shirt',
				'T-Shirt with Logo',
				'V-Neck T-Shirt',
				'WordPress Pennant',
			];
			const productNamesDesc = [ ...productNamesAsc ].reverse();
			const productNamesByRatingAsc = [
				'Sunglasses',
				'Cap',
				'T-Shirt',
			];
			const productNamesByRatingDesc = [ ...productNamesByRatingAsc ].reverse();
			const productNamesByPopularityDesc = [
				'Beanie with Logo',
				'Single',
				'T-Shirt',
			];
			const productNamesByPopularityAsc = [ ...productNamesByPopularityDesc ].reverse();

			it( 'default', async () => {
				// Default = date desc.
				const result = await productsApi.listAll.products();
				expect( result.statusCode ).toEqual( 200 );

				// Verify all dates are in descending order.
				let lastDate = Date.now();
				result.body.forEach( ( { date_created_gmt } ) => {
					const created = Date.parse( date_created_gmt + '.000Z' );
					expect( lastDate ).toBeGreaterThan( created );
					lastDate = created;
				} );
			} );

			it( 'date', async () => {
				const result = await productsApi.listAll.products( {
					order: 'asc',
					orderby: 'date',
				} );
				expect( result.statusCode ).toEqual( 200 );

				// Verify all dates are in ascending order.
				let lastDate = 0;
				result.body.forEach( ( { date_created_gmt } ) => {
					const created = Date.parse( date_created_gmt + '.000Z' );
					expect( created ).toBeGreaterThan( lastDate );
					lastDate = created;
				} );
			} );

			it( 'id', async () => {
				const result1 = await productsApi.listAll.products( {
					order: 'asc',
					orderby: 'id',
				} );
				expect( result1.statusCode ).toEqual( 200 );

				// Verify all results are in ascending order.
				let lastId = 0;
				result1.body.forEach( ( { id } ) => {
					expect( id ).toBeGreaterThan( lastId );
					lastId = id;
				} );

				const result2 = await productsApi.listAll.products( {
					order: 'desc',
					orderby: 'id',
				} );
				expect( result2.statusCode ).toEqual( 200 );

				// Verify all results are in descending order.
				lastId = Number.MAX_SAFE_INTEGER;
				result2.body.forEach( ( { id } ) => {
					expect( lastId ).toBeGreaterThan( id );
					lastId = id;
				} );
			} );

			it( 'title', async () => {
				const result1 = await productsApi.listAll.products( {
					order: 'asc',
					orderby: 'title',
					per_page: productNamesAsc.length,
				} );
				expect( result1.statusCode ).toEqual( 200 );

				// Verify all results are in ascending order.
				result1.body.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesAsc[ idx ] );
				} );

				const result2 = await productsApi.listAll.products( {
					order: 'desc',
					orderby: 'title',
					per_page: productNamesDesc.length,
				} );
				expect( result2.statusCode ).toEqual( 200 );

				// Verify all results are in descending order.
				result2.body.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesDesc[ idx ] );
				} );
			} );

			// This case will remain skipped until orderby slug is fixed.
			// See: https://github.com/woocommerce/woocommerce/issues/30354#issuecomment-925955099.
			it.skip( 'slug', async () => {
				const result1 = await productsApi.listAll.products( {
					order: 'asc',
					orderby: 'slug',
					per_page: productNamesAsc.length,
				} );
				expect( result1.statusCode ).toEqual( 200 );

				// Verify all results are in ascending order.
				result1.body.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesAsc[ idx ] );
				} );

				const result2 = await productsApi.listAll.products( {
					order: 'desc',
					orderby: 'slug',
					per_page: productNamesDesc.length,
				} );
				expect( result2.statusCode ).toEqual( 200 );

				// Verify all results are in descending order.
				result2.body.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesDesc[ idx ] );
				} );
			} );

			it( 'price', async () => {
				const productNamesMinPriceAsc = [
					'Parent Product',
					'Child Product',
					'Single',
					'WordPress Pennant',
					'Album',
					'V-Neck T-Shirt',
					'Cap',
					'Beanie with Logo',
					'T-Shirt with Logo',
					'Beanie',
					'T-Shirt',
					'Logo Collection',
					'Polo',
					'Long Sleeve Tee',
					'Hoodie with Pocket',
					'Hoodie',
					'Hoodie with Zipper',
					'Hoodie with Logo',
					'Belt',
					'Sunglasses',
				];
				const result1 = await productsApi.listAll.products( {
					order: 'asc',
					orderby: 'price',
					per_page: productNamesMinPriceAsc.length
				} );
				expect( result1.statusCode ).toEqual( 200 );
				expect( result1.body ).toHaveLength( productNamesMinPriceAsc.length );

				// Verify all results are in ascending order.
				// The query uses the min price calculated in the product meta lookup table,
				// so we can't just check the price property of the response.
				result1.body.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesMinPriceAsc[ idx ] );
				} );

				const productNamesMaxPriceDesc = [
					'Sunglasses',
					'Belt',
					'Hoodie',
					'Logo Collection',
					'Hoodie with Logo',
					'Hoodie with Zipper',
					'Hoodie with Pocket',
					'Long Sleeve Tee',
					'V-Neck T-Shirt',
					'Polo',
					'T-Shirt',
					'Beanie',
					'T-Shirt with Logo',
					'Beanie with Logo',
					'Cap',
					'Album',
					'WordPress Pennant',
					'Single',
					'Child Product',
					'Parent Product',
				];

				const result2 = await productsApi.listAll.products( {
					order: 'desc',
					orderby: 'price',
					per_page: productNamesMaxPriceDesc.length
				} );
				expect( result2.statusCode ).toEqual( 200 );
				expect( result2.body ).toHaveLength( productNamesMaxPriceDesc.length );

				// Verify all results are in descending order.
				// The query uses the max price calculated in the product meta lookup table,
				// so we can't just check the price property of the response.
				result2.body.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesMaxPriceDesc[ idx ] );
				} );
			} );

			// This case will remain skipped until orderby include is fixed.
			// See: https://github.com/woocommerce/woocommerce/issues/30354#issuecomment-925955099.
			it.skip( 'include', async () => {
				const includeIds = [
					sampleData.groupedProducts[ 0 ].id,
					sampleData.simpleProducts[ 3 ].id,
					sampleData.hierarchicalProducts.parent.id,
				];

				const result1 = await productsApi.listAll.products( {
					order: 'asc',
					orderby: 'include',
					include: includeIds.join( ',' ),
				} );
				expect( result1.statusCode ).toEqual( 200 );
				expect( result1.body ).toHaveLength( includeIds.length );

				// Verify all results are in proper order.
				result1.body.forEach( ( { id }, idx ) => {
					expect( id ).toBe( includeIds[ idx ] );
				} );

				const result2 = await productsApi.listAll.products( {
					order: 'desc',
					orderby: 'include',
					include: includeIds.join( ',' ),
				} );
				expect( result2.statusCode ).toEqual( 200 );
				expect( result2.body ).toHaveLength( includeIds.length );

				// Verify all results are in proper order.
				result2.body.forEach( ( { id }, idx ) => {
					expect( id ).toBe( includeIds[ idx ] );
				} );
			} );

			it( 'rating (desc)', async () => {
				const result2 = await productsApi.listAll.products( {
					order: 'desc',
					orderby: 'rating',
					per_page: productNamesByRatingDesc.length,
				} );
				expect( result2.statusCode ).toEqual( 200 );

				// Verify all results are in descending order.
				result2.body.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesByRatingDesc[ idx ] );
				} );
			} );

			// This case will remain skipped until ratings can be sorted ascending.
			// See: https://github.com/woocommerce/woocommerce/issues/30354#issuecomment-925955099.
			it.skip( 'rating (asc)', async () => {
				const result1 = await productsApi.listAll.products( {
					order: 'asc',
					orderby: 'rating',
					per_page: productNamesByRatingAsc.length,
				} );
				expect( result1.statusCode ).toEqual( 200 );

				// Verify all results are in ascending order.
				result1.body.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesByRatingAsc[ idx ] );
				} );
			} );

			it( 'popularity (desc)', async () => {
				const result2 = await productsApi.listAll.products( {
					order: 'desc',
					orderby: 'popularity',
					per_page: productNamesByPopularityDesc.length,
				} );
				expect( result2.statusCode ).toEqual( 200 );

				// Verify all results are in descending order.
				result2.body.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesByPopularityDesc[ idx ] );
				} );
			} );

			// This case will remain skipped until popularity can be sorted ascending.
			// See: https://github.com/woocommerce/woocommerce/issues/30354#issuecomment-925955099.
			it.skip( 'popularity (asc)', async () => {
				const result1 = await productsApi.listAll.products( {
					order: 'asc',
					orderby: 'popularity',
					per_page: productNamesByPopularityAsc.length,
				} );
				expect( result1.statusCode ).toEqual( 200 );

				// Verify all results are in ascending order.
				result1.body.forEach( ( { name }, idx ) => {
					expect( name ).toBe( productNamesByPopularityAsc[ idx ] );
				} );
			} );
		} );
	} );
} );
