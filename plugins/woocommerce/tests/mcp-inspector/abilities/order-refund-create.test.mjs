import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/order-refund-create';

const EMAIL = 'order-refund-create@fixtures.mcp.test';
const SKU = 'mcp-refund';

const removeAll = async () => {
	await seed( 'remove-orders', { emails: [ EMAIL ] } );
	await seed( 'remove-products', { skus: [ SKU ] } );
};

// The tests run in order and refund the same order: 5, 5 again, then one of its two lines of 10.
describe( ABILITY, () => {
	let id;
	let lineId;
	let productId;
	let first;
	before( async () => {
		await removeAll();
		( { id: productId } = await seed( 'product', {
			sku: SKU,
			regular_price: '10',
			manage_stock: true,
			stock_quantity: 5,
		} ) );
		const { order } = await execute( 'woocommerce/order-create', {
			billing: { email: EMAIL },
			status: 'processing',
			line_items: [ { product_id: productId, quantity: 2 } ],
		} );
		id = order.id;
		lineId = order.line_items[ 0 ].id;
	} );
	after( removeAll );

	const stock = async () =>
		( await execute( 'woocommerce/products-query', { id: productId } ) )
			.products[ 0 ].stock_quantity;

	test( 'records a refund of an amount without the payment gateway', async () => {
		const { refund } = await execute( ABILITY, {
			id,
			amount: '5',
			reason: 'MCP partial refund',
		} );
		first = refund.id;

		assert.deepEqual(
			{ ...refund, id: typeof refund.id, date_created: undefined },
			{
				id: 'number',
				order_id: id,
				amount: '5',
				refunded_payment: false,
				line_items: [],
				reason: 'MCP partial refund',
				refunded_by: 1,
				date_created: undefined,
			}
		);
	} );

	test( 'refuses the same refund again, naming the first one', () =>
		refused(
			ABILITY,
			{ id, amount: '5', reason: 'MCP partial refund' },
			new RegExp( `Refund ${ first } already refunded the same amount` )
		) );

	test( 'creates the same refund again when allow_duplicate_of names the first', async () => {
		const { refund } = await execute( ABILITY, {
			id,
			amount: '5',
			reason: 'MCP partial refund',
			allow_duplicate_of: first,
		} );

		assert.notEqual( refund.id, first );
		assert.equal( refund.amount, '5' );
	} );

	test( 'refuses a refund through a gateway that cannot refund', () =>
		refused(
			ABILITY,
			{ id, amount: '1', refund_payment: true },
			/payment gateway of this order cannot refund it automatically/
		) );

	test( 'refuses more than the order has left to refund', () =>
		refused(
			ABILITY,
			{ id, amount: '50' },
			/exceeds the remaining refundable amount \(10\.00\)/
		) );

	test( 'refuses more units of a line than it has', () =>
		refused(
			ABILITY,
			{ id, line_items: [ { line_item_id: lineId, quantity: 5 } ] },
			/remaining refundable quantity \(2\)/
		) );

	test( 'refuses a refund without an amount or lines', () =>
		refused( ABILITY, { id }, /No amount or lines to refund were sent/ ) );

	test( 'refuses an unknown line', () =>
		refused(
			ABILITY,
			{ id, line_items: [ { line_item_id: 999999999 } ] },
			/Line item not found/
		) );

	test( 'refunds a line, returns it to stock and marks the order refunded', async () => {
		assert.equal( await stock(), 3 );

		const { refund } = await execute( ABILITY, {
			id,
			line_items: [ { line_item_id: lineId, quantity: 1 } ],
			restock_items: true,
		} );

		assert.equal( refund.amount, '10' );
		assert.deepEqual(
			refund.line_items.map(
				( { refunded_item_id: item, quantity, total } ) => ( {
					refunded_item_id: item,
					quantity,
					total,
				} )
			),
			[ { refunded_item_id: lineId, quantity: 1, total: '10' } ]
		);
		assert.equal( await stock(), 4 );
		assert.equal(
			( await execute( 'woocommerce/orders-query', { id } ) ).orders[ 0 ]
				.status,
			'refunded'
		);
	} );

	test( 'refuses a refund of an order with nothing left to refund', () =>
		refused(
			ABILITY,
			{ id, amount: '1' },
			/This order cannot be refunded/
		) );

	test( 'refuses an unknown order', () =>
		refused(
			ABILITY,
			{ id: 999999999, amount: '1' },
			/Order 999999999 does not exist/
		) );
} );
