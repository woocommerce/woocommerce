/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const { HTTPClientFactory, ExternalProduct } = require( '@woocommerce/api' );

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
const runExternalProductAPITest = () => {
	// @todo: add a call to ensure pretty permalinks are enabled once settings api is in use.
	describe('REST API > External Product', () => {
		let client;
		let defaultExternalProduct;
		let product;
		let repository;

		beforeAll(async () => {
			defaultExternalProduct = config.get( 'products.external' );
			const admin = config.get( 'users.admin' );
			const url = config.get( 'url' );

			client = HTTPClientFactory.build( url )
				.withBasicAuth( admin.username, admin.password )
				.withIndexPermalinks()
				.create();
		} );

		it('can create an external product', async () => {
			repository = ExternalProduct.restRepository( client );

			// Check properties of product in the create product response.
			product = await repository.create( defaultExternalProduct );
			expect( product ).toEqual( expect.objectContaining( defaultExternalProduct ) );
		});

		it('can retrieve a raw external product', async () => {
			const rawProperties = {
				id: product.id,
				button_text: defaultExternalProduct.buttonText,
				external_url: defaultExternalProduct.externalUrl,
				price: defaultExternalProduct.regularPrice,
			};

			// Read product directly from api.
			const response = await client.get( `/wc/v3/products/${product.id}` );
			expect( response.statusCode ).toBe( 200 );
			expect( response.data ).toEqual( expect.objectContaining( rawProperties ) );
		});

		it('can retrieve a transformed external product', async () => {
			const transformedProperties = {
				...defaultExternalProduct,
				id: product.id,
				price: defaultExternalProduct.regularPrice,
			};

			// Read product via the repository.
			const transformed = await repository.read( product.id );
			expect( transformed ).toEqual( expect.objectContaining( transformedProperties ) );
		});

		it('can delete an external product', async () => {
			const status = repository.delete( product.id );
			expect( status ).toBeTruthy();
		});
	});
};

module.exports = runExternalProductAPITest;
