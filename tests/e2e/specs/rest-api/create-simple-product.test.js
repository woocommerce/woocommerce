import { getTestConfig } from '@woocommerce/e2e-environment';
import { HTTPClientFactory } from "@woocommerce/api";
import { SimpleProduct } from '@woocommerce/api';

describe('REST API > Product Endpoint', () => {
	const productProperties = {
		name: 'Test Product',
		regular_price: '99.95',
		manage_stock: true,
		type: 'simple',
		stock_quantity: 1000,
	};
	let client;
	let product;

	beforeAll( () => {
		const testConfig = getTestConfig();
		const admin = testConfig.users.admin;

		client = HTTPClientFactory.build(testConfig.url)
			.withBasicAuth(admin.username, admin.password)
			.withIndexPermalinks()
			.create();
	} );

	it('can create a simple product', async () => {
		const repository = SimpleProduct.restRepository( client );
		const transformedProperties = {
			name: productProperties.name,
			regularPrice: productProperties.regular_price,
			trackInventory: productProperties.manage_stock,
			type: productProperties.type,
			remainingStock: productProperties.stock_quantity,
		};

		// Check properties of product in the create product response.
		product = await repository.create( productProperties );
		expect( product ).toEqual( expect.objectContaining( transformedProperties ) );
	});

	it('can retrieve a simple product', async () => {
		const combinedProperties = {
			...productProperties,
			id: product.id,
			stock_status: 'instock',
			catalog_visibility: 'visible',
			price: productProperties.regular_price,
			sale_price: '',
		};

		// Read product directly from api.
		const response = await client.get( `/wc/v3/products/${product.id}` );
		expect( response.statusCode ).toBe( 200 );
		expect( response.data ).toEqual( expect.objectContaining( combinedProperties ) );
	});
});
