import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/tax-rate-delete';

const NAME = 'MCP delete';

// The tests run in order: the rate is deleted, then added back from what the deletion returned.
describe( ABILITY, () => {
	let id;
	let deleted;
	before( async () => {
		( { id } = await seed( 'tax-rate', { name: NAME } ) );
	} );
	after( () => seed( 'remove-tax-rates', { names: [ NAME ] } ) );

	test( 'deletes the rate and returns it', async () => {
		( { rate: deleted } = await execute( ABILITY, { id } ) );

		assert.deepEqual(
			{
				id: deleted.id,
				name: deleted.name,
				postcodes: deleted.postcodes,
			},
			{ id, name: NAME, postcodes: [ '75001' ] }
		);
		assert.deepEqual(
			(
				await execute( 'woocommerce/tax-rates-query', {
					include: [ id ],
				} )
			).rates,
			[]
		);
	} );

	test( 'adds it back through tax-rate-create from what it returned', async () => {
		const { id: oldId, ...fields } = deleted;
		const { rate } = await execute( 'woocommerce/tax-rate-create', fields );

		assert.notEqual( rate.id, oldId );
		assert.deepEqual( { ...rate, id: oldId }, deleted );
	} );

	test( 'refuses a rate that is gone', () =>
		refused(
			ABILITY,
			{ id },
			new RegExp( `There is no tax rate with ID ${ id }` )
		) );
} );
