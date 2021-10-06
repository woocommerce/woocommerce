/* eslint-disable jest/no-export, jest/no-disabled-tests */
/**
 * Internal dependencies
 */
const { HTTPClientFactory, VariableProduct, ProductVariation } = require( '@woocommerce/api' );

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
 * Create a variable product and retrieve via the API.
 */
const runVariableProductAPITest = () => {
	describe('REST API > Variable Product', () => {
		let client;
		let defaultVariableProduct;
		let defaultVariations;
		let baseVariableProduct;
		let product;
		let variations = [];
		let productRepository;
		let variationRepository;

		beforeAll(async () => {
			defaultVariableProduct = config.get('products.variable');
			defaultVariations = config.get('products.variations');
			const admin = config.get('users.admin');
			const url = config.get('url');

			client = HTTPClientFactory.build(url)
				.withBasicAuth(admin.username, admin.password)
				.withIndexPermalinks()
				.create();
		});

		it('can create a variable product', async () => {
			productRepository = VariableProduct.restRepository(client);

			// Check properties of product in the create product response.
			product = await productRepository.create(defaultVariableProduct);
			expect(product).toEqual(expect.objectContaining(defaultVariableProduct));
		});

		it('can add variations', async () => {
			variationRepository = ProductVariation.restRepository(client);
			for (let v = 0; v < defaultVariations.length; v++) {
				const variation = await variationRepository.create(product.id, defaultVariations[v]);
				// Test that variation id is a number.
				expect(variation.id).toBeGreaterThan(0);
				variations.push(variation.id);
			}

			baseVariableProduct = {
				id: product.id,
				...defaultVariableProduct,
				variations,
			};
		});

		it('can retrieve a transformed variable product', async () => {
			// Read product via the repository.
			const transformed = await productRepository.read(product.id);
			expect(transformed).toEqual(expect.objectContaining(baseVariableProduct));
		});

		it('can retrieve transformed product variations', async () => {
			// Read variations via the repository.
			const transformed = await variationRepository.list(product.id);
			expect(transformed).toHaveLength(defaultVariations.length);
		});

		it('can delete a variation', async () => {
			const variationId = baseVariableProduct.variations.pop();
			const status = variationRepository.delete(product.id, variationId);
			expect(status).toBeTruthy();
		});

		it('can delete a variable product', async () => {
			const status = productRepository.delete(product.id);
			expect(status).toBeTruthy();
		});
	});
}

module.exports = runVariableProductAPITest;
