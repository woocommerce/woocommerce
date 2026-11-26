import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/order-refunds-query';

const EMAIL = 'order-refunds-query@fixtures.mcp.test';
const SKU = 'mcp-refunds-query';

const removeAll = async () => {
	await seed( 'remove-orders', { emails: [ EMAIL ] } );
	await seed( 'remove-products', { skus: [ SKU ] } );
};

describe( ABILITY, () => {
	let id;
	let older;
	let newer;
	before( async () => {
		await removeAll();
		const product = await seed( 'product', {
			sku: SKU,
			regular_price: '10',
		} );
		const { order } = await execute( 'woocommerce/order-create', {
			billing: { email: EMAIL },
			status: 'processing',
			line_items: [ { product_id: product.id, quantity: 2 } ],
		} );
		id = order.id;
		( { refund: older } = await execute(
			'woocommerce/order-refund-create',
			{
				id,
				amount: '3',
				reason: 'MCP older',
			}
		) );
		( { refund: newer } = await execute(
			'woocommerce/order-refund-create',
			{
				id,
				line_items: [
					{ line_item_id: order.line_items[ 0 ].id, quantity: 1 },
				],
			}
		) );
	} );
	after( removeAll );

	test( 'lists the refunds of the order, newest first', async () => {
		assert.deepEqual( await execute( ABILITY, { id } ), {
			refunds: [ newer, older ],
			total: 2,
		} );
	} );

	test( 'limits the list to the refunds it includes', async () => {
		assert.deepEqual(
			await execute( ABILITY, { id, include: [ older.id ] } ),
			{
				refunds: [ older ],
				total: 1,
			}
		);
	} );

	test( 'returns nothing for an order without refunds', async () => {
		const order = await seed( 'order', {
			key: 'order-refunds-query-none',
		} );

		assert.deepEqual( await execute( ABILITY, { id: order.id } ), {
			refunds: [],
			total: 0,
		} );
	} );

	test( 'refuses an unknown order', () =>
		refused(
			ABILITY,
			{ id: 999999999 },
			/Order 999999999 does not exist/
		) );
} );
