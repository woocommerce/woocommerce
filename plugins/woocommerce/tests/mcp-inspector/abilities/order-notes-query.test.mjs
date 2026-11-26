import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/order-notes-query';

describe( ABILITY, () => {
	let id;
	before( async () => {
		( { id } = await seed( 'order', {
			key: 'order-notes-query',
			notes: [
				{ note: 'Packed in two boxes' },
				{ note: 'Your order ships tomorrow', customer_note: true },
			],
		} ) );
	} );

	test( 'lists the notes, newest first, marking those for the customer', async () => {
		const { notes } = await execute( ABILITY, { id } );

		assert.deepEqual(
			notes.map( ( { note, customer_note } ) => ( {
				note,
				customer_note,
			} ) ),
			[
				{ note: 'Your order ships tomorrow', customer_note: true },
				{ note: 'Packed in two boxes', customer_note: false },
			]
		);
	} );

	test( 'refuses an unknown order', () =>
		refused(
			ABILITY,
			{ id: 999999999 },
			/Order 999999999 does not exist/
		) );
} );
