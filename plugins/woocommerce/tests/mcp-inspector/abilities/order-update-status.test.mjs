import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/order-update-status';

describe( ABILITY, () => {
	let id;
	before( async () => {
		( { id } = await seed( 'order', {
			key: 'order-update-status',
			status: 'pending',
		} ) );
	} );

	test( 'moves the order to the new status', async () => {
		const { order } = await execute( ABILITY, {
			id,
			status: 'processing',
		} );
		const { orders } = await execute( 'woocommerce/orders-query', { id } );

		assert.equal( order.status, 'processing' );
		assert.equal( orders[ 0 ].status, 'processing' );
	} );

	test( 'records the note it is given', async () => {
		await execute( ABILITY, {
			id,
			status: 'completed',
			note: 'Handed to the courier',
		} );
		const { notes } = await execute( 'woocommerce/order-notes-query', {
			id,
		} );

		assert.ok(
			notes.some( ( { note } ) =>
				note.includes( 'Handed to the courier' )
			),
			JSON.stringify( notes )
		);
	} );

	for ( const [ title, parameters, pattern ] of [
		[
			'refuses a status outside its list',
			{ status: 'trash' },
			/input\[status\]/,
		],
		[
			'refuses a call without a status',
			{},
			/status is a required property/,
		],
	] ) {
		test( title, () => refused( ABILITY, { id, ...parameters }, pattern ) );
	}

	test( 'refuses an unknown order', () =>
		refused(
			ABILITY,
			{ id: 999999999, status: 'completed' },
			/Order 999999999 does not exist/
		) );
} );
