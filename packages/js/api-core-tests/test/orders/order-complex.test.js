const {
	taxRatesApi,
	productsApi,
	ordersApi,
	variationsApi,
} = require( '../../endpoints' );
const {
	getOrderExample,
	getTaxRateExamples,
	getVariationExample,
	simpleProduct: defaultSimpleProduct,
	variableProduct: defaultVariableProduct,
	groupedProduct: defaultGroupedProduct,
	externalProduct: defaultExternalProduct,
} = require( '../../data' );

/**
 * Simple product with Standard tax rate
 */
const simpleProduct = {
	...defaultSimpleProduct,
	regular_price: '10.00',
	tax_class: 'standard',
};

/**
 * Variable product with 1 variation with Reduced tax rate
 */
const variableProduct = {
	...defaultVariableProduct,
	tax_class: 'reduced-rate',
};

const variation = {
	...getVariationExample(),
	regular_price: '20.00',
	tax_class: 'reduced-rate',
};

/**
 * External product with Zero rate tax
 */
const externalProduct = {
	...defaultExternalProduct,
	regular_price: '400.00',
	tax_class: 'zero-rate',
};

/**
 * Grouped product
 */
const groupedProduct = defaultGroupedProduct;

/**
 * Tax rates for each tax class
 */
const { standardTaxRate, reducedTaxRate, zeroTaxRate } = getTaxRateExamples();

/**
 * Delete all pre-existing tax rates.
 */
const deletePreExistingTaxRates = async () => {
	const { body } = await taxRatesApi.listAll.taxRates( {
		_fields: 'id',
	} );

	if ( Array.isArray( body ) && body.length > 0 ) {
		const ids = body.map( ( { id } ) => id );
		await taxRatesApi.batch.taxRates( { delete: ids } );
	}
};

/**
 * Create a tax rate for each tax class, and save their ID's.
 */
const createTaxRates = async () => {
	const taxRates = [ standardTaxRate, reducedTaxRate, zeroTaxRate ];

	for ( const taxRate of taxRates ) {
		const { body } = await taxRatesApi.create.taxRate( taxRate );
		taxRate.id = body.id;
	}
};

/**
 * Create simple, variable, grouped, and external products.
 */
const createProducts = async () => {
	// Create a simple product
	const { body: createdSimpleProduct } = await productsApi.create.product(
		simpleProduct
	);
	simpleProduct.id = createdSimpleProduct.id;

	// Create a variable product with 1 variation
	const { body: createdVariableProduct } = await productsApi.create.product(
		variableProduct
	);
	variableProduct.id = createdVariableProduct.id;
	await variationsApi.create.variation( variableProduct.id, variation );

	// Create a grouped product using the simple product created earlier.
	groupedProduct.grouped_products = [ simpleProduct.id ];
	const { body: createdGroupedProduct } = await productsApi.create.product(
		groupedProduct
	);
	groupedProduct.id = createdGroupedProduct.id;

	// Create an external product
	const { body: createdExternalProduct } = await productsApi.create.product(
		externalProduct
	);
	externalProduct.id = createdExternalProduct.id;
};

/**
 * The complex order to be created.
 */
const order = {
	...getOrderExample(),
	shipping_lines: [],
	fee_lines: [],
	coupon_lines: [],
	line_items: [],
};

/**
 * Expected totals
 */
const expectedOrderTotal = '442.20';
const expectedTaxTotal = '2.20';
const expectedSimpleProductTaxTotal = '1.00';
const expectedVariableProductTaxTotal = '0.20';
const expectedExternalProductTaxTotal = '0.00';

/**
 *
 * Test for adding a complex order with different product types and tax classes.
 *
 * @group api
 * @group orders
 *
 */
describe( 'Orders API test', () => {
	beforeAll( async () => {
		await deletePreExistingTaxRates();
		await createTaxRates();
		await createProducts();

		// Add line items to the order
		order.line_items = [
			{ product_id: simpleProduct.id },
			{ product_id: variableProduct.id },
			{ product_id: externalProduct.id },
			{ product_id: groupedProduct.id },
		];
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
		// Create the complex order and save its ID.
		const { status, body } = await ordersApi.create.order( order );
		order.id = body.id;

		expect( status ).toEqual( ordersApi.create.responseCode );

		// Verify order and tax totals
		expect( body.total ).toEqual( expectedOrderTotal );
		expect( body.total_tax ).toEqual( expectedTaxTotal );

		// Verify total tax of each product line item
		const expectedTaxTotalsPerLineItem = [
			[ simpleProduct, expectedSimpleProductTaxTotal ],
			[ variableProduct, expectedVariableProductTaxTotal ],
			[ groupedProduct, expectedSimpleProductTaxTotal ],
			[ externalProduct, expectedExternalProductTaxTotal ],
		];
		for ( const [
			product,
			expectedLineTaxTotal,
		] of expectedTaxTotalsPerLineItem ) {
			const { total_tax: actualLineTaxTotal } = body.line_items.find(
				// eslint-disable-next-line
				( { product_id } ) => product_id === product.id
			);

			expect( actualLineTaxTotal ).toEqual( expectedLineTaxTotal );
		}
	} );
} );
