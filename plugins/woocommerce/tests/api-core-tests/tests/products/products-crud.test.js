const { test, expect } = require( '@playwright/test' );

/**
 * Internal dependencies
 */
const {
	simpleProduct,
	virtualProduct,
	variableProduct,
} = require( '../../data/products-crud' );
const { batch } = require( '../../data/shared/batch-update' );

/**
 * Tests for the WooCommerce Products API.
 * These tests cover API endpoints for creating, retrieving, updating, and deleting a single product.
 *
 * @group api
 * @group products
 *
 */
test.describe( 'Products API tests: CRUD', () => {
	let productId;

	test( 'can add a simple product', async ( { request } ) => {
		const response = await request.post( 'wp-json/wc/v3/products', {
			data: simpleProduct,
		} );
		const responseJSON = await response.json();
		productId = responseJSON.id;

		expect( response.status() ).toEqual( 201 );
		expect( typeof productId ).toEqual( 'number' );
		expect( responseJSON ).toMatchObject( simpleProduct );
		expect( responseJSON.type ).toEqual( 'simple' );
		expect( responseJSON.status ).toEqual( 'publish' );
		expect( responseJSON.virtual ).toEqual( false );
		expect( responseJSON.downloadable ).toEqual( false );
		expect( responseJSON.shipping_required ).toEqual( true );
	} );

	test( 'can add a virtual product', async ( { request } ) => {
		const response = await request.post( 'wp-json/wc/v3/products', {
			data: virtualProduct,
		} );
		const responseJSON = await response.json();
		const virtualProductId = responseJSON.id;

		expect( response.status() ).toEqual( 201 );
		expect( typeof virtualProductId ).toEqual( 'number' );
		expect( responseJSON ).toMatchObject( virtualProduct );
		expect( responseJSON.type ).toEqual( 'simple' );
		expect( responseJSON.status ).toEqual( 'publish' );
		expect( responseJSON.shipping_required ).toEqual( false );

		// Cleanup: Delete the virtual product
		await request.delete( `wp-json/wc/v3/products/${ virtualProductId }`, {
			data: {
				force: true,
			},
		} );
	} );

	test( 'can add a variable product', async ( { request } ) => {
		const response = await request.post( 'wp-json/wc/v3/products', {
			data: variableProduct,
		} );
		const responseJSON = await response.json();
		const variableProductId = responseJSON.id;

		expect( response.status() ).toEqual( 201 );
		expect( typeof variableProductId ).toEqual( 'number' );
		expect( responseJSON ).toMatchObject( variableProduct );
		expect( responseJSON.status ).toEqual( 'publish' );

		// Cleanup: Delete the variable product
		await request.delete( `wp-json/wc/v3/products/${ variableProductId }`, {
			data: {
				force: true,
			},
		} );
	} );

	test( 'can view a single product', async ( { request } ) => {
		const response = await request.get(
			`wp-json/wc/v3/products/${ productId }`
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( responseJSON.id ).toEqual( productId );
	} );

	test( 'can update a single product', async ( { request } ) => {
		const updatePayload = {
			regular_price: '25.99',
		};

		const response = await request.put(
			`wp-json/wc/v3/products/${ productId }`,
			{
				data: updatePayload,
			}
		);
		const responseJSON = await response.json();

		expect( response.status() ).toEqual( 200 );
		expect( responseJSON.id ).toEqual( productId );
		expect( responseJSON.regular_price ).toEqual(
			updatePayload.regular_price
		);
	} );

	test( 'can delete a product', async ( { request } ) => {
		const response = await request.delete(
			`wp-json/wc/v3/products/${ productId }`,
			{
				data: {
					force: true,
				},
			}
		);
		const responseJSON = await response.json();

		expect( response.status() ).toEqual( 200 );
		expect( responseJSON.id ).toEqual( productId );

		const retrieveDeletedProductResponse = await request.get(
			`wp-json/wc/v3/products/${ productId }`
		);
		expect( retrieveDeletedProductResponse.status() ).toEqual( 404 );
	} );

	test.describe( 'Batch update products', () => {
		const product1 = {
			...simpleProduct,
			name: 'Batch Created Product 1',
		};
		const product2 = {
			...simpleProduct,
			name: 'Batch Created Product 2',
		};
		const expectedProducts = [ product1, product2 ];

		test( 'can batch create products', async ( { request } ) => {
			// Send request to batch create products
			const batchCreatePayload = batch( 'create', expectedProducts );
			const response = await request.post(
				'wp-json/wc/v3/products/batch',
				{
					data: batchCreatePayload,
				}
			);
			const responseJSON = await response.json();
			const actualBatchCreatedProducts = responseJSON.create;

			expect( response.status() ).toEqual( 200 );
			expect( actualBatchCreatedProducts ).toHaveLength(
				expectedProducts.length
			);

			// Verify id and name of the batch created products
			for ( let i = 0; i < actualBatchCreatedProducts.length; i++ ) {
				const { id, name } = actualBatchCreatedProducts[ i ];

				expect( typeof id ).toEqual( 'number' );
				expect( name ).toEqual( expectedProducts[ i ].name );

				// Save the product ID for use later in the batch update and delete tests
				expectedProducts[ i ].id = id;
			}
		} );

		test( 'can batch update products', async ( { request } ) => {
			// Send request to batch update the regular price
			const newRegularPrice = '12.34';
			for ( let i = 0; i < expectedProducts.length; i++ ) {
				expectedProducts[ i ].regular_price = newRegularPrice;
			}
			const batchUpdatePayload = batch( 'update', expectedProducts );
			const response = await request.put(
				'wp-json/wc/v3/products/batch',
				{
					data: batchUpdatePayload,
				}
			);
			const responseJSON = await response.json();
			const actualUpdatedProducts = responseJSON.update;

			expect( response.status() ).toEqual( 200 );
			expect( actualUpdatedProducts ).toHaveLength(
				expectedProducts.length
			);

			// Verify that the regular price of each product was updated
			for ( let i = 0; i < actualUpdatedProducts.length; i++ ) {
				const { id, regular_price } = actualUpdatedProducts[ i ];

				expect( id ).toEqual( expectedProducts[ i ].id );
				expect( regular_price ).toEqual( newRegularPrice );
			}
		} );

		test( 'can batch delete products', async ( { request } ) => {
			// Send request to batch delete the products created earlier
			const idsToDelete = expectedProducts.map( ( { id } ) => id );
			const batchDeletePayload = batch( 'delete', idsToDelete );
			const response = await request.post(
				'wp-json/wc/v3/products/batch',
				{
					data: batchDeletePayload,
				}
			);
			const responseJSON = await response.json();
			const actualBatchDeletedProducts = responseJSON.delete;

			expect( response.status() ).toEqual( 200 );
			expect( actualBatchDeletedProducts ).toHaveLength(
				expectedProducts.length
			);

			// Verify that the correct products were deleted
			for ( let i = 0; i < actualBatchDeletedProducts.length; i++ ) {
				const deletedProduct = actualBatchDeletedProducts[ i ];
				expect( deletedProduct ).toMatchObject( expectedProducts[ i ] );
			}

			// Verify that the deleted product ID's can no longer be retrieved
			for ( const id of idsToDelete ) {
				const response = await request.get(
					`wp-json/wc/v3/products/${ id }`
				);
				expect( response.status() ).toEqual( 404 );
			}
		} );
	} );
} );
