import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/order-send-email';

// The environment accepts mail without sending it, so a send succeeds and records the usual order note.
const SENT = /^Email ".+" sent\.$/;

describe( ABILITY, () => {
	let id;
	before( async () => {
		( { id } = await seed( 'order', {
			key: 'order-send-email',
			status: 'processing',
		} ) );
	} );

	const sentNotes = async () =>
		(
			await execute( 'woocommerce/order-notes-query', { id } )
		).notes.filter( ( { note } ) => SENT.test( note ) );

	test( 'sends the invoice and records it on the order', async () => {
		assert.deepEqual( await execute( ABILITY, { id, email: 'invoice' } ), {
			order_id: id,
			email: 'invoice',
		} );
		assert.equal( ( await sentNotes() ).length, 1 );
	} );

	test( "sends the email of the order's own status", async () => {
		assert.deepEqual(
			await execute( ABILITY, { id, email: 'processing' } ),
			{ order_id: id, email: 'processing' }
		);
	} );

	test( 'refuses a status email for an order without that status', () =>
		refused(
			ABILITY,
			{ id, email: 'completed' },
			/only applies to an order with that status/
		) );

	test( 'refuses an email type outside its list', () =>
		refused( ABILITY, { id, email: 'refunded' }, /input\[email\]/ ) );

	test( 'refuses an unknown order', () =>
		refused(
			ABILITY,
			{ id: 999999999, email: 'invoice' },
			/Order 999999999 does not exist/
		) );
} );
