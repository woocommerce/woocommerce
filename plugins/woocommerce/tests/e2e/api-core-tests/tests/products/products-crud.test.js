/**
 * Internal dependencies
 */
const {
	simpleProduct,
	virtualProduct,
	variableProduct,
} = require( '../../data/products-crud' );
const { batch } = require( '../../data/shared/batch-update' );
const { productsApi } = require( '../../endpoints/products' );

/**
 * Tests for the WooCommerce Products API.
 * These tests cover API endpoints for creating, retrieving, updating, and deleting a single product.
 *
 * @group api
 * @group products
 *
 */
describe( 'Products API tests: CRUD', () => {
	let productId;

	it( 'can add a simple product', async () => {
		const { status, body } = await productsApi.create.product(
			simpleProduct
		);
		productId = body.id;

		expect( status ).toEqual( productsApi.create.responseCode );
		expect( typeof productId ).toEqual( 'number' );
		expect( body ).toMatchObject( simpleProduct );
		expect( body.type ).toEqual( 'simple' );
		expect( body.status ).toEqual( 'publish' );
		expect( body.virtual ).toEqual( false );
		expect( body.downloadable ).toEqual( false );
		expect( body.shipping_required ).toEqual( true );
	} );

	it( 'can add a virtual product', async () => {
		const { status, body } = await productsApi.create.product(
			virtualProduct
		);
		const virtualProductId = body.id;

		expect( status ).toEqual( productsApi.create.responseCode );
		expect( typeof virtualProductId ).toEqual( 'number' );
		expect( body ).toMatchObject( virtualProduct );
		expect( body.type ).toEqual( 'simple' );
		expect( body.status ).toEqual( 'publish' );
		expect( body.shipping_required ).toEqual( false );

		// Cleanup: Delete the virtual product
		await productsApi.delete.product( virtualProductId, true );
	} );

	it( 'can add a variable product', async () => {
		const { status, body } = await productsApi.create.product(
			variableProduct
		);
		const variableProductId = body.id;

		expect( status ).toEqual( productsApi.create.responseCode );
		expect( typeof variableProductId ).toEqual( 'number' );
		expect( body ).toMatchObject( variableProduct );
		expect( body.status ).toEqual( 'publish' );

		// Cleanup: Delete the variable product
		await productsApi.delete.product( variableProductId, true );
	} );

	it( 'can view a single product', async () => {
		const { status, body } = await productsApi.retrieve.product(
			productId
		);

		expect( status ).toEqual( productsApi.retrieve.responseCode );
		expect( body.id ).toEqual( productId );
	} );

	it( 'can update a single product', async () => {
		const updatePayload = {
			regular_price: '25.99',
		};
		const { status, body } = await productsApi.update.product(
			productId,
			updatePayload
		);

		expect( status ).toEqual( productsApi.update.responseCode );
		expect( body.id ).toEqual( productId );
		expect( body.regular_price ).toEqual( updatePayload.regular_price );
	} );

	it( 'can delete a product', async () => {
		const { status, body } = await productsApi.delete.product(
			productId,
			true
		);

		expect( status ).toEqual( productsApi.delete.responseCode );
		expect( body.id ).toEqual( productId );

		// Verify that the product can no longer be retrieved.
		const {
			status: retrieveDeletedProductStatus,
		} = await productsApi.retrieve.product( productId );
		expect( retrieveDeletedProductStatus ).toEqual( 404 );
	} );

	describe( 'Batch update products', () => {
		const product1 = { ...simpleProduct, name: 'Batch Created Product 1' };
		const product2 = { ...simpleProduct, name: 'Batch Created Product 2' };
		const expectedProducts = [ product1, product2 ];

		it( 'can batch create products', async () => {
			// Send request to batch create products
			const batchCreatePayload = batch( 'create', expectedProducts );
			const { status, body } = await productsApi.batch.products(
				batchCreatePayload
			);
			const actualBatchCreatedProducts = body.create;

			expect( status ).toEqual( productsApi.batch.responseCode );
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

		it( 'can batch update products', async () => {
			// Send request to batch update the regular price
			const newRegularPrice = '12.34';
			for ( let i = 0; i < expectedProducts.length; i++ ) {
				expectedProducts[ i ].regular_price = newRegularPrice;
			}
			const batchUpdatePayload = batch( 'update', expectedProducts );
			const { status, body } = await productsApi.batch.products(
				batchUpdatePayload
			);
			const actualUpdatedProducts = body.update;

			expect( status ).toEqual( productsApi.batch.responseCode );
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

		it( 'can batch delete products', async () => {
			// Send request to batch delete the products created earlier
			const idsToDelete = expectedProducts.map( ( { id } ) => id );
			const batchDeletePayload = batch( 'delete', idsToDelete );
			const { status, body } = await productsApi.batch.products(
				batchDeletePayload
			);
			const actualBatchDeletedProducts = body.delete;

			expect( status ).toEqual( productsApi.batch.responseCode );
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
				const { status } = await productsApi.retrieve.product( id );

				expect( status ).toEqual( 404 );
			}
		} );
	} );
} );
