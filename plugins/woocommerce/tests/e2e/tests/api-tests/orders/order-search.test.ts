/**
 * Internal dependencies
 */
import { test, expect } from '../../../fixtures/api-tests-fixtures';
import { getOrderExampleSearchTest } from '../../../data/order';
import { customerShippingSearchTest } from '../../../data/shared/customer';
import { simpleProduct } from '../../../data/products-crud';

/**
 * Order to be searched
 */
const order = {
	...getOrderExampleSearchTest(),
	shipping: {
		...customerShippingSearchTest,
		company: 'Murphy LLCsearch',
		phone: '6146524353search',
	},
	shipping_lines: [],
	fee_lines: [],
	coupon_lines: [],
};

test.describe( 'Order Search API tests', () => {
	test.beforeAll( async ( { request } ) => {
		// Create a product to be associated with the order
		const productResponse = await request.post( 'wp-json/wc/v3/products', {
			data: simpleProduct,
		} );
		const productResponseJSON = await productResponse.json();

		// Save the created product id against the order line_items
		order.line_items[ 0 ].product_id = productResponseJSON.id;

		// Create an order and save its ID
		const response = await request.post( './wp-json/wc/v3/orders', {
			data: order,
		} );
		const responseJSON = await response.json();
		order.id = responseJSON.id;
	} );

	test.afterAll( async ( { request } ) => {
		// Cleanup: Delete the product
		await request.delete(
			`./wp-json/wc/v3/products/${ order.line_items[ 0 ].product_id }`,
			{
				data: { force: true },
			}
		);
		// Cleanup: Delete the order
		await request.delete( `./wp-json/wc/v3/orders/${ order.id }`, {
			data: { force: true },
		} );
	} );

	test( 'can search by billing first name', async ( { request } ) => {
		const response = await request.get( './wp-json/wc/v3/orders/', {
			params: { search: order.billing.first_name },
		} );
		const responseJSON = await response.json();
		const responseIDs = responseJSON.map(
			( result: { id: number } ) => result.id
		);

		expect( response.status() ).toEqual( 200 );
		expect( responseJSON.length ).toBeGreaterThanOrEqual( 1 );
		expect( responseIDs ).toContain( order.id );
	} );
} );
