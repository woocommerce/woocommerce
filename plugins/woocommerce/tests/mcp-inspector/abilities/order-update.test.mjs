import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/order-update';

const SKU = 'mcp-order-line';

const linesOf = ( { line_items: lines } ) =>
	lines.map( ( { product_id: productId, quantity, total } ) => ( {
		product_id: productId,
		quantity,
		total,
	} ) );

// The tests run in order: the line is added, changed, then removed again.
describe( ABILITY, () => {
	let id;
	let productId;
	let lineId;
	before( async () => {
		( { id } = await seed( 'order', {
			key: 'order-update',
			status: 'pending',
		} ) );
		( { id: productId } = await seed( 'product', {
			sku: SKU,
			regular_price: '12',
		} ) );
	} );
	after( () => seed( 'remove-products', { skus: [ SKU ] } ) );

	test( 'changes only the address fields it is given', async () => {
		const { order } = await execute( ABILITY, {
			id,
			shipping: { city: 'Berlin', country: 'DE' },
		} );

		assert.equal( order.shipping.city, 'Berlin' );
		assert.equal( order.shipping.country, 'DE' );
		assert.equal( order.shipping.address_1, '' );
		assert.equal( order.billing_email, 'order-update@fixtures.mcp.test' );
	} );

	test( 'adds a product line and recalculates the total', async () => {
		const { order } = await execute( ABILITY, {
			id,
			line_items: [ { product_id: productId, quantity: 2 } ],
		} );

		assert.deepEqual( linesOf( order ), [
			{ product_id: productId, quantity: 2, total: '24' },
		] );
		assert.equal( order.total, '24.00' );
		lineId = order.line_items[ 0 ].id;
	} );

	test( 'changes the quantity of a line', async () => {
		const { order } = await execute( ABILITY, {
			id,
			line_items: [ { id: lineId, quantity: 3 } ],
		} );

		assert.deepEqual( linesOf( order ), [
			{ product_id: productId, quantity: 3, total: '36' },
		] );
		assert.equal( order.total, '36.00' );
	} );

	test( 'removes a line', async () => {
		const { order } = await execute( ABILITY, {
			id,
			remove_items: [ lineId ],
		} );

		assert.deepEqual( order.line_items, [] );
		assert.equal( order.total, '0.00' );
	} );

	test( 'refuses to change the lines of a completed order', async () => {
		const completed = await seed( 'order', {
			key: 'order-update-completed',
			status: 'completed',
		} );

		await refused(
			ABILITY,
			{
				id: completed.id,
				line_items: [ { product_id: productId, quantity: 1 } ],
			},
			/cannot change/
		);
	} );

	test( 'refuses an unknown order', () =>
		refused(
			ABILITY,
			{ id: 999999999, customer_note: 'Lost' },
			/Order 999999999 does not exist/
		) );
} );
