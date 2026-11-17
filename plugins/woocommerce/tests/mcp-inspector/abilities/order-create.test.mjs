import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/order-create';

const SKUS = [ 'mcp-order-create', 'mcp-order-create-stock' ];
const EMAIL = 'order-create@fixtures.mcp.test';

const linesOf = ( { line_items: lines } ) =>
	lines.map( ( { product_id: productId, quantity, total } ) => ( {
		product_id: productId,
		quantity,
		total,
	} ) );

const stockOf = async ( id ) =>
	( await execute( 'woocommerce/products-query', { id } ) ).products[ 0 ]
		.stock_quantity;

describe( ABILITY, () => {
	let product;
	let stocked;
	let customer;
	const removeAll = async () => {
		await seed( 'remove-orders', { emails: [ EMAIL ] } );
		await seed( 'remove-products', { skus: SKUS } );
	};
	before( async () => {
		await removeAll();
		product = await seed( 'product', {
			sku: SKUS[ 0 ],
			regular_price: '12',
		} );
		stocked = await seed( 'product', {
			sku: SKUS[ 1 ],
			manage_stock: true,
			stock_quantity: 5,
		} );
		customer = await seed( 'customer', { key: 'order-create' } );
	} );
	after( removeAll );

	test( 'creates a pending guest order and calculates its total', async () => {
		const { order } = await execute( ABILITY, {
			billing: { email: EMAIL },
			line_items: [ { product_id: product.id, quantity: 2 } ],
			shipping_lines: [ { method_title: 'MCP courier', total: '5' } ],
		} );

		assert.equal( order.status, 'pending' );
		assert.equal( order.customer_id, 0 );
		assert.equal( order.billing_email, EMAIL );
		assert.deepEqual( linesOf( order ), [
			{ product_id: product.id, quantity: 2, total: '24' },
		] );
		assert.equal( order.total, '29.00' );
	} );

	test( 'leaves the stock alone for a pending order', async () => {
		await execute( ABILITY, {
			billing: { email: EMAIL },
			line_items: [ { product_id: stocked.id } ],
		} );

		assert.equal( await stockOf( stocked.id ), 5 );
	} );

	test( 'reduces the stock for a processing order', async () => {
		const { order } = await execute( ABILITY, {
			customer_id: customer.id,
			billing: { email: EMAIL },
			status: 'processing',
			line_items: [ { product_id: stocked.id, quantity: 2 } ],
		} );

		assert.equal( order.status, 'processing' );
		assert.equal( order.customer_id, customer.id );
		assert.equal( await stockOf( stocked.id ), 3 );
	} );

	test( 'refuses an unknown product', () =>
		refused(
			ABILITY,
			{ line_items: [ { product_id: 999999999 } ] },
			/Product 999999999 does not exist/
		) );

	test( 'refuses an unknown customer', () =>
		refused(
			ABILITY,
			{
				customer_id: 999999999,
				line_items: [ { product_id: product.id } ],
			},
			/User 999999999 is not a user of this site/
		) );

	test( 'refuses an invalid billing email', () =>
		refused(
			ABILITY,
			{
				billing: { email: 'not-an-email' },
				line_items: [ { product_id: product.id } ],
			},
			/not a valid email address/
		) );
} );
