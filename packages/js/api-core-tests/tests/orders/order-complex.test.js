const {
	taxRatesApi,
	productsApi,
	ordersApi,
	variationsApi,
} = require( '../../endpoints' );
const { getOrderExample, getExampleTaxRate } = require( '../../data' );

/**
 * Simple product with Standard tax rate
 */
const simpleProduct = {
	name: 'Black Compact Keyboard',
	regular_price: '10.00',
	tax_class: 'standard',
};

/**
 * Variable product with 1 variation with Reduced tax rate
 */
const variableProduct = {
	name: 'Unbranded Granite Shirt',
	type: 'variable',
	tax_class: 'reduced-rate',
	defaultAttributes: [
		{
			name: 'Size',
			option: 'Medium',
		},
		{
			name: 'Colour',
			option: 'Blue',
		},
	],
	attributes: [
		{
			name: 'Colour',
			visible: true,
			variation: true,
			options: [ 'Red', 'Green', 'Blue' ],
		},
		{
			name: 'Size',
			visible: true,
			variation: true,
			options: [ 'Small', 'Medium', 'Large' ],
		},
		{
			name: 'Logo',
			visible: true,
			variation: true,
			options: [ 'Woo', 'WordPress' ],
		},
	],
};
const variation = {
	regular_price: '20.00',
	tax_class: 'reduced-rate',
	attributes: [
		{
			name: 'Size',
			option: 'Large',
		},
		{
			name: 'Colour',
			option: 'Red',
		},
	],
};

/**
 * External product with Zero rate tax
 */
const externalProduct = {
	name: 'Ergonomic Steel Computer',
	regular_price: '400.00',
	type: 'external',
	tax_class: 'zero-rate',
};

/**
 * Grouped product with 1 linked product
 */
const groupedProduct = {
	name: 'Full Modern Computer Set',
	type: 'grouped',
};

/**
 * Tax rates for each tax class
 */
const standardTaxRate = getExampleTaxRate();
const reducedTaxRate = {
	name: 'Reduced Rate',
	rate: '1.0000',
	class: 'reduced-rate',
};
const zeroTaxRate = {
	name: 'Zero Rate',
	rate: '0.0000',
	class: 'zero-rate',
};

/**
 * Expected totals
 */
const expectedOrderTotal = '442.20';
const expectedTaxTotal = '2.20';
const expectedSimpleProductTaxTotal = '1.00';
const expectedVariableProductTaxTotal = '0.20';
const expectedExternalProductTaxTotal = '0.00';

let order;

/**
 *
 * Test for adding a complex order with different product types and tax classes.
 *
 * @group orders
 * @group api
 * @group tax-rates
 */
describe( 'Orders API test', () => {
	beforeAll( async () => {
		// Create a tax rate for each tax class, and save their ID's
		const { body: createdStandardRate } = await taxRatesApi.create.taxRate(
			standardTaxRate
		);
		standardTaxRate.id = createdStandardRate.id;

		const { body: createdReducedRate } = await taxRatesApi.create.taxRate(
			reducedTaxRate
		);
		reducedTaxRate.id = createdReducedRate.id;

		const { body: createdZeroRate } = await taxRatesApi.create.taxRate(
			zeroTaxRate
		);
		zeroTaxRate.id = createdZeroRate.id;

		// Create a simple product
		const { body: createdSimpleProduct } = await productsApi.create.product(
			simpleProduct
		);
		simpleProduct.id = createdSimpleProduct.id;

		// Create a variable product with 1 variation
		const {
			body: createdVariableProduct,
		} = await productsApi.create.product( variableProduct );
		variableProduct.id = createdVariableProduct.id;
		await variationsApi.create.variation( variableProduct.id, variation );

		// Create a grouped product using the simple product created earlier.
		const {
			body: createdGroupedProduct,
		} = await productsApi.create.product( groupedProduct );
		groupedProduct.grouped_products = [ simpleProduct.id ]; // Link the simple product
		groupedProduct.id = createdGroupedProduct.id;

		// Create an external product
		const {
			body: createdExternalProduct,
		} = await productsApi.create.product( externalProduct );
		externalProduct.id = createdExternalProduct.id;
	} );

	afterAll( async () => {
		// Delete order
		await ordersApi.delete.order( order.id, true );

		// Delete products
		await productsApi.batch.products( {
			delete: [
				simpleProduct.id,
				variableProduct.id,
				externalProduct.id,
				groupedProduct.id,
			],
		} );

		// Delete tax rates
		await taxRatesApi.batch.taxRates( {
			delete: [ standardTaxRate.id, zeroTaxRate.id, reducedTaxRate.id ],
		} );
	} );

	it( 'can add complex order', async () => {
		// Create an order with products having different tax classes
		const createOrderPayload = {
			...getOrderExample(),
			shipping_lines: [],
			fee_lines: [],
			coupon_lines: [],
			line_items: [
				{ product_id: simpleProduct.id },
				{ product_id: variableProduct.id },
				{ product_id: externalProduct.id },
				{ product_id: groupedProduct.id },
			],
		};
		const { status, body } = await ordersApi.create.order(
			createOrderPayload
		);
		order = body;

		expect( status ).toEqual( ordersApi.create.responseCode );

		// Verify totals
		expect( body.total ).toEqual( expectedOrderTotal );
		expect( body.total_tax ).toEqual( expectedTaxTotal );

		// Verify tax total of each product line item
		const actualSimpleProductLineItem = body.line_items.find(
			( { product_id } ) => product_id === simpleProduct.id
		);
		const actualVariableProductLineItem = body.line_items.find(
			( { product_id } ) => product_id === variableProduct.id
		);
		const actualGroupedProductLineItem = body.line_items.find(
			( { product_id } ) => product_id === groupedProduct.id
		);
		const actualExternalProductLineItem = body.line_items.find(
			( { product_id } ) => product_id === externalProduct.id
		);
		expect( actualSimpleProductLineItem.total_tax ).toEqual(
			expectedSimpleProductTaxTotal
		);
		expect( actualGroupedProductLineItem.total_tax ).toEqual(
			expectedSimpleProductTaxTotal
		);
		expect( actualVariableProductLineItem.total_tax ).toEqual(
			expectedVariableProductTaxTotal
		);
		expect( actualExternalProductLineItem.total_tax ).toEqual(
			expectedExternalProductTaxTotal
		);
	} );
} );
