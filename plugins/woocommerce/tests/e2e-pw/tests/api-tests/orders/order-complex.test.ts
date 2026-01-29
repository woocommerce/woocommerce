/**
 * Internal dependencies
 */
import { test, expect } from '../../../fixtures/api-tests-fixtures';
import {
	getOrderExample,
	getTaxRateExamples,
	getVariationExample,
	simpleProduct as defaultSimpleProduct,
	variableProduct as defaultVariableProduct,
	groupedProduct as defaultGroupedProduct,
	externalProduct as defaultExternalProduct,
} from '../../../data';
import type {
	SimpleProduct,
	VariableProduct,
	GroupedProduct,
	ExternalProduct,
	Variation,
	TaxRate,
	OrderExample,
} from '../../../data';

/**
 * Extended product types with id and tax_class properties.
 */
interface SimpleProductWithId extends SimpleProduct {
	id?: number;
	tax_class?: string;
}

interface VariableProductWithId extends VariableProduct {
	id?: number;
	regular_price?: string;
	tax_class?: string;
}

interface VariationWithId extends Variation {
	id?: number;
	tax_class?: string;
}

interface ExternalProductWithId extends ExternalProduct {
	id?: number;
	tax_class?: string;
}

interface GroupedProductWithId extends GroupedProduct {
	id?: number;
	grouped_products?: number[];
}

interface TaxRateWithId extends TaxRate {
	id?: number;
}

interface OrderLineItem {
	product_id?: number;
}

interface OrderWithLineItems extends Omit< OrderExample, 'line_items' > {
	line_items: OrderLineItem[];
}

interface TaxClassResponse {
	code?: string;
	message?: string;
	slug?: string;
}

interface OrderLineItemResponse {
	product_id: number;
	total_tax: string;
}

interface OrderResponse {
	id: number;
	total: string;
	total_tax: string;
	line_items: OrderLineItemResponse[];
}

/**
 * Simple product with Standard tax rate
 */
const simpleProduct: SimpleProductWithId = {
	...defaultSimpleProduct,
	regular_price: '10.00',
	tax_class: 'standard',
};

/**
 * Variable product with 1 variation with Reduced tax rate
 */
const variableProduct: VariableProductWithId = {
	...defaultVariableProduct,
	regular_price: '20.00',
	tax_class: 'reduced-rate',
};

const variation: VariationWithId = {
	...getVariationExample(),
	regular_price: '20.00',
	tax_class: 'reduced-rate',
};

/**
 * External product with Zero rate tax
 */
const externalProduct: ExternalProductWithId = {
	...defaultExternalProduct,
	regular_price: '400.00',
	tax_class: 'zero-rate',
};

/**
 * Grouped product
 */
const groupedProduct: GroupedProductWithId = { ...defaultGroupedProduct };

/**
 * Tax rates for each tax class
 */
const { standardTaxRate, reducedTaxRate, zeroTaxRate } = getTaxRateExamples();
const standardTaxRateWithId: TaxRateWithId = { ...standardTaxRate };
const reducedTaxRateWithId: TaxRateWithId = { ...reducedTaxRate };
const zeroTaxRateWithId: TaxRateWithId = { ...zeroTaxRate };

/**
 * The complex order to be created.
 */
const order: OrderWithLineItems = {
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
 * Slugs of tax classes to delete at tear down.
 */
const taxClassSlugsToTearDown: string[] = [];

test.describe( 'Orders API test', () => {
	test.beforeAll( async ( { request } ) => {
		/**
		 * Delete all pre-existing tax rates.
		 */
		const response = await request.get( './wp-json/wc/v3/taxes', {
			params: {
				_fields: 'id',
			},
		} );
		const responseJSON: { id: number }[] = await response.json();

		if ( Array.isArray( responseJSON ) && responseJSON.length > 0 ) {
			const ids = responseJSON.map( ( { id } ) => id );

			await request.post( './wp-json/wc/v3/taxes/batch', {
				data: {
					delete: ids,
				},
				failOnStatusCode: true,
			} );
		}

		/**
		 * Create the default "Reduced rate" and "Zero rate" tax classes if they're missing.
		 * If successfully created, delete them at teardown to revert the list of tax classes.
		 *
		 * Allow 400 Bad Request as long as it's due to a duplicate tax class creation attempt.
		 * If not, fail immediately.
		 */
		for ( const name of [ 'Reduced rate', 'Zero rate' ] ) {
			const responseCreateTaxClasses = await request.post(
				'./wp-json/wc/v3/taxes/classes',
				{
					data: {
						name,
					},
				}
			);

			if ( responseCreateTaxClasses.status() === 400 ) {
				const expectedCodes = [
					'woocommerce_rest_tax_class_exists',
					'woocommerce_rest_tax_class_slug_exists',
				];
				const expectedMessages = [
					'Tax class already exists',
					'Tax class slug already exists',
				];
				const { code, message }: TaxClassResponse =
					await responseCreateTaxClasses.json();
				expect( expectedCodes ).toContain( code );
				expect( expectedMessages ).toContain( message );
			}

			if ( responseCreateTaxClasses.ok() ) {
				const { slug }: TaxClassResponse =
					await responseCreateTaxClasses.json();
				if ( slug ) {
					taxClassSlugsToTearDown.push( slug );
				}
			}
		}

		/**
		 * Create a tax rate for each tax class, and save their ID's.
		 */
		const taxRates = [
			standardTaxRateWithId,
			reducedTaxRateWithId,
			zeroTaxRateWithId,
		];
		for ( const taxRate of taxRates ) {
			const taxResponse = await request.post( './wp-json/wc/v3/taxes', {
				data: taxRate,
				failOnStatusCode: true,
			} );
			const taxResponseJSON: { id: number } = await taxResponse.json();
			taxRate.id = taxResponseJSON.id;
		}

		/**
		 * Create simple, variable, grouped, and external products.
		 */
		// Create a simple product
		const createdSimpleProduct = await request.post(
			'./wp-json/wc/v3/products',
			{
				data: simpleProduct,
			}
		);
		const createdSimpleProductJSON: { id: number } =
			await createdSimpleProduct.json();
		simpleProduct.id = createdSimpleProductJSON.id;

		// Create a variable product with 1 variation
		const createdVariableProduct = await request.post(
			'./wp-json/wc/v3/products',
			{
				data: variableProduct,
			}
		);
		const createdVariableProductJSON: { id: number } =
			await createdVariableProduct.json();

		variableProduct.id = createdVariableProductJSON.id;
		await request.post(
			`./wp-json/wc/v3/products/${ variableProduct.id }/variations`,
			{
				data: variation,
			}
		);

		// Create a grouped product using the simple product created earlier.
		groupedProduct.grouped_products = [ simpleProduct.id! ];
		const createdGroupedProduct = await request.post(
			'./wp-json/wc/v3/products',
			{
				data: groupedProduct,
			}
		);
		const createdGroupedProductJSON: { id: number } =
			await createdGroupedProduct.json();
		groupedProduct.id = createdGroupedProductJSON.id;

		// Create an external product
		const createdExternalProduct = await request.post(
			'./wp-json/wc/v3/products',
			{
				data: externalProduct,
			}
		);
		const createdExternalProductJSON: { id: number } =
			await createdExternalProduct.json();
		externalProduct.id = createdExternalProductJSON.id;

		// Add line items to the order
		order.line_items = [
			{
				product_id: simpleProduct.id,
			},
			{
				product_id: variableProduct.id,
			},
			{
				product_id: externalProduct.id,
			},
			{
				product_id: groupedProduct.id,
			},
		];
	} );

	test.afterAll( async ( { request } ) => {
		// Delete order
		await request.delete( `./wp-json/wc/v3/orders/${ order.id }`, {
			data: {
				force: true,
			},
		} );

		// Delete products
		await request.post( './wp-json/wc/v3/products/batch', {
			data: {
				delete: [
					simpleProduct.id,
					variableProduct.id,
					externalProduct.id,
					groupedProduct.id,
				],
			},
		} );

		// Delete tax rates
		await request.post( './wp-json/wc/v3/taxes/batch', {
			data: {
				delete: [
					standardTaxRateWithId.id,
					zeroTaxRateWithId.id,
					reducedTaxRateWithId.id,
				],
			},
		} );

		// Delete tax classes
		for ( const slug of taxClassSlugsToTearDown ) {
			await request.delete( `./wp-json/wc/v3/taxes/classes/${ slug }`, {
				params: {
					force: true,
				},
				failOnStatusCode: true,
			} );
		}
	} );

	test( 'can add complex order', async ( { request } ) => {
		//ensure tax calculations are enabled
		await request.put(
			'./wp-json/wc/v3/settings/general/woocommerce_calc_taxes',
			{
				data: {
					value: 'yes',
				},
			}
		);

		// Create the complex order and save its ID.
		const response = await request.post( './wp-json/wc/v3/orders', {
			data: order,
		} );
		const responseJSON: OrderResponse = await response.json();

		order.id = responseJSON.id;

		expect( response.status() ).toEqual( 201 );

		// Verify order and tax totals
		expect( responseJSON.total ).toEqual( expectedOrderTotal );
		expect( responseJSON.total_tax ).toEqual( expectedTaxTotal );

		// Verify total tax of each product line item
		const expectedTaxTotalsPerLineItem: [
			(
				| SimpleProductWithId
				| VariableProductWithId
				| GroupedProductWithId
				| ExternalProductWithId
			),
			string
		][] = [
			[ simpleProduct, expectedSimpleProductTaxTotal ],
			[ variableProduct, expectedVariableProductTaxTotal ],
			[ groupedProduct, expectedSimpleProductTaxTotal ],
			[ externalProduct, expectedExternalProductTaxTotal ],
		];
		for ( const [
			product,
			expectedLineTaxTotal,
		] of expectedTaxTotalsPerLineItem ) {
			const lineItem = responseJSON.line_items.find(
				( { product_id } ) => product_id === product.id
			);

			expect( lineItem?.total_tax ).toEqual( expectedLineTaxTotal );
		}
	} );
} );
