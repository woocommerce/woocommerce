import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/tax-rate-update';

const NAME = 'MCP update';

// The tests run in order and change the same rate.
describe( ABILITY, () => {
	let id;
	before( async () => {
		( { id } = await seed( 'tax-rate', { name: NAME } ) );
	} );
	after( () => seed( 'remove-tax-rates', { names: [ NAME ] } ) );

	test( 'changes only the fields it is given', async () => {
		assert.deepEqual(
			await execute( ABILITY, { id, rate: '10', priority: 2 } ),
			{
				rate: {
					id,
					country: 'FR',
					state: '',
					postcodes: [ '75001' ],
					cities: [],
					rate: '10.0000',
					name: NAME,
					priority: 2,
					compound: false,
					shipping: true,
					class: 'reduced-rate',
				},
			}
		);
	} );

	test( 'applies the rate to every postcode with an empty list', async () => {
		const { rate } = await execute( ABILITY, { id, postcodes: [] } );

		assert.deepEqual( rate.postcodes, [] );
	} );

	test( 'moves the rate to another country and state', async () => {
		const { rate } = await execute( ABILITY, {
			id,
			country: 'US',
			state: 'CA',
		} );

		assert.deepEqual(
			{ country: rate.country, state: rate.state },
			{ country: 'US', state: 'CA' }
		);
	} );

	test( 'refuses a state the country does not have', () =>
		refused( ABILITY, { id, state: 'ZZ' }, /"ZZ" is not a state of US/ ) );

	test( 'refuses an unknown rate', () =>
		refused(
			ABILITY,
			{ id: 999999999, rate: '1' },
			/There is no tax rate with ID 999999999/
		) );
} );
