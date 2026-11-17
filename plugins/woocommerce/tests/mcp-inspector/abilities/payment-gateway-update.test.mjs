import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';

const ABILITY = 'woocommerce/payment-gateway-update';

const orderOf = async () =>
	( await execute( 'woocommerce/payment-gateways-query' ) ).gateways
		.filter( ( { id } ) => [ 'bacs', 'cheque', 'cod' ].includes( id ) )
		.map( ( { id } ) => id );

// The tests change the cash on delivery gateway, and `after` puts back what they changed.
describe( ABILITY, () => {
	let original;
	before( async () => {
		[ original ] = (
			await execute( 'woocommerce/payment-gateways-query', {
				id: 'cod',
			} )
		).gateways;
	} );
	after( () =>
		execute( ABILITY, {
			id: 'cod',
			enabled: original.enabled,
			title: original.title,
			order: original.order,
		} )
	);

	test( 'changes the title and turns the gateway on', async () => {
		const { payment_gateway: gateway } = await execute( ABILITY, {
			id: 'cod',
			title: 'MCP cash',
			enabled: true,
		} );

		assert.equal( gateway.title, 'MCP cash' );
		assert.equal( gateway.enabled, true );
		assert.equal( gateway.method_title, 'Cash on delivery' );
	} );

	test( 'moves the gateway in the checkout order', async () => {
		const { payment_gateway: gateway } = await execute( ABILITY, {
			id: 'cod',
			order: 0,
		} );

		assert.equal( gateway.order, 0 );
		assert.equal( ( await orderOf() )[ 0 ], 'cod' );
	} );

	test( 'refuses a call without a change', () =>
		refused( ABILITY, { id: 'cod' }, /at least 2 properties/ ) );

	test( 'refuses an unknown gateway', () =>
		refused(
			ABILITY,
			{ id: 'not-a-gateway', enabled: true },
			/Payment gateway not found/
		) );
} );
