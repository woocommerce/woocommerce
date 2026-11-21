import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/order-add-note';

describe( ABILITY, () => {
	let id;
	before( async () => {
		( { id } = await seed( 'order', { key: 'order-add-note' } ) );
	} );

	// Read back the way a client would, through the notes query.
	const readNote = async ( noteId ) => {
		const { notes } = await execute( 'woocommerce/order-notes-query', {
			id,
		} );
		const found = notes.find( ( note ) => note.id === noteId );
		return (
			found && { note: found.note, customer_note: found.customer_note }
		);
	};

	for ( const [ title, parameters, customerNote ] of [
		[
			'adds a private note by default',
			{ note: 'Checked the address' },
			false,
		],
		[
			'adds a note for the customer',
			{ note: 'Thanks for your order', customer_note: true },
			true,
		],
	] ) {
		test( title, async () => {
			const data = await execute( ABILITY, { id, ...parameters } );

			assert.equal( data.order.id, id );
			assert.deepEqual( await readNote( data.note_id ), {
				note: parameters.note,
				customer_note: customerNote,
			} );
		} );
	}

	test( 'refuses a note without text', () =>
		refused( ABILITY, { id }, /note is a required property/ ) );

	test( 'refuses an unknown order', () =>
		refused(
			ABILITY,
			{ id: 999999999, note: 'Lost' },
			/Order 999999999 does not exist/
		) );
} );
