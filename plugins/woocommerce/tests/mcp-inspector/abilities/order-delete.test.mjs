import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/order-delete';

const statusOf = async ( id ) =>
	( await execute( 'woocommerce/orders-query', { id } ) ).orders[ 0 ].status;

// The tests run in order: the order goes to the trash first, then away for good.
describe( ABILITY, () => {
	let id;
	before( async () => {
		await seed( 'remove-orders', {
			emails: [ 'order-delete@fixtures.mcp.test' ],
		} );
		( { id } = await seed( 'order', {
			key: 'order-delete',
			status: 'pending',
		} ) );
	} );

	test( 'moves the order to the trash by default', async () => {
		assert.deepEqual( await execute( ABILITY, { id } ), {
			id,
			status: 'trash',
		} );
		assert.equal( await statusOf( id ), 'trash' );
	} );

	test( 'trashes an order in the trash again', async () => {
		assert.deepEqual( await execute( ABILITY, { id } ), {
			id,
			status: 'trash',
		} );
	} );

	test( 'deletes it for good with force', async () => {
		assert.deepEqual( await execute( ABILITY, { id, force: true } ), {
			id,
			status: 'deleted',
		} );
		await refused(
			'woocommerce/orders-query',
			{ id },
			new RegExp( `Order ${ id } does not exist` )
		);
	} );

	test( 'refuses an order that is gone', () =>
		refused(
			ABILITY,
			{ id },
			new RegExp( `Order ${ id } does not exist` )
		) );
} );
