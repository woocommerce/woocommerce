/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const { HTTPClientFactory, GroupedProduct, SimpleProduct } = require( '@woocommerce/api' );

/**
 * External dependencies
 */
const config = require( 'config' );
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

/**
 * Create an external product and retrieve via the API.
 */
const runGroupedProductAPITest = () => {
	// @todo: add a call to ensure pretty permalinks are enabled once settings api is in use.
	describe('REST API > Grouped Product', () => {
		let client;
		let defaultGroupedProduct;
		let baseGroupedProduct;
		let product;
		let groupedProducts = [];
		let repository;

		beforeAll(async () => {
			defaultGroupedProduct = config.get( 'products.grouped' );
			const admin = config.get( 'users.admin' );
			const url = config.get( 'url' );

			client = HTTPClientFactory.build( url )
				.withBasicAuth( admin.username, admin.password )
				.withIndexPermalinks()
				.create();

			// Create the simple products to be grouped first.
			repository = SimpleProduct.restRepository( client );
			for ( let c = 0; c < defaultGroupedProduct.groupedProducts.length; c++ ) {
				product = await repository.create( defaultGroupedProduct.groupedProducts[ c ] );
				groupedProducts.push( product.id );
			}
		});

		it('can create a grouped product', async () => {
			baseGroupedProduct = {
				...defaultGroupedProduct,
				groupedProducts,
			};
			repository = GroupedProduct.restRepository( client );

			// Check properties of product in the create product response.
			product = await repository.create( baseGroupedProduct );
			expect( product ).toEqual( expect.objectContaining( baseGroupedProduct ) );
		});

		it('can retrieve a raw grouped product', async () => {
			let rawProperties = {
				id: product.id,
				grouped_products: baseGroupedProduct.groupedProducts,
				...defaultGroupedProduct,
			};
			delete rawProperties['groupedProducts'];

			// Read product directly from api.
			const response = await client.get( `/wc/v3/products/${product.id}` );
			expect( response.statusCode ).toBe( 200 );
			expect( response.data ).toEqual( expect.objectContaining( rawProperties ) );
		});

		it('can retrieve a transformed grouped product', async () => {
			// Read product via the repository.
			const transformed = await repository.read( product.id );
			expect( transformed ).toEqual( expect.objectContaining( baseGroupedProduct ) );
		});

		it('can delete a grouped product', async () => {
			const status = repository.delete( product.id );
			expect( status ).toBeTruthy();
			// Delete the simple "child" products.
			groupedProducts.forEach( ( productId ) => {
				repository.delete( productId );
			});
		});
	});
};

module.exports = runGroupedProductAPITest;
